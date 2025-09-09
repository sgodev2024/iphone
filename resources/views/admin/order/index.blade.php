@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Đơn hàng']]" />

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="row g-2 w-100">
                    <!-- Ô lọc ngày -->
                    <div class="col-md-3 col-12">
                        <input type="text" id="dateFilter" class="form-control search-input" placeholder="Chọn khoảng ngày">
                    </div>

                    <!-- Trạng thái & Phương thức -->
                    <div class="col-md-4 col-12 d-flex gap-2">
                        <select id="filter-status" class="form-control">
                            <option value="">-- Trạng thái --</option>
                            <option value="1">Đã thanh toán</option>
                            <option value="0">Công nợ</option>
                        </select>

                        <select id="filter-payment" class="form-control">
                            <option value="">-- Phương thức --</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="debt">Công nợ</option>
                        </select>
                    </div>
                </div>


                <div class="d-flex justify-content-end align-items-center">
                    <input type="search" name="search" class="form-control me-2" style="width: 250px;"
                        placeholder="Tìm kiếm...">

                    <button type="button" class="btn" id="btn-reset">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="table-wrapper">
                </div>
            </div>
        </div>
    </div>
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

            $('#dateFilter').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

            const fetchOrders = (page = 1, search, dateRange) => {
                let status = $('#filter-status').val();
                let paymentMethod = $('#filter-payment').val();

                $.ajax({
                    url: window.location.pathname,
                    method: 'GET',
                    data: {
                        page,
                        s: search,
                        date_range: dateRange,
                        status: status,
                        payment_method: paymentMethod
                    },
                    success: (res) => {
                        $('#table-wrapper').html(res.html);
                    },
                    error: (xhr) => {
                        console.log(xhr);
                    }
                })
            }

            $('#dateFilter').on('apply.daterangepicker cancel.daterangepicker', function() {
                fetchOrders(1, searchText, $(this).val());
            });

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");
                fetchOrders(page, searchText, $('#dateFilter').val());
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
                fetchOrders(1, searchText, $('#dateFilter').val());
            }));

            $('#filter-status, #filter-payment').on('change', function() {
                fetchOrders(1, searchText, $('#dateFilter').val());
            });

            $('#btn-reset').click(function() {
                $('input[name="search"]').val('');
                $('#filter-status').val('');
                $('#filter-payment').val('');
                $('#dateFilter').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                fetchOrders();
            });

            fetchOrders();
        })
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endpush
