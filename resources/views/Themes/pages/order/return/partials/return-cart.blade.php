{{-- Giỏ hàng trả --}}
<div class="card mb-3">

    <div class="card-header py-2 px-3">

        <div class="d-flex align-items-center justify-content-between">

            <h6 class="card-title mb-0">
                Hàng khách trả
            </h6>

            <button
                id="clearReturnCartBtn"
                type="button"
                class="btn btn-outline-danger btn-sm"
                @disabled($isFullyReturned)>
                Xóa danh sách
            </button>

        </div>

    </div>


    <div class="card-body p-3">

        <div id="returnCartBody"></div>


        <div
            id="returnCartEmpty"
            class="return-cart-empty small">

            Chưa chọn sản phẩm nào để trả.

        </div>


        {{-- Summary --}}
        <div class="return-summary mt-3 pt-3">

            {{-- Giá trị hàng theo giá bán --}}
            <div class="return-summary-line small">

                <span>
                    Giá trị hàng theo giá bán
                </span>

                <span id="returnGrossPreview">
                    0 VND
                </span>

            </div>


            {{-- Giảm giá --}}
            <div class="return-summary-line text-muted small">

                <span>
                    Giảm giá của đơn gốc phân bổ
                </span>

                <span id="returnDiscountPreview">
                    -0 VND
                </span>

            </div>


            {{-- Giá trị hàng trả --}}
            <div class="return-summary-line fw-semibold small">

                <span>
                    Giá trị hàng trả
                </span>

                <span id="returnAmountPreview">
                    0 VND
                </span>

            </div>


            {{-- Phí trả hàng --}}
            <div class="return-summary-line small">

                <span>
                    Phí trả hàng
                </span>

                <span id="returnFeePreview">
                    -0 VND
                </span>

            </div>


            {{-- Hoàn khách --}}
            <div
                id="refundPreviewRow"
                class="return-summary-line return-summary-final small">

                <span>
                    Hoàn khách
                </span>

                <span
                    id="refundPreview"
                    class="text-success fw-semibold">

                    0 VND

                </span>

            </div>


            {{-- Khách trả thêm --}}
            <div
                id="additionalPaymentPreviewRow"
                class="return-summary-line return-summary-final d-none small">

                <span>
                    Khách trả thêm
                </span>

                <span
                    id="additionalPaymentPreview"
                    class="text-danger fw-semibold">

                    0 VND

                </span>

            </div>


            {{-- Ghi chú --}}
            <div class="return-preview-note mt-2 small">

                Số tiền trên màn hình là giá trị dự kiến.
                Khi lưu, hệ thống sẽ tính lại từ dữ liệu đơn hàng gốc.

            </div>

        </div>

    </div>

</div>