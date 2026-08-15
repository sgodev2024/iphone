{{-- Thông tin phiếu trả --}}
<div class="card return-side-card">

    <div class="card-header py-2 px-3">
        <h6 class="card-title mb-0">
            Phiếu trả hàng
        </h6>
    </div>


    <div class="card-body p-3">

        {{-- Phí trả hàng --}}
        <div class="mb-3">

            <label
                for="returnFeeInput"
                class="form-label small mb-1">

                Phí trả hàng

            </label>

            <div class="input-group">

                <input
                    id="returnFeeInput"
                    type="text"
                    inputmode="numeric"
                    class="form-control form-control-sm return-fee-input"
                    placeholder="0"
                    value="0"
                    @disabled($isFullyReturned)>

                <span class="input-group-text small">
                    VND
                </span>

            </div>

            <div class="form-text small">
                Ví dụ: khách chịu phí trả hàng 20.000 VND.
            </div>

        </div>


        {{-- Ghi chú --}}
        <div class="mb-3">

            <label
                for="returnNote"
                class="form-label small mb-1">

                Ghi chú

            </label>

            <textarea
                id="returnNote"
                class="form-control form-control-sm"
                rows="3"
                placeholder="Nhập ghi chú cho phiếu trả..."
                @disabled($isFullyReturned)></textarea>

        </div>


        {{-- Xác nhận --}}
        @if (!$isFullyReturned)

            <div class="d-grid">

                <button
                    type="button"
                    id="saveReturnBtn"
                    class="btn btn-success btn-sm return-save-btn">

                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Xác nhận trả hàng

                </button>

            </div>

        @else

            <div class="alert alert-secondary mb-0 text-center small">
                Đơn đã trả toàn bộ.
            </div>

        @endif

    </div>

</div>