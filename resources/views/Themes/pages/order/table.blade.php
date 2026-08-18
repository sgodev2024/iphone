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
            <th class="text-center">THAO TÁC</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($orders as $order)
            <tr>
                <td class="align-middle">
                    {{ $loop->iteration }} |
                    {{ $order->created_at->format('d/m/Y') }}
                </td>

                <td class="align-middle">
                    {{ $order->code }}
                </td>

                <td class="align-middle">
                    {{ $order->name }} <br>
                    {{ $order->phone ?? '-' }} <br>
                    {{ $order->email ?? '-' }}
                </td>

                <td class="align-middle">
                    {{ number_format(
                        $order->total_money + $order->discount_value,
                        0,
                        ',',
                        '.'
                    ) }}
                </td>

                <td class="align-middle">
                    {{ number_format(
                        $order->discount_value,
                        0,
                        ',',
                        '.'
                    ) }}

                    @if (
                        $order->discount_type == 'percent' &&
                        ($order->total_money + $order->discount_value) > 0
                    )
                        (
                            {{ number_format(
                                ($order->discount_value /
                                    ($order->total_money + $order->discount_value)
                                ) * 100,
                                0
                            ) }}%
                        )
                    @endif
                </td>

                <td class="align-middle">
                    {{ number_format(
                        $order->total_money,
                        0,
                        ',',
                        '.'
                    ) }}
                </td>

                @php
                    $paymentMethods = [
                        'cash' => 'Tiền mặt',
                        'bank_transfer' => 'Chuyển khoản',
                        'debt' => 'Công nợ',
                        'exchange' => 'Đổi trả'
                    ];
                @endphp

                <td class="align-middle">
                    {{ $paymentMethods[$order->payment_method]
                        ?? $order->payment_method }}
                </td>

                <td class="align-middle">
                    @if ($order->status)
                        <span class="badge bg-success">
                            Đã hoàn thành
                        </span>
                    @else
                        <span class="badge bg-warning text-dark">
                            Chưa hoàn thành
                        </span>
                    @endif
                </td>

                {{-- THAO TÁC --}}
                <td class="align-middle text-center">
                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn btn-sm btn-light border"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Thao tác"
                        >
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">

                            @if ($order->status)
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="{{ route(
                                            'staff.orders.returns.create',
                                            $order->id
                                        ) }}"
                                    >
                                        <i
                                            class="fa-solid fa-arrow-right-arrow-left me-2"
                                        ></i>

                                        Đổi / trả hàng
                                    </a>
                                </li>
                            @else
                                <li>
                                    <span
                                        class="dropdown-item text-muted disabled"
                                    >
                                        <i
                                            class="fa-solid fa-arrow-right-arrow-left me-2"
                                        ></i>

                                        Đổi / trả hàng
                                    </span>
                                </li>
                            @endif

                        </ul>
                    </div>
                </td>
            </tr>

        @empty
            <tr>
                <td
                    class="text-center"
                    colspan="9"
                >
                    Không có đơn hàng
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $orders->links('vendor.pagination.custom') }}
    </div>
</div>