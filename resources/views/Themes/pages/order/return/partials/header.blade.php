{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-2 return-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h6 class="mb-0">
                Đổi / trả hàng
            </h6>

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

        <div class="text-muted mt-1 small">
            Đơn hàng:
            <span class="return-order-code text-dark">
                {{ $order->code }}
                @if ($sourceOrderInfo)

<div class="source-order-reference">

    <i class="fa-solid fa-arrow-turn-up me-1"></i>

    <span class="text-muted">
        Đơn đổi từ
    </span>

    <button
        type="button"
        class="source-order-link"
        data-bs-toggle="modal"
        data-bs-target="#sourceOrderModal">

        {{ $sourceOrderInfo['order_code'] }}

    </button>

    <span class="text-muted">
        ·
        {{ $sourceOrderInfo['return_code'] }}
    </span>

</div>

@endif
            </span>
        </div>

    </div>

    <div class="return-header-actions">
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick="history.back()"
        >
            <i class="fa-solid fa-arrow-left me-1"></i>
            Quay lại
        </button>
    </div>
</div>

@if ($isFullyReturned)
    <div class="alert alert-info py-2 px-3 small">
        <i class="fa-solid fa-circle-info me-1"></i>
        Toàn bộ sản phẩm trong đơn này đã được trả.
        Phiếu hiện chỉ có thể xem, không thể phát sinh thêm lượt trả hàng.
    </div>
@endif
