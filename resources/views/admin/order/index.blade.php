@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Đơn hàng']]" />

        <div class="card">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-xl-3 col-md-6 col-12">
                        <input type="text" id="dateFilter" class="form-control" placeholder="Chọn khoảng ngày"
                            autocomplete="off">
                    </div>

                    <div class="col-xl-2 col-md-3 col-12">
                        <select id="filter-status" class="form-select">
                            <option value="">-- Trạng thái --</option>
                            <option value="1">Đã thanh toán</option>
                            <option value="0">Công nợ</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-3 col-12">
                        <select id="filter-payment" class="form-select">
                            <option value="">-- Phương thức --</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="debt">Công nợ</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-10 col-10 ms-xl-auto">
                        <input type="search" id="order-search" class="form-control"
                            placeholder="Tìm mã đơn, khách hàng, số điện thoại..." autocomplete="off">
                    </div>

                    <div class="col-xl-1 col-md-2 col-2">
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
                '#table-wrapper .pagination a.page-link',
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
        #table-wrapper {
            transition: opacity 0.2s ease;
        }

        #table-wrapper .table th {
            white-space: nowrap;
        }
    </style>
@endpush
