<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Expense;
use App\Models\SupplierDebt;
use App\Models\User;
use App\Services\DebtNccService;
use App\Services\ExpenseService;
use App\Services\SupplierService;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    protected $expenseService;
    protected $supplierService;
    protected $debtNccService;
    public function __construct(ExpenseService $expenseService, SupplierService $supplierService, DebtNccService $debtNccService, private readonly BranchContext $branchContext){
        $this->expenseService = $expenseService;
        $this->supplierService = $supplierService;
        $this->debtNccService = $debtNccService;
    }

    public function index(){
        $title = 'Quản lý chi';
        $debtncc = $this->expenseQuery()
            ->orderByDesc('updated_at')
            ->paginate(10);
        $expenses = $this->expenseQuery()->get();
        return view('admin.quanlythuchi.expense.index', compact('expenses', 'title', 'debtncc'));
    }

    public function add(){
        $title = 'Quản lý chi';
        $debtNcc = $this->supplierDebtQuery()->orderByDesc('created_at')->get();

        return view('admin.quanlythuchi.expense.add', compact('title', 'debtNcc'));
    }

    public function addSubmit(Request $request){
        abort(Response::HTTP_GONE, 'Thanh toán nhà cung cấp qua phiếu chi legacy đã được vô hiệu hóa.');
    }

    public function detail($id){
        $title = 'Quản lý chi';
        $expenses = $this->expenseQuery()->whereKey($id)->firstOrFail();
        return view('admin.quanlythuchi.expense.detail', compact('expenses', 'title'));
    }

    public function debt(Request $request){
        $supplier = $request->supplier;
        $debt = $this->supplierDebtQuery()->where('companies_id', $supplier)->first();
        return  response()->json(explode(',', $debt->amount)[0]);
    }

    private function expenseQuery(?User $actor = null): Builder
    {
        $companies = Company::query();
        $this->branchContext->scope($companies, $actor ?? request()->user());

        return Expense::query()->whereIn('companies_id', $companies->select('id'));
    }

    private function supplierDebtQuery(?User $actor = null): Builder
    {
        $query = SupplierDebt::query();

        if (Schema::hasColumn('supplier_debts', 'branch_id')) {
            return $this->branchContext->scope($query, $actor ?? request()->user());
        }

        $companies = Company::query();
        $this->branchContext->scope($companies, $actor ?? request()->user());

        return $query->whereIn('companies_id', $companies->select('id'));
    }
}
