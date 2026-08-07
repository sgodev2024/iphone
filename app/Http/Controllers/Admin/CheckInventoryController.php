<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CheckDetail;
use App\Services\CheckInventoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckInventoryController extends Controller
{
    protected $checkInventory;
    public function __construct(CheckInventoryService $checkInventory)
    {
        $this->checkInventory = $checkInventory;
    }

    public function index()
    {
        $title = 'Quản lý kho';
        try {
            $check = $this->checkInventory->getAllCheckInventory();
            return view('admin.check.index', compact('check', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to get Check Tickets: ' . $e->getMessage());
            return redirect()->route('admin.check.index')->with('error', 'Failed to get check tickets');
        }
    }

    public function filterCheck(Request $request)
    {
        $phone = $request->input('phone');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $title = 'Quản lý kho';
        try {
            $check = $this->checkInventory->filterCheck($startDate, $endDate, $phone);
            return view('admin.check.index', compact('check', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to find check ticket: ' . $e->getMessage());
            return redirect()->route('admin.check.index')->with('error', 'Failed to find Check Tickets');
        }
    }

    public function detail($id)
    {
        $title = 'Chi tiết kho';
        try {
            $check = $this->checkInventory->getCheckInventoryById($id);
            $details = CheckDetail::where('check_inventory_id', $id)->get();
            $tongthucte = 0;
            $slgiam = 0;
            $sltang = 0;
            $sum1 = 0;
            $sum2 = 0;
            $sum3 = 0;
            foreach ($details as $item) {
                $tongthucte += $item->difference + $item->product->quantity;
                $sum1 += ($item->difference + $item->product->quantity) *$item->product->price;
                if ($item->difference < 0) {
                    $slgiam += $item->difference;
                    $sum2 += $item->difference * $item->product->price;
                }
                if ($item->difference >= 0) {
                    $sltang += $item->difference;
                    $sum3 += $item->difference * $item->product->price;
                }
            }

            $tong_lech = $slgiam + $sltang;


            return view('admin.check.detail', compact('check', 'details', 'title', 'tongthucte', 'slgiam', 'sltang', 'sum1', 'sum2', 'sum3', 'tong_lech'));
        } catch (Exception $e) {
            Log::error('Failed to get check detail');
            return ApiResponse::error('Check not found', 500);
        }
    }
}
