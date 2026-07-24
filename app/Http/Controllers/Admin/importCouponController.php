<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\StoreImportCouponRequest;
use App\Models\Account;
use App\Models\Company;
use App\Models\ExpenseDetail;
use App\Models\Import;
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
    ) {}

    public function add(StoreImportCouponRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $this->confirmImportCoupon($request);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['imeis' => 'Có IMEI đã tồn tại trong hệ thống. Phiếu nhập chưa được lưu.'])
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
            ->route('admin.importproduct.index')
            ->with('success', 'Nhập hàng thành công.');
    }

    private function confirmImportCoupon(StoreImportCouponRequest $request): void
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

        foreach ($imports as $import) {
            $productName = $import->product?->name ?? "#{$import->product_id}";
            $tracking = $import->product?->inventory_tracking;

            if (! in_array($tracking, Product::INVENTORY_TRACKING_OPTIONS, true)) {
                throw ValidationException::withMessages([
                    "items.{$import->id}" => "Sản phẩm {$productName} chưa có phương thức quản lý tồn kho hợp lệ.",
                ]);
            }

            if ($tracking === Product::INVENTORY_TRACKING_QUANTITY) {
                if (array_key_exists($import->id, $submittedImeis) || array_key_exists((string) $import->id, $submittedImeis)) {
                    throw ValidationException::withMessages([
                        "imeis.{$import->id}" => "Sản phẩm {$productName} là sản phẩm thường nên không được gửi danh sách IMEI.",
                    ]);
                }

                continue;
            }

            if ((int) $import->quantity > ProductImei::MAX_IMPORT_QUANTITY) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" => 'Mỗi lần chỉ được nhập tối đa 35 sản phẩm',
                ]);
            }

            $imeis = array_values((array) ($submittedImeis[$import->id] ?? $submittedImeis[(string) $import->id] ?? []));

            if (count($imeis) !== (int) $import->quantity) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" => "Số IMEI phải bằng số lượng nhập của sản phẩm {$productName}.",
                ]);
            }
        }

        $total = (int) $imports->sum('total');
        $paidAmount = (int) $request->validated('totalncc', 0);

        if ($paidAmount > $total) {
            throw ValidationException::withMessages([
                'totalncc' => 'Số tiền trả nhà cung cấp không được vượt quá tổng tiền phiếu nhập.',
            ]);
        }

        $importCoupon = $this->importProductService->addImportCoupon([
            'user_id' => $user->id,
            'companies_id' => $supplierId,
            'total' => $total,
            'payment_ncc' => $paidAmount,
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
                $imeis = array_values((array) ($submittedImeis[$import->id] ?? $submittedImeis[(string) $import->id] ?? []));
                $importDetail->imeis()->createMany(array_map(
                    fn(string $imei) => [
                        'product_id' => $product->id,
                        'imei' => $imei,
                        'status' => ProductImei::STATUS_IN_STOCK,
                    ],
                    $imeis
                ));
            } elseif (array_key_exists($import->id, $submittedImeis) || array_key_exists((string) $import->id, $submittedImeis)) {
                throw ValidationException::withMessages([
                    "imeis.{$import->id}" => "Sản phẩm {$product->name} là sản phẩm thường nên không được gửi danh sách IMEI.",
                ]);
            }

            $product->update(['price' => $import->price]);

            $this->productStorageService->updateProductStorage($product->id, $storageId, [
                'quantity' => (int) $import->quantity,
            ]);
            $this->companyProductService->updateCompanyProduct($product->id, $supplierId);
        }

        $debtAmount = $total - $paidAmount;
        $this->recordSupplierDebt($supplierId, $debtAmount);
        $this->recordSupplierPayment($supplierId, $paidAmount);
        $this->recordAccountingEntries($user->id, $supplierId, $total, $debtAmount);

        Import::query()->whereKey($imports->modelKeys())->delete();
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

    private function recordSupplierPayment(int $supplierId, int $paidAmount): void
    {
        if ($paidAmount <= 0) {
            return;
        }

        $company = Company::findOrFail($supplierId);
        $supplierIds = $this->expenseService->getAllExpense()->pluck('companies_id');

        if ($supplierIds->contains($supplierId)) {
            $expense = $this->expenseService->findExpenseByCompany($supplierId);
            $this->expenseService->updateExpense([
                'amount_spent' => $paidAmount + $expense->amount_spent,
            ], $supplierId);
        } else {
            $expense = $this->expenseService->addExpense([
                'companies_id' => $supplierId,
                'content' => "Thanh toán cho nhà cung cấp {$company->name}",
                'amount_spent' => $paidAmount,
                'date_spent' => Carbon::now()->toDateString(),
            ]);
        }

        ExpenseDetail::create([
            'expense_id' => $expense->id,
            'content' => "Thanh toán cho nhà cung cấp {$company->name}",
            'amount' => $paidAmount,
            'date' => Carbon::now()->toDateString(),
        ]);
    }

    private function recordAccountingEntries(int $userId, int $supplierId, int $total, int $debtAmount): void
    {
        $accountGoodsId = Account::where('code', '156')->value('id');
        $accountSupplierId = Account::where('code', '331')->value('id');

        if (! $accountGoodsId || ! $accountSupplierId) {
            return;
        }

        $transaction = Transaction::create([
            'user_id' => $userId,
            'transaction_date' => now(),
            'description' => 'Nhập hàng NCC',
            'type' => 'expense',
            'document_type' => 'import',
            'reference_number' => 'IMP-' . now()->format('YmdHis'),
            'created_by' => $userId,
        ]);

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $accountGoodsId,
            'debit_amount' => $total,
            'credit_amount' => 0,
            'tableable_type' => Company::class,
            'tableable_id' => $supplierId,
        ]);

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $accountSupplierId,
            'debit_amount' => $debtAmount > 0 ? $debtAmount : 0,
            'credit_amount' => $debtAmount > 0 ? 0 : $total,
        ]);
    }
}
