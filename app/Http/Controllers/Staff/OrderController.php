<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    //
    public function index(Request $request)
    {
        $config = Config::first();
        $title = "Lịch sử mua hàng";

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
                ->with(['user', 'client'])
                ->paginate(10);


            return response()->json([
                'html' => view('Themes.pages.order.table', compact('orders'))->render(),
            ]);
        }

        return view("Themes.pages.order.index", compact('config', 'title'));
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
                    'client_name' => $order->client->name,
                    'total_money' => $order->total_money,
                    'created_at' => $order->created_at,
                    'status' => $order->status,
                    // Thêm các thông tin khác của đơn hàng cần hiển thị
                ];
            });

            return response()->json([
                'data' => $formattedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'pageOrder' => $page
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Danh sách sản phẩm
            'items'                 => 'required|array|min:1',
            'items.*.id'            => 'required|integer|exists:products,id',
            'items.*.name'          => 'required|string|max:255',
            'items.*.price'         => 'required|numeric|min:0',
            'items.*.qty'           => 'required|integer|min:1',

            // Tổng tiền
            'subtotal'              => 'required|numeric|min:0',
            'discountType'          => 'nullable|in:percent,amount',
            'discountInput'         => 'nullable|numeric|min:0',
            'grand'                 => 'required|numeric|min:0',

            // Thông tin khách hàng
            'customer'              => 'required|array',
            'customer.id'           => 'nullable|integer|exists:clients,id',
            'customer.name'         => 'required|string|max:255',
            'customer.email'        => 'nullable|email|max:255',
            'customer.phone'        => 'required|string|max:20',
            'customer.address'      => 'nullable|string|max:500',
            'customer.payment'      => 'required|in:cash,bank_transfer,debt',
            'customer.note'         => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $validator->validated();

        $subtotal = 0;
        $items = [];

        foreach ($credentials['items'] as $item) {
            $product = Product::where('id', $item['id'])
                ->where('status', true)
                ->first();

            if (!$product) {
                return response()->json([
                    'message' => "Sản phẩm {$item['name']} không tồn tại hoặc chưa được xuất bản!",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($product->quantity < $item['qty']) {
                return response()->json([
                    'message' => "Sản phẩm {$product->name} không đủ tồn kho (còn {$product->quantity})!",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $lineTotal = $product->price_buy * $item['qty'];
            $subtotal += $lineTotal;

            $items[] = [
                'id'    => $product->id,
                'name'  => $product->name,
                'price' => $product->price_buy,
                'qty'   => $item['qty'],
                'total' => $lineTotal,
            ];
        }

        $discount = 0;
        if (!empty($credentials['discountType']) && !empty($credentials['discountInput'])) {
            if ($credentials['discountType'] === 'percent') {
                $discount = $subtotal * ($credentials['discountInput'] / 100);
            } elseif ($credentials['discountType'] === 'amount') {
                $discount = $credentials['discountInput'];
            }
        }

        $grand = max(0, $subtotal - $discount);

        if ($subtotal != $credentials['subtotal'] || $grand != $credentials['grand']) {
            return response()->json([
                'message' => 'Dữ liệu đơn hàng không hợp lệ, vui lòng tải lại giỏ hàng.',
                'server_calculation' => [
                    'subtotal' => $subtotal . ' - ' . $credentials['subtotal'],
                    'discount' => $discount,
                    'grand'    => $grand . ' - ' . $credentials['grand'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = Auth::user();

        $userId = $user->role_id === 3 ? $user->manager_id : $user->id;

        DB::beginTransaction();
        $status = $credentials['customer']['payment'] === 'debt' ? 0 : 1;
        try {
            $order = Order::create([
                'client_id'      => $credentials['customer']['id'] ?? null,
                'user_id'        => $userId,
                'code' => generateCode('orders', 'ODR'),
                'name'  => $credentials['customer']['name'],
                'email' => $credentials['customer']['email'] ?? null,
                'phone' => $credentials['customer']['phone'],
                'address'  => $credentials['customer']['address'] ?? null,
                'payment_method' => $credentials['customer']['payment'],
                'note'           => $credentials['customer']['note'] ?? null,
                'discount_value'       => $discount,
                'discount_type'       => $credentials['discountType'],
                'total_money'    => $grand,
                'status'         => $status, // Trạng thái đơn hàng, 0 = chờ xử lý
                'created_by' => $user->id
            ]);

            foreach ($items as $i) {
                $order->orderDetails()->create([
                    'product_id' => $i['id'],
                    'p_name' => $i['name'],
                    'p_price' => $i['price'],
                    'p_quantity' => $i['qty'],
                ]);

                Product::where('id', $i['id'])->decrement('quantity', $i['qty']);
            }

            DB::commit();

            return response()->json([
                'message' => "Tạo đơn hàng thành công!"
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
