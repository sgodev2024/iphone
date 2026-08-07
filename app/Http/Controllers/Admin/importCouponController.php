<?php

namespace App\Http\Controllers\Admin;

use App\Services\InternalBarcodeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Import\StoreImportCouponRequest;
use App\Models\Account;
use App\Models\Company;
use App\Models\ExpenseDetail;
use App\Models\Import;
use App\Models\ImportCoupon;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\SupplierDebtsDetail;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Services\CompanyProductService;
use App\Services\CompanyService;
use App\Services\DebtNccService;
use App\Services\ExpenseService;
use App\Services\ImportProductService;
use App\Services\ProductStorageService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class importCouponController extends Controller
{
    public function __construct(
        protected ImportProductService $importProductService,
        protected ExpenseService $expenseService,
        protected DebtNccService $debtNccService,
        protected CompanyService $companyService,
        protected ProductStorageService $productStorageService,
        protected CompanyProductService $companyProductService,
        protected InternalBarcodeService $internalBarcodeService,
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
                    'imeis' => 'Có IMEI hoặc barcode đã tồn tại. Phiếu nhập chưa được lưu.',
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
        $ownerIds = collect([$user?->id, $user?->manager_id])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $imports = Import::query()
            ->with('product')
            ->where('quantity', '>', 0)
            ->whereHas('product', function ($query) use ($ownerIds) {
                $query->whereIn('user_id', $ownerIds);
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
                ->filter(fn(string $imei) => $imei !== '')
                ->values();

            /*
    |--------------------------------------------------------------------------
    | Sản phẩm quản lý theo số lượng không được nhập IMEI
    |--------------------------------------------------------------------------
    */
            if ($tracking === Product::INVENTORY_TRACKING_QUANTITY) {
                if ($normalizedImeis->isNotEmpty()) {
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

            if ($expectedQuantity > ProductImei::MAX_IMPORT_QUANTITY) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" =>
                    'Mỗi lần chỉ được nhập tối đa ' . ProductImei::MAX_IMPORT_QUANTITY . ' thiết bị.',
                ]);
            }

            if ($normalizedImeis->count() !== $expectedQuantity) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" =>
                    "Sản phẩm {$productName} cần {$expectedQuantity} IMEI, " .
                        "nhưng hiện có {$normalizedImeis->count()} IMEI.",
                ]);
            }

            /*
    |--------------------------------------------------------------------------
    | Kiểm tra đúng 15 chữ số
    |--------------------------------------------------------------------------
    */
            $invalidImei = $normalizedImeis->first(
                fn(string $imei) => ! preg_match('/^\d{15}$/', $imei)
            );

            if ($invalidImei !== null) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" =>
                    "IMEI {$invalidImei} của sản phẩm {$productName} phải gồm đúng 15 chữ số.",
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
                'IMEI bị nhập trùng trong phiếu: ' . $duplicatedImeis->implode(', '),
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
                'IMEI đã tồn tại trong hệ thống: ' . $existingImeis->implode(', '),
            ]);
        }

        $total = (int) $imports->sum('total');
        $payment = $this->normalizePaymentData(
            (string) $request->validated('payment_method', ImportCoupon::PAYMENT_METHOD_CASH),
            (int) $request->validated('totalncc', 0),
            $total
        );

        $importCoupon = $this->importProductService->addImportCoupon([
            'user_id' => $user->id,
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
                ->whereIn('user_id', $ownerIds)
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

        $this->recordSupplierDebt($supplierId, $payment['debt_amount']);
        $this->recordSupplierPayment($supplierId, $payment['paid_amount'], $payment['payment_method']);
        $this->recordAccountingEntries($user->id, $importCoupon);

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

        if (in_array($paymentMethod, [
            ImportCoupon::PAYMENT_METHOD_CASH,
            ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER,
        ], true)) {
            $paidAmount = $total;
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

    private function recordSupplierDebt(int $supplierId, int $debtAmount): void
    {
        if ($debtAmount <= 0) {
            return;
        }

        $supplierIds = $this->debtNccService->getAllSupplierDebt()->pluck('companies_id');

        if ($supplierIds->contains($supplierId)) {
            $supplierDebt = $this->debtNccService->findCompanyDebtBySupplier($supplierId);
            $this->debtNccService->updateSupplierDebt([
                'amount' => $supplierDebt->amount + $debtAmount,
            ], $supplierId);
        } else {
            $company = $this->companyService->findCompanyById($supplierId);
            $supplierDebt = $this->debtNccService->addSupplierDebt([
                'companies_id' => $supplierId,
                'amount' => $debtAmount,
                'description' => "Nợ nhà cung cấp {$company->name} ({$company->phone})",
            ]);
        }

        SupplierDebtsDetail::create([
            'supplier_debts_id' => $supplierDebt->id,
            'content' => 'Ghi nhận công nợ từ phiếu nhập',
            'amount' => $debtAmount,
        ]);
    }

    private function recordSupplierPayment(int $supplierId, int $paidAmount, string $paymentMethod): void
    {
        if ($paidAmount <= 0) {
            return;
        }

        $company = Company::findOrFail($supplierId);
        $supplierIds = $this->expenseService->getAllExpense()->pluck('companies_id');
        $paymentMethodLabel = $this->paymentDisbursementMethodLabel($paymentMethod);

        if ($supplierIds->contains($supplierId)) {
            $expense = $this->expenseService->findExpenseByCompany($supplierId);
            $this->expenseService->updateExpense([
                'amount_spent' => $paidAmount + $expense->amount_spent,
            ], $supplierId);
        } else {
            $expense = $this->expenseService->addExpense([
                'companies_id' => $supplierId,
                'content' => "Thanh toán cho nhà cung cấp {$company->name} ({$paymentMethodLabel})",
                'amount_spent' => $paidAmount,
                'date_spent' => Carbon::now()->toDateString(),
            ]);
        }

        ExpenseDetail::create([
            'expense_id' => $expense->id,
            'content' => "Thanh toán cho nhà cung cấp {$company->name} ({$paymentMethodLabel})",
            'amount' => $paidAmount,
            'date' => Carbon::now()->toDateString(),
        ]);
    }

    private function recordAccountingEntries(int $userId, ImportCoupon $importCoupon): void
    {
        $total = (int) $importCoupon->total;
        $paidAmount = (int) ($importCoupon->paid_amount ?? $importCoupon->payment_ncc ?? 0);
        $debtAmount = (int) ($importCoupon->debt_amount ?? max($total - $paidAmount, 0));
        $paymentMethod = (string) ($importCoupon->payment_method ?? ImportCoupon::PAYMENT_METHOD_CASH);
        $supplierId = (int) $importCoupon->companies_id;
        $accountGoodsId = $this->resolveRequiredAccountId('156');

        $transaction = Transaction::create([
            'user_id' => $userId,
            'transaction_date' => now(),
            'description' => 'Nhập hàng NCC',
            'type' => 'expense',
            'document_type' => 'import',
            'reference_number' => $importCoupon->coupon_code ?: 'IMP-' . now()->format('YmdHis'),
            'created_by' => $userId,
        ]);

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $accountGoodsId,
            'debit_amount' => $total,
            'credit_amount' => 0,
            'tableable_type' => Company::class,
            'tableable_id' => $supplierId,
            'note' => 'Ghi nhận hàng nhập kho',
        ]);

        if ($paidAmount > 0) {
            $paymentAccountCode = $paymentMethod === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER ? '112' : '111';

            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $this->resolveRequiredAccountId($paymentAccountCode),
                'debit_amount' => 0,
                'credit_amount' => $paidAmount,
                'tableable_type' => Company::class,
                'tableable_id' => $supplierId,
                'note' => 'Thanh toán nhà cung cấp bằng ' . $this->paymentDisbursementMethodLabel($paymentMethod),
            ]);
        }

        if ($debtAmount > 0) {
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $this->resolveRequiredAccountId('331'),
                'debit_amount' => 0,
                'credit_amount' => $debtAmount,
                'tableable_type' => Company::class,
                'tableable_id' => $supplierId,
                'note' => 'Ghi nhận công nợ nhà cung cấp',
            ]);
        }
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
