<div class='table-responsive'>
    <table class='table table-hover table-striped table-bordered mb-0'>
        <thead>
            <tr>
                <th class='text-center'>STT</th>
                <th>Ngày trả</th>
                <th>Mã phiếu</th>
                <th>Mã đơn gốc</th>
                <th>Khách hàng</th>
                <th class='text-end'>Hàng trả</th>
                <th class='text-end'>Hàng đổi</th>
                <th class='text-end'>Phí trả</th>
                <th class='text-end'>Hoàn khách</th>
                <th class='text-end'>Thu thêm</th>
                <th class='text-center'>Trạng thái</th>
                <th class='text-center'>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orderReturns as $orderReturn)
                @php
                    $originalOrder = $orderReturn->originalOrder;
                    $customerName = $originalOrder?->customer_display_name
                        ?? $orderReturn->client?->name
                        ?? 'Khách lẻ';
                @endphp
                <tr>
                    <td class='text-center'>
                        {{ ($orderReturns->currentPage() - 1) * $orderReturns->perPage() + $loop->iteration }}
                    </td>
                    <td>{{ optional($orderReturn->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <strong class='text-primary'>{{ $orderReturn->code }}</strong>
                    </td>
                    <td>{{ $originalOrder?->code ?? '-' }}</td>
                    <td>
                        <div>{{ $customerName }}</div>
                        @if ($originalOrder?->customer_display_phone)
                            <small class='text-muted'>{{ $originalOrder->customer_display_phone }}</small>
                        @endif
                    </td>
                    <td class='text-end'>{{ number_format($orderReturn->return_amount, 0, ',', '.') }}</td>
                    <td class='text-end'>{{ number_format($orderReturn->exchange_amount, 0, ',', '.') }}</td>
                    <td class='text-end'>{{ number_format($orderReturn->fee_amount, 0, ',', '.') }}</td>
                    <td class='text-end fw-semibold text-danger'>
                        {{ number_format($orderReturn->refund_amount, 0, ',', '.') }}
                    </td>
                    <td class='text-end fw-semibold text-success'>
                        {{ number_format($orderReturn->additional_payment, 0, ',', '.') }}
                    </td>
                    <td class='text-center'>
                        @if ($orderReturn->status === 'completed')
                            <span class='badge bg-success'>Hoàn thành</span>
                        @else
                            <span class='badge bg-secondary'>{{ $orderReturn->status }}</span>
                        @endif
                    </td>
                    <td class='text-center'>
                        @if (auth()->user()->hasPermission('order_return.detail'))
                            <a
                                href='{{ route('staff.returns.show', $orderReturn) }}'
                                class='btn btn-sm btn-outline-primary'
                            >
                                <i class='fa-solid fa-eye me-1'></i>
                                Xem chi tiết
                            </a>
                        @else
                            <span class='text-muted'>-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan='12' class='text-center text-muted py-4'>
                        Không có phiếu đổi / trả hàng phù hợp.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($orderReturns->hasPages())
    <div class='mt-3'>
        {{ $orderReturns->links('vendor.pagination.custom') }}
    </div>
@endif
