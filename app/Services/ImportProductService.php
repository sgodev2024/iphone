<?php

namespace App\Services;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductStorage;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportProductService
{
    protected $importCoupon;

    protected $importDetail;

    public function __construct(ImportCoupon $importCoupon, ImportDetail $importDetail)
    {
        $this->importCoupon = $importCoupon;
        $this->importDetail = $importDetail;
    }

    public function getImportCoupon($perPage = 10, $search = null)
    {
        $query = ImportCoupon::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('coupon_code', 'like', '%'.$search.'%')
                    ->orWhere('user_id', 'like', '%'.$search.'%')
                    ->orWhere('supplier_id', 'like', '%'.$search.'%');
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getImportCouponByid($id)
    {
        try {
            Log::info('Fetching all ImportCoupon');

            return $this->importCoupon
                ->with(['details.product', 'details.imeis', 'storage', 'user', 'companyRelation'])
                ->findOrFail($id);
        } catch (Exception $e) {
            Log::error('Failed to fetch ImportCoupon: '.$e->getMessage());
            throw new Exception('Failed to fetch ImportCoupon');
        }
    }

    public function addImportCoupon($data)
    {
        try {
            Log::info('Fetching add ImportCoupon');
            $importCoupon = $this->importCoupon->create($data);

            return $importCoupon;
        } catch (Exception $e) {
            Log::error('Failed to fetch ImportCoupon: '.$e->getMessage());
            throw new Exception('Failed to fetch add ImportCoupon');
        }
    }

    public function addImportDetail($data)
    {
        try {
            Log::info('Fetching add importDetail');
            $importDetail = $this->importDetail->create($data);

            return $importDetail;
        } catch (Exception $e) {
            Log::error('Failed to fetch importDetail: '.$e->getMessage());
            throw new Exception('Failed to fetch add importDetail');
        }
    }

    public function deleteImportCoupons(array $ids, $user): int
    {
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            throw new DomainException('Vui lòng chọn ít nhất một phiếu nhập cần xóa.');
        }

        return DB::transaction(function () use ($ids, $user) {
            $query = ImportCoupon::query()
                ->with(['details.product', 'details.imeis'])
                ->whereIn('id', $ids)
                ->lockForUpdate();

            $roleId = (int) ($user->role_id ?? 0);
            if (! in_array($roleId, [1, 2], true)) {
                $query->where('user_id', $user->id);
            }

            $coupons = $query->get();

            if ($coupons->count() !== count($ids)) {
                throw new DomainException('Không tìm thấy phiếu nhập phù hợp hoặc bạn không có quyền xóa.');
            }

            foreach ($ids as $id) {
                $coupon = $coupons->firstWhere('id', $id);
                $this->ensureImportCouponCanBeDeleted($coupon);
            }

            foreach ($ids as $id) {
                $coupon = $coupons->firstWhere('id', $id);
                $this->rollbackInventoryForImportCoupon($coupon);
                $coupon->details()->delete();
                $coupon->delete();
            }

            return count($ids);
        });
    }

    private function ensureImportCouponCanBeDeleted(ImportCoupon $coupon): void
    {
        $code = $coupon->coupon_code ?: "#{$coupon->id}";
        $paidAmount = (int) ($coupon->paid_amount ?? $coupon->payment_ncc ?? 0);
        $totalAmount = (int) ($coupon->total ?? 0);
        $debtAmount = (int) ($coupon->debt_amount ?? max($totalAmount - $paidAmount, 0));

        if ($coupon->details->contains(fn (ImportDetail $detail) => $detail->imeis->isNotEmpty())) {
            throw new DomainException(
                "Không thể xóa phiếu nhập {$code} vì dữ liệu IMEI đã được ghi nhận vào kho."
            );
        }

        if ($paidAmount > 0) {
            throw new DomainException("Phiếu nhập {$code} đã phát sinh thanh toán nhà cung cấp, không thể xóa.");
        }

        if ($debtAmount > 0) {
            throw new DomainException("Phiếu nhập {$code} đã phát sinh công nợ nhà cung cấp, không thể xóa.");
        }
    }

    private function rollbackInventoryForImportCoupon(ImportCoupon $coupon): void
    {
        $details = $coupon->details;

        if ($details->isEmpty()) {
            return;
        }

        if (empty($coupon->storage_id)) {
            $code = $coupon->coupon_code ?: "#{$coupon->id}";
            throw new DomainException("Phiếu nhập {$code} không xác định kho nhập, không thể hoàn tồn kho an toàn.");
        }

        $detailsByProduct = $details->groupBy('product_id');

        foreach ($detailsByProduct as $productId => $productDetails) {
            $quantity = (int) $productDetails->sum('quantity');

            if ($quantity <= 0) {
                continue;
            }

            $this->rollbackProductInventory((int) $productId, (int) $coupon->storage_id, $quantity);
        }
    }

    private function rollbackProductInventory(int $productId, int $storageId, int $quantity): void
    {
        $stock = ProductStorage::query()
            ->where('product_id', $productId)
            ->where('storage_id', $storageId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new DomainException("Không tìm thấy tồn kho của sản phẩm #{$productId} trong kho #{$storageId}.");
        }

        if ((int) $stock->quantity < $quantity) {
            throw new DomainException("Sản phẩm #{$productId} trong kho #{$storageId} không đủ tồn kho để hoàn tác phiếu nhập.");
        }

        $product = Product::query()
            ->whereKey($productId)
            ->lockForUpdate()
            ->first();

        if (! $product) {
            throw new DomainException("Không tìm thấy sản phẩm #{$productId}.");
        }

        $stock->quantity = (int) $stock->quantity - $quantity;
        $stock->save();

        $this->syncProductTotalQuantity($productId);
    }

    private function syncProductTotalQuantity(int $productId): void
    {
        DB::statement(
            'UPDATE products SET quantity = (SELECT COALESCE(SUM(quantity), 0) FROM product_storage WHERE product_id = ?), updated_at = ? WHERE id = ?',
            [$productId, now()->toDateTimeString(), $productId]
        );
    }
}
