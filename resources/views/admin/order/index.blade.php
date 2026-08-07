@extends('admin.layout.index')

@section('content')
    <div class="page-inner order-page">
        <x-breadcrumb :items="[['label' => 'Đơn hàng']]" />

        <div class="card">
            <div class="card-header">
                <div class="row g-2 align-items-center order-filter-row">
                    <div class="col-xl-3 col-md-6 col-12 order-filter-date">
                        <input type="text" id="dateFilter" class="form-control" placeholder="Chọn khoảng ngày"
                            autocomplete="off">
                    </div>

                    <div class="col-xl-2 col-md-3 col-12 order-filter-status">
                        <select id="filter-status" class="form-select">
                            <option value="">-- Trạng thái --</option>
                            <option value="1">Đã thanh toán</option>
                            <option value="0">Công nợ</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-3 col-12 order-filter-payment">
                        <select id="filter-payment" class="form-select">
                            <option value="">-- Phương thức --</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="debt">Công nợ</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-10 col-10 ms-xl-auto order-filter-search">
                        <input type="search" id="order-search" class="form-control"
                            placeholder="Tìm mã đơn, khách hàng, số điện thoại..." autocomplete="off">
                    </div>

                    <div class="col-xl-1 col-md-2 col-2 order-filter-reset">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btn-reset"
                            title="Đặt lại bộ lọc">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div id="table-wrapper">
                    <div class="text-center py-5">
                        <span class="spinner-border spinner-border-sm"></span>
                        Đang tải dữ liệu...
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
            const orderIndexUrl = @json(route('admin.order.index'));
            const $tableWrapper = $('#table-wrapper');
            const $dateFilter = $('#dateFilter');

            let currentRequest = null;

            const pickerStart = moment().subtract(1, 'month');
            const pickerEnd = moment();

            $dateFilter.daterangepicker({
                startDate: pickerStart,
                endDate: pickerEnd,
                parentEl: '.order-page',

                // Để trống ban đầu nhằm hiển thị toàn bộ đơn hàng
                autoUpdateInput: false,

                locale: {
                    format: 'DD/MM/YYYY',
                    cancelLabel: 'Xóa lọc',
                    applyLabel: 'Áp dụng',
                    customRangeLabel: 'Tùy chọn',
                    daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                    monthNames: [
                        'Tháng 1',
                        'Tháng 2',
                        'Tháng 3',
                        'Tháng 4',
                        'Tháng 5',
                        'Tháng 6',
                        'Tháng 7',
                        'Tháng 8',
                        'Tháng 9',
                        'Tháng 10',
                        'Tháng 11',
                        'Tháng 12'
                    ],
                    firstDay: 1
                },

                ranges: {
                    'Hôm nay': [
                        moment().startOf('day'),
                        moment().endOf('day')
                    ],
                    '7 ngày gần đây': [
                        moment().subtract(6, 'days').startOf('day'),
                        moment().endOf('day')
                    ],
                    '30 ngày gần đây': [
                        moment().subtract(29, 'days').startOf('day'),
                        moment().endOf('day')
                    ],
                    'Tuần này': [
                        moment().startOf('week'),
                        moment().endOf('week')
                    ],
                    'Tháng này': [
                        moment().startOf('month'),
                        moment().endOf('month')
                    ]
                }
            });

            function debounce(callback, delay = 500) {
                let timer;

                return function(...args) {
                    clearTimeout(timer);

                    timer = setTimeout(() => {
                        callback.apply(this, args);
                    }, delay);
                };
            }

            function fetchOrders(page = 1) {
                if (currentRequest) {
                    currentRequest.abort();
                }

                const requestData = {
                    page: page,
                    s: $('#order-search').val().trim(),
                    date_range: $dateFilter.val().trim(),
                    status: $('#filter-status').val(),
                    payment_method: $('#filter-payment').val()
                };

                $tableWrapper.css({
                    opacity: 0.55,
                    pointerEvents: 'none'
                });

                currentRequest = $.ajax({
                    url: orderIndexUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: requestData,

                    success: function(response) {
                        if (!response || typeof response.html === 'undefined') {
                            $tableWrapper.html(
                                '<div class="alert alert-warning mb-0">' +
                                'Máy chủ không trả về dữ liệu bảng.' +
                                '</div>'
                            );

                            return;
                        }

                        $tableWrapper.html(response.html);
                    },

                    error: function(xhr, textStatus) {
                        if (textStatus === 'abort') {
                            return;
                        }

                        let message = 'Không thể tải danh sách đơn hàng.';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        $tableWrapper.html(
                            '<div class="alert alert-danger mb-0">' +
                            message +
                            '</div>'
                        );

                        console.error(xhr.responseText);
                    },

                    complete: function() {
                        currentRequest = null;

                        $tableWrapper.css({
                            opacity: 1,
                            pointerEvents: 'auto'
                        });
                    }
                });
            }

            $dateFilter.on('apply.daterangepicker', function(event, picker) {
                $(this).val(
                    picker.startDate.format('DD/MM/YYYY') +
                    ' - ' +
                    picker.endDate.format('DD/MM/YYYY')
                );

                fetchOrders(1);
            });

            $dateFilter.on('cancel.daterangepicker', function() {
                $(this).val('');
                fetchOrders(1);
            });

            $('#order-search').on('input', debounce(function() {
                fetchOrders(1);
            }));

            $('#filter-status, #filter-payment').on('change', function() {
                fetchOrders(1);
            });

            $(document).on(
                'click',
                '#table-wrapper .pagination a.page-link, #table-wrapper .order-pagination-mobile a.page-link',
                function(event) {
                    event.preventDefault();

                    const href = $(this).attr('href');

                    if (!href) {
                        return;
                    }

                    const url = new URL(href, window.location.origin);
                    const page = Number(url.searchParams.get('page')) || 1;

                    fetchOrders(page);
                }
            );

            $('#btn-reset').on('click', function() {
                $('#order-search').val('');
                $('#filter-status').val('');
                $('#filter-payment').val('');
                $dateFilter.val('');

                const picker = $dateFilter.data('daterangepicker');

                if (picker) {
                    picker.setStartDate(pickerStart);
                    picker.setEndDate(pickerEnd);
                }

                fetchOrders(1);
            });

            // Ban đầu hiển thị toàn bộ đơn hàng
            fetchOrders(1);
        });
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <style>
        .order-page #table-wrapper {
            transition: opacity 0.2s ease;
        }

        .order-page #table-wrapper .table th {
            white-space: nowrap;
        }

        .order-page .order-table-hint,
        .order-page .order-pagination-mobile {
            display: none;
        }

        @media (max-width: 767.98px) {
            .order-page {
                box-sizing: border-box;
                max-width: 100%;
                padding-left: 10px !important;
                padding-right: 10px !important;
            }

            .order-page .breadcrumb {
                margin-left: 0;
                margin-right: 0;
            }

            .order-page .card {
                width: 100%;
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .order-page .card-header,
            .order-page .card-body {
                padding-left: 10px;
                padding-right: 10px;
            }

            .order-page .order-filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-left: 0;
                margin-right: 0;
            }

            .order-page .order-filter-row > [class*="col-"] {
                padding-left: 0;
                padding-right: 0;
            }

            .order-page .order-filter-date,
            .order-page .order-filter-status,
            .order-page .order-filter-payment {
                flex: 0 0 100%;
                max-width: 100%;
                width: 100%;
            }

            .order-page .order-filter-search {
                flex: 1 1 0;
                max-width: calc(100% - 50px);
                min-width: 0;
                width: auto;
            }

            .order-page .order-filter-reset {
                flex: 0 0 42px;
                max-width: 42px;
                width: 42px;
            }

            .order-page #dateFilter,
            .order-page #filter-status,
            .order-page #filter-payment,
            .order-page #order-search,
            .order-page #btn-reset {
                height: 40px;
                min-height: 40px;
            }

            .order-page #filter-status,
            .order-page #filter-payment,
            .order-page #order-search,
            .order-page #dateFilter {
                max-width: 100%;
                min-width: 0;
                font-size: 14px;
                text-overflow: ellipsis;
            }

            .order-page #btn-reset {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                width: 42px;
            }

            .order-page .daterangepicker {
                left: 10px !important;
                right: auto !important;
                max-width: calc(100vw - 20px) !important;
            }

            .order-page .daterangepicker,
            .order-page .daterangepicker .drp-calendar,
            .order-page .daterangepicker .calendar-table {
                width: calc(100vw - 20px) !important;
                max-width: calc(100vw - 20px) !important;
            }

            .order-page .daterangepicker .calendar-table table {
                width: 100% !important;
                min-width: 0 !important;
            }

            .order-page .order-table-hint {
                display: block;
                margin: 0 0 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.4;
            }

            .order-page .order-table-scroll {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .order-page .order-table-scroll .table {
                display: table !important;
                width: 100% !important;
                min-width: 1200px;
                table-layout: auto;
            }

            .order-page .order-table-scroll thead {
                display: table-header-group !important;
            }

            .order-page .order-table-scroll tbody {
                display: table-row-group !important;
            }

            .order-page .order-table-scroll tr {
                display: table-row !important;
                width: auto !important;
                margin-bottom: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            .order-page .order-table-scroll th,
            .order-page .order-table-scroll td {
                display: table-cell !important;
                width: auto !important;
                vertical-align: middle;
            }

            .order-page .order-table-scroll td::before {
                content: none !important;
                display: none !important;
            }

            .order-page .order-col-created,
            .order-page .order-col-code,
            .order-page .order-col-employee,
            .order-page .order-col-quantity,
            .order-page .order-col-payment,
            .order-page .order-col-status,
            .order-page .order-col-total {
                white-space: nowrap;
            }

            .order-page .order-col-created,
            .order-page .order-col-code,
            .order-page .order-col-employee,
            .order-page .order-col-customer,
            .order-page .order-col-payment,
            .order-page .order-col-status {
                text-align: left !important;
            }

            .order-page .order-col-quantity {
                text-align: center !important;
            }

            .order-page .order-col-total {
                text-align: right !important;
            }

            .order-page .order-col-created {
                min-width: 140px;
            }

            .order-page .order-col-code {
                min-width: 150px;
            }

            .order-page .order-col-employee {
                min-width: 160px;
            }

            .order-page .order-col-customer {
                min-width: 220px;
            }

            .order-page .order-col-quantity {
                min-width: 130px;
            }

            .order-page .order-col-payment {
                min-width: 190px;
            }

            .order-page .order-col-status {
                min-width: 140px;
            }

            .order-page .order-col-total {
                min-width: 150px;
            }

            .order-page #pagination {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }

            .order-page .order-pagination-desktop {
                display: none;
            }

            .order-page .order-pagination-mobile {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                width: 100%;
            }

            .order-page .order-pagination-mobile .page-link,
            .order-page .order-pagination-mobile .page-disabled,
            .order-page .order-page-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 36px;
            }

            .order-page .order-pagination-mobile .page-link,
            .order-page .order-pagination-mobile .page-disabled {
                width: 38px;
                padding: 0;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                background: #fff;
            }

            .order-page .order-pagination-mobile .page-disabled {
                color: #adb5bd;
            }

            .order-page .order-page-count {
                color: #495057;
                font-size: 13px;
                font-weight: 500;
                white-space: nowrap;
            }
        }
    </style>
@endpush
