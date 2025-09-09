@extends('admin.layout.index')

@section('content')
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        /* Existing styles */
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

        #category_kho {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #category_kho h2 {
            color: #343a40;
            margin-bottom: 20px;
            font-weight: bold;
        }

        #category_kho label {
            padding: 0px 25px;
        }

        #category_kho .form-control {
            border-radius: 20px;
            padding: 10px 20px;
            font-size: 1.1em;
        }

        #category_kho .form-check-input {
            margin-top: 6px;
        }

        #category_kho .form-check-label {
            font-size: 1.1em;
        }

        #category_kho .form-check {
            margin-bottom: 10px;
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
                    <a href="{{ route('admin.storage.index') }}">Kho hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="">Sản phẩm trong kho</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title text-center text-white">
                            Danh sách sản phẩm trong kho {{ $storage->name ?? 'Không xác định' }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12" id="product-table">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr role="row">
                                                    <th>STT</th>
                                                    <th>Tên</th>
                                                    <th>Số lượng(đơn vị)</th>
                                                    <th>Giá nhập</th>
                                                    <th>Giá bán</th>
                                                </tr>
                                            </thead>
                                            @if ($product->count() > 0)
                                                <tbody>
                                                    @foreach ($product as $key => $value)
                                                        <tr id="product-{{ $value->id }}">
                                                            <td>{{ ($product->currentPage() - 1) * $product->perPage() + $loop->index + 1 }}
                                                            </td>
                                                            <td>{{ $value->product->name ?? 'Chưa có tên' }}</td>
                                                            <td>{{ $value->quantity ?? '0' }}
                                                                {{ $value->product->product_unit ?? 'đơn vị' }}</td>
                                                            <td>{{ number_format($value->product->price) ?? '0' }} đ</td>
                                                            <td>{{ number_format($value->product->price_buy) ?? '0' }} đ</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            @else
                                                <tbody>
                                                    <tr>
                                                        <td class="text-center" colspan="5">Kho hàng chưa có sản phẩm nào
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12" id="pagination">
                                        {{ $product->links('vendor.pagination.custom') }}
                                    </div>
                                </div>
                                <div class="row">
                                    <a class="btn btn-primary rounded-5" href="{{route('admin.storage.index')}}">Danh sách kho hàng</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
