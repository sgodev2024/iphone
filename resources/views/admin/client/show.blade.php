@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Khách hàng', 'url' => route('admin.client.index')], ['label' => 'Chi tiết']]" />

        <div class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Thông tin khách hàng #{{ $client->id }}</h4>
                @can('client.update')
                    <a href="{{ route('admin.client.edit', $client) }}" class="btn btn-warning btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Sửa
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr><th style="width: 220px">Họ tên</th><td>{{ $client->name }}</td></tr>
                            <tr><th>Số điện thoại</th><td>{{ $client->phone }}</td></tr>
                            <tr><th>Email</th><td>{{ $client->email ?: '---' }}</td></tr>
                            <tr><th>Địa chỉ</th><td>{{ $client->address ?: '---' }}</td></tr>
                            <tr><th>Cửa hàng</th><td>{{ $client->branch?->name ?? 'Chưa xác định' }}</td></tr>
                            <tr><th>Ngày tạo</th><td>{{ $client->created_at?->format('d/m/Y H:i') ?? '---' }}</td></tr>
                            <tr><th>Cập nhật lần cuối</th><td>{{ $client->updated_at?->format('d/m/Y H:i') ?? '---' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Lịch sử đơn hàng</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-3">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày tạo</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>{{ $order->code ?: '#'.$order->id }}</td>
                                    <td>{{ $order->created_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td class="text-end">{{ formatPrice($order->total_money ?? 0) }} VND</td>
                                    <td><span class="badge {{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span></td>
                                    <td>{{ $order->status ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4">Chưa có đơn hàng</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($orders->hasPages())
                    <div class="d-flex justify-content-center">{{ $orders->links('vendor.pagination.custom') }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
