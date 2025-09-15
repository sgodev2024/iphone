<table class="table table-hover table-striped table-bordered mt-3">
    <thead>
        <tr>
            <th style="width: 14%"># | Ngày tạo</th>
            <th>Mã đơn hàng</th>
            <th>Nhân viên</th>
            <th>Khách hàng</th>
            <th>SL Sản phẩm</th>
            <th>Trạng thại thanh toán</th>
            <th>Trạng thái</th>
            <th class="text-end">Tổng tiền</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            <tr>
                <!-- Số thứ tự + Ngày tạo -->
                <td>
                    {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                    | {{ $order->created_at->format('d/m/Y') }}
                </td>

                <td>
                    <a href="{{ route('admin.order.show', $order->id) }}" class="text-primary fw-bold">
                        {{ $order->code }}
                    </a>
                </td>

                <td>{{ $order->creator->name ?? '---' }}</td>

                <td>{{ $order->client->name ?? $order->name }}</td>

                <td>{{ $order->order_details_count }}</td>
                <td>
                    @if ($order->payment_method === 'cash')
                        Tiền mặt
                    @elseif($order->payment_method === 'bank_transfer')
                        Chuyển khoản
                    @elseif($order->payment_method === 'debt')
                        Công nợ
                    @else
                        {{ ucfirst($order->payment_method) }}
                    @endif
                </td>
                <td>
                    {!! $order->status
                        ? '<span class="badge bg-primary">Đã hoàn thành</span>'
                        : '<span class="badge bg-danger">Chưa hoàn thành</span>' !!}
                </td>

                <td class="text-end">{{ formatPrice($order->total_money) }} VND</td>

            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="7">Không có đơn hàng nào</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $orders->links('vendor.pagination.custom') }}
    </div>
</div>

{{-- @forelse ($orders as $order)
            <tr>
                <!-- Số thứ tự + Ngày tạo -->
                <td>
                    {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                    | {{ $order->created_at->format('d/m/Y') }}
                </td>

                <!-- Mã đơn hàng -->
                <td>
                    <a href="{{ route('admin.order.show', $order->id) }}" class="text-primary fw-bold">
                        {{ $order->code ?? 'DH-' . $order->id }}
                    </a>
                </td>

                <!-- Nhân viên -->
                <td>{{ $order->user->name ?? '---' }}</td>

                <!-- Ngày tạo (chi tiết) -->
                <td>{{ $order->created_at->format('H:i d/m/Y') }}</td>

                <!-- Khách hàng -->
                <td>{{ $order->client->name ?? $order->name }}</td>

                <!-- Trạng thái -->
                <td>
                    @switch($order->status)
                        @case(0)
                            <span class="badge bg-secondary">Chờ xử lý</span>
                        @break

                        @case(1)
                            <span class="badge bg-info">Đang xử lý</span>
                        @break

                        @case(2)
                            <span class="badge bg-primary">Hoàn tất</span>
                        @break

                        @case(3)
                            <span class="badge bg-danger">Đã hủy</span>
                        @break

                        @case(4)
                            <span class="badge bg-warning text-dark">Công nợ</span>
                        @break

                        @default
                            <span class="badge bg-light text-dark">Không rõ</span>
                    @endswitch
                </td>

                <!-- Tổng tiền -->
                <td class="text-end">{{ formatPrice($order->total_money) }} VND</td>
            </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="7">Không có đơn hàng nào</td>
                </tr>
            @endforelse --}}
