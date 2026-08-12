<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreOrderRequest;
use App\Models\Config;
use App\Models\Order;
use App\Services\SaleService;
use App\Services\SaleStorageResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $config = Config::with(['bank', 'user'])->first();
        $title = 'Lịch sử mua hàng';

        if ($request->ajax()) {
            $searchText = $request->query('s');
            $dateRange = $request->query('date_range');
            $start = $end = null;

            if (! empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) === 2) {
                    $start = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                    $end = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                }
            }

            $orders = Order::query()
                ->where('user_id', Auth::id())
                ->when(! empty($searchText), function ($query) use ($searchText) {
                    $query->where('code', 'like', "%$searchText%")
                        ->orWhereHas('client', function ($q) use ($searchText) {
                            $q->where('name', 'like', "%$searchText%")
                                ->orWhere('phone', 'like', "%$searchText%");
                        });
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                })
                ->with(['user', 'client'])
                ->paginate(10);

            return response()->json([
                'html' => view('Themes.pages.order.table', compact('orders'))->render(),
            ]);
        }

        return view('Themes.pages.order.index', compact('config', 'title'));
    }

    public function orderFetch(Request $request)
    {
        if ($request->ajax()) {
            $page = 6;
            $orders = Order::paginate($page);
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user_name' => $order->user->name,
                    'client_name' => $order->customer_display_name,
                    'total_money' => $order->total_money,
                    'created_at' => $order->created_at,
                    'status' => $order->status,
                ];
            });

            return response()->json([
                'data' => $formattedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'pageOrder' => $page,
            ]);
        }
    }

    public function store(
        StoreOrderRequest $request,
        SaleService $saleService,
        SaleStorageResolver $saleStorageResolver
    ) {
        try {
            $storageId = $saleStorageResolver->resolveSaleStorageId(
                $request->user(),
                $request->input('storage_id')
            );
            $order = $saleService->createPosOrder($request->user(), $request->validated(), $storageId)
                ->load(['orderDetails.product', 'orderDetails.productImei']);

            return response()->json([
                'message' => 'Tạo đơn hàng thành công!',
                'order' => [
                    'id' => (int) $order->id,
                    'code' => $order->code,
                    'subtotal' => (float) $order->orderDetails->sum(
                        fn ($detail) => (float) $detail->price * (int) $detail->quantity
                    ),
                    'discount' => (float) ($order->discount_value ?? 0),
                    'total' => (float) $order->total_money,
                    'payment_method' => $order->payment_method,
                    'paid_amount' => (int) $order->paid_amount,
                    'debt_amount' => (int) $order->debt_amount,
                    'payment_status' => $order->payment_status,
                    'note' => $order->note,
                    'items' => $order->orderDetails->map(fn ($detail) => [
                        'product_id' => (int) $detail->product_id,
                        'name' => $detail->product?->name,
                        'imei' => $detail->productImei?->imei,
                        'quantity' => (int) $detail->quantity,
                        'unit_price' => (float) $detail->price,
                        'line_total' => (float) $detail->price * (int) $detail->quantity,
                    ])->values(),
                ],
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
