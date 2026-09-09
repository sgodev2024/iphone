@extends('Themes.layout_staff.app')

@section('content')
    <div class='container-fluid px-3 mt-3 return-history-page'>
        <div class='card shadow-sm'>
            <div class='card-header d-flex align-items-center justify-content-between gap-3 flex-wrap'>
                <div class='d-flex align-items-center gap-3'>
                    <a href='{{ route('staff.order') }}' class='btn btn-outline-danger btn-sm'>
                        <i class='fa-solid fa-arrow-left'></i>
                    </a>
                    <div>
                        <h4 class='card-title mb-0'>Lịch sử đổi / trả hàng</h4>
                        <div class='text-muted small'>Tra cứu các phiếu RTN đã phát sinh</div>
                    </div>
                </div>
            </div>

            <div class='card-body'>
                <div class='row g-2 align-items-end mb-3'>
                    <div class='col-lg-4 col-md-6'>
                        <label for='returnDateFilter' class='form-label small mb-1'>Ngày trả</label>
                        <input
                            type='text'
                            id='returnDateFilter'
                            class='form-control'
                            value='{{ request('date_range') }}'
                            placeholder='Chọn khoảng ngày'
                            autocomplete='off'
                        >
                    </div>

                    <div class='col-lg-5 col-md-6'>
                        <label for='returnSearch' class='form-label small mb-1'>Tìm kiếm</label>
                        <input
                            type='search'
                            id='returnSearch'
                            class='form-control'
                            value='{{ request('s') }}'
                            placeholder='Mã RTN, mã đơn gốc, tên hoặc số điện thoại khách hàng'
                        >
                    </div>

                    <div class='col-lg-3 d-flex gap-2'>
                        <button type='button' class='btn btn-primary flex-grow-1' id='returnSearchButton'>
                            <i class='fa-solid fa-magnifying-glass me-1'></i>
                            Tìm kiếm
                        </button>
                        <button type='button' class='btn btn-outline-secondary' id='returnResetButton' title='Đặt lại'>
                            <i class='fa-solid fa-rotate'></i>
                        </button>
                    </div>
                </div>

                <div id='returnTableWrapper'>
                    @include('Themes.pages.order.returns.table', ['orderReturns' => $orderReturns])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css'>
    <style>
        .return-history-page table th {
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .return-history-page table td {
            vertical-align: middle;
        }
    </style>
@endpush

@push('script')
    <script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js'></script>
    <script>
        $(function() {
            const listUrl = @json(route('staff.returns.index'));
            let request = null;

            $('#returnDateFilter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: 'DD/MM/YYYY',
                    cancelLabel: 'Xóa',
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
                    '7 ngày qua': [moment().subtract(6, 'days'), moment()],
                    '30 ngày qua': [moment().subtract(29, 'days'), moment()],
                    'Tháng này': [moment().startOf('month'), moment().endOf('month')]
                }
            });

            $('#returnDateFilter').on('apply.daterangepicker', function(event, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                fetchReturns(1);
            });

            $('#returnDateFilter').on('cancel.daterangepicker', function() {
                $(this).val('');
                fetchReturns(1);
            });

            function fetchReturns(page) {
                if (request) {
                    request.abort();
                }

                request = $.ajax({
                    url: listUrl,
                    method: 'GET',
                    data: {
                        page: page || 1,
                        s: $('#returnSearch').val().trim(),
                        date_range: $('#returnDateFilter').val().trim()
                    },
                    success: function(response) {
                        $('#returnTableWrapper').html(response.html);
                    },
                    error: function(xhr) {
                        if (xhr.status !== 0) {
                            const message = xhr.responseJSON?.message || 'Không thể tải lịch sử đổi / trả hàng.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', text: message });
                            }
                        }
                    },
                    complete: function() {
                        request = null;
                    }
                });
            }

            $('#returnSearchButton').on('click', function() {
                fetchReturns(1);
            });

            $('#returnSearch').on('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    fetchReturns(1);
                }
            });

            $('#returnResetButton').on('click', function() {
                $('#returnSearch').val('');
                $('#returnDateFilter').val('');
                fetchReturns(1);
            });

            $(document).on('click', '#returnTableWrapper a.page-link', function(event) {
                event.preventDefault();
                const page = new URL($(this).attr('href'), window.location.origin).searchParams.get('page');
                fetchReturns(page);
            });
        });
    </script>
@endpush
