@extends('Themes.layout_staff.app')

@section('content')
    <div class="container-fluid mx-2 mt-3">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3">
                <a href="/ban-hang  " class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-backward"></i></a>
                <h4 class="card-title mb-0">{{ $title }}</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div class="search-container">
                        <input type="text" id="dateFilter" style="width: 350px" class="form-control search-input"
                            placeholder="Chọn khoảng ngày">
                    </div>

                    <div class="d-flex justify-content-end align-items-center">
                        <input type="text" name="search" class="form-control me-2" style="width: 300px;"
                            placeholder="Nhập tên hoặc mã đơn hàng...">

                        <button type="button" class="btn btn-outline-secondary" id="btn-reset"> <i
                                class="fa-solid fa-rotate"></i></button>
                    </div>
                </div>

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

    <style>
        table tr th {
            font-size: 12px;
            text-transform: uppercase;
        }
    </style>
@endpush
