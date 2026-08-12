<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Expense;
use App\Services\DebtNccService;
use App\Services\ExpenseService;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $ownerId = (int) request()->user()->ownerId();
        $debtncc = Expense::query()
            ->whereIn('companies_id', Company::query()->where('user_id', $ownerId)->select('id'))
            ->orderByDesc('updated_at')
            ->paginate(10);
        $expenses = $this->expenseService->getAllExpense($ownerId);
        return view('admin.quanlythuchi.expense.index', compact('expenses', 'title', 'debtncc'));
    }

    public function add(){
        $title = 'Quản lý chi';
        $debtNcc = $this->debtNccService->getAllSupplierDebt((int) request()->user()->ownerId());

        return view('admin.quanlythuchi.expense.add', compact('title', 'debtNcc'));
    }

    public function addSubmit(Request $request){
        abort(Response::HTTP_GONE, 'Thanh toán nhà cung cấp qua phiếu chi legacy đã được vô hiệu hóa.');
    }

    public function detail($id){
        $title = 'Quản lý chi';
        $expenses = $this->expenseService->findExpenseById($id, (int) request()->user()->ownerId());
        return view('admin.quanlythuchi.expense.detail', compact('expenses', 'title'));
    }

    public function debt(Request $request){
        $supplier = $request->supplier;
        $debt = $this->debtNccService->findCompanyDebtBySupplier($supplier, (int) $request->user()->ownerId());
        return  response()->json(explode(',', $debt->amount)[0]);
    }
}
