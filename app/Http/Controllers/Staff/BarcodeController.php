<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Services\SaleStorageResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BarcodeController extends Controller
{
    public function __construct(
        private readonly SaleStorageResolver $saleStorageResolver
    ) {}

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:50'],
            'cart_imei_ids' => ['nullable', 'array'],
            'cart_imei_ids.*' => ['integer', 'distinct'],
            'cart_product_quantities' => ['nullable', 'array'],
            'cart_product_quantities.*' => ['integer', 'min:0'],
        ]);

        $barcode = trim($validated['barcode']);
        $storageId = $this->saleStorageResolver->resolveSaleStorageId(
            $request->user(),
            $request->input('storage_id')
        );

        $imei = ProductImei::query()
            ->with(['product', 'importDetail.import'])
            ->where(function ($query) use ($barcode) {
                $query
                    ->where('barcode', $barcode)
                    ->orWhere('imei', $barcode);
            })
            ->orderByRaw(
                'CASE WHEN barcode = ? THEN 0 WHEN imei = ? THEN 1 ELSE 2 END',
                [$barcode, $barcode]
            )
            ->first();

        if ($imei) {
            return $this->resolveImei(
                $imei,
                $storageId,
                collect($validated['cart_imei_ids'] ?? [])->map(fn ($id) => (int) $id)
            );
        }

        $product = Product::query()
            ->where('barcode', $barcode)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Không tìm thấy barcode.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->resolveQuantityProduct(
            $product,
            $storageId,
            collect($validated['cart_product_quantities'] ?? [])
        );
    }

    private function resolveImei(
        ProductImei $imei,
        int $storageId,
        $cartImeiIds
    ): JsonResponse {
        $product = $imei->product;

        if (! $product || ! $product->isImeiTracked()) {
            return $this->error(
                'Barcode IMEI không hợp lệ với sản phẩm quản lý IMEI.'
            );
        }

        if ($imei->status === ProductImei::STATUS_SOLD) {
            return $this->error('Thiết bị đã bán.');
        }

        if ($imei->status !== ProductImei::STATUS_IN_STOCK) {
            return $this->error(
                'Thiết bị đang không ở trạng thái có thể bán.'
            );
        }

        $imeiStorageId = $this->resolveImeiStorageId($imei);

        if ($imeiStorageId !== $storageId) {
            return $this->error('Thiết bị không thuộc kho hiện tại.');
        }

        if ($cartImeiIds->contains((int) $imei->id)) {
            return $this->error('Thiết bị đã có trong giỏ.');
        }

        if (
            OrderDetail::query()
                ->where('product_imei_id', $imei->id)
                ->exists()
        ) {
            return $this->error(
                'Thiết bị đang được gắn với đơn hàng khác.'
            );
        }

        $stock = ProductStorage::query()
            ->where('storage_id', $storageId)
            ->where('product_id', $product->id)
            ->first();

        if (! $stock || (int) $stock->quantity <= 0) {
            return $this->error(
                'Tồn kho của sản phẩm tại kho hiện tại không đủ.'
            );
        }

        return response()->json([
            'type' => Product::INVENTORY_TRACKING_IMEI,
            'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
            'id' => (int) $product->id,
            'product_id' => (int) $product->id,
            'product_imei_id' => (int) $imei->id,
            'code' => $product->code,
            'product_name' => $product->name,
            'name' => $product->name,
            'thumbnail' => $product->thumbnail,
            'thumbnail_url' => $product->thumbnail_url,
            'imei' => $imei->imei,
            'barcode' => $imei->barcode,
            'price' => (float) $product->price,
            'unit_price' => (float) $product->price,
            'price_buy' => (float) $product->price_buy,
            'available_quantity' => 1,
            'quantity' => 1,
            'storage_id' => $storageId,
            'result_type' => 'imei_device',
        ]);
    }

    private function resolveQuantityProduct(
        Product $product,
        int $storageId,
        $cartProductQuantities
    ): JsonResponse {
        if (! $product->isQuantityTracked()) {
            return $this->error(
                'Barcode này không phải của sản phẩm thường.'
            );
        }

        if (! $this->productIsActive($product)) {
            return $this->error('Sản phẩm đang ngừng hoạt động.');
        }

        $stock = ProductStorage::query()
            ->where('storage_id', $storageId)
            ->where('product_id', $product->id)
            ->first();

        if (! $stock || (int) $stock->quantity <= 0) {
            return $this->error('Sản phẩm thường đã hết hàng.');
        }

        $existingQuantity = (int) $cartProductQuantities->get((string) $product->id, 0);

        if ($existingQuantity + 1 > (int) $stock->quantity) {
            return $this->error('Số lượng yêu cầu vượt tồn kho.');
        }

        return response()->json([
            'type' => Product::INVENTORY_TRACKING_QUANTITY,
            'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
            'id' => (int) $product->id,
            'product_id' => (int) $product->id,
            'product_name' => $product->name,
            'name' => $product->name,
            'thumbnail' => $product->thumbnail,
            'thumbnail_url' => $product->thumbnail_url,
            'barcode' => $product->barcode,
            'price' => (float) $product->price,
            'unit_price' => (float) $product->price,
            'price_buy' => (float) $product->price_buy,
            'available_quantity' => (int) $stock->quantity,
            'quantity' => 1,
            'storage_id' => $storageId,
        ]);
    }

    private function resolveImeiStorageId(ProductImei $imei): ?int
    {
        $storageId = $imei->importDetail?->import?->storage_id;

        return $storageId ? (int) $storageId : null;
    }

    private function productIsActive(Product $product): bool
    {
        return in_array((string) $product->status, ['1', 'published'], true)
            || $product->status === true;
    }

    private function error(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
