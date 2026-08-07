@extends('admin.layout.index')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .icon-bell:before {
            content: "\f0f3";
            font-family: FontAwesome;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, #6f42c1, #007bff);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .breadcrumbs {
            background: #fff;
            padding: 0.75rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .breadcrumbs a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumbs i {
            color: #6c757d;
        }

        .table-responsive {
            margin-top: 1rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th,
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .btn-warning,
        .btn-danger,
        .btn-primary {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .btn-warning:hover,
        .btn-danger:hover,
        .btn-primary:hover {
            transform: scale(1.05);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef;
        }

        .dataTables_info,
        .dataTables_paginate {
            margin-top: 1rem;
        }

        .pagination .page-link {
            color: #007bff;
        }

        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        .pagination .page-item:hover .page-link {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .pagination .page-item.active .page-link,
        .pagination .page-item .page-link {
            transition: all 0.3s ease;
        }
    </style>

    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Đơn hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Danh sách</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Báo cáo đơn hàng</h4>
                    </div>

                    <div class="card-body">
                        <div class="">
                            <!-- Filter Form -->
                            <form action="{{ route('admin.order.filter') }}" method="GET">
                                <div class="row">
                                    <!-- Start Date Input -->
                                    <div class="col-md-4 mb-3">
                                        <label for="start_date">Ngày bắt đầu</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control"
                                            value="{{ old('start_date') }}">
                                    </div>

                                    <!-- End Date Input -->
                                    <div class="col-md-4 mb-3">
                                        <label for="end_date">Ngày kết thúc</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control"
                                            value="{{ old('end_date') }}">
                                    </div>

                                    <!-- Phone Number Input -->
                                    <div class="col-md-4 mb-3">
                                        <label for="phone">Tìm số điện thoại</label>
                                        <input type="text" name="phone" id="phone" class="form-control"
                                            placeholder="Nhập số điện thoại" value="{{ old('phone') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="text-center mt-2">
                                        <div class="d-inline-block">
                                            <button type="submit" class="btn btn-primary">Tìm</button>
                                        </div>
                                        <div class="d-inline-block ml-2">
                                            <button type="button"
                                                onclick="window.location.href='{{ route('admin.order.index') }}'"
                                                class="btn btn-danger">Xóa</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <!-- End Filter Form -->

                            <!-- Table -->
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr role="row">
                                                    <th>Mã đơn hàng</th>
                                                    <th>Nhân viên</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Khách hàng</th>
                                                    <th>Trạng thái</th>
                                                    <th>Tổng tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($orders as $order)
                                                    <tr>
                                                        <td>
                                                            <a style="color: black; font-weight:bold"
                                                                href="{{ route('admin.order.show', $order->id) }}">{{ $order->id }}</a>
                                                        </td>
                                                        <td>
                                                            <a style="color:black"
                                                                href="">
                                                                {{ $order->user->name ?? '' }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $order->created_at->format('d/m/y') }}</td>
                                                        <td>
                                                            <a style="color:black"
                                                                href="{{ route('admin.client.detail', ['id' => $order->client->id]) }}">
                                                                {{ $order->client->name ?? '' }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @if ($order->status == 1)
                                                                <span class="badge badge-success">Đã thanh toán</span>
                                                            @else
                                                                <span class="badge badge-danger">Công nợ</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ number_format($order->total_money) }} VND</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="text-center" colspan="6">Không có đơn hàng nào</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <!-- Pagination -->
                                        {{ $orders->appends(request()->query())->links('vendor.pagination.custom') }}
                                    </div>
                                </div>
                            </div>
                            <!-- End Table -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
