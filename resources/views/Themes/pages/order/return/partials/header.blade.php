    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 return-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="mb-0">
                    Đổi / trả hàng
                </h4>

                @if ($isFullyReturned)
                <span class="badge bg-secondary">
                    Đã trả toàn bộ
                </span>
                @else
                <span class="badge bg-success">
                    Có thể trả hàng
                </span>
                @endif
            </div>

            <div class="text-muted mt-1">
                Đơn hàng:
                <span class="return-order-code text-dark">
                    {{ $order->code }}
                </span>
            </div>
        </div>

        <div class="return-header-actions">
            <button
                type="button"
                class="btn btn-outline-secondary"
                onclick="history.back()">
                <i class="fa-solid fa-arrow-left me-1"></i>
                Quay lại
            </button>
        </div>
    </div>


    @if ($isFullyReturned)
    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info me-1"></i>

        Toàn bộ sản phẩm trong đơn này đã được trả.
        Phiếu hiện chỉ có thể xem, không thể phát sinh thêm lượt trả hàng.
    </div>
    @endif
