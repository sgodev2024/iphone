@extends('admin.layout.index')

@section('content')
    <style>
        .profit-page .loader {
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

        .profit-page .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .profit-page .close:hover,
        .profit-page .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .profit-page #error {
            color: red;

        }

        .profit-page .modal-dialog {
            margin: 0 auto;
            max-width: 500px;
        }

        .profit-page .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profit-page .profit-table-hint,
        .profit-page .profit-page-arrow,
        .profit-page .profit-pagination-status {
            display: none;
        }

        .profit-page .profit-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .profit-page {
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px !important;
                padding-left: 10px !important;
                overflow-x: visible;
            }

            .profit-page .page-header {
                align-items: flex-start;
                margin-bottom: 10px;
            }

            .profit-page .page-header .breadcrumb,
            .profit-page .page-header .breadcrumbs {
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
            }

            .profit-page > .row {
                --bs-gutter-x: 0;
                margin-right: 0;
                margin-left: 0;
            }

            .profit-page > .row > [class*="col-"] {
                min-width: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .profit-page .card {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin-right: auto;
                margin-left: auto;
                overflow: visible;
            }

            .profit-page .card-header,
            .profit-page .card-body {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding-right: 10px;
                padding-left: 10px;
            }

            .profit-page .profit-title-header {
                padding-top: 12px;
                padding-bottom: 8px;
            }

            .profit-page .profit-report-title {
                margin-bottom: 0;
                font-size: 18px;
                line-height: 1.3;
                white-space: normal;
            }

            .profit-page .profit-toolbar {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 42px;
                gap: 8px;
                align-items: center;
                justify-content: normal !important;
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .profit-page .profit-date-field {
                grid-column: 1 / -1;
                width: 100%;
                min-width: 0;
            }

            .profit-page #dateFilter {
                width: 100% !important;
            }

            .profit-page .profit-search-row {
                display: contents !important;
                min-width: 0;
            }

            .profit-page .profit-search-input {
                grid-column: 1;
                width: 100% !important;
                min-width: 0;
                max-width: 100%;
                margin-right: 0 !important;
            }

            .profit-page #btn-reset {
                display: inline-flex;
                grid-column: 2;
                align-items: center;
                justify-content: center;
                width: 42px;
                min-width: 42px;
                height: 42px;
                min-height: 42px;
                padding: 0;
                border: 1px solid #d7dde7;
                border-radius: 4px;
                background: #fff;
                color: #495057;
            }

            .profit-page #dateFilter,
            .profit-page .profit-search-input,
            .profit-page #storageSelect,
            .profit-page #periodSelect {
                height: 40px;
                min-height: 40px;
                font-size: 14px;
            }

            .profit-page .profit-filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 0;
            }

            .profit-page .profit-filter-row > .profit-filter-field {
                flex: 0 0 100%;
                max-width: 100%;
                width: 100%;
                min-width: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .profit-page .profit-filter-row label {
                margin-bottom: 4px;
                font-size: 13px;
            }

            .profit-page #storageSelect,
            .profit-page #periodSelect {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                text-overflow: ellipsis;
            }

            .profit-page .loader {
                margin-top: 4px;
            }

            .profit-page .profit-report-table-area {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: visible;
            }

            .profit-page .profit-item-count-row {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                margin-top: 8px;
                margin-bottom: 6px !important;
            }

            .profit-page #itemCount {
                font-weight: 600;
            }

            .profit-page .profit-table-hint {
                display: block;
                margin: 0 0 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.4;
            }

            .profit-page .profit-table-scroll {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .profit-page .profit-table {
                display: table !important;
                width: 100% !important;
                min-width: 1000px;
                table-layout: auto;
            }

            .profit-page .profit-table th,
            .profit-page .profit-table td {
                display: table-cell !important;
                vertical-align: middle;
                font-size: 13px;
            }

            .profit-page .profit-table th {
                white-space: nowrap;
            }

            .profit-page .profit-table th:nth-child(1),
            .profit-page .profit-table td:nth-child(1),
            .profit-page .profit-table th:nth-child(3),
            .profit-page .profit-table td:nth-child(3),
            .profit-page .profit-table th:nth-child(4),
            .profit-page .profit-table td:nth-child(4),
            .profit-page .profit-table th:nth-child(5),
            .profit-page .profit-table td:nth-child(5),
            .profit-page .profit-table th:nth-child(6),
            .profit-page .profit-table td:nth-child(6),
            .profit-page .profit-table th:nth-child(7),
            .profit-page .profit-table td:nth-child(7) {
                white-space: nowrap;
            }

            .profit-page .profit-table th:nth-child(1),
            .profit-page .profit-table td:nth-child(1) {
                min-width: 130px;
            }

            .profit-page .profit-table th:nth-child(2),
            .profit-page .profit-table td:nth-child(2) {
                min-width: 260px;
                white-space: normal;
                word-break: normal;
                overflow-wrap: break-word;
            }

            .profit-page .profit-table th:nth-child(3),
            .profit-page .profit-table td:nth-child(3) {
                min-width: 90px;
                text-align: center;
            }

            .profit-page .profit-table th:nth-child(4),
            .profit-page .profit-table td:nth-child(4),
            .profit-page .profit-table th:nth-child(5),
            .profit-page .profit-table td:nth-child(5),
            .profit-page .profit-table th:nth-child(6),
            .profit-page .profit-table td:nth-child(6) {
                min-width: 140px;
                text-align: right;
            }

            .profit-page .profit-table th:nth-child(7),
            .profit-page .profit-table td:nth-child(7) {
                min-width: 100px;
                text-align: right;
            }

            .profit-page #pagination {
                display: flex !important;
                align-items: center;
                justify-content: center !important;
                gap: 8px;
                width: 100%;
                max-width: 100%;
                margin-top: 12px !important;
                overflow-x: hidden;
            }

            .profit-page .profit-pagination-number {
                display: none;
            }

            .profit-page .profit-page-arrow,
            .profit-page .profit-pagination-status {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .profit-page .profit-page-arrow {
                width: 38px;
                min-width: 38px;
                height: 38px;
                padding: 0;
                font-size: 18px;
                line-height: 1;
            }

            .profit-page .profit-pagination-status {
                min-height: 38px;
                color: #495057;
                font-size: 13px;
                font-weight: 500;
                white-space: nowrap;
            }
        }
    </style>

    <div class="page-inner profit-page">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'BÁO CÁO'], ['label' => 'BÁO CÁO LỢI NHUẬN']]" />
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
                    <a href="#">BÁO CÁO LỢI NHUẬN</a>
                </li>
            </ul> --}}
        </div>



        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-header profit-title-header">
                        <h4 style="color: rgb(15, 0, 0); text-align: center" class="card-title profit-report-title">Báo cáo lợi nhuận</h4>
                    </div>
                    <div class="card-header d-flex justify-content-between align-items-center profit-toolbar">
                        <div class="search-container profit-date-field">
                            <input type="text" id="dateFilter" style="width: 350px" class="form-control search-input profit-date-input"
                                placeholder="Chọn khoảng ngày">
                        </div>

                        <div class="d-flex justify-content-end align-items-center profit-search-row">
                            <input type="search" name="search" class="form-control me-2 profit-search-input" style="width: 300px;"
                                placeholder="Tìm kiếm...">

                            <button type="button" class="btn profit-reset-btn" id="btn-reset"> <i
                                    class="fa-solid fa-rotate"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row profit-filter-row">
                            <div class="col-md-6 profit-filter-field">
                                <label for="storageSelect">Chọn kho:</label>
                                <select id="storageSelect" class="form-control">
                                    <option value="">--- Chọn kho ---</option>
                                    @foreach ($storages as $storage)
                                        <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 profit-filter-field">
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
                            </div>

                            <div class="modal fade" id="dateRangeModal" tabindex="-1" role="dialog"
                                aria-labelledby="dateRangeModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
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

                        <div class="profit-report-table-area">
                            <div
                                class="profit-item-count-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px">
                                <span id="itemCount">Số lượng mặt hàng: 0</span>
                                {{-- <div style="display: flex">
                                    <button id="exportPdf" class="btn btn-primary" style="display: none;">Xuất PDF</button>
                                </div> --}}
                            </div>

                            <p class="profit-table-hint">Vuốt ngang để xem đầy đủ báo cáo</p>
                            <div class="table-responsive profit-table-scroll">
                                <table class="table table-hover profit-table" id="reportTable">
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
                            <div id="pagination" class="d-flex justify-content-end mt-3 profit-pagination"></div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.17/jspdf.plugin.autotable.min.js"></script> --}}

    <script>
        $(document).ready(function() {
            $.ajax({
                url: '{{ route('admin.profit.getProfitReport') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    // var exportPdfBtn = $('#exportPdf');
                    updateTable(response.product);
                    // exportPdfBtn.show();
                }
            });
            $('#storageSelect').change(function() {
                var storageId = $('#storageSelect').val();
                var periodSelect = $('#periodSelect');
                var period = $('#periodSelect').val('');
                var tableBody = $('#reportTableBody');
                const paginationContainer = $('#pagination');
                paginationContainer.empty();

                var itemCount = $('#itemCount');
                var error = $('#error');
                tableBody.empty();
                itemCount.text('Số lượng mặt hàng: 0');

                if (storageId) {
                    periodSelect.off('change').on('change', function() {
                        var period = periodSelect.val();
                        if (period == 6) {
                            $('#dateRangeModal').modal('show');
                            $('#applyDateRange').click(function() {

                                var startDate = $('#startDate').val();
                                var endDate = $('#endDate').val();
                                if (startDate && endDate) {
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
                                            // exportPdfBtn.show();
                                        }
                                    });
                                } else {
                                    error.html('Nhập đủ thông tin !');
                                }

                            })

                        } else {
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
                                    // exportPdfBtn.show();
                                }
                            });
                        }

                        // exportPdfBtn.off('click').click(function() {
                        //     if (period == 6) {
                        //         var startDate = $('#startDate').val();
                        //         var endDate = $('#endDate').val();
                        //         $.ajax({
                        //             url: '{{ route('admin.profit.getProfitReportByFilterPDF') }}',
                        //             type: 'POST',
                        //             data: {
                        //                 _token: '{{ csrf_token() }}',
                        //                 storage_id: storageId,
                        //                 filter: period,
                        //                 startDate: startDate,
                        //                 endDate: endDate
                        //             },
                        //             xhrFields: {
                        //                 responseType: 'blob'
                        //             },
                        //             success: function(response) {
                        //                 var link = document.createElement('a');
                        //                 var url = window.URL.createObjectURL(
                        //                     response);
                        //                 link.href = url;
                        //                 link.download = 'profit_report.pdf';
                        //                 document.body.appendChild(link);
                        //                 link.click();
                        //                 window.URL.revokeObjectURL(url);
                        //                 document.body.removeChild(link);
                        //             }
                        //         });

                        //     } else {
                        //         $.ajax({
                        //             url: '{{ route('admin.profit.getProfitReportByFilterPDF') }}',
                        //             type: 'POST',
                        //             data: {
                        //                 _token: '{{ csrf_token() }}',
                        //                 storage_id: storageId,
                        //                 filter: period,
                        //             },
                        //             xhrFields: {
                        //                 responseType: 'blob'
                        //             },
                        //             success: function(response) {
                        //                 var link = document.createElement('a');
                        //                 var url = window.URL.createObjectURL(
                        //                     response);
                        //                 link.href = url;
                        //                 link.download = 'profit_report.pdf';
                        //                 document.body.appendChild(link);
                        //                 link.click();
                        //                 window.URL.revokeObjectURL(url);
                        //                 document.body.removeChild(link);
                        //             }
                        //         });
                        //     }

                        // })


                    });
                }
            });

            let currentPage = 1;
            const itemsPerPage = 10;
            let fullList = [];

            function updateTable(list) {
                fullList = list; // lưu danh sách đầy đủ để phân trang
                currentPage = 1;
                renderPage(currentPage);
                setupPagination();
            }

            function renderPage(page) {
                var error = $('#error');
                var tableBody = $('#reportTableBody');
                var itemCount = $('#itemCount');

                error.html('');
                tableBody.empty();
                itemCount.text('Số lượng mặt hàng: ' + fullList.length);

                let startIndex = (page - 1) * itemsPerPage;
                let endIndex = startIndex + itemsPerPage;
                let paginatedItems = fullList.slice(startIndex, endIndex);

                paginatedItems.forEach(function(item) {
                    if (!item.product) return;

                    var newRow = `
                    <tr>
                        <td>${item.product.code}</td>
                        <td>${item.product.name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.product.price_buy * item.quantity}</td>
                        <td>${item.product.price * item.quantity}</td>
                        <td>${item.product.price_buy * item.quantity - item.product.price * item.quantity }</td>
                        <td>${(100 * (item.product.price_buy * item.quantity - item.product.price * item.quantity) / (item.product.price_buy * item.quantity)).toFixed(2)}%</td>
                    </tr>`;
                    tableBody.append(newRow);
                });
            }

            function setupPagination() {
                const totalPages = Math.ceil(fullList.length / itemsPerPage);
                const paginationContainer = $('#pagination');
                paginationContainer.empty();

                const pageCount = Math.max(totalPages, 1);
                const prevBtn = $(
                    `<button type="button" class="btn btn-sm btn-outline-primary mx-1 profit-page-arrow" aria-label="Trang trước">‹</button>`
                );
                const nextBtn = $(
                    `<button type="button" class="btn btn-sm btn-outline-primary mx-1 profit-page-arrow" aria-label="Trang sau">›</button>`
                );
                const pageStatus = $(`<span class="profit-pagination-status">Trang ${currentPage} / ${pageCount}</span>`);

                prevBtn.prop('disabled', currentPage <= 1 || totalPages <= 1);
                nextBtn.prop('disabled', currentPage >= totalPages || totalPages <= 1);

                prevBtn.on('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        renderPage(currentPage);
                        setupPagination();
                    }
                });

                nextBtn.on('click', function() {
                    if (currentPage < totalPages) {
                        currentPage++;
                        renderPage(currentPage);
                        setupPagination();
                    }
                });

                paginationContainer.append(prevBtn, pageStatus, nextBtn);

                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = $(`<button class="btn btn-sm btn-outline-primary mx-1 profit-pagination-number">${i}</button>`);
                    if (i === currentPage) {
                        pageBtn.addClass('active');
                    }

                    pageBtn.on('click', function() {
                        currentPage = i;
                        renderPage(currentPage);
                        setupPagination();
                    });

                    paginationContainer.append(pageBtn);
                }
            }


            $('.close').click(function() {
                $('#dateRangeModal').modal('hide');
            });

            // Khởi tạo sự kiện cho storageSelect
            $('#storageSelect').trigger('change');
        });
    </script>
@endsection
@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
            let currentPage = 1
            let searchText = '';

            let start = moment().subtract(1, 'month');
            let end = moment();

            $('#dateFilter').daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: true,
                locale: {
                    format: 'DD/MM/YYYY',
                    cancelLabel: 'Hủy',
                    applyLabel: 'Áp dụng',
                    customRangeLabel: 'Tùy chọn',
                    daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                    monthNames: [
                        'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                        'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
                    ],
                    firstDay: 1
                },
                ranges: {
                    'Hôm nay': [moment(), moment()],
                    'Ngày mai': [moment().add(1, 'days'), moment().add(1, 'days')],
                    'Tuần này': [moment().startOf('week'), moment().endOf('week')],
                    'Tuần sau': [moment().add(1, 'week').startOf('week'), moment().add(1, 'week').endOf(
                        'week')],
                    'Tháng này': [moment().startOf('month'), moment().endOf('month')],
                    'Tháng sau': [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf(
                        'month')]
                }
            });

            // Hiển thị mặc định trên input khi load
            $('#dateFilter').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

            $('#dateFilter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                    'DD/MM/YYYY'));

                let dateRange = $(this).val();
                fetchOrders(1, searchText, dateRange);
            });

            $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

                let dateRange = $(this).val();
                fetchOrders(1, searchText, dateRange);
            });

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchOrders(page, searchText);
            });

            function debounce(fn, delay = 500) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchOrders(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                $('input[name="search"]').val('');
                fetchOrders()
            })

            const fetchOrders = (page = 1, search, dateRange) => {
                $.ajax({
                    url: window.location.pathname,
                    method: 'GET',
                    data: {
                        page,
                        s: search,
                        date_range: dateRange
                    },
                    success: (res) => {
                        $('#table-wrapper').html(res.html);
                    },
                    error: (xhr) => {
                        console.log(xhr);
                    }
                })
            }

            fetchOrders();
        })
    </script>
@endpush
@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endpush
