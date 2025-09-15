<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;
    protected $order;
    public function __construct(OrderService $orderService, Order $order)
    {
        $this->orderService = $orderService;
        $this->order = $order;
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $searchText = $request->query('s');
            $dateRange = $request->query('date_range'); // ví dụ: "12/05/2025 - 12/04/2026"
            $start = $end = null;

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) === 2) {
                    $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                    $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                }
            }

            $orders = Order::query()
                ->where('user_id', Auth::id())
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('code', 'like', "%$searchText%")
                        ->orWhereHas('client', function ($q) use ($searchText) {
                            $q->where('name', 'like', "%$searchText%")
                                ->orWhere('phone', 'like', "%$searchText%");
                        });
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                })
                ->with(['user', 'client', 'creator'])
                ->withCount('orderDetails')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'html' => view('admin.order.table', compact('orders'))->render(),
            ]);
        }

        return view('admin.order.index');
    }

    public function show(string $id)
    {
        $order = Order::query()
            ->where('user_id', Auth::id())
            ->with(['client', 'creator', 'orderDetails'])
            ->findOrFail($id);

        $title = "Chi tiết đơn hàng - {$order->code}";

        return view('admin.order.detail', compact('title', 'order'));
    }
}
