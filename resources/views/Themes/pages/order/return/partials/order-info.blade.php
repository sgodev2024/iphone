{{-- Thông tin đơn hàng + quyết toán realtime --}}
<div class="card mb-2 return-side-card return-order-summary-card">

    <div class="card-header">
        <h5 class="card-title mb-0">
            Thông tin đơn hàng
        </h5>
    </div>

    <div class="card-body">

        {{-- ================================================= --}}
        {{-- GIÁ TRỊ ĐƠN GỐC --}}
        {{-- ================================================= --}}

        <!-- <div class="info-row">
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
        </div> -->


        {{-- ================================================= --}}
        {{-- QUYẾT TOÁN ĐỔI / TRẢ REALTIME --}}
        {{-- ================================================= --}}

        <div class="return-settlement">

            <div
                id="returnSettlementTitle"
                class="return-settlement-title">
                Quyết toán trả hàng
            </div>


            <div class="info-row">
                <span class="info-label">
                    Giá trị hàng trả
                </span>

                <span
                    id="returnAmountPreview"
                    class="info-value fw-semibold">
                    0 VND
                </span>
            </div>


            {{-- Chỉ hiện khi có hàng khách lấy mới --}}
            <div
                id="exchangeAmountPreviewRow"
                class="info-row d-none">

                <span class="info-label">
                    Giá trị hàng mới
                </span>

                <span
                    id="exchangeAmountPreview"
                    class="info-value fw-semibold">
                    0 VND
                </span>
            </div>


            <div class="info-row">
                <span class="info-label">
                    Phí trả hàng
                </span>

                <span
                    id="returnFeePreview"
                    class="info-value">
                    0 VND
                </span>
            </div>


            {{-- Hoàn khách --}}
            <div
                id="refundPreviewRow"
                class="info-row return-settlement-final">

                <span class="info-label fw-semibold">
                    Hoàn khách
                </span>

                <span
                    id="refundPreview"
                    class="info-value text-success fw-bold">
                    0 VND
                </span>
            </div>


            {{-- Khách trả thêm --}}
            <div
                id="additionalPaymentPreviewRow"
                class="info-row return-settlement-final d-none">

                <span class="info-label fw-semibold">
                    Khách trả thêm
                </span>

                <span
                    id="additionalPaymentPreview"
                    class="info-value text-danger fw-bold">
                    0 VND
                </span>
            </div>

        </div>

    </div>
</div>