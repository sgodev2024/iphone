<?php

namespace App\Http\Requests\Staff;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /*
         |--------------------------------------------------------------------------
         | ORIGINAL ORDER
         |--------------------------------------------------------------------------
         |
         | original_order_id luôn lấy từ route:
         |
         | /orders/{order}/returns
         |
         | Không tin original_order_id do frontend gửi.
         |
         */
        $routeOrder = $this->route('order');

        if ($routeOrder) {
            $this->merge([
                'original_order_id' => is_object($routeOrder)
                    ? (int) $routeOrder->getKey()
                    : (int) $routeOrder,
            ]);
        }


        /*
         |--------------------------------------------------------------------------
         | FEE
         |--------------------------------------------------------------------------
         |
         | Cho phép frontend gửi:
         |
         | 20000
         | "20000"
         | "20.000"
         |
         */
        $feeAmount = $this->input('fee_amount', 0);

        if (is_string($feeAmount)) {
            $feeAmount = preg_replace('/[^\d]/', '', $feeAmount);
        }

        $feeAmount = $feeAmount === ''
            ? 0
            : (int) $feeAmount;


        /*
         |--------------------------------------------------------------------------
         | NORMALIZE NEW ITEMS
         |--------------------------------------------------------------------------
         |
         | new_items là hàng khách lấy mới khi đổi hàng.
         |
         | Không nhận storage_id từ client.
         | Kho sẽ được backend xác định.
         |
         */
        $newItems = $this->input('new_items');

        if (is_array($newItems)) {
            $newItems = collect($newItems)
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return $item;
                    }

                    $unitPrice = $item['unit_price'] ?? null;

                    if (is_string($unitPrice)) {
                        $unitPrice = preg_replace(
                            '/[^\d]/',
                            '',
                            $unitPrice
                        );
                    }

                    return [
                        'tracking_type'
                            => $item['tracking_type'] ?? null,

                        'product_id'
                            => isset($item['product_id'])
                                ? (int) $item['product_id']
                                : null,

                        'product_imei_id'
                            => ! empty($item['product_imei_id'])
                                ? (int) $item['product_imei_id']
                                : null,

                        'quantity'
                            => isset($item['quantity'])
                                ? (int) $item['quantity']
                                : null,

                        'unit_price'
                            => $unitPrice !== null
                                && $unitPrice !== ''
                                    ? (int) $unitPrice
                                    : null,
                    ];
                })
                ->values()
                ->all();
        }


        $this->merge([
            'fee_amount' => $feeAmount,
            'new_items' => $newItems,
        ]);
    }


    public function rules(): array
    {
        return [

            /*
             |--------------------------------------------------------------------------
             | ĐƠN GỐC
             |--------------------------------------------------------------------------
             */
            'original_order_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],


            /*
             |--------------------------------------------------------------------------
             | HÀNG KHÁCH TRẢ
             |--------------------------------------------------------------------------
             |
             | Dù là:
             |
             | - trả hàng thuần túy
             | - hay đổi hàng
             |
             | endpoint này vẫn phải có ít nhất một hàng được trả.
             |
             */
            'return_items' => [
                'required',
                'array',
                'min:1',
            ],

            'return_items.*.order_detail_id' => [
                'required',
                'integer',
                'distinct',
                'exists:order_details,id',
            ],

            'return_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],


            /*
             |--------------------------------------------------------------------------
             | HÀNG KHÁCH LẤY MỚI
             |--------------------------------------------------------------------------
             |
             | Không có new_items:
             |     → return V1
             |
             | Có new_items:
             |     → exchange V2
             |
             */
            'new_items' => [
                'nullable',
                'array',
            ],

            'new_items.*.tracking_type' => [
                'required',
                'string',

                Rule::in([
                    Product::INVENTORY_TRACKING_QUANTITY,
                    Product::INVENTORY_TRACKING_IMEI,
                ]),
            ],

            'new_items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            /*
             * Với quantity:
             * product_imei_id = null.
             *
             * Với IMEI:
             * kiểm tra bắt buộc ở withValidator().
             */
            'new_items.*.product_imei_id' => [
                'nullable',
                'integer',
                'distinct',
                'exists:product_imeis,id',
            ],

            'new_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            /*
             * Giá của hàng mới.
             *
             * Sau này SaleService vẫn kiểm tra lại dữ liệu
             * trước khi tạo order.
             */
            'new_items.*.unit_price' => [
                'required',
                'integer',
                'min:1',
            ],


            /*
             |--------------------------------------------------------------------------
             | PHÍ + GHI CHÚ
             |--------------------------------------------------------------------------
             */
            'fee_amount' => [
                'required',
                'integer',
                'min:0',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }


    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $newItems = $this->input(
                'new_items',
                []
            );


            if (! is_array($newItems)) {
                return;
            }


            foreach ($newItems as $index => $item) {

                if (! is_array($item)) {
                    continue;
                }


                $trackingType =
                    $item['tracking_type']
                    ?? null;


                $productImeiId =
                    $item['product_imei_id']
                    ?? null;


                $quantity =
                    (int) (
                        $item['quantity']
                        ?? 0
                    );


                /*
                 |--------------------------------------------------------------------------
                 | IMEI
                 |--------------------------------------------------------------------------
                 |
                 | Một IMEI luôn là một thiết bị:
                 |
                 | quantity = 1
                 | product_imei_id bắt buộc có
                 |
                 */
                if (
                    $trackingType
                    === Product::INVENTORY_TRACKING_IMEI
                ) {

                    if (! $productImeiId) {
                        $validator
                            ->errors()
                            ->add(
                                "new_items.{$index}.product_imei_id",
                                'Sản phẩm quản lý IMEI phải có thiết bị IMEI cụ thể.'
                            );
                    }


                    if ($quantity !== 1) {
                        $validator
                            ->errors()
                            ->add(
                                "new_items.{$index}.quantity",
                                'Sản phẩm quản lý IMEI chỉ được có số lượng bằng 1.'
                            );
                    }
                }


                /*
                 |--------------------------------------------------------------------------
                 | QUANTITY PRODUCT
                 |--------------------------------------------------------------------------
                 |
                 | Sản phẩm thường không được gửi product_imei_id.
                 |
                 */
                if (
                    $trackingType
                    === Product::INVENTORY_TRACKING_QUANTITY
                    && $productImeiId
                ) {
                    $validator
                        ->errors()
                        ->add(
                            "new_items.{$index}.product_imei_id",
                            'Sản phẩm thường không được gắn product_imei_id.'
                        );
                }
            }
        });
    }


    public function messages(): array
    {
        return [
            'return_items.required'
                => 'Vui lòng chọn ít nhất một sản phẩm cần trả.',

            'return_items.min'
                => 'Vui lòng chọn ít nhất một sản phẩm cần trả.',

            'return_items.*.order_detail_id.required'
                => 'Dữ liệu sản phẩm trả không hợp lệ.',

            'return_items.*.order_detail_id.distinct'
                => 'Một dòng hàng không được xuất hiện nhiều lần trong cùng phiếu trả.',

            'return_items.*.quantity.required'
                => 'Vui lòng nhập số lượng trả.',

            'return_items.*.quantity.min'
                => 'Số lượng trả phải lớn hơn 0.',


            'new_items.array'
                => 'Danh sách hàng đổi không hợp lệ.',

            'new_items.*.tracking_type.required'
                => 'Thiếu loại quản lý tồn kho của sản phẩm đổi.',

            'new_items.*.tracking_type.in'
                => 'Loại quản lý tồn kho của sản phẩm đổi không hợp lệ.',

            'new_items.*.product_id.required'
                => 'Thiếu sản phẩm đổi.',

            'new_items.*.product_id.exists'
                => 'Sản phẩm đổi không tồn tại.',

            'new_items.*.product_imei_id.distinct'
                => 'Một thiết bị IMEI không thể xuất hiện hai lần trong hàng đổi.',

            'new_items.*.product_imei_id.exists'
                => 'Thiết bị IMEI được chọn không tồn tại.',

            'new_items.*.quantity.required'
                => 'Thiếu số lượng sản phẩm đổi.',

            'new_items.*.quantity.min'
                => 'Số lượng sản phẩm đổi phải lớn hơn 0.',

            'new_items.*.unit_price.required'
                => 'Thiếu giá bán của sản phẩm đổi.',

            'new_items.*.unit_price.min'
                => 'Giá bán của sản phẩm đổi phải lớn hơn 0.',


            'fee_amount.integer'
                => 'Phí trả hàng không hợp lệ.',

            'fee_amount.min'
                => 'Phí trả hàng không được nhỏ hơn 0.',

            'note.max'
                => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }


    protected function failedValidation(
        Validator $validator
    ): void {
        throw new HttpResponseException(
            response()->json([
                'message' =>
                    $validator
                        ->errors()
                        ->first(),

                'errors' =>
                    $validator
                        ->errors(),
            ], 422)
        );
    }
}