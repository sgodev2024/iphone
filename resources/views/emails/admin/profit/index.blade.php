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

    /* .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    } */

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

    /* Các kiểu CSS bạn đã định nghĩa */
    /* .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0, 0, 0);
        background-color: rgba(0, 0, 0, 0.4);
        padding-top: 60px;
    } */

    /* .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    } */

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    #error {
        color: red;

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
                    <h4 style="color: white" class="card-title">Báo cáo lợi nhuận</h4>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <label for="storageSelect">Chọn kho:</label>
                        <select id="storageSelect" class="form-control">
                            <option value="">--- Chọn kho ---</option>
                            @foreach ($storages as $storage)
                            <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                            @endforeach
                        </select>

                        <label for="periodSelect">Chọn thời gian:</label>
                        <select id="periodSelect" class="form-control">
                            <option value="">--- Chọn thời gian ---</option>
                            <option value="1">Hôm nay</option>
                            <option value="2">Tuần này</option>
                            <option value="3">Tháng này</option>
                            <option value="4">Quý này</option>
                            <option value="5">Năm này</option>
                            <option value="6">Chọn ngày</option>
                        </select>

                        <div class="modal fade" id="dateRangeModal" tabindex="-1" role="dialog"
                            aria-labelledby="dateRangeModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="dateRangeModalLabel">Chọn khoảng thời gian</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="startDate">Ngày bắt đầu:</label>
                                            <input type="date" id="startDate" class="form-control">
                                        </div>
                                        <div class="form-group m-0">
                                            <label for="endDate">Ngày kết thúc:</label>
                                            <input type="date" id="endDate" class="form-control">
                                        </div>
                                    </div>
                                    <div>
                                        <p id="error"></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" id="applyDateRange" class="btn btn-primary">Áp
                                            dụng</button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="loader" id="loader"></div>
                    </div>

                    <div class="table-responsive">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px">
                            <span id="itemCount">Số lượng mặt hàng: 0</span>
                            <button id="exportPdf" class="btn btn-primary" style="display: none;">Xuất PDF</button>
                        </div>

                        <table class="table table-hover" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Mã hàng</th>
                                    <th>Tên hàng</th>
                                    <th>SL Bán</th>
                                    <th>Doanh thu</th>
                                    <th>Tổng vốn</th>
                                    <th>Lợi nhuận</th>
                                    <th>Tỷ suất</th>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.17/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {
        $('#storageSelect').change(function() {
            var storageId = $('#storageSelect').val();
            var periodSelect = $('#periodSelect');
            var period  = $('#periodSelect').val('');
            var tableBody = $('#reportTableBody');
            var itemCount = $('#itemCount');
            var exportPdfBtn = $('#exportPdf');
            var error = $('#error');
            tableBody.empty();
            itemCount.text('Số lượng mặt hàng: 0');

            if (storageId) {
                periodSelect.off('change').on('change', function() {
                    var period = periodSelect.val();
                    if (period == 6) {
                        $('#dateRangeModal').modal('show');
                        $('#applyDateRange').click(function(){

                        var startDate = $('#startDate').val();
                        var endDate = $('#endDate').val();
                        if(startDate && endDate){
                            $('#dateRangeModal').modal('hide');
                            $.ajax({
                                url: '{{ route('admin.profit.getProfitReportByFilter') }}',
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    storage_id: storageId,
                                    filter: period,
                                    startDate: startDate,
                                    endDate: endDate
                                },
                                success: function(response) {
                                    updateTable(response.product);
                                    exportPdfBtn.show();
                                }
                            });
                        }else{
                                error.html('Nhập đủ thông tin !');
                        }

                        })

                    }else{
                        $.ajax({
                            url: '{{ route('admin.profit.getProfitReportByFilter') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                storage_id: storageId,
                                filter: period,
                            },
                            success: function(response) {
                                updateTable(response.product);
                                exportPdfBtn.show();
                            }
                        });
                    }

                    exportPdfBtn.off('click').click(function(){
                        if(period == 6){
                            var startDate = $('#startDate').val();
                            var endDate = $('#endDate').val();
                            $.ajax({
                                url: '{{ route('admin.profit.getProfitReportByFilterPDF') }}',
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    storage_id: storageId,
                                    filter: period,
                                    startDate: startDate,
                                    endDate: endDate
                                },
                                xhrFields: {
                                    responseType: 'blob'
                                },
                                success: function(response) {
                                    var link = document.createElement('a');
                                    var url = window.URL.createObjectURL(response);
                                    link.href = url;
                                    link.download = 'profit_report.pdf';
                                    document.body.appendChild(link);
                                    link.click();
                                    window.URL.revokeObjectURL(url);
                                    document.body.removeChild(link);
                                }
                            });

                        }else{
                            $.ajax({
                                url: '{{ route('admin.profit.getProfitReportByFilterPDF') }}',
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    storage_id: storageId,
                                    filter: period,
                                },
                                xhrFields: {
                                    responseType: 'blob'
                                },
                                success: function(response) {
                                    var link = document.createElement('a');
                                    var url = window.URL.createObjectURL(response);
                                    link.href = url;
                                    link.download = 'profit_report.pdf';
                                    document.body.appendChild(link);
                                    link.click();
                                    window.URL.revokeObjectURL(url);
                                    document.body.removeChild(link);
                                }
                            });
                        }

                    })


                });
            }

            function updateTable(list) {
                period  = $('#periodSelect').val('');
                var error = $('#error');
                error.html('');
                // var startDate = $('#startDate').val('');
                // var endDate = $('#endDate').val('');
                itemCount.text('Số lượng mặt hàng: ' + list.length);
                tableBody.empty();

                list.forEach(function(item) {
                var newRow = `
                    <tr>
                        <td>${item.product.code}</td>
                        <td>${item.product.name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.product.price_buy * item.quantity}</td>
                        <td>${item.product.price * item.quantity}</td>
                        <td>${item.product.price * item.quantity - item.product.price_buy * item.quantity}</td>
                        <td>${(100 * (item.product.price * item.quantity - item.product.price_buy * item.quantity) / (item.product.price_buy * item.quantity)).toFixed(2)}%</td>
                    </tr>`;
                    tableBody.append(newRow);
                });
            }



        });


    // Khởi tạo sự kiện đóng modal khi nhấn nút đóng
    $('.close').click(function() {
        $('#dateRangeModal').modal('hide');
    });

    // Khởi tạo sự kiện cho storageSelect
    $('#storageSelect').trigger('change');
});
</script>
@endsection
