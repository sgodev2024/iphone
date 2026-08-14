<div class="order-summary d-flex align-items-center gap-4 mb-3 text-nowrap">
    <span>
        <span class="fw-medium">Tổng đơn hàng:</span>
        <strong>{{ number_format($totalOrders, 0, ',', '.') }}</strong>
    </span>
    <span>
        <span class="fw-medium">Tổng doanh thu:</span>
        <strong>{{ formatPrice($totalRevenue) }} VND</strong>
    </span>
</div>

<div class="order-table-hint">Vuốt ngang để xem đầy đủ bảng</div>

<div class="table-responsive order-table-scroll">
    <table class="table table-hover table-striped table-bordered align-middle mb-3">
        <thead>
            <tr>
                <th class="order-col-created" style="width: 14%"># | Ngày tạo</th>
                <th class="order-col-code">Mã đơn hàng</th>
                <th class="order-col-employee">Nhân viên</th>
                <th class="order-col-customer">Khách hàng</th>
                <th class="text-center order-col-quantity">SL sản phẩm</th>
                <th class="order-col-payment">Phương thức thanh toán</th>
                <th class="order-col-status">Trạng thái thanh toán</th>
                <th class="text-end order-col-total">Tổng tiền</th>
                <th class="text-end order-col-debt">CÒN NỢ</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($orders as $order)
                @php
                    $paymentMethodLabel = match ($order->payment_method) {
                        'cash' => 'Tiền mặt',
                        'bank_transfer' => 'Chuyển khoản',
                        'debt' => 'Công nợ',
                        default => $order->payment_method ? ucfirst($order->payment_method) : 'Chưa xác định',
                    };

                    $employeeName = $order->creator?->name ?? ($order->user?->name ?? '---');

                    $customerName = $order->customer_display_name;

                    $saleDebit131 = (float) ($order->sale_debit_131 ?? 0);
                    $paymentCredit131 = (float) ($order->payment_credit_131 ?? 0);
                    $remainingDebt = $saleDebit131 - $paymentCredit131;
                    $hasSaleAccountingEntry = (int) ($order->sale_entry_count_131 ?? 0) > 0;
                @endphp

                <tr>
                    <td class="order-col-created">
                        {{ $orders->firstItem() + $loop->index }}
                        |
                        {{ $order->created_at?->format('d/m/Y') ?? '---' }}
                    </td>

                    <td class="order-col-code">
                        <a href="{{ route('admin.order.show', $order->id) }}" class="text-primary fw-bold">
                            {{ $order->code ?? 'DH-' . $order->id }}
                        </a>
                    </td>

                    <td class="order-col-employee">{{ $employeeName }}</td>

                    <td class="order-col-customer">{{ $customerName }}</td>

                    <td class="text-center order-col-quantity">
                        {{ (int) ($order->product_quantity ?? 0) }}
                    </td>

                    <td class="order-col-payment">{{ $paymentMethodLabel }}</td>

                    <td class="order-col-status">
                        <span class="badge {{ $order->paymentStatusBadgeClass() }}">
                            {{ $order->paymentStatusLabel() }}
                        </span>
                    </td>

                    <td class="text-end fw-semibold order-col-total">
                        {{ formatPrice($order->total_money ?? 0) }} VND
                    </td>

                    <td class="text-end fw-semibold order-col-debt">
                        @if ($hasSaleAccountingEntry && $remainingDebt > 0)
                            <span class="text-danger">{{ formatPrice($remainingDebt) }} VND</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center py-4" colspan="9">
                        Không có đơn hàng nào
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($orders->hasPages())
    <div class="d-flex justify-content-center order-pagination" id="pagination">
        <div class="order-pagination-desktop">
            {{ $orders->onEachSide(1)->links('vendor.pagination.custom') }}
        </div>

        <div class="order-pagination-mobile">
            @if ($orders->onFirstPage())
                <span class="page-disabled" aria-disabled="true">&lsaquo;</span>
            @else
                <a class="page-link" href="{{ $orders->previousPageUrl() }}" rel="prev">&lsaquo;</a>
            @endif

            <span class="order-page-count">
                Trang {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
            </span>

            @if ($orders->hasMorePages())
                <a class="page-link" href="{{ $orders->nextPageUrl() }}" rel="next">&rsaquo;</a>
            @else
                <span class="page-disabled" aria-disabled="true">&rsaquo;</span>
            @endif
        </div>
    </div>
@endif
