@if ($sourceOrderInfo)

<div
    class="modal fade source-order-modal"
    id="sourceOrderModal"
    tabindex="-1"
    aria-hidden="true">

    <div
        class="
            modal-dialog
            modal-lg
            modal-dialog-centered
            modal-dialog-scrollable
        ">

        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">

                <div>

                    <h6 class="modal-title fw-bold mb-0">

                        Đơn gốc
                        {{ $sourceOrderInfo['order_code'] }}

                    </h6>

                    <div class="small text-muted mt-1">

                        Phiếu đổi / trả:

                        <strong>
                            {{ $sourceOrderInfo['return_code'] }}
                        </strong>

                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Đóng">
                </button>

            </div>


            {{-- BODY --}}
            <div class="modal-body">

                {{-- ================================ --}}
                {{-- THÔNG TIN CƠ BẢN --}}
                {{-- ================================ --}}

                <div class="source-order-info-grid">

                    <div>
                        <span class="source-info-label">
                            Ngày bán
                        </span>

                        <strong>
                            {{ $sourceOrderInfo['created_at'] }}
                        </strong>
                    </div>


                    <div>
                        <span class="source-info-label">
                            Khách hàng
                        </span>

                        <strong>
                            {{ $sourceOrderInfo['customer_name'] }}
                        </strong>
                    </div>


                    <div>
                        <span class="source-info-label">
                            SĐT
                        </span>

                        <strong>
                            {{ $sourceOrderInfo['customer_phone'] }}
                        </strong>
                    </div>

                </div>


                {{-- ================================ --}}
                {{-- GIÁ TRỊ ĐƠN GỐC --}}
                {{-- ================================ --}}

                <div class="source-order-section">

                    <div class="source-order-section-title">
                        Giá trị đơn gốc
                    </div>


                    <div class="source-order-money-row">
                        <span>Tạm tính</span>

                        <span>
                            {{ number_format(
                                $sourceOrderInfo['subtotal'],
                                0,
                                ',',
                                '.'
                            ) }}
                            VND
                        </span>
                    </div>


                    <div class="source-order-money-row">
                        <span>Giảm giá</span>

                        <span>
                            {{ number_format(
                                $sourceOrderInfo['discount_value'],
                                0,
                                ',',
                                '.'
                            ) }}
                            VND
                        </span>
                    </div>


                    <div
                        class="
                            source-order-money-row
                            fw-bold
                        ">

                        <span>Thành tiền</span>

                        <span>
                            {{ number_format(
                                $sourceOrderInfo['total_money'],
                                0,
                                ',',
                                '.'
                            ) }}
                            VND
                        </span>

                    </div>

                </div>


                {{-- ================================ --}}
                {{-- GIAO DỊCH ĐỔI / TRẢ --}}
                {{-- ================================ --}}

                <div class="source-order-section">

                    <div class="source-order-section-title">
                        Giao dịch
                        {{ $sourceOrderInfo['return_code'] }}
                    </div>


                    <div class="source-order-money-row">

                        <span>Giá trị hàng trả</span>

                        <span>
                            {{ number_format(
                                $sourceOrderInfo['return_amount'],
                                0,
                                ',',
                                '.'
                            ) }}
                            VND
                        </span>

                    </div>


                    <div class="source-order-money-row">

                        <span>Giá trị hàng lấy mới</span>

                        <span>
                            {{ number_format(
                                $sourceOrderInfo['exchange_amount'],
                                0,
                                ',',
                                '.'
                            ) }}
                            VND
                        </span>

                    </div>


                    <div class="source-order-money-row">

                        <span>Phí</span>

                        <span>
                            {{ number_format(
                                $sourceOrderInfo['fee_amount'],
                                0,
                                ',',
                                '.'
                            ) }}
                            VND
                        </span>

                    </div>


                    @if (
                        $sourceOrderInfo['additional_payment']
                        > 0
                    )

                        <div
                            class="
                                source-order-money-row
                                fw-bold
                                text-danger
                            ">

                            <span>Khách đã trả thêm</span>

                            <span>
                                {{ number_format(
                                    $sourceOrderInfo['additional_payment'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                                VND
                            </span>

                        </div>

                    @elseif (
                        $sourceOrderInfo['refund_amount']
                        > 0
                    )

                        <div
                            class="
                                source-order-money-row
                                fw-bold
                                text-success
                            ">

                            <span>Đã hoàn khách</span>

                            <span>
                                {{ number_format(
                                    $sourceOrderInfo['refund_amount'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                                VND
                            </span>

                        </div>

                    @endif

                </div>


                {{-- ================================ --}}
                {{-- SẢN PHẨM ĐƠN GỐC --}}
                {{-- ================================ --}}

                <div class="source-order-section">

                    <div class="source-order-section-title">
                        Sản phẩm đơn gốc
                    </div>


                    <div class="table-responsive">

                        <table
                            class="
                                table
                                table-sm
                                align-middle
                                mb-0
                                source-order-table
                            ">

                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>

                                    <th class="text-center">
                                        SL
                                    </th>

                                    <th class="text-end">
                                        Đơn giá
                                    </th>

                                    <th class="text-end">
                                        Thành tiền
                                    </th>
                                </tr>
                            </thead>


                            <tbody>

                                @foreach (
                                    $sourceOrderInfo['items']
                                    as $item
                                )

                                    <tr>

                                        <td>

                                            <div class="fw-semibold">
                                                {{ $item['product_name'] }}
                                            </div>


                                            @if (
                                                !empty(
                                                    $item['product_code']
                                                )
                                            )

                                                <div class="small text-muted">
                                                    {{ $item['product_code'] }}
                                                </div>

                                            @endif


                                            @if (
                                                !empty(
                                                    $item['imei']
                                                )
                                            )

                                                <div class="small text-muted">
                                                    IMEI:
                                                    {{ $item['imei'] }}
                                                </div>

                                            @endif

                                        </td>


                                        <td class="text-center">

                                            {{ $item['quantity'] }}

                                        </td>


                                        <td class="text-end">

                                            {{ number_format(
                                                $item['unit_price'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        <td class="text-end fw-semibold">

                                            {{ number_format(
                                                $item['line_total'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            {{-- FOOTER --}}
            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-bs-dismiss="modal">

                    Đóng

                </button>

            </div>

        </div>

    </div>

</div>

@endif