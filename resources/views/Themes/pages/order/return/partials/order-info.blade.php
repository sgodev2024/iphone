{{-- Thông tin hóa đơn --}}
<div class="card mb-3 return-side-card">

    <div class="card-header py-2 px-3">
        <h6 class="card-title mb-0">
            Thông tin đơn hàng
        </h6>
    </div>

    <div class="card-body p-3">

        <div class="info-row">
            <span class="info-label small">
                Mã đơn
            </span>

            <span class="info-value small">
                {{ $order->code }}
            </span>
        </div>

        <div class="info-row">
            <span class="info-label small">
                Ngày bán
            </span>

            <span class="info-value small">
                {{ optional($order->created_at)->format('d/m/Y H:i') }}
            </span>
        </div>

        <hr class="my-2">

        <div class="info-row">
            <span class="info-label small">
                Khách hàng
            </span>

            <span class="info-value small">
                {{ $order->customer_display_name }}
            </span>
        </div>

        <div class="info-row">
            <span class="info-label small">
                SĐT
            </span>

            <span class="info-value small">
                {{ $order->customer_display_phone ?? '-' }}
            </span>
        </div>

        <hr class="my-2">

        <div class="info-row">
            <span class="info-label small">
                Tạm tính
            </span>

            <span class="info-value small">
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
            <span class="info-label small">
                Giảm giá
            </span>

            <span class="info-value small">
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
            <span class="info-label small fw-semibold">
                Thành tiền
            </span>

            <span class="info-value small fw-bold">
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
                <span class="info-label small">
                    Đã trả trước
                </span>

                <span class="info-value small text-warning">
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