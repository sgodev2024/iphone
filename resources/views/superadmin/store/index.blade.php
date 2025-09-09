@extends('superadmin.layout.index')
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
            text-align: center;
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
        .btn-danger {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .btn-warning:hover,
        .btn-danger:hover {
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

        .dataTables_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .dataTables_filter label {
            margin-right: 0.5rem;
        }

        /* Accordion styles */
        .accordion-button {
            cursor: pointer;
            text-align: left;
            border: none;
            outline: none;
            background: #f8f9fa;
            padding: 0.5rem;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
        }

        .accordion-content {
            display: none;
            padding: 0.5rem;
            border-top: 1px solid #dee2e6;
            background: #fff;
        }

        .accordion-content ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .accordion-content ul li {
            padding: 0.25rem 0;
        }
    </style>
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('super.store.index') }}">Cửa hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('super.store.index') }}">Danh sách</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Danh sách cửa hàng</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <form action="{{ route('super.store.findByPhone') }}" method="GET">
                                            <div class="dataTables_filter">
                                                <label>Tìm kiếm</label>
                                                <input type="text" name="phone" class="form-control form-control-sm"
                                                    placeholder="Nhập số điện thoại" value="{{ old('phone') }}">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr>
                                                    <th>STT</th>
                                                    <th>Tên Cửa hàng</th>
                                                    <th>Tên chủ</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Ngày hết hạn</th>
                                                    <th>Chiến dịch</th>
                                                    <th style="text-align: center">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if ($stores && $stores->count() > 0)
                                                    @php
                                                        $stt = ($stores->currentPage() - 1) * $stores->perPage();
                                                    @endphp
                                                    @foreach ($stores as $value)
                                                        @if (is_object($value))
                                                            <tr>
                                                                <td>{{ ++$stt }}</td>
                                                                <td>{{ $value->store_name ?? '' }}</td>
                                                                <td>{{ $value->name ?? '' }}</td>
                                                                <td>{{ $value->created_at ? $value->created_at->format('d/m/Y') : '' }}
                                                                </td>
                                                                <td>{{ isset($value->created_at)? \Carbon\Carbon::parse($value->created_at)->addMonths(6)->format('d/m/Y'): '' }}
                                                                </td>
                                                                <td>
                                                                    {{-- Accordion for campaigns --}}
                                                                    @if ($value->campaignDetails && $value->campaignDetails->isNotEmpty())
                                                                        <button class="accordion-button">
                                                                            Xem chiến dịch
                                                                        </button>
                                                                        <div class="accordion-content">
                                                                            <ul>
                                                                                @foreach ($value->campaignDetails as $campaignDetail)
                                                                                    <li>{{ $campaignDetail->campaign->name ?? 'Không có tên chiến dịch' }}
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        </div>
                                                                    @else
                                                                        Không có chiến dịch
                                                                    @endif
                                                                </td>
                                                                <td style="text-align:center">
                                                                    <a class="btn btn-warning"
                                                                        href="{{ route('super.store.detail', ['id' => $value->id]) }}">Chi
                                                                        tiết</a>
                                                                    <a onclick="return confirm('Bạn có chắc chắn muốn xóa?')"
                                                                        class="btn btn-danger"
                                                                        href="{{ route('super.store.delete', ['id' => $value->id]) }}">Xóa</a>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td class="text-center" colspan="7">
                                                            <div class="">
                                                                Chưa có cửa hàng
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>

                                        @if ($stores instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            {{ $stores->links('vendor.pagination.custom') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-notify/0.2.0/js/bootstrap-notify.min.js"></script>
    <script>
        $(document).ready(function() {
            // Accordion functionality
            $('.accordion-button').click(function() {
                $(this).next('.accordion-content').slideToggle();
                $(this).toggleClass('active');
            });

            // Notify functionality
            @if (session('success'))
                $.notify({
                    icon: 'icon-bell',
                    title: 'Thông báo',
                    message: '{{ session('success') }}',
                }, {
                    type: 'secondary',
                    placement: {
                        from: "bottom",
                        align: "right"
                    },
                    time: 1000,
                });
            @endif
        });
    </script>
@endsection
