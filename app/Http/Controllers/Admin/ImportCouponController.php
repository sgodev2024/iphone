<?php

namespace App\Http\Controllers\Admin;

use App\Services\InternalBarcodeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Import\StoreImportCouponRequest;
use App\Models\Account;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportCoupon;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Storage;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Services\CompanyProductService;
use App\Services\ImportProductService;
use App\Services\ProductStorageService;
use App\Support\BranchContext;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportCouponController extends Controller
{
    public function __construct(
        protected ImportProductService $importProductService,
        protected ProductStorageService $productStorageService,
        protected CompanyProductService $companyProductService,
        protected InternalBarcodeService $internalBarcodeService,
        protected BranchContext $branchContext,
    ) {}

    public function add(StoreImportCouponRequest $request): RedirectResponse
    {
        try {
            $importCouponId = DB::transaction(function () use ($request): int {
                return $this->confirmImportCoupon($request);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors([
                    'accounting' => 'Phiếu nhập hoặc bút toán canonical đã tồn tại. Phiếu nhập chưa được lưu.',
                ])
                ->withInput();
        } catch (Throwable $exception) {
            Log::error('Failed to confirm import coupon with IMEIs.', [
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->with('error', 'Không thể lưu phiếu nhập. Vui lòng kiểm tra dữ liệu và thử lại.')
                ->withInput();
        }

        return redirect()
            ->route('admin.importproduct.importCoupon.detail', [
                'id' => $importCouponId,
            ])
            ->with(
                'success',
                'Nhập hàng thành công. Barcode nội bộ đã được tạo.'
            );
    }

    private function confirmImportCoupon(StoreImportCouponRequest $request): int
    {
        $user = $request->user();
        $supplierId = (int) $request->validated('supplier');
        $storageId = (int) $request->validated('storage');
        $ownerId = (int) $user->ownerId();

        $companyQuery = Company::query();
        $storageQuery = Storage::query();

        if ($user->roleKey() === 'warehouse') {
            $companyQuery->where('user_id', $ownerId);
            $storageQuery->where('user_id', $ownerId);
        } else {
            $this->branchContext->scope($companyQuery, $user);
            $this->branchContext->scope($storageQuery, $user);
        }

        $companyExists = $companyQuery->whereKey($supplierId)->exists();

        if (! $companyExists) {
            throw ValidationException::withMessages([
                'supplier' => 'Nhà cung cấp không hợp lệ hoặc không thuộc phạm vi dữ liệu của bạn.',
            ]);
        }

        if (! Schema::hasTable('storages') || ! Schema::hasColumn('storages', 'user_id')) {
            throw ValidationException::withMessages([
                'storage' => 'Kho nhập chưa được cấu hình owner scope an toàn.',
            ]);
        }

        if (! $storageQuery->whereKey($storageId)->exists()) {
            throw ValidationException::withMessages([
                'storage' => 'Kho nhập không hợp lệ hoặc không thuộc phạm vi dữ liệu của bạn.',
            ]);
        }

        $imports = Import::query()
            ->with('product')
            ->where('quantity', '>', 0)
            ->whereHas('product', function ($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->lockForUpdate()
            ->get();

        if ($imports->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Phiếu nhập phải có ít nhất một sản phẩm.',
            ]);
        }

        $duplicatedProductId = $imports->pluck('product_id')
            ->duplicates()
            ->first();

        if ($duplicatedProductId !== null) {
            $productName = $imports->firstWhere('product_id', $duplicatedProductId)?->product?->name ?? "#{$duplicatedProductId}";

            throw ValidationException::withMessages([
                'items' => "Sản phẩm {$productName} đang bị lặp trong phiếu nhập.",
            ]);
        }

        $submittedImeis = (array) $request->input('imeis', []);

        /*
|--------------------------------------------------------------------------
| Chuẩn hóa và kiểm tra toàn bộ IMEI trước khi tạo phiếu nhập
|--------------------------------------------------------------------------
*/
        $validatedImeisByImportId = [];

        foreach ($imports as $import) {
            $product = $import->product;
            $productName = $product?->name ?? "#{$import->product_id}";
            $tracking = $product?->inventory_tracking;

            if (! in_array($tracking, Product::INVENTORY_TRACKING_OPTIONS, true)) {
                throw ValidationException::withMessages([
                    "items.{$import->id}" =>
                    "Sản phẩm {$productName} chưa có phương thức quản lý tồn kho hợp lệ.",
                ]);
            }

            $rawImeis = (array) (
                $submittedImeis[$import->id]
                ?? $submittedImeis[(string) $import->id]
                ?? []
            );

            $normalizedImeis = collect($rawImeis)
                ->map(fn($imei) => trim((string) $imei))
                ->values();

            $nonEmptyImeis = $normalizedImeis->filter(fn(string $imei) => $imei !== '');

            /*
    |--------------------------------------------------------------------------
    | Sản phẩm quản lý theo số lượng không được nhập IMEI
    |--------------------------------------------------------------------------
    */
            if ($tracking === Product::INVENTORY_TRACKING_QUANTITY) {
                if ($nonEmptyImeis->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        "imeis.{$import->id}" =>
                        "Sản phẩm {$productName} quản lý theo số lượng nên không được nhập IMEI.",
                    ]);
                }

                $validatedImeisByImportId[$import->id] = [];

                continue;
            }

            /*
    |--------------------------------------------------------------------------
    | Chỉ xử lý từ đây với sản phẩm quản lý IMEI
    |--------------------------------------------------------------------------
    */
            $expectedQuantity = (int) $import->quantity;

            if ($normalizedImeis->count() !== $expectedQuantity) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" =>
                    "Sản phẩm {$productName} cần {$expectedQuantity} IMEI, " .
                        "nhưng hiện có {$normalizedImeis->count()} IMEI.",
                ]);
            }

            $invalidImei = $normalizedImeis->first(fn(string $imei) => $imei === '');

            if ($invalidImei !== null) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" =>
                    'IMEI/Serial không được để trống.',
                ]);
            }

            $tooLongImei = $normalizedImeis->first(
                fn(string $imei) => mb_strlen($imei) > ProductImei::IMEI_MAX_LENGTH
            );

            if ($tooLongImei !== null) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" =>
                    'IMEI/Serial phải có tối đa 50 ký tự.',
                ]);
            }

            $validatedImeisByImportId[$import->id] = $normalizedImeis->all();
        }

        /*
|--------------------------------------------------------------------------
| Kiểm tra IMEI trùng trong toàn bộ phiếu nhập
|--------------------------------------------------------------------------
*/
        $allSubmittedImeis = collect($validatedImeisByImportId)
            ->flatten()
            ->values();

        $duplicatedImeis = $allSubmittedImeis
            ->duplicates()
            ->unique()
            ->values();

        if ($duplicatedImeis->isNotEmpty()) {
            throw ValidationException::withMessages([
                'imeis' =>
                'Mã IMEI/Serial bị nhập trùng trong phiếu: ' . $duplicatedImeis->implode(', '),
            ]);
        }

        /*
|--------------------------------------------------------------------------
| Kiểm tra IMEI đã tồn tại trong database
|--------------------------------------------------------------------------
|
| Dùng withTrashed để không cho tái sử dụng IMEI đã bị xóa mềm.
*/
        $existingImeis = ProductImei::query()
            ->withTrashed()
            ->whereIn('imei', $allSubmittedImeis->all())
            ->pluck('imei')
            ->unique()
            ->values();

        if ($existingImeis->isNotEmpty()) {
            throw ValidationException::withMessages([
                'imeis' =>
                'Mã IMEI/Serial đã tồn tại trong kho: ' . $existingImeis->implode(', '),
            ]);
        }

        // Recalculate from the persisted quantity and unit price at save time.
        // The hidden total field and the staging total are frontend-derived values.
        $total = (int) $imports->sum(function (Import $import): int {
            $quantity = max((int) $import->quantity, 0);
            $price = max((float) $import->price, 0);

            return (int) round($quantity * $price);
        });
        $payment = $this->normalizePaymentData(
            (string) $request->validated('payment_method', ImportCoupon::PAYMENT_METHOD_CASH),
            (int) $request->validated('totalncc', 0),
            $total
        );
        $bankAccountId = $this->resolveBankAccountId(
            $payment['payment_method'],
            $payment['paid_amount'],
            $request->validated('bank_account_id')
        );

        $importCoupon = $this->importProductService->addImportCoupon([
            'user_id' => $ownerId,
            'companies_id' => $supplierId,
            'total' => $total,
            'payment_ncc' => $payment['paid_amount'],
            'payment_method' => $payment['payment_method'],
            'paid_amount' => $payment['paid_amount'],
            'debt_amount' => $payment['debt_amount'],
            'payment_status' => $payment['payment_status'],
            'storage_id' => $storageId,
        ]);

        foreach ($imports as $import) {
            $product = Product::query()
                ->whereKey($import->product_id)
                ->where('user_id', $ownerId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($product->inventory_tracking, Product::INVENTORY_TRACKING_OPTIONS, true)) {
                throw ValidationException::withMessages([
                    "items.{$import->id}" => "Sản phẩm {$product->name} chưa có phương thức quản lý tồn kho hợp lệ.",
                ]);
            }

            $importDetail = $this->importProductService->addImportDetail([
                'import_id' => $importCoupon->id,
                'product_id' => $product->id,
                'quantity' => (int) $import->quantity,
                'price' => $import->price,
                'old_price' => $product->price,
            ]);

            if ($product->isImeiTracked()) {
                $imeis = $validatedImeisByImportId[$import->id] ?? [];

                foreach ($imeis as $imei) {
                    /*
        |--------------------------------------------------------------------------
        | Bước 1: Tạo IMEI để lấy ID tự tăng
        |--------------------------------------------------------------------------
        */
                    $productImei = $importDetail->imeis()->create([
                        'product_id' => $product->id,
                        'imei' => $imei,
                        'status' => ProductImei::STATUS_IN_STOCK,
                    ]);

                    /*
        |--------------------------------------------------------------------------
        | Bước 2: Sinh barcode từ ID vừa tạo
        |--------------------------------------------------------------------------
        |
        | Ví dụ ID 125 -> 2900000000125
        */
                    $barcode = $this->internalBarcodeService->generate($productImei);

                    /*
        |--------------------------------------------------------------------------
        | Bước 3: Lưu barcode
        |--------------------------------------------------------------------------
        */
                    $productImei->forceFill([
                        'barcode' => $barcode,
                    ])->save();
                }
            } else {
                $this->internalBarcodeService->resolveProductBarcode($product);

                if (
                    array_key_exists($import->id, $submittedImeis)
                    || array_key_exists((string) $import->id, $submittedImeis)
                ) {
                    $quantityProductImeis = collect(
                        $submittedImeis[$import->id]
                            ?? $submittedImeis[(string) $import->id]
                            ?? []
                    )
                        ->map(fn($imei) => trim((string) $imei))
                        ->filter(fn(string $imei) => $imei !== '');

                    if ($quantityProductImeis->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            "imeis.{$import->id}" =>
                            "Sản phẩm {$product->name} là sản phẩm thường nên không được gửi danh sách IMEI.",
                        ]);
                    }
                }
            }

            $product->update(['price' => $import->price]);

            $this->productStorageService->updateProductStorage($product->id, $storageId, [
                'quantity' => (int) $import->quantity,
            ]);
            $this->companyProductService->updateCompanyProduct($product->id, $supplierId);
        }

        $this->recordAccountingEntries(
            $ownerId,
            (int) $user->id,
            $importCoupon,
            $bankAccountId,
            $this->transactionDate($request->validated('datetime'))
        );

        Import::query()->whereKey($imports->modelKeys())->delete();
        return (int) $importCoupon->id;
    }

    private function normalizePaymentData(string $paymentMethod, int $paidAmount, int $total): array
    {
        if (! in_array($paymentMethod, ImportCoupon::paymentMethods(), true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Phương thức thanh toán không hợp lệ.',
            ]);
        }

        if ($paidAmount < 0) {
            throw ValidationException::withMessages([
                'totalncc' => 'Số tiền trả nhà cung cấp không được âm.',
            ]);
        }

        if ($paidAmount > $total) {
            throw ValidationException::withMessages([
                'totalncc' => 'Số tiền trả nhà cung cấp không được vượt quá tổng tiền phiếu nhập.',
            ]);
        }

        $debtAmount = max($total - $paidAmount, 0);
        $paymentStatus = match (true) {
            $paidAmount >= $total => ImportCoupon::PAYMENT_STATUS_PAID,
            $paidAmount > 0 => ImportCoupon::PAYMENT_STATUS_PARTIAL,
            default => ImportCoupon::PAYMENT_STATUS_UNPAID,
        };

        return [
            'payment_method' => $paymentMethod,
            'paid_amount' => $paidAmount,
            'debt_amount' => $debtAmount,
            'payment_status' => $paymentStatus,
        ];
    }

    private function recordAccountingEntries(
        int $ownerId,
        int $createdBy,
        ImportCoupon $importCoupon,
        ?int $bankAccountId,
        string $transactionDate
    ): void
    {
        $total = (int) $importCoupon->total;
        $paidAmount = (int) ($importCoupon->paid_amount ?? $importCoupon->payment_ncc ?? 0);
        $paymentMethod = (string) ($importCoupon->payment_method ?? ImportCoupon::PAYMENT_METHOD_CASH);
        $supplierId = (int) $importCoupon->companies_id;
        $accountGoodsId = $this->resolveRequiredAccountId('156');
        $accountPayableId = $this->resolveRequiredAccountId('331');
        $purchaseReference = 'IMP-' . $importCoupon->getKey();

        if (Transaction::query()
            ->where('type', 'expense')
            ->where('document_type', 'import')
            ->where('reference_number', $purchaseReference)
            ->exists()) {
            throw ValidationException::withMessages([
                'accounting' => 'Phiếu nhập này đã có bút toán mua hàng canonical.',
            ]);
        }

        $purchase = $this->createCompletedTransaction([
            'user_id' => $ownerId,
            'transaction_date' => $transactionDate,
            'description' => 'Nhập hàng NCC',
            'type' => 'expense',
            'document_type' => 'import',
            'reference_number' => $purchaseReference,
            'created_by' => $createdBy,
        ]);

        TransactionEntry::create([
            'transaction_id' => $purchase->id,
            'account_id' => $accountGoodsId,
            'debit_amount' => $total,
            'credit_amount' => 0,
            'note' => 'Ghi nhận hàng nhập kho',
        ]);

        TransactionEntry::create([
            'transaction_id' => $purchase->id,
            'account_id' => $accountPayableId,
            'debit_amount' => 0,
            'credit_amount' => $total,
            'tableable_type' => Company::class,
            'tableable_id' => $supplierId,
            'note' => 'Ghi nhận phải trả nhà cung cấp',
        ]);

        if ($paidAmount > 0) {
            $paymentAccountId = $paymentMethod === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
                ? $bankAccountId
                : $this->resolveRequiredAccountId('111');

            if (! $paymentAccountId) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Vui lòng chọn tài khoản con của 112 cho khoản thanh toán chuyển khoản.',
                ]);
            }

            $payment = $this->createCompletedTransaction([
                'user_id' => $ownerId,
                'transaction_date' => $transactionDate,
                'description' => 'Thanh toán NCC',
                'type' => 'expense',
                'document_type' => 'import_payment',
                'reference_number' => $purchaseReference . '-PAY-INITIAL',
                'created_by' => $createdBy,
            ]);

            TransactionEntry::create([
                'transaction_id' => $payment->id,
                'account_id' => $accountPayableId,
                'debit_amount' => $paidAmount,
                'credit_amount' => 0,
                'tableable_type' => Company::class,
                'tableable_id' => $supplierId,
                'note' => 'Giảm công nợ nhà cung cấp',
            ]);

            TransactionEntry::create([
                'transaction_id' => $payment->id,
                'account_id' => $paymentAccountId,
                'debit_amount' => 0,
                'credit_amount' => $paidAmount,
                'note' => 'Chi tiền thanh toán nhà cung cấp bằng ' . $this->paymentDisbursementMethodLabel($paymentMethod),
            ]);
        }
    }

    private function resolveBankAccountId(string $paymentMethod, int $paidAmount, mixed $requestedAccountId): ?int
    {
        if ($paymentMethod !== ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER || $paidAmount <= 0) {
            return null;
        }

        if (! Schema::hasTable('accounts')
            || ! Schema::hasColumn('accounts', 'parent_id')
            || ! Schema::hasColumn('accounts', 'status')
            || ! Schema::hasColumn('accounts', 'is_default')) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Hệ thống chưa có cấu hình tài khoản ngân hàng con dưới 112.',
            ]);
        }

        $parentId = Account::query()->where('code', '112')->value('id');
        $account = Account::query()
            ->whereKey((int) $requestedAccountId)
            ->where('parent_id', $parentId)
            ->where('status', true)
            ->where('is_default', false)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Tài khoản ngân hàng không hợp lệ, không hoạt động hoặc không thuộc tài khoản 112.',
            ]);
        }

        return (int) $account->id;
    }

    private function createCompletedTransaction(array $attributes): Transaction
    {
        if (Schema::hasColumn('transactions', 'status')) {
            $attributes['status'] = Transaction::STATUS_COMPLETED;
        }

        return Transaction::create($attributes);
    }

    private function transactionDate(mixed $value): string
    {
        return $value
            ? Carbon::parse((string) $value)->toDateString()
            : now()->toDateString();
    }

    private function resolveRequiredAccountId(string $code): int
    {
        $accountId = Account::where('code', $code)->value('id');

        if (! $accountId) {
            throw ValidationException::withMessages([
                'accounting' => "Không tìm thấy tài khoản kế toán {$code}. Vui lòng cấu hình tài khoản trước khi xác nhận phiếu nhập.",
            ]);
        }

        return (int) $accountId;
    }

    private function paymentDisbursementMethodLabel(string $paymentMethod): string
    {
        return $paymentMethod === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
            ? 'Chuyển khoản'
            : 'Tiền mặt';
    }
}
