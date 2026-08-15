{{-- Thông tin phiếu trả --}}
<div class="card return-side-card return-form-card">

    <div class="card-header">
        <h5 class="card-title mb-0">
            Phiếu đổi / trả
        </h5>
    </div>

    <div class="card-body">

        <div class="mb-2">

            <label
                for="returnFeeInput"
                class="form-label mb-1">
                Phí trả hàng
            </label>

            <div class="input-group input-group-sm">

                <input
                    id="returnFeeInput"
                    type="text"
                    inputmode="numeric"
                    class="form-control return-fee-input"
                    placeholder="0"
                    value="0"
                    @disabled($isFullyReturned)>

                <span class="input-group-text">
                    VND
                </span>

            </div>

        </div>


        <div class="mb-2">

            <label
                for="returnNote"
                class="form-label mb-1">
                Ghi chú
            </label>

            <textarea
                id="returnNote"
                class="form-control form-control-sm"
                rows="2"
                placeholder="Ghi chú..."
                @disabled($isFullyReturned)></textarea>

        </div>


        @if (!$isFullyReturned)

            <div class="d-grid">

                <button
                    type="button"
                    id="saveReturnBtn"
                    class="btn btn-success btn-sm return-save-btn">

                    <i class="fa-solid fa-rotate-left me-1"></i>

                    Xác nhận trả hàng

                </button>

            </div>

        @else

            <div class="alert alert-secondary py-2 mb-0 text-center small">
                Đơn đã trả toàn bộ.
            </div>

        @endif

    </div>
</div>