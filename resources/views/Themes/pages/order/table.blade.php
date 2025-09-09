<table class="table table-hover table-striped table-bordered mb-0">
    <thead>
        <tr>
            <th># | NGÀY TẠO</th>
            <th>MÃ ĐƠN</th>
            <th>KHÁCH HÀNG</th>
            <th>TỔNG TIỀN</th>
            <th>GIẢM GIÁ</th>
            <th>THÀNH TIỀN</th>
            <th>THANH TOÁN</th>
            <th>TRẠNG THÁI</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            <tr>
                <td class="align-middle">{{ $loop->iteration }} | {{ $order->created_at->format('d/m/Y') }}</td>
                <td class="align-middle">{{ $order->code }}</td>
                <td class="align-middle">
                    {{ $order->name }} <br>
                    {{ $order->phone ?? '-' }} <br>
                    {{ $order->email ?? '-' }}
                </td>
                <td class="align-middle">{{ number_format($order->total_money + $order->discount_value, 0, ',', '.') }}
                </td>
                <td class="align-middle">
                    {{ number_format($order->discount_value, 0, ',', '.') }}
                    @if ($order->discount_type == 'percent')
                        ({{ number_format(($order->discount_value / ($order->total_money + $order->discount_value)) * 100, 0) }}%)
                    @endif
                </td>
                <td class="align-middle">{{ number_format($order->total_money, 0, ',', '.') }}</td>
                @php
                    $paymentMethods = [
                        'cash' => 'Tiền mặt',
                        'bank_transfer' => 'Chuyển khoản',
                        'debt' => 'Công nợ',
                    ];
                @endphp

                <td class="align-middle">
                    {{ $paymentMethods[$order->payment_method] ?? $order->payment_method }}
                </td>

                <td class="align-middle">
                    @if ($order->status)
                        <span class="badge bg-success">Đã hoàn thành</span>
                    @else
                        <span class="badge bg-warning text-dark">Chưa hoàn thành</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="8">Không có đơn hàng</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $orders->links('vendor.pagination.custom') }}
    </div>
</div>
