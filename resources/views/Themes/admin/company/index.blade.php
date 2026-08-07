@extends('admin.layout.index')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        p{
            margin: 0;
        }
        .dataTables_filter {
            margin-top: 1rem !important;
        }

        .input-group {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            /* Tạo khoảng cách giữa các phần tử trong input-group */
        }

        .input-group select {
            border-radius: 20px 0 0 20px !important;
            /* Bo góc cho phần bên trái */
            border: 1px solid #ced4da !important;
            padding: 0.5rem 1rem !important;
            font-size: 14px !important;
            height: 38px !important;
            /* Chiều cao cố định để đồng nhất với nút tìm kiếm */
        }

        .input-group input {
            border-radius: 0 !important;
            /* Không bo góc cho ô nhập tên */
            border: 1px solid #ced4da !important;
            padding: 0.5rem 1rem !important;
            font-size: 14px !important;
            height: 38px !important;
            /* Chiều cao cố định để đồng nhất với nút tìm kiếm */
            flex: 1 !important;
        }

        .input-group-btn {
            margin-left: 0 !important;
            /* Loại bỏ khoảng cách giữa ô nhập liệu và nút tìm kiếm */
        }

        .input-group-btn .btn-primary {
            border-radius: 0 20px 20px 0 !important;
            /* Bo góc cho phần bên phải */
            padding: 0.5rem 1rem !important;
            font-size: 14px !important;
            font-weight: bold !important;
            height: 38px !important;
            /* Chiều cao cố định để đồng nhất với ô nhập liệu */
            border: 1px solid #ced4da !important;
        }

        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .table {
            width: 100% !important;
            white-space: nowrap !important;
        }

        .table th,
        .table td {
            padding: 1rem !important;
            vertical-align: middle !important;
            text-align: center !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .table th {
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6 !important;
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef !important;
        }

        .btn-warning,
        .btn-danger,
        .btn-secondary,
        .btn-dark {
            border-radius: 20px !important;
            padding: 5px 15px !important;
            font-size: 14px !important;
            font-weight: bold !important;
            transition: background 0.3s ease !important, transform 0.3s ease !important;
            margin: 0 2px !important;
        }

        .btn-warning:hover,
        .btn-danger:hover,
        .btn-primary:hover,
        .btn-secondary:hover,
        .btn-dark:hover {
            transform: scale(1.05) !important;
        }

        .page-header {
            margin-bottom: 2rem !important;
        }

        .pagination .page-link {
            color: #007bff !important;
        }

        .pagination .page-item.active .page-link {
            background-color: #007bff !important;
            border-color: #007bff !important;
        }

        .pagination .page-item:hover .page-link {
            background-color: #0056b3 !important;
            border-color: #0056b3 !important;
        }

        .pagination .page-item.active .page-link,
        .pagination .page-item .page-link {
            transition: all 0.3s ease !important;
        }

        body {
            font-family: 'Roboto', sans-serif !important;
            background-color: #f4f6f9 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .icon-bell:before {
            content: "\f0f3" !important;
            font-family: FontAwesome !important;
        }

        .card {
            border-radius: 15px !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
            overflow: hidden !important;
            background-color: #fff !important;
            margin-bottom: 2rem !important;
        }

        .card-header {
            background: linear-gradient(135deg, #6f42c1, #007bff) !important;
            color: white !important;
            border-top-left-radius: 15px !important;
            border-top-right-radius: 15px !important;
            padding: 1.5rem !important;
        }

        .card-title {
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            margin: 0 !important;
            text-align: center !important;
        }

        .breadcrumbs {
            background: #fff !important;
            padding: 0.75rem !important;
            border-radius: 10px !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            margin-bottom: 1rem !important;
        }

        .breadcrumbs a {
            color: #007bff !important;
            text-decoration: none !important;
            font-weight: 500 !important;
        }

        .breadcrumbs i {
            color: #6c757d !important;
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
                    <a href="{{ route('admin.company.index') }}">Nhà cung cấp</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.company.index') }}">Danh sách</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="color: white">Danh sách nhà cung cấp</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <div class="dataTables_length" id="basic-datatables_length">
                                            <a class="btn btn-primary" href="{{ route('admin.company.add') }}">
                                                Thêm công ty cung cấp mới
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <form action="{{ route('admin.company.filter') }}" method="GET">
                                            <div class="dataTables_filter">
                                                <div class="input-group">
                                                    <select name="city_id" id="city_id"
                                                        class="form-control form-control-sm">
                                                        <option value="">Khu vực</option>
                                                        @foreach ($cities as $city)
                                                            <option value="{{ $city->id }}"
                                                                {{ request('city_id') == $city->id ? 'selected' : '' }}>
                                                                {{ $city->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="name_filter"
                                                        class="form-control form-control-sm" placeholder="Nhập tên NCC"
                                                        value="{{ old('name') }}">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                                                    </span>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12" id="company-table">
                                        @include('admin.company.table', ['companies' => $companies])
                                    </div>
                                    <div class="col-sm-12" id="pagination">
                                        @if ($companies instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            {{ $companies->links('vendor.pagination.custom') }}
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
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault(); // Ngăn chặn hành vi mặc định của liên kết

            if (confirm('Bạn có chắc chắn muốn xóa?')) {
                var companyID = $(this).data('id'); // Đảm bảo điều này được thiết lập chính xác trong HTML của bạn
                var deleteUrl = '{{ route('admin.company.delete', ['id' => ':id']) }}';
                deleteUrl = deleteUrl.replace(':id', companyID);

                $.ajax({
                    url: deleteUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Cập nhật bảng công ty
                            $('#company-table').html(response.table);
                            $('#pagination').html(response
                                .pagination); // Đảm bảo bạn bao gồm phân trang trong phản hồi
                            $.notify({
                                icon: 'icon-bell',
                                title: 'Nhà cung cấp',
                                message: response.message,
                            }, {
                                type: 'success',
                                placement: {
                                    from: "bottom",
                                    align: "right"
                                },
                                time: 1000,
                            });
                        } else {
                            $.notify({
                                icon: 'icon-bell',
                                title: 'Nhà cung cấp',
                                message: response.message,
                            }, {
                                type: 'danger',
                                placement: {
                                    from: "bottom",
                                    align: "right"
                                },
                                time: 1000,
                            });
                        }
                    },
                    error: function(xhr) {
                        $.notify({
                            icon: 'icon-bell',
                            title: 'Nhà cung cấp',
                            message: 'Xóa công ty thất bại!',
                        }, {
                            type: 'danger',
                            placement: {
                                from: "bottom",
                                align: "right"
                            },
                            time: 1000,
                        });
                    }
                });
            }
        });

        @if (session('success'))
            $(document).ready(function() {
                $.notify({
                    icon: 'icon-bell',
                    title: 'Nhà cung cấp',
                    message: '{{ session('success') }}',
                }, {
                    type: 'secondary',
                    placement: {
                        from: "bottom",
                        align: "right"
                    },
                    time: 1000,
                });
            });
        @endif
    </script>
@endsection
