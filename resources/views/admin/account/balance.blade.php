@extends('admin.layout.index')

@section('content')
    <div class="page-inner">

        <x-breadcrumb :items="[['label' => 'báo cáo tổng hợp theo tài khoản']]" />

        <div class="card">
            <div class="card-header">
                <div class="row g-3 align-items-center">
                    <!-- Lọc ngày sang trái -->
                    <div class="col-md-3">
                        <input type="text" id="dateFilter" class="form-control" placeholder="Chọn khoảng ngày">
                    </div>

                    <!-- Khoảng trống hoặc dùng offset nếu muốn -->
                    <div class="col-md-3 ms-auto">
                        <input type="text" class="form-control" id="searchInput"
                            placeholder="Tìm kiếm mã hoặc tên tài khoản...">
                    </div>
                    <div class="col-auto">
                        <button type="button" id="filterButton" class="btn btn-primary">
                            <i class="bi bi-search"></i> Lọc
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table financial-table table-bordered">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 50px;">#</th>
                                <th rowspan="2" style="width: 100px;">Mã</th>
                                <th rowspan="2" style="width: 300px;">Tên</th>
                                <th colspan="2" style="width: 200px;">Số dư đầu kì</th>
                                <th colspan="2" style="width: 200px;">Phát sinh trong kì</th>
                                <th colspan="2" style="width: 200px;">Số dư cuối kì</th>
                            </tr>
                            <tr>
                                <th style="width: 100px;">Ghi nợ</th>
                                <th style="width: 100px;">Ghi có</th>
                                <th style="width: 100px;">Ghi nợ</th>
                                <th style="width: 100px;">Ghi có</th>
                                <th style="width: 100px;">Ghi nợ</th>
                                <th style="width: 100px;">Ghi có</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
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
            });

            $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            function fetchApi() {

                let dateRange = $('#dateFilter').val()
                let searchInput = $('#searchInput').val()

                $.ajax({
                    url: "",
                    type: "GET",
                    data: {
                        dateRange,
                        searchInput
                    },
                    beforeSend: function() {
                        $("#loadingSpinner").fadeIn();
                    },
                    success: function(res) {
                        if (res.success) {
                            $('table tbody').html(res.html);
                        }
                    },
                    error: function(xhr) {
                        Notifications('Tải dữ liệu thất bại', "error");
                    },
                    complete: function() {
                        $("#loadingSpinner").fadeOut();
                    }
                });

            }

            fetchApi()

            $('#filterButton').on('click', function() {
                fetchApi()
            })
        })
    </script>
@endpush


@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <style>
        .financial-table {
            font-size: 14px;
            border-collapse: collapse;
        }

        .financial-table th {
            background-color: #bcc4cb;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            padding: 12px 8px;
        }

        .financial-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            vertical-align: middle;
        }

        .financial-table .row-even {
            background-color: #f8f9fa;
        }

        .financial-table .row-odd {
            background-color: white;
        }

        .financial-table .total-row {
            background-color: #e9ecef;
            font-weight: 600;
        }

        .text-right-custom {
            text-align: right;
        }

        .code-column {
            color: #007bff;
            text-decoration: none;
        }

        .code-column:hover {
            text-decoration: underline;
        }

        .name-column {
            color: #007bff;
        }
    </style>
@endpush
