@extends('admin.layout.index')

@section('content')
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .d-flex {
            display: flex !important;
            align-items: center !important;
        }

        .justify-content-start {
            justify-content: flex-start !important;
        }

        .justify-content-end {
            justify-content: flex-end !important;
        }

        #checkboxForm_category .col-lg-6,
        #checkboxForm_brand .col-lg-6,
        #checkboxForm_company .col-lg-6 {
            flex: 1 1 33% !important;
            /* Mỗi phần tử chiếm 25% chiều rộng, tức là 4 phần tử trên một dòng */
            max-width: 33% !important;
            /* Đảm bảo kích thước tối đa của mỗi phần tử là 25% */
            padding: 0 10px !important;
            /* Khoảng cách giữa các phần tử */
        }

        /* Căn chỉnh Flexbox cho các danh sách */
        #checkboxForm_category .row,
        #checkboxForm_brand .row,
        #checkboxForm_company .row {
            display: flex !important;
            flex-wrap: wrap !important;
            /* Đảm bảo các phần tử sẽ xuống dòng nếu quá số lượng */
        }

        .modal-body h6 {
            font-weight: bold !important;
            /* In đậm tiêu đề */
            margin-bottom: 10px !important;
            /* Khoảng cách dưới tiêu đề */
        }

        /* Điều chỉnh kích thước và vị trí modal */
        .modal-dialog {
            max-width: 90% !important;
            /* Tùy chỉnh độ rộng modal */
            margin: 0 !important;
            /* Xóa khoảng cách mặc định */
            position: absolute !important;
            right: 0 !important;
            /* Kéo modal về phía bên phải màn hình */
        }

        /* Sắp xếp các danh sách trong một hàng ngang */
        .modal-body .row {
            display: flex !important;
            flex-wrap: wrap !important;
        }

        .modal-body .col-lg-12 {
            flex: 1 1 33% !important;
            /* Mỗi cột chiếm 1/3 hàng */
            padding: 0 10px !important;
            /* Thêm khoảng cách giữa các cột */
        }

        .form-check {
            display: flex !important;
            align-items: center !important;
            margin-bottom: 10px !important;
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

        /* Your existing styles */
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
                    <a href="{{ route('admin.product.store') }}">Sản phẩm</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ Route('admin.product.store') }}">Danh sách</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Danh sách sản phẩm</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="row align-items-center">
                                        <div class="col-sm-12 col-md-6 d-flex justify-content-start">
                                            <div class="dataTables_length" id="basic-datatables_length">
                                                <a class="btn btn-primary" href="{{ route('admin.product.addForm') }}">Thêm
                                                    sản
                                                    phẩm</a>

                                                <a class="btn btn-primary"
                                                    href="{{ route('admin.product.formimport') }}">Import
                                                    excel</a>
                                                <a class="btn btn-primary" data-toggle="modal"
                                                    data-target="#exportModal">Export
                                                    excel</a>

                                                <div class="modal fade" id="exportModal" tabindex="-1" role="dialog"
                                                    aria-labelledby="exportModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exportModalLabel">Xuất file danh
                                                                    sách sản phẩm</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body" id="category_kho">
                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <h6><strong>Danh sách danh mục</strong></h6>
                                                                        <!-- Tiêu đề cho danh mục -->
                                                                        <div class="form-check mb-3">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                id="selectAllCategories">
                                                                            <label class="form-check-label"
                                                                                for="selectAllCategories"
                                                                                style="font-size: 14px">
                                                                                Chọn tất cả danh mục
                                                                            </label>
                                                                        </div>
                                                                        <form id="checkboxForm_category">
                                                                            <div class="row">
                                                                                @foreach ($category as $item)
                                                                                    <div class="col-lg-6 mb-2">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input"
                                                                                                name='category[]'
                                                                                                type="checkbox"
                                                                                                value="{{ $item->id }}"
                                                                                                id="checkbox{{ $item->id }}">
                                                                                            <label class="form-check-label"
                                                                                                for="checkbox{{ $item->id }}">
                                                                                                {{ $item->name }}
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </form>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <h6><strong>Danh sách thương hiệu</strong></h6>
                                                                        <!-- Tiêu đề cho thương hiệu -->
                                                                        <div class="form-check mb-3">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                id="selectAllBrands">
                                                                            <label class="form-check-label"
                                                                                for="selectAllBrands"
                                                                                style="font-size: 14px">
                                                                                Chọn tất cả thương hiệu
                                                                            </label>
                                                                        </div>
                                                                        <form id="checkboxForm_brand">
                                                                            <div class="row">
                                                                                @foreach ($brand as $item)
                                                                                    <div class="col-lg-6 mb-2">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input"
                                                                                                name='brand[]'
                                                                                                type="checkbox"
                                                                                                value="{{ $item->id }}"
                                                                                                id="checkbox{{ $item->id }}">
                                                                                            <label class="form-check-label"
                                                                                                for="checkbox{{ $item->id }}">
                                                                                                {{ $item->name }}
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </form>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <h6><strong>Danh sách nhà cung cấp</strong></h6>
                                                                        <!-- Tiêu đề cho nhà cung cấp -->
                                                                        <div class="form-check mb-3">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                id="selectAllCompanies">
                                                                            <label class="form-check-label"
                                                                                for="selectAllCompanies"
                                                                                style="font-size: 14px">
                                                                                Chọn tất cả nhà cung cấp
                                                                            </label>
                                                                        </div>
                                                                        <form id="checkboxForm_company">
                                                                            <div class="row">
                                                                                @foreach ($companies as $item)
                                                                                    <div class="col-lg-6 mb-2">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input"
                                                                                                name='company[]'
                                                                                                type="checkbox"
                                                                                                value="{{ $item->id }}"
                                                                                                id="checkbox{{ $item->id }}">
                                                                                            <label class="form-check-label"
                                                                                                for="checkbox{{ $item->id }}">
                                                                                                {{ $item->name }}
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal">Hủy</button>
                                                                <button type="button" class="btn btn-secondary"
                                                                    id="exportproduct" data-dismiss="modal">Xuất</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6 d-flex justify-content-end">
                                            <form action="{{ route('admin.product.productFilter') }}" method="GET" class="d-flex">
                                                <div class="dataTables_filter">
                                                    <div class="input-group">
                                                        <select name="company_id" id="company_id"
                                                            class="form-control form-control-sm">
                                                            <option value="">Nhà cung cấp</option>
                                                            @foreach ($companies as $company)
                                                                <option value="{{ $company->id }}"
                                                                    {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                                                    {{ $company->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" class="form-control form-control-sm"
                                                            name="name" placeholder="Nhập tên sản phẩm"
                                                            value="{{ old('name') }}">
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-primary" type="submit">Tìm
                                                                kiếm</button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12" id="product-table">
                                        @include('admin.product.table', ['products' => $product])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12" id="pagination">
                                        {{ $product->links('vendor.pagination.custom') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript code -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-notify/0.2.0/js/bootstrap-notify.min.js"></script>
    <script>
        $(document).ready(function() {
            //Accordion functionality
            $('.accordion-button').click(function() {
                $(this).next('.accordion-content').slideToggle();
                $(this).toggleClass('active');
            });

            // Handle delete button click
            $(document).on('click', '.btn-delete', function() {
                if (confirm('Bạn có chắc chắn muốn xóa?')) {
                    var productId = $(this).data('id');
                    var deleteUrl = '{{ route('admin.product.delete', ['id' => ':id']) }}';
                    deleteUrl = deleteUrl.replace(':id', productId);

                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update the table and pagination with the new HTML
                                $('#product-table').html(response.table);
                                $('#pagination').html(response.pagination);

                                $.notify({
                                    icon: 'icon-bell',
                                    title: 'Sản phẩm',
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
                                    title: 'Sản phẩm',
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
                                title: 'Sản phẩm',
                                message: 'Xóa sản phẩm thất bại!',
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
                $.notify({
                    icon: 'icon-bell',
                    title: 'Sản phẩm',
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

    <!-- Đảm bảo bạn đã bao gồm jQuery trong dự án của bạn -->

    <script>
        document.getElementById('selectAllCategories').addEventListener('change', function() {
           const checkboxes = document.querySelectorAll('#checkboxForm_category .form-check-input');
           checkboxes.forEach(checkbox => {
               checkbox.checked = this.checked;
           });
       });
        document.getElementById('selectAllBrands').addEventListener('change', function() {
           const checkboxes = document.querySelectorAll('#checkboxForm_brand .form-check-input');
           checkboxes.forEach(checkbox => {
               checkbox.checked = this.checked;
           });
       });
        document.getElementById('selectAllCompanies').addEventListener('change', function() {
           const checkboxes = document.querySelectorAll('#checkboxForm_company .form-check-input');
           checkboxes.forEach(checkbox => {
               checkbox.checked = this.checked;
           });
       });


   </script>

    <script>
        $(document).ready(function() {
            $('#exportproduct').on('click', function() {
                const selectedCategories = $('#checkboxForm_category input[type="checkbox"]:checked')
                    .map(function() {
                        return $(this).val();
                    }).get();

                const selectedBrands = $('#checkboxForm_brand input[type="checkbox"]:checked')
                    .map(function() {
                        return $(this).val();
                    }).get();

                const selectedCompanies = $('#checkboxForm_company input[type="checkbox"]:checked')
                    .map(function() {
                        return $(this).val();
                    }).get();

                const exportUrl = '{{ route('admin.product.export1') }}';
                $.ajax({
                    url: exportUrl,
                    method: 'GET',
                    data: {
                        categories: JSON.stringify(selectedCategories),
                        brands: JSON.stringify(selectedBrands),
                        companies: JSON.stringify(selectedCompanies)
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(data) {
                        const url = window.URL.createObjectURL(new Blob([data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.setAttribute('download', 'products.xlsx');
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        $('#checkboxForm_category input[type="checkbox"]').prop('checked',
                            false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Có lỗi xảy ra:', error);
                    }
                });
            });
        });
    </script>
@endsection
