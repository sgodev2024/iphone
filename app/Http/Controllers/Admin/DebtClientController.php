<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DebtKHService;
use Illuminate\Http\Request;

class DebtClientController extends Controller
{
    protected $debtKHService;
    public function __construct(DebtKHService $debtKHService){
        $this->debtKHService = $debtKHService;
    }

    public function index(){
        $title = 'Công nợ khách hàng';
        $debtclients = $this->debtKHService->getAllClientDebt();
        return view('admin.debt.client.index', compact('debtclients', 'title'));

    }

    public function detail($id){
        $title = 'Công nợ khách hàng';
        $debtdetail = $this->debtKHService->findClientDebtById($id);
        return view('admin.debt.client.detail', compact('debtdetail', 'title'));
    }
}
