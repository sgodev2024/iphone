<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseDetail;
use App\Models\Supplier;
use App\Models\SupplierDebtsDetail;
use App\Services\DebtNccService;
use App\Services\ExpenseService;
use App\Services\SupplierService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected $expenseService;
    protected $supplierService;
    protected $debtNccService;
    public function __construct(ExpenseService $expenseService, SupplierService $supplierService, DebtNccService $debtNccService){
        $this->expenseService = $expenseService;
        $this->supplierService = $supplierService;
        $this->debtNccService = $debtNccService;
    }

    public function index(){
        $title = 'Quản lý chi';
        $debtncc =  Expense::orderByDesc('updated_at')->paginate(10);;
        $expenses = $this->expenseService->getAllExpense();
        return view('admin.quanlythuchi.expense.index', compact('expenses', 'title', 'debtncc'));
    }

    public function add(){
        $title = 'Quản lý chi';
        $debtNcc = $this->debtNccService->getAllSupplierDebt();

        return view('admin.quanlythuchi.expense.add', compact('title', 'debtNcc'));
    }

    public function addSubmit(Request $request){
        $currentDate = Carbon::now()->format('Y-m-d');
        $supplier = Company::find($request->supplier);
        $expenses = $this->expenseService->getAllExpense()->pluck('companies_id');
        if($expenses->contains($request->supplier)){
            $expense = $this->expenseService->findExpenseByCompany($request->supplier);
            $expensedata = [
                'amount_spent' => $request->amount_spent + $expense->amount_spent,
            ];
            $this->expenseService->updateExpense($expensedata, $request->supplier);
            ExpenseDetail::create([
                'expense_id' => $expense->id,
                'content' => $request->content,
                'amount' => $request->amount_spent,
                'date' => Carbon::now()->toDateString(),
            ]);
        }else{
            $add = [
                'companies_id' => $request->supplier,
                'content' => 'Thanh toán cho nhà cung cấp '.$supplier->name,
                'amount_spent' => $request->amount_spent,
                'date_spent' => Carbon::now()->toDateString(),
            ];
            $expense = $this->expenseService->addExpense($add);
            ExpenseDetail::create([
                'expense_id' => $expense->id,
                'content' => $request->content,
                'amount' => $request->amount_spent,
                'date' => Carbon::now()->toDateString(),
            ]);
        }
        $debtSupplier = $this->debtNccService->findCompanyDebtBySupplier($request->supplier);
        $updatedebt = [
            'amount' => $debtSupplier->amount -  $request->amount_spent,
        ];
        SupplierDebtsDetail::create([
            'supplier_debts_id' => $debtSupplier->id,
            'content' => 'Tạo phiếu chi',
            'amount' => 0 - $request->amount_spent
        ]);
        $this->debtNccService->updateSupplierDebt($updatedebt, $request->supplier );
        $debtnew = $this->debtNccService->findCompanyDebtBySupplier($request->supplier );
        if($debtnew->amount == 0){
            SupplierDebtsDetail::truncate();
            $this->debtNccService->delete($request->supplier);
        }
        return redirect()->route('admin.quanlythuchi.expense.index')->with('success', 'Tạo phiếu thành công !');
    }

    public function detail($id){
        $title = 'Quản lý chi';
        $expenses = $this->expenseService->findExpenseById($id);
        return view('admin.quanlythuchi.expense.detail', compact('expenses', 'title'));
    }

    public function debt(Request $request){
        $supplier = $request->supplier;
        $debt = $this->debtNccService->findCompanyDebtBySupplier($supplier);
        return  response()->json(explode(',', $debt->amount)[0]);
    }
}
