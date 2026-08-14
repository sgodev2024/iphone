<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\OrderPaymentHistoryService;
use App\Services\OrderService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;
    protected $order;
    protected $orderPaymentHistoryService;

    public function __construct(
        OrderService $orderService,
        Order $order,
        OrderPaymentHistoryService $orderPaymentHistoryService
    )
    {
        $this->orderService = $orderService;
        $this->order = $order;
        $this->orderPaymentHistoryService = $orderPaymentHistoryService;
    }
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return view('admin.order.index');
        }

        $searchText = trim((string) $request->query('s', ''));
        $dateRange = trim((string) $request->query('date_range', ''));
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');

        $startDate = null;
        $endDate = null;

        if ($dateRange !== '') {
            // Hỗ trợ cả:
            // 29/06/2026 - 29/07/2026
            // 29/06/2026-29/07/2026
            $dates = preg_split('/\s*-\s*/', $dateRange);

            if (count($dates) === 2) {
                try {
                    $startDate = \Carbon\Carbon::createFromFormat(
                        'd/m/Y',
                        trim($dates[0])
                    )->startOfDay();

                    $endDate = \Carbon\Carbon::createFromFormat(
                        'd/m/Y',
                        trim($dates[1])
                    )->endOfDay();
                } catch (\Throwable $e) {
                    Log::warning('Khoảng ngày lọc đơn hàng không hợp lệ', [
                        'date_range' => $dateRange,
                        'message' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'message' => 'Khoảng ngày không hợp lệ.',
                    ], 422);
                }
            }
        }

        $orderLedger = DB::table('transactions as t')
            ->join('transaction_entries as te', 'te.transaction_id', '=', 't.id')
            ->join('accounts as a', 'a.id', '=', 'te.account_id')
            ->where('t.status', Transaction::STATUS_COMPLETED)
            ->where('t.document_type', 'order')
            ->whereIn('t.type', ['sale', 'income', 'credit_notice'])
            ->where('a.code', '131')
            ->select('t.reference_number')
            ->selectRaw(
                'SUM(CASE WHEN t.type = ? THEN COALESCE(te.debit_amount, 0) ELSE 0 END) AS sale_debit_131',
                ['sale']
            )
            ->selectRaw(
                'SUM(CASE WHEN t.type IN (?, ?) THEN COALESCE(te.credit_amount, 0) ELSE 0 END) AS payment_credit_131',
                ['income', 'credit_notice']
            )
            ->selectRaw(
                'SUM(CASE WHEN t.type = ? THEN 1 ELSE 0 END) AS sale_entry_count_131',
                ['sale']
            )
            ->groupBy('t.reference_number');

        $baseOrdersQuery = Order::query()
            ->leftJoinSub($orderLedger, 'order_ledger', function ($join) {
                $join->on(
                    'order_ledger.reference_number',
                    '=',
                    DB::raw('CAST(orders.id AS CHAR)')
                );
            })
            ->select([
                'orders.*',
                'order_ledger.sale_debit_131',
                'order_ledger.payment_credit_131',
                'order_ledger.sale_entry_count_131',
            ])
            // Admin xem toàn bộ đơn nên không lọc user_id theo tài khoản đăng nhập
            ->when($searchText !== '', function ($query) use ($searchText) {
                $query->where(function ($searchQuery) use ($searchText) {
                    $searchQuery
                        ->where('code', 'like', "%{$searchText}%")
                        ->orWhere('name', 'like', "%{$searchText}%")
                        ->orWhere('phone', 'like', "%{$searchText}%")
                        ->orWhereHas('client', function ($clientQuery) use ($searchText) {
                            $clientQuery
                                ->where('name', 'like', "%{$searchText}%")
                                ->orWhere('phone', 'like', "%{$searchText}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($searchText) {
                            $creatorQuery->where('name', 'like', "%{$searchText}%");
                        })
                        ->orWhereHas('user', function ($userQuery) use ($searchText) {
                            $userQuery->where('name', 'like', "%{$searchText}%");
                        });
                });
            })

            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })

            ->when(
                in_array($paymentStatus, [
                    Order::PAYMENT_STATUS_PAID,
                    Order::PAYMENT_STATUS_PARTIAL,
                    Order::PAYMENT_STATUS_DEBT,
                ], true),
                function ($query) use ($paymentStatus) {
                    $query->where('payment_status', $paymentStatus);
                }
            )

            ->when(
                in_array($paymentMethod, ['cash', 'bank_transfer', 'debt'], true),
                function ($query) use ($paymentMethod) {
                    $query->where('payment_method', $paymentMethod);
                }
            )
            ->where('orders.branch_id', auth()->user()->branch_id);

        $summary = (clone $baseOrdersQuery)
            ->toBase()
            ->select([])
            ->selectRaw(
                'COUNT(DISTINCT orders.id) AS total_orders, COALESCE(SUM(orders.total_money), 0) AS total_revenue'
            )
            ->first();

        $totalOrders = (int) ($summary->total_orders ?? 0);
        $totalRevenue = (float) ($summary->total_revenue ?? 0);

        $orders = $baseOrdersQuery
            ->with([
                'user',
                'client',
                'creator',
            ])
            ->withSum('orderDetails as product_quantity', 'quantity')
            ->latest('orders.created_at')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'html' => view('admin.order.table', compact('orders', 'totalOrders', 'totalRevenue'))->render(),
        ]);
    }

    public function show(string $id)
    {
        $order = Order::query()
            ->with([
                'user',
                'client',
                'creator',
                'orderDetails.product',
                'orderDetails.productImei',
            ])
            ->findOrFail($id);

        $title = 'Chi tiết đơn hàng - ' . ($order->code ?? $order->id);
        $paymentHistory = $this->orderPaymentHistoryService->forOrder($order);

        return view('admin.order.detail', compact('title', 'order', 'paymentHistory'));
    }
}
