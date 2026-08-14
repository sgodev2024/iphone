                {{-- Thông tin hóa đơn --}}
                <div class="card mb-4 return-side-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Thông tin đơn hàng
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="info-row">
                            <span class="info-label">
                                Mã đơn
                            </span>

                            <span class="info-value">
                                {{ $order->code }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                Ngày bán
                            </span>

                            <span class="info-value">
                                {{ optional($order->created_at)->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <hr class="my-2">

                        <div class="info-row">
                            <span class="info-label">
                                Khách hàng
                            </span>

                            <span class="info-value">
                                {{ $order->customer_display_name }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                SĐT
                            </span>

                            <span class="info-value">
                                {{ $order->customer_display_phone ?? '-' }}
                            </span>
                        </div>

                        <hr class="my-2">

                        <div class="info-row">
                            <span class="info-label">
                                Tạm tính
                            </span>

                            <span class="info-value">
                                {{ number_format(
                                        $summary['subtotal'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                VND
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                Giảm giá
                            </span>

                            <span class="info-value">
                                {{ number_format(
                                        $summary['discount_value'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                VND
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label fw-semibold">
                                Thành tiền
                            </span>

                            <span class="info-value fw-bold">
                                {{ number_format(
                                        $summary['total_money'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                VND
                            </span>
                        </div>

                        @if ($summary['returned_amount'] > 0)
                        <hr class="my-2">

                        <div class="info-row">
                            <span class="info-label">
                                Đã trả trước
                            </span>

                            <span class="info-value text-warning">
                                {{ number_format(
                                            $summary['returned_amount'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                VND
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
