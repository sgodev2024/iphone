<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered align-middle mb-3">
        <thead>
            <tr>
                <th style="width: 14%"># | Ngày tạo</th>
                <th>Mã đơn hàng</th>
                <th>Nhân viên</th>
                <th>Khách hàng</th>
                <th class="text-center">SL sản phẩm</th>
                <th>Phương thức thanh toán</th>
                <th>Trạng thái</th>
                <th class="text-end">Tổng tiền</th>
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
                @endphp

                <tr>
                    <td>
                        {{ $orders->firstItem() + $loop->index }}
                        |
                        {{ $order->created_at?->format('d/m/Y') ?? '---' }}
                    </td>

                    <td>
                        <a href="{{ route('admin.order.show', $order->id) }}" class="text-primary fw-bold">
                            {{ $order->code ?? 'DH-' . $order->id }}
                        </a>
                    </td>

                    <td>{{ $employeeName }}</td>

                    <td>{{ $customerName }}</td>

                    <td class="text-center">
                        {{ $order->order_details_count ?? 0 }}
                    </td>

                    <td>{{ $paymentMethodLabel }}</td>

                    <td>
                        @if ((int) $order->status === 1)
                            <span class="badge bg-success">
                                Đã thanh toán
                            </span>
                        @else
                            <span class="badge bg-danger">
                                Công nợ
                            </span>
                        @endif
                    </td>

                    <td class="text-end fw-semibold">
                        {{ formatPrice($order->total_money ?? 0) }} VND
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center py-4" colspan="8">
                        Không có đơn hàng nào
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($orders->hasPages())
    <div class="d-flex justify-content-center" id="pagination">
        {{ $orders->onEachSide(1)->links('vendor.pagination.custom') }}
    </div>
@endif
