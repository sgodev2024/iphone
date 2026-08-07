<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DebtNccService;
use Illuminate\Http\Request;

class DebtNccController extends Controller
{
    protected $debtNccService;
    public function __construct(DebtNccService $debtNccService){
        $this->debtNccService = $debtNccService;
    }

    public function index(){
        $title = 'Công nợ nhà cung cấp';
        $debtsupplier = $this->debtNccService->getAllSupplierDebt();
        return view('admin.debt.supplier.index', compact('debtsupplier', 'title'));

    }

    public function detail($id){
        $title = 'Công nợ nhà cung cấp';
        $debtdetail = $this->debtNccService->findSupplierDebtById($id);
        return view('admin.debt.supplier.detail', compact('debtdetail', 'title'));
    }
}
