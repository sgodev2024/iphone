<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Import;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\ImportCoupon;
use Illuminate\Http\Request;
use App\Models\ExpenseDetail;
use App\Models\TransactionEntry;
use App\Services\CompanyService;
use App\Services\DebtNccService;
use App\Services\ExpenseService;
use App\Services\ProductService;
use App\Services\SupplierService;
use App\Models\SupplierDebtsDetail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\ImportProductService;
use App\Services\CompanyProductService;
use App\Services\ProductStorageService;

class importCouponController extends Controller
{

    protected $ImportProductService;
    protected $productService;
    protected $expenseService;
    protected $debtNccService;
    protected $supplierService;
    protected $companyService;
    protected $productStorageService;
    protected $companyProductService;

    public function __construct(ImportProductService $ImportProductService, ProductService $productService, ExpenseService $expenseService, DebtNccService $debtNccService, SupplierService $supplierService, CompanyService $companyService, ProductStorageService $productStorageService, CompanyProductService $companyProductService)
    {
        $this->ImportProductService = $ImportProductService;
        $this->productService = $productService;
        $this->expenseService = $expenseService;
        $this->debtNccService = $debtNccService;
        $this->supplierService = $supplierService;
        $this->companyService = $companyService;
        $this->productStorageService = $productStorageService;
        $this->companyProductService = $companyProductService;
    }
    public function add(Request $request)
    {
        $user        = Auth::user();
        $supplier_id = $request->supplier;
        $total       = $request->total;
        $totalncc    = $request->totalncc ? $request->totalncc : 0;
        $congno      = $total - $totalncc;

        $data = [
            'user_id'      => $user->id,
            'companies_id' => $supplier_id,
            'total'        => $total,
            'payment_ncc'  => $totalncc,
            'storage_id'   => $request->storage,
        ];

        // --- Quản lý công nợ NCC ---
        if ($congno > 0) {
            $debtncc = $this->debtNccService->getAllSupplierDebt()->pluck('companies_id');
            if ($debtncc->contains($supplier_id)) {
                $supplier = $this->debtNccService->findCompanyDebtBySupplier($supplier_id);
                $update = [
                    'amount' => $supplier->amount + $congno,
                ];
                $this->debtNccService->updateSupplierDebt($update, $supplier_id);
                SupplierDebtsDetail::create([
                    'supplier_debts_id' => $supplier->id,
                    'content'           => 'Thanh toán thành công',
                    'amount'            => $congno,
                ]);
            } else {
                $supplier = $this->companyService->findCompanyById($supplier_id);
                $add = [
                    'companies_id' => $supplier_id,
                    'amount'       => $congno,
                    'description'  => 'Nợ nhà cung cấp ' . $supplier->name . '(' . $supplier->phone . ')',
                ];
                $debt = $this->debtNccService->addSupplierDebt($add);
                SupplierDebtsDetail::create([
                    'supplier_debts_id' => $debt->id,
                    'content'           => 'Thanh toán thành công',
                    'amount'            => $congno,
                ]);
            }
        }

        if ($totalncc > 0) {
            $supplier = Company::find($supplier_id);
            $expenses = $this->expenseService->getAllExpense()->pluck('supplier_id');
            if ($expenses->contains($supplier_id)) {
                $expense = $this->expenseService->findExpenseBysupplier($supplier_id);
                $expensedata = [
                    'amount_spent' => $totalncc + $expense->amount_spent,
                ];
                $this->expenseService->updateExpense($expensedata, $supplier_id);
                ExpenseDetail::create([
                    'expense_id' => $expense->id,
                    'content'    => 'Thanh toán cho nhà cung cấp ' . $supplier->name,
                    'amount'     => $totalncc,
                    'date'       => Carbon::now()->toDateString(),
                ]);
            } else {
                $add = [
                    'companies_id' => $supplier_id,
                    'content'      => 'Thanh toán cho nhà cung cấp ' . $supplier->name,
                    'amount_spent' => $totalncc,
                    'date_spent'   => Carbon::now()->toDateString(),
                ];
                $expense = $this->expenseService->addExpense($add);
                ExpenseDetail::create([
                    'expense_id' => $expense->id,
                    'content'    => 'Thanh toán cho nhà cung cấp ' . $supplier->name,
                    'amount'     => $totalncc,
                    'date'       => Carbon::now()->toDateString(),
                ]);
            }
        }

        $importCoupon = $this->ImportProductService->addImportCoupon($data);
        $import = Import::where('quantity', '>', 0)->get();

        foreach ($import as $value) {
            $data1 = [
                'import_id'  => $importCoupon->id,
                'product_id' => $value->product_id,
                'quantity'   => $value->quantity,
                'price'      => $value->price,
                'old_price'  => $value->product->price,
            ];
            $this->ImportProductService->addImportDetail($data1);

            $product = $this->productService->getProductById($value->product_id);
            $data2 = [
                'quantity' => $product->quantity + $value->quantity,
                'price'    => $value->price,
            ];
            Product::find($value->product_id)->update($data2);

            $productStorageData = [
                'quantity' => $value->quantity,
            ];
            $this->productStorageService->updateProductStorage($value->product_id, $request->storage, $productStorageData);

            $this->companyProductService->updateCompanyProduct($value->product_id, $supplier_id);
        }

        Import::truncate();
        $accountGoodsId = Account::where('code', '156')->value('id'); // Hàng hóa
        $accountNCCId   = Account::where('code', '331')->value('id'); // Phải trả NCC

        if ($accountGoodsId && $accountNCCId) {
            $transaction = Transaction::create([
                'user_id'          => $user->id,
                'transaction_date' => now(),
                'description'      => "Nhập hàng NCC",
                'type'             => 'expense',
                'document_type'    => 'import',
                'reference_number' => 'IMP-' . now()->format('YmdHis'),
                'created_by'       => $user->id,
            ]);

            // 1. Nợ 156 - gắn NCC
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id'     => $accountGoodsId,
                'debit_amount'   => $total,
                'credit_amount'  => 0,
                'tableable_type' => 'App\\Models\\Company',
                'tableable_id'   => $supplier_id,
            ]);

            // 2. 331 - nếu trả hết thì Ghi Có, nếu chưa trả thì Ghi Nợ
            if ($congno > 0) {
                TransactionEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $accountNCCId,
                    'debit_amount'   => $congno,
                    'credit_amount'  => 0,
                ]);
            } else {
                TransactionEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $accountNCCId,
                    'debit_amount'   => 0,
                    'credit_amount'  => $total,
                ]);
            }
        }
        return redirect()->route('admin.importproduct.index')->with('success', 'Nhập hàng thành công');
    }
}
