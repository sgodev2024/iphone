<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CheckDetail;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Services\CheckInventoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            $check = $this->checkInventory->getAllCheckInventory(Auth::user());
            return view('admin.check.index', compact('check', 'title'));
        } catch (HttpExceptionInterface $e) {
            throw $e;
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
            $check = $this->checkInventory->filterCheck($startDate, $endDate, $phone, Auth::user());
            return view('admin.check.index', compact('check', 'title'));
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to find check ticket: ' . $e->getMessage());
            return redirect()->route('admin.check.index')->with('error', 'Failed to find Check Tickets');
        }
    }

    public function detail($id)
    {
        $title = 'Chi tiết kho';
        try {
            $check = $this->checkInventory->getCheckInventoryById($id, Auth::user());
            $details = CheckDetail::query()
                ->with('product')
                ->where('check_inventory_id', $id)
                ->get();
            $storageQuery = Storage::query()->visibleTo(Auth::user());

            if ($check->user?->storage_id
                && (clone $storageQuery)->whereKey($check->user->storage_id)->exists()
            ) {
                $storageQuery->whereKey($check->user->storage_id);
            }

            $stockByProduct = ProductStorage::query()
                ->whereIn('storage_id', $storageQuery->pluck('id')->all())
                ->whereIn('product_id', $details->pluck('product_id')->all())
                ->selectRaw('product_id, SUM(quantity) AS quantity')
                ->groupBy('product_id')
                ->pluck('quantity', 'product_id');
            $tongthucte = 0;
            $slgiam = 0;
            $sltang = 0;
            $sum1 = 0;
            $sum2 = 0;
            $sum3 = 0;
            foreach ($details as $item) {
                $stockQuantity = (int) ($stockByProduct->get($item->product_id) ?? 0);
                $item->setAttribute('scoped_stock_quantity', $stockQuantity);
                $tongthucte += $item->difference + $stockQuantity;
                $sum1 += ($item->difference + $stockQuantity) * $item->product->price;
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
        } catch (ModelNotFoundException $e) {
            abort(404);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to get check detail');
            return ApiResponse::error('Check not found', 500);
        }
    }
}
