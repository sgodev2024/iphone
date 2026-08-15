{{-- Hàng khách trả --}}
<div class="card return-cart-card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                Hàng khách trả
            </h5>

            <button
                id="clearReturnCartBtn"
                type="button"
                class="btn btn-outline-danger btn-sm"
                @disabled($isFullyReturned)>
                Xóa
            </button>
        </div>
    </div>

    <div class="card-body">
        <div id="returnCartBody"></div>

        <div id="returnCartEmpty" class="return-cart-empty">
            Chưa chọn sản phẩm nào để trả.
        </div>
    </div>
</div>