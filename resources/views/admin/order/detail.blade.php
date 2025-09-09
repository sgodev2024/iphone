@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Đơn hàng', 'url' => '/admin/order'], ['label' => $title]]" />

        <div class="card shadow-lg">
            <div class="card-body">
                <!-- Hàng trên: Khách hàng & Đơn hàng -->
                <div class="row">
                    <!-- Khách hàng -->
                    <div class="col-md-6 mb-3">
                        <h5 class="text-center text-primary"><b>Thông tin khách hàng</b></h5>
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr>
                                    <th><i class="fas fa-user"></i> Tên khách hàng</th>
                                    <td>{{ $order->client->name }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-phone"></i> Số điện thoại</th>
                                    <td>{{ $order->client->phone }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-envelope"></i> Email</th>
                                    <td>{{ $order->client->email }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-map-marker-alt"></i> Địa chỉ</th>
                                    <td>{{ $order->client->address }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Đơn hàng -->
                    <div class="col-md-6 mb-3">
                        <h5 class="text-center text-primary"><b>Thông tin đơn hàng</b></h5>
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr>
                                    <th><i class="fas fa-receipt"></i> Mã đơn hàng</th>
                                    <td>{{ $order->code }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-user-tie"></i> Nhân viên</th>
                                    <td>{{ $order->creator->name }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-money-bill-wave"></i> Tổng tiền</th>
                                    <td>{{ formatPrice($order->total_money) }} VND</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-hand-holding-usd"></i> Trạng thái</th>
                                    <td>{{ $order->status ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="text-center text-primary mb-0"><b>Danh sách sản phẩm</b></h5>
            </div>
            <div class="card-body">
                <!-- Hàng dưới: Danh sách sản phẩm -->

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->orderDetails as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->p_name }}</td>
                                <td>{{ formatPrice($item->p_price) }} VND</td>
                                <td>x{{ $item->p_quantity }}</td>
                                <td>{{ formatPrice($item->p_price * $item->p_quantity) }} VND</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <!-- Tổng kết -->
                <div class="row justify-content-end mt-3">
                    <div class="col-md-4">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <th>Tạm tính:</th>
                                    <td class="text-end">{{ formatPrice($order->total_money + $order->discount_value) }}
                                        VND</td>
                                </tr>
                                <tr>
                                    <th>Khuyến mãi:</th>
                                    <td class="text-end">-{{ formatPrice($order->discount_value) }} VND</td>
                                </tr>
                                <tr class="table-active fw-bold">
                                    <th>Tổng cộng:</th>
                                    <td class="text-end">{{ formatPrice($order->total_money) }} VND</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
