@extends('admin.layout.index')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

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
            <x-breadcrumb :items="[['label' => 'BÁO CÁO'], ['label' => 'XUẤT NHẬP TỒN']]" />
            {{-- <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">BÁO CÁO</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">XUẤT NHẬP TỒN</a>
                </li>
            </ul> --}}
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
