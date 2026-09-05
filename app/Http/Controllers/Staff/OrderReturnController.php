<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreOrderReturnRequest;
use App\Models\Order;
use App\Services\OrderReturnService;
use App\Support\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\OrderReturnDetail;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\OrderReturn;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class OrderReturnController extends Controller
{
    public function __construct(
        protected OrderReturnService $orderReturnService,
        protected BranchContext $branchContext
    ) {}

    public function create(
        Request $request,
        Order $order
    ): View {
        $user = $request->user();

        /*
         |--------------------------------------------------------------------------
         | 1. Lấy lại đơn và kiểm tra phạm vi chi nhánh
         |--------------------------------------------------------------------------
         |
         | Không dùng trực tiếp $order từ route binding vì cần bảo đảm nhân viên
         | chỉ mở được đơn thuộc chi nhánh mình được phép thao tác.
         |
         */
        $orderQuery = Order::query()
            ->with([
                'client',
                'creator',
                'user',
                'orderDetails.product',
                'orderDetails.productImei',
            ])
            ->whereKey($order->id);
        $this->branchContext->scope($orderQuery, $user);

        $order = $orderQuery
            ->firstOrFail();


        /*
         |--------------------------------------------------------------------------
         | 2. Chỉ cho phép thao tác trên đơn đã hoàn thành
         |--------------------------------------------------------------------------
         */
        if (! (bool) $order->status) {
            abort(422, 'Chỉ có thể trả hàng từ đơn đã hoàn thành.');
        }
        /*
|--------------------------------------------------------------------------
| XÁC ĐỊNH NGUỒN GỐC CỦA EXCHANGE ORDER
|--------------------------------------------------------------------------
|
| Nếu order hiện tại từng được dùng làm exchange_order_id
| của một order_return completed thì đây là đơn được sinh
| ra từ nghiệp vụ đổi hàng.
|
*/

$sourceReturnQuery = OrderReturn::query()
->with([
    'originalOrder.client',
    'originalOrder.creator',
    'originalOrder.user',
    'originalOrder.orderDetails.product',
    'originalOrder.orderDetails.productImei',
])
->where(
    'exchange_order_id',
    $order->id
)
->where(
    'status',
    'completed'
);
$this->branchContext->scope($sourceReturnQuery, $user);

$sourceReturn = $sourceReturnQuery
->first();


$sourceOrderInfo = null;


if (
$sourceReturn
&& $sourceReturn->originalOrder
) {

$sourceOrder =
    $sourceReturn->originalOrder;


$sourceSubtotal =
    (int) $sourceOrder->orderDetails
        ->sum(function ($detail) {

            return
                (int) $detail->price
                *
                (int) $detail->quantity;
        });


$sourceOrderInfo = [

    /*
     * Phiếu đã sinh ra order hiện tại.
     */
    'return_id'
        => (int) $sourceReturn->id,

    'return_code'
        => $sourceReturn->code,


    /*
     * Đơn nguồn trực tiếp.
     */
    'order_id'
        => (int) $sourceOrder->id,

    'order_code'
        => $sourceOrder->code,

    'created_at'
        => optional(
            $sourceOrder->created_at
        )->format('d/m/Y H:i'),

    'customer_name'
        => $sourceOrder
            ->customer_display_name,

    'customer_phone'
        => $sourceOrder
            ->customer_display_phone
            ?? '-',


    /*
     * Giá trị đơn nguồn.
     */
    'subtotal'
        => $sourceSubtotal,

    'discount_value'
        => (int) (
            $sourceOrder->discount_value
            ?? 0
        ),

    'total_money'
        => (int) (
            $sourceOrder->total_money
            ?? 0
        ),


    /*
     * Giá trị giao dịch đổi / trả đã sinh ra
     * order hiện tại.
     */
    'return_amount'
        => (int) $sourceReturn
            ->return_amount,

    'exchange_amount'
        => (int) $sourceReturn
            ->exchange_amount,

    'fee_amount'
        => (int) $sourceReturn
            ->fee_amount,

    'refund_amount'
        => (int) $sourceReturn
            ->refund_amount,

    'additional_payment'
        => (int) $sourceReturn
            ->additional_payment,


    /*
     * Hàng của đơn nguồn.
     */
    'items'
        => $sourceOrder
            ->orderDetails
            ->map(
                function ($detail) {

                    return [

                        'product_name'
                            => $detail
                                ->product
                                ?->name
                                ?? 'Sản phẩm',

                        'product_code'
                            => $detail
                                ->product
                                ?->code,

                        'imei'
                            => $detail
                                ->productImei
                                ?->imei,

                        'quantity'
                            => (int) $detail
                                ->quantity,

                        'unit_price'
                            => (int) $detail
                                ->price,

                        'line_total'
                            => (int) $detail
                                ->price
                                *
                                (int) $detail
                                ->quantity,
                    ];
                }
            )
            ->values()
            ->all(),
];
}


        /*
         |--------------------------------------------------------------------------
         | 3. Lấy tổng số lượng đã trả của từng order_detail
         |--------------------------------------------------------------------------
         |
         | Chỉ tính phiếu trả completed.
         |
         */
        $orderDetailIds = $order->orderDetails
            ->pluck('id')
            ->values();

        $returnedByDetail = $orderDetailIds->isEmpty()
            ? collect()
            : OrderReturnDetail::query()
            ->whereIn(
                'order_detail_id',
                $orderDetailIds->all()
            )
            ->whereHas(
                'orderReturn',
                fn($query) => $query->where(
                    'status',
                    'completed'
                )
            )
            ->selectRaw(
                'order_detail_id, SUM(quantity) AS returned_quantity'
            )
            ->groupBy('order_detail_id')
            ->pluck(
                'returned_quantity',
                'order_detail_id'
            );


        /*
         |--------------------------------------------------------------------------
         | 4. Chuẩn bị danh sách sản phẩm cho giao diện
         |--------------------------------------------------------------------------
         */
        $returnItems = $order->orderDetails
            ->map(function ($detail) use ($returnedByDetail) {

                $originalQuantity =
                    (int) $detail->quantity;

                $returnedQuantity =
                    (int) (
                        $returnedByDetail->get($detail->id)
                        ?? 0
                    );

                $returnableQuantity = max(
                    0,
                    $originalQuantity - $returnedQuantity
                );

                $product = $detail->product;

                return [
                    'order_detail_id'
                    => (int) $detail->id,

                    'product_id'
                    => (int) $detail->product_id,

                    'product_name'
                    => $product?->name
                        ?? 'Sản phẩm không xác định',

                    'product_code'
                    => $product?->code,

                    'tracking_type'
                    => $product?->inventory_tracking,

                    'product_imei_id'
                    => $detail->product_imei_id
                        ? (int) $detail->product_imei_id
                        : null,

                    'imei'
                    => $detail->productImei?->imei,

                    'storage_id'
                    => $detail->storage_id
                        ? (int) $detail->storage_id
                        : null,

                    'unit_price'
                    => (int) $detail->price,

                    /*
                     * Số lượng đã mua ban đầu.
                     */
                    'original_quantity'
                    => $originalQuantity,

                    /*
                     * Tổng số lượng đã trả qua các phiếu trước.
                     */
                    'returned_quantity'
                    => $returnedQuantity,

                    /*
                     * Số lượng tối đa còn được phép trả.
                     */
                    'returnable_quantity'
                    => $returnableQuantity,

                    'can_return'
                    => $returnableQuantity > 0,
                ];
            })
            ->values();


        /*
         |--------------------------------------------------------------------------
         | 5. Xác định đơn đã được trả hết hay chưa
         |--------------------------------------------------------------------------
         */
        $hasReturnableItems = $returnItems
            ->contains(
                fn($item) =>
                $item['returnable_quantity'] > 0
            );

        $isFullyReturned = ! $hasReturnableItems;


        /*
         |--------------------------------------------------------------------------
         | 6. Một số số liệu phục vụ phần đầu giao diện
         |--------------------------------------------------------------------------
         */
        $subtotal = (int) $order->orderDetails
            ->sum(function ($detail) {
                return (int) $detail->price
                    * (int) $detail->quantity;
            });

        $returnedAmount = (int) OrderReturnDetail::query()
            ->whereIn(
                'order_detail_id',
                $orderDetailIds->all()
            )
            ->whereHas(
                'orderReturn',
                fn($query) => $query->where(
                    'status',
                    'completed'
                )
            )
            ->sum('return_amount');


        $summary = [
            'subtotal' => $subtotal,

            'discount_value'
            => (int) ($order->discount_value ?? 0),

            'total_money'
            => (int) ($order->total_money ?? 0),

            /*
             * Giá trị đã được công nhận trả trước đây.
             * Đây không phải số lượng.
             */
            'returned_amount'
            => $returnedAmount,

            'is_fully_returned'
            => $isFullyReturned,
        ];


        $title =
            'Đổi / trả hàng - '
            . ($order->code ?? "#{$order->id}");


        return view(
            'Themes.pages.order.return',
            compact(
                'title',
                'order',
                'returnItems',
                'summary',
                'hasReturnableItems',
                'isFullyReturned',
                'sourceOrderInfo'
            )
        );
    }

    public function store(
        StoreOrderReturnRequest $request,
        Order $order
    ): JsonResponse {
        try {
            $data = $request->validated();

            /*
             * Không tin original_order_id từ client.
             * Luôn lấy từ route model binding.
             */
            $data['original_order_id'] = (int) $order->id;

            $orderReturn = $this->orderReturnService
                ->createReturn(
                    $request->user(),
                    $data
                )
                ->load([
                    'originalOrder',
                    'creator',
                    'details.product',
                    'details.productImei',
                    'details.storage',
                ]);

            return response()->json([
                'message' => 'Trả hàng thành công.',

                'order_return' => [
                    'id' => (int) $orderReturn->id,

                    'code' => $orderReturn->code,

                    'original_order' => [
                        'id' => (int) $orderReturn->originalOrder->id,
                        'code' => $orderReturn->originalOrder->code,
                    ],

                    'return_amount' => (int) $orderReturn->return_amount,

                    'fee_amount' => (int) $orderReturn->fee_amount,

                    'refund_amount' => (int) $orderReturn->refund_amount,

                    'additional_payment' => (int) $orderReturn->additional_payment,

                    'status' => $orderReturn->status,

                    'note' => $orderReturn->note,

                    'created_at' => $orderReturn->created_at,

                    'items' => $orderReturn->details
                        ->map(function ($detail) {
                            return [
                                'id' => (int) $detail->id,

                                'order_detail_id'
                                => (int) $detail->order_detail_id,

                                'product_id'
                                => (int) $detail->product_id,

                                'product_name'
                                => $detail->product?->name,

                                'product_imei_id'
                                => $detail->product_imei_id
                                    ? (int) $detail->product_imei_id
                                    : null,

                                'imei'
                                => $detail->productImei?->imei,

                                'storage_id'
                                => (int) $detail->storage_id,

                                'storage_name'
                                => $detail->storage?->name,

                                'quantity'
                                => (int) $detail->quantity,

                                'original_unit_price'
                                => (int) $detail->original_unit_price,

                                'gross_amount'
                                => (int) $detail->gross_amount,

                                'discount_amount'
                                => (int) $detail->discount_amount,

                                'return_amount'
                                => (int) $detail->return_amount,
                            ];
                        })
                        ->values(),
                ],
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())
                    ->flatten()
                    ->first() ?: $e->getMessage(),

                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                // 'message' => $e->getMessage(),
                // 'exception' => get_class($e),
                // 'file' => $e->getFile(),
                // 'line' => $e->getLine(),
                'message' => 'Không thể thực hiện trả hàng.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
