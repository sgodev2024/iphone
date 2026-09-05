<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderReturn;
use App\Models\OrderReturnDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReturnService
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly SaleStorageResolver $saleStorageResolver,
        private readonly BranchContext $branchContext
    ) {
    }


    public function createReturn(
        User $user,
        array $data
    ): OrderReturn {
        return DB::transaction(function () use ($user, $data) {

            /*
             |--------------------------------------------------------------------------
             | 1. LOCK ĐƠN GỐC
             |--------------------------------------------------------------------------
             */
            $originalOrderQuery = Order::query()
                ->whereKey(
                    (int) $data['original_order_id']
                );
            $this->branchContext->scope($originalOrderQuery, $user);

            $originalOrder = $originalOrderQuery
                ->lockForUpdate()
                ->first();


            if (! $originalOrder) {
                throw ValidationException::withMessages([
                    'original_order_id'
                        => 'Không tìm thấy đơn hàng hoặc đơn hàng không thuộc chi nhánh hiện tại.',
                ]);
            }


            if (! (bool) $originalOrder->status) {
                throw ValidationException::withMessages([
                    'original_order_id'
                        => 'Chỉ có thể đổi / trả hàng từ đơn đã hoàn thành.',
                ]);
            }


            /*
             |--------------------------------------------------------------------------
             | 2. LOCK TOÀN BỘ ORDER DETAILS CỦA ĐƠN GỐC
             |--------------------------------------------------------------------------
             */
            $orderDetails = OrderDetail::query()
                ->where(
                    'order_id',
                    $originalOrder->id
                )
                ->with([
                    'product',
                    'productImei',
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');


            if ($orderDetails->isEmpty()) {
                throw ValidationException::withMessages([
                    'return_items'
                        => 'Đơn hàng không có sản phẩm để trả.',
                ]);
            }


            /*
             |--------------------------------------------------------------------------
             | 3. NORMALIZE RETURN ITEMS
             |--------------------------------------------------------------------------
             */
            $requestedReturnItems = collect(
                $data['return_items'] ?? []
            )
                ->map(function ($item) {
                    return [
                        'order_detail_id'
                            => (int) (
                                $item['order_detail_id']
                                ?? 0
                            ),

                        'quantity'
                            => (int) (
                                $item['quantity']
                                ?? 0
                            ),
                    ];
                })
                ->values();


            if ($requestedReturnItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'return_items'
                        => 'Vui lòng chọn ít nhất một sản phẩm cần trả.',
                ]);
            }


            /*
             |--------------------------------------------------------------------------
             | 4. KIỂM TRA DETAIL PHẢI THUỘC ĐÚNG ĐƠN
             |--------------------------------------------------------------------------
             */
            foreach ($requestedReturnItems as $item) {

                if (
                    ! $orderDetails->has(
                        $item['order_detail_id']
                    )
                ) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Sản phẩm #{$item['order_detail_id']} không thuộc đơn hàng {$originalOrder->code}.",
                    ]);
                }
            }


            /*
             |--------------------------------------------------------------------------
             | 5. TỔNG SỐ ĐÃ TRẢ TRƯỚC ĐÂY
             |--------------------------------------------------------------------------
             |
             | Chỉ tính phiếu completed.
             |
             */
            $orderDetailIds = $orderDetails
                ->keys()
                ->all();


            $returnedByDetail = OrderReturnDetail::query()
                ->whereIn(
                    'order_detail_id',
                    $orderDetailIds
                )
                ->whereHas(
                    'orderReturn',
                    function ($query) {
                        $query->where(
                            'status',
                            'completed'
                        );
                    }
                )
                ->selectRaw(
                    'order_detail_id, SUM(quantity) AS returned_quantity'
                )
                ->groupBy(
                    'order_detail_id'
                )
                ->pluck(
                    'returned_quantity',
                    'order_detail_id'
                );


            /*
             |--------------------------------------------------------------------------
             | 6. PHÂN BỔ DISCOUNT CỦA ĐƠN GỐC
             |--------------------------------------------------------------------------
             */
            $discountByDetail =
                $this->allocateOriginalOrderDiscount(
                    $orderDetails->values(),
                    (int) (
                        $originalOrder
                            ->discount_value
                        ?? 0
                    )
                );


            /*
             |--------------------------------------------------------------------------
             | 7. TÍNH CÁC DÒNG HÀNG TRẢ
             |--------------------------------------------------------------------------
             */
            $returnDetailRows = collect();

            $totalReturnAmount = 0;

            $returnedProductIds = collect();


            foreach ($requestedReturnItems as $requested) {

                /** @var OrderDetail $detail */
                $detail = $orderDetails->get(
                    $requested['order_detail_id']
                );


                $product = $detail->product;


                if (! $product) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Sản phẩm của dòng #{$detail->id} không còn tồn tại.",
                    ]);
                }


                $originalQuantity =
                    (int) $detail->quantity;


                $requestedQuantity =
                    (int) $requested['quantity'];


                $alreadyReturned =
                    (int) (
                        $returnedByDetail->get(
                            $detail->id
                        )
                        ?? 0
                    );


                $returnableQuantity =
                    $originalQuantity
                    - $alreadyReturned;


                if ($returnableQuantity <= 0) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Sản phẩm {$product->name} đã được trả hết.",
                    ]);
                }


                if (
                    $requestedQuantity
                    > $returnableQuantity
                ) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Sản phẩm {$product->name} chỉ còn có thể trả {$returnableQuantity} sản phẩm.",
                    ]);
                }


                if ($requestedQuantity <= 0) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => 'Số lượng trả phải lớn hơn 0.',
                    ]);
                }


                /*
                 |--------------------------------------------------------------------------
                 | Kho hoàn hàng
                 |--------------------------------------------------------------------------
                 |
                 | Luôn sử dụng storage_id của order_detail gốc.
                 |
                 */
                $returnStorageId =
                    (int) (
                        $detail->storage_id
                        ?? 0
                    );


                if ($returnStorageId <= 0) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Không xác định được kho bán ban đầu của sản phẩm {$product->name}.",
                    ]);
                }

                $returnStorageQuery = Storage::query()
                    ->whereKey($returnStorageId)
                    ->where('branch_id', $originalOrder->branch_id);
                $this->branchContext->scopeStorages($returnStorageQuery, $user);
                $returnStorage = $returnStorageQuery
                    ->lockForUpdate()
                    ->first();

                if (! $returnStorage) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Kho hoàn hàng của sản phẩm {$product->name} không thuộc chi nhánh của đơn gốc.",
                    ]);
                }


                /*
                 |--------------------------------------------------------------------------
                 | IMEI
                 |--------------------------------------------------------------------------
                 */
                if ($product->isImeiTracked()) {

                    if (
                        $originalQuantity !== 1
                        || $requestedQuantity !== 1
                        || ! $detail->product_imei_id
                    ) {
                        throw ValidationException::withMessages([
                            'return_items'
                                => "Dữ liệu IMEI của sản phẩm {$product->name} không hợp lệ.",
                        ]);
                    }
                }


                /*
                 |--------------------------------------------------------------------------
                 | Giá trị trả
                 |--------------------------------------------------------------------------
                 */
                $unitPrice =
                    (int) $detail->price;


                $lineGross =
                    $unitPrice
                    * $originalQuantity;


                $lineDiscount =
                    (int) (
                        $discountByDetail[
                            $detail->id
                        ]
                        ?? 0
                    );


                $lineNet =
                    max(
                        0,
                        $lineGross
                        - $lineDiscount
                    );


                /*
                 * Tính lũy kế để lần trả cuối
                 * hấp thụ phần làm tròn VND.
                 */
                $amountBefore =
                    $this->cumulativeAmount(
                        $lineNet,
                        $alreadyReturned,
                        $originalQuantity
                    );


                $amountAfter =
                    $this->cumulativeAmount(
                        $lineNet,
                        $alreadyReturned
                            + $requestedQuantity,
                        $originalQuantity
                    );


                $returnAmount =
                    $amountAfter
                    - $amountBefore;


                $grossAmount =
                    $unitPrice
                    * $requestedQuantity;


                $discountAmount =
                    $grossAmount
                    - $returnAmount;


                $returnDetailRows->push([
                    'order_detail_id'
                        => (int) $detail->id,

                    'product_id'
                        => (int) $detail->product_id,

                    'product_imei_id'
                        => $detail->product_imei_id
                            ? (int) $detail->product_imei_id
                            : null,

                    'storage_id'
                        => $returnStorageId,

                    'quantity'
                        => $requestedQuantity,

                    'original_unit_price'
                        => $unitPrice,

                    'gross_amount'
                        => $grossAmount,

                    'discount_amount'
                        => $discountAmount,

                    'return_amount'
                        => $returnAmount,

                    /*
                     * Không ghi DB.
                     * Chỉ dùng trong transaction.
                     */
                    '_product'
                        => $product,
                ]);


                $totalReturnAmount +=
                    $returnAmount;


                $returnedProductIds->push(
                    (int) $product->id
                );
            }


            /*
             |--------------------------------------------------------------------------
             | 8. HOÀN HÀNG CŨ VỀ KHO
             |--------------------------------------------------------------------------
             |
             | Làm trước khi tạo đơn đổi.
             |
             | Nếu phía sau tạo đơn đổi thất bại,
             | transaction rollback toàn bộ.
             |
             */
            foreach ($returnDetailRows as $row) {

                /** @var Product $product */
                $product =
                    $row['_product'];


                /*
                 |--------------------------------------------------------------------------
                 | Lock ProductStorage đúng kho gốc
                 |--------------------------------------------------------------------------
                 */
                $stock = ProductStorage::query()
                    ->where(
                        'product_id',
                        $row['product_id']
                    )
                    ->where(
                        'storage_id',
                        $row['storage_id']
                    )
                    ->lockForUpdate()
                    ->first();


                if (! $stock) {
                    throw ValidationException::withMessages([
                        'return_items'
                            => "Không tìm thấy tồn kho của sản phẩm {$product->name} tại kho trả hàng.",
                    ]);
                }


                $stock->increment(
                    'quantity',
                    (int) $row['quantity']
                );


                /*
                 |--------------------------------------------------------------------------
                 | IMEI: sold → in_stock
                 |--------------------------------------------------------------------------
                 */
                if ($product->isImeiTracked()) {

                    $imei = ProductImei::query()
                        ->whereKey(
                            $row['product_imei_id']
                        )
                        ->where('product_id', $row['product_id'])
                        ->where('storage_id', $row['storage_id'])
                        ->lockForUpdate()
                        ->first();


                    if (! $imei) {
                        throw ValidationException::withMessages([
                            'return_items'
                                => "Không tìm thấy IMEI của sản phẩm {$product->name}.",
                        ]);
                    }


                    if (
                        $imei->status
                        !== ProductImei::STATUS_SOLD
                    ) {
                        throw ValidationException::withMessages([
                            'return_items'
                                => "IMEI {$imei->imei} không còn ở trạng thái đã bán.",
                        ]);
                    }


                    $imei->forceFill([
                        'status'
                            => ProductImei::STATUS_IN_STOCK,

                        /*
                         * IMEI trở về đúng kho
                         * của order_detail gốc.
                         */
                        'storage_id'
                            => (int) $row['storage_id'],
                    ])->save();
                }
            }


            /*
             |--------------------------------------------------------------------------
             | 9. XỬ LÝ HÀNG KHÁCH LẤY MỚI
             |--------------------------------------------------------------------------
             |
             | Không có new_items:
             |     exchangeOrder = null
             |     exchangeAmount = 0
             |
             | Có new_items:
             |     tạo một orders mới thật sự.
             |
             */
            $newItems = collect(
                $data['new_items']
                ?? []
            )
                ->filter(
                    fn ($item) =>
                        is_array($item)
                )
                ->values();


            $exchangeOrder = null;
            $exchangeAmount = 0;


            if ($newItems->isNotEmpty()) {

                /*
                 |--------------------------------------------------------------------------
                 | Kho xuất hàng mới
                 |--------------------------------------------------------------------------
                 |
                 | Không lấy storage_id từ frontend.
                 |
                 | Dùng đúng SaleStorageResolver
                 | của hệ thống bán hàng.
                 |
                 */
                $exchangeStorageId =
                    $this
                        ->saleStorageResolver
                        ->resolveSaleStorageId(
                            $user,
                            null
                        );

                $exchangeStorageQuery = Storage::query()
                    ->whereKey($exchangeStorageId)
                    ->where('branch_id', $originalOrder->branch_id);
                $this->branchContext->scopeStorages($exchangeStorageQuery, $user);

                if (! $exchangeStorageQuery->exists()) {
                    throw ValidationException::withMessages([
                        'new_items' => 'Kho xuất hàng đổi không thuộc chi nhánh của đơn gốc.',
                    ]);
                }


                /*
                 |--------------------------------------------------------------------------
                 | Chuẩn hóa payload cho SaleService
                 |--------------------------------------------------------------------------
                 */
                $exchangeItems =
                    $newItems
                        ->map(function ($item) {

                            return [
                                'tracking_type'
                                    => $item[
                                        'tracking_type'
                                    ],

                                'product_id'
                                    => (int) $item[
                                        'product_id'
                                    ],

                                'product_imei_id'
                                    => ! empty(
                                        $item[
                                            'product_imei_id'
                                        ]
                                    )
                                        ? (int) $item[
                                            'product_imei_id'
                                        ]
                                        : null,

                                'quantity'
                                    => (int) $item[
                                        'quantity'
                                    ],

                                'unit_price'
                                    => (int) $item[
                                        'unit_price'
                                    ],
                            ];
                        })
                        ->values();


                /*
                 * Giá trị hàng mới chưa có discount.
                 *
                 * Nếu sau này nghiệp vụ muốn cho giảm giá
                 * riêng hàng đổi, ta mở rộng ở V3.
                 */
                $exchangeSubtotal =
                    (int) $exchangeItems
                        ->sum(
                            fn ($item) =>
                                (int) $item[
                                    'unit_price'
                                ]
                                *
                                (int) $item[
                                    'quantity'
                                ]
                        );


                if ($exchangeSubtotal <= 0) {
                    throw ValidationException::withMessages([
                        'new_items'
                            => 'Giá trị hàng đổi không hợp lệ.',
                    ]);
                }


                /*
                 |--------------------------------------------------------------------------
                 | Tạo order mới bằng SaleService
                 |--------------------------------------------------------------------------
                 |
                 | Accounting = false.
                 |
                 | Vì số tiền thực sự cần thanh toán là:
                 |
                 | exchange - return + fee
                 |
                 | chứ KHÔNG phải toàn bộ exchangeSubtotal.
                 |
                 */
                $exchangeOrderData = [

                    'items'
                        => $exchangeItems->all(),

                    'subtotal'
                        => $exchangeSubtotal,

                    'discountType'
                        => 'amount',

                    'discountInput'
                        => 0,

                    'grand'
                        => $exchangeSubtotal,


                    'customer' => [

                        'id'
                            => $originalOrder
                                ->client_id,

                        'name'
                            => $originalOrder
                                ->name,

                        'email'
                            => $originalOrder
                                ->email,

                        'phone'
                            => $originalOrder
                                ->phone,

                        'address'
                            => $originalOrder
                                ->receive_address
                                ?? null,

                        /*
                         * Đây là đơn sinh từ nghiệp vụ đổi.
                         *
                         * Không phải một lần thanh toán độc lập.
                         */
                        'payment'
                            => 'exchange',

                        'note'
                            => $this
                                ->makeExchangeOrderNote(
                                    $originalOrder,
                                    $data['note']
                                        ?? null
                                ),
                    ],
                ];


                $exchangeOrder =
                    $this
                        ->saleService
                        ->createPosOrder(
                            $user,
                            $exchangeOrderData,
                            $exchangeStorageId,

                            /*
                             * Không tạo accounting
                             * cho toàn giá trị đơn mới.
                             */
                            false
                        );


                /*
                 |--------------------------------------------------------------------------
                 | Chuẩn hóa trạng thái thanh toán order đổi
                 |--------------------------------------------------------------------------
                 |
                 | SaleService mặc định coi payment != debt
                 | là paid_amount = grand.
                 |
                 | Với exchange điều đó không đúng.
                 |
                 | Chênh lệch thực tế nằm trong order_returns.
                 |
                 */
                $exchangeOrder->forceFill([
                    'payment_method'
                        => 'exchange',

                    'paid_amount'
                        => 0,

                    'debt_amount'
                        => 0,

                    'payment_status'
                        => 'exchange',
                ])->save();


                $exchangeAmount =
                    (int) $exchangeOrder
                        ->total_money;
            }


            /*
             |--------------------------------------------------------------------------
             | 10. PHÍ + QUYẾT TOÁN CHÊNH LỆCH
             |--------------------------------------------------------------------------
             |
             | settlement
             | =
             | exchange_amount
             | - return_amount
             | + fee_amount
             |
             */
            $feeAmount =
                (int) (
                    $data['fee_amount']
                    ?? 0
                );


            $settlement =
                $exchangeAmount
                - $totalReturnAmount
                + $feeAmount;


            $refundAmount =
                $settlement < 0
                    ? abs($settlement)
                    : 0;


            $additionalPayment =
                $settlement > 0
                    ? $settlement
                    : 0;


            /*
             |--------------------------------------------------------------------------
             | 11. TẠO PHIẾU RETURN HEADER
             |--------------------------------------------------------------------------
             */
            $orderReturn =
                OrderReturn::create([

                    'code'
                        => generateCode(
                            'order_returns',
                            'RTN'
                        ),

                    'original_order_id'
                        => (int) $originalOrder
                            ->id,

                    'exchange_order_id'
                        => $exchangeOrder
                            ? (int) $exchangeOrder
                                ->id
                            : null,

                    'user_id'
                        => $originalOrder
                            ->user_id,

                    'branch_id'
                        => $originalOrder
                            ->branch_id,

                    'client_id'
                        => $originalOrder
                            ->client_id,

                    'created_by'
                        => $user->id,

                    'return_amount'
                        => $totalReturnAmount,

                    'exchange_amount'
                        => $exchangeAmount,

                    'fee_amount'
                        => $feeAmount,

                    'refund_amount'
                        => $refundAmount,

                    'additional_payment'
                        => $additionalPayment,

                    'status'
                        => 'completed',

                    'note'
                        => $data['note']
                            ?? null,
                ]);


            /*
             |--------------------------------------------------------------------------
             | 12. TẠO RETURN DETAILS
             |--------------------------------------------------------------------------
             */
            foreach ($returnDetailRows as $row) {

                $orderReturn
                    ->details()
                    ->create([

                        'order_detail_id'
                            => $row[
                                'order_detail_id'
                            ],

                        'product_id'
                            => $row[
                                'product_id'
                            ],

                        'product_imei_id'
                            => $row[
                                'product_imei_id'
                            ],

                        'storage_id'
                            => $row[
                                'storage_id'
                            ],

                        'quantity'
                            => $row[
                                'quantity'
                            ],

                        'original_unit_price'
                            => $row[
                                'original_unit_price'
                            ],

                        'gross_amount'
                            => $row[
                                'gross_amount'
                            ],

                        'discount_amount'
                            => $row[
                                'discount_amount'
                            ],

                        'return_amount'
                            => $row[
                                'return_amount'
                            ],
                    ]);
            }


            /*
             |--------------------------------------------------------------------------
             | 13. SYNC TỔNG TỒN products.quantity
             |--------------------------------------------------------------------------
             |
             | SaleService tự sync sản phẩm mới.
             |
             | Ta sync lại toàn bộ sản phẩm vừa trả.
             |
             */
            foreach (
                $returnedProductIds
                    ->unique()
                    ->values()
                as $productId
            ) {
                $this->syncProductTotalQuantity(
                    (int) $productId
                );
            }


            /*
             |--------------------------------------------------------------------------
             | 14. RETURN RESULT
             |--------------------------------------------------------------------------
             */
            return $orderReturn;

        }, 3);
    }


    /*
     |--------------------------------------------------------------------------
     | PHÂN BỔ DISCOUNT ĐƠN GỐC
     |--------------------------------------------------------------------------
     |
     | Largest remainder.
     |
     | Tổng discount phân bổ cuối cùng luôn bằng
     | discount_value của order.
     |
     */
    private function allocateOriginalOrderDiscount(
        Collection $orderDetails,
        int $orderDiscount
    ): array {
        $allocation = [];


        foreach ($orderDetails as $detail) {
            $allocation[
                (int) $detail->id
            ] = 0;
        }


        if (
            $orderDetails->isEmpty()
            || $orderDiscount <= 0
        ) {
            return $allocation;
        }


        $subtotal = $orderDetails
            ->sum(function ($detail) {
                return (int) $detail->price
                    * (int) $detail->quantity;
            });


        if ($subtotal <= 0) {
            return $allocation;
        }


        $orderDiscount = min(
            $orderDiscount,
            (int) $subtotal
        );


        $allocated = 0;
        $remainders = [];


        foreach ($orderDetails as $detail) {

            $detailId =
                (int) $detail->id;


            $gross =
                (int) $detail->price
                * (int) $detail->quantity;


            /*
             * Không dùng float.
             */
            $numerator =
                $orderDiscount
                * $gross;


            $base =
                intdiv(
                    $numerator,
                    (int) $subtotal
                );


            $remainder =
                $numerator
                % (int) $subtotal;


            $allocation[
                $detailId
            ] = $base;


            $allocated +=
                $base;


            $remainders[] = [
                'id'
                    => $detailId,

                'remainder'
                    => $remainder,
            ];
        }


        /*
         * Remainder lớn nhận 1 VND trước.
         *
         * Nếu remainder bằng nhau:
         * order_detail_id nhỏ hơn nhận trước
         * để kết quả deterministic.
         */
        usort(
            $remainders,
            function ($a, $b) {

                if (
                    $a['remainder']
                    === $b['remainder']
                ) {
                    return $a['id']
                        <=> $b['id'];
                }

                return $b['remainder']
                    <=> $a['remainder'];
            }
        );


        $remaining =
            $orderDiscount
            - $allocated;


        for (
            $i = 0;
            $i < $remaining
                && $i < count($remainders);
            $i++
        ) {

            $detailId =
                $remainders[$i]['id'];


            $allocation[
                $detailId
            ]++;
        }


        return $allocation;
    }


    /*
     |--------------------------------------------------------------------------
     | TÍNH GIÁ TRỊ LŨY KẾ
     |--------------------------------------------------------------------------
     */
    private function cumulativeAmount(
        int $lineNetAmount,
        int $quantity,
        int $originalQuantity
    ): int {
        if (
            $quantity <= 0
            || $originalQuantity <= 0
        ) {
            return 0;
        }


        if (
            $quantity >=
            $originalQuantity
        ) {
            return $lineNetAmount;
        }


        return intdiv(
            $lineNetAmount
                * $quantity,
            $originalQuantity
        );
    }


    /*
     |--------------------------------------------------------------------------
     | GHI CHÚ ĐƠN ĐỔI
     |--------------------------------------------------------------------------
     */
    private function makeExchangeOrderNote(
        Order $originalOrder,
        ?string $returnNote
    ): string {
        $note =
            "Đơn hàng đổi từ {$originalOrder->code}";


        $returnNote =
            trim(
                (string) $returnNote
            );


        if ($returnNote !== '') {
            $note .=
                " - {$returnNote}";
        }


        return $note;
    }


    /*
     |--------------------------------------------------------------------------
     | SYNC products.quantity
     |--------------------------------------------------------------------------
     */
    private function syncProductTotalQuantity(
        int $productId
    ): void {
        DB::statement(
            '
                UPDATE products

                SET quantity = (
                    SELECT COALESCE(
                        SUM(quantity),
                        0
                    )

                    FROM product_storage

                    WHERE product_id = ?
                ),

                updated_at = ?

                WHERE id = ?
            ',
            [
                $productId,
                now()->toDateTimeString(),
                $productId,
            ]
        );
    }
}
