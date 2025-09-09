@extends('admin.layout.index')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
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
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            vertical-align: middle;
            text-align: center;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 700;
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef;
            cursor: pointer;
            transition: background-color 0.3s ease;
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

        .dataTables_info,
        .dataTables_paginate {
            margin-top: 1rem;
        }

        .pagination .page-link {
            color: #007bff;
            transition: all 0.3s ease;
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

        .form-group {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group select {
            border-radius: 10px;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            transition: border-color 0.3s ease;
            width: 50%;
            max-width: 300px;
            margin: 0 auto;
            display: block;
        }

        .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }

        #reportDateRange {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            font-weight: 500;
        }

        #reportDateRange span {
            font-weight: 700;
            color: #007bff;
        }

        /* Loader */
        .loader {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid #007bff;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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
                    <a href="#">Báo cáo</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Xuất nhập tồn</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 style="color: white" class="card-title">Báo cáo xuất nhập tồn</h4>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <p id="reportDateRange">
                                Từ ngày: <span id="startDate"></span> đến ngày: <span id="endDate"></span><br>
                                Kho: <span id="storageName"></span>
                            </p>
                            <label for="storageSelect" id="label">Chọn kho:</label>
                            <select id="storageSelect" class="form-control">
                                @foreach ($storages as $storage)
                                    <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                                @endforeach
                            </select>
                            <div class="loader" id="loader"></div>
                        </div>

                        <div class="table-responsive">
                            <span id="itemCount">Số lượng mặt hàng: 0</span>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã hàng</th>
                                        <th>Tên hàng</th>
                                        <th>Tồn đầu kỳ</th>
                                        <th>Giá trị đầu kỳ</th>
                                        <th>SL Nhập</th>
                                        <th>Giá trị nhập</th>
                                        <th>SL Xuất</th>
                                        <th>Giá trị xuất</th>
                                        <th>Tồn cuối kỳ</th>
                                        <th>Giá trị cuối kỳ</th>
                                    </tr>
                                </thead>
                                <tbody id="reportTableBody">
                                    <!-- Dữ liệu sẽ được chèn vào đây qua AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#storageSelect').change(function() {
                var storageId = $(this).val();
                $('#loader').show(); // Hiển thị loader

                $.ajax({
                    url: '{{ route('admin.inventory.getReportByStorage') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        storage_id: storageId
                    },
                    success: function(response) {
                        $('#loader').hide(); // Ẩn loader

                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        // Update information
                        if (response.latestImportDate) {
                            $('#startDate').text(moment(response.latestImportDate).format(
                                'DD/MM/YYYY'));
                        } else {
                            $('#startDate').text('N/A');
                        }
                        $('#endDate').text(moment(response.yesterday).format('DD/MM/YYYY'));
                        $('#storageName').text(response.storage.name);
                        $('#itemCount').text('Số lượng mặt hàng: ' + response.products.length);

                        // Update table
                        var tableBody = $('#reportTableBody');
                        tableBody.empty();

                        if (response.products.length === 0) {
                            tableBody.append(
                                '<tr><td class="text-center" colspan="10">Kho hàng trống</td></tr>'
                                );
                        } else {
                            response.products.forEach(function(item) {
                                tableBody.append('<tr>' +
                                    '<td>' + item.product_id + '</td>' +
                                    '<td>' + item.product.name + '</td>' +
                                    '<td>' + item.quantity_before_import + '</td>' +
                                    '<td>' + new Intl.NumberFormat().format(item
                                        .before_import_value) + '</td>' +
                                    '<td>' + item.imported_quantity + '</td>' +
                                    '<td>' + new Intl.NumberFormat().format(item
                                        .imported_value) + '</td>' +
                                    '<td>' + item.sold_quantity + '</td>' +
                                    '<td>' + new Intl.NumberFormat().format(item
                                        .sold_value) + '</td>' +
                                    '<td>' + item.current_quantity + '</td>' +
                                    '<td>' + new Intl.NumberFormat().format(item
                                        .current_value) + '</td>' +
                                    '</tr>');
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#loader').hide(); // Ẩn loader
                        alert('Failed to fetch report. Please try again later.');
                    }
                });
            });

            $('#storageSelect').trigger('change'); // Trigger the change event on page load
        });
    </script>
@endsection
