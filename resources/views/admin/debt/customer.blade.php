@extends('admin.layout.index')

@section('content')
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
                    <span class="text-muted">công nợ khách hàng</span>
                </li>
            </ul>
        </div>

        <div class="card p-3 mb-3 shadow-sm">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <!-- Lọc ngày sang trái -->
        <div class="d-flex align-items-center mb-2">
            <input type="text" id="dateFilter" name="date_range" class="form-control" placeholder="Chọn khoảng ngày">
        </div>

        <!-- Tên khách hàng và nút Lọc sang phải -->
        <div class="d-flex align-items-center mb-2">
            <input type="text" class="form-control me-2" name="name" placeholder="Tên khách hàng">
            <button type="button" id="filter" class="btn btn-primary">
                <i class="bi bi-search"></i> Lọc
            </button>
        </div>
    </div>
</div>


        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center mb-0" id="customerDebtTable">
                <thead class="table-light align-middle">
                    <tr>
                        <th rowspan="3" style="width: 50px;">#</th>
                        <th rowspan="3">Khách hàng</th>
                        <th colspan="2">Số dư đầu kỳ</th>
                        <th colspan="2">Phát sinh trong kỳ</th>
                        <th colspan="2">Số dư cuối kỳ</th>
                    </tr>
                    <tr>
                        <th>Nợ [Phải thu]</th>
                        <th>Có [Phải trả]</th>
                        <th>Ghi nợ</th>
                        <th>Ghi có</th>
                        <th>Nợ [Phải thu] = 3 + 5 - 4 - 6</th>
                        <th>Có [Phải trả] = 4 + 6 - 3 - 5</th>
                    </tr>
                    <tr>
                        <th>[3]</th>
                        <th>[4]</th>
                        <th>[5]</th>
                        <th>[6]</th>
                        <th>[7]</th>
                        <th>[8]</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientDebts as $index => $debt)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-start">{{ $debt->client_name }} <br /> SDT: {{ $debt->client_phone }}</td>
                            <td class="text-end">{{ formatPrice($debt->opening_debit) }}</td>
                            <td class="text-end">{{ formatPrice($debt->opening_credit) }}</td>
                            <td class="text-end">{{ formatPrice($debt->period_debit) }}</td>
                            <td class="text-end">{{ formatPrice($debt->period_credit) }}</td>
                            <td class="text-end">{{ formatPrice($debt->ending_debit) }}</td>
                            <td class="text-end">{{ formatPrice($debt->ending_credit) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection

@push('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        let start = moment().subtract(1, 'month'); // 15/06/2025
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

        // $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
        //     $(this).val('');
        // });

        $('#filter').on('click', function() {
            let date_range = $('input[name="date_range"]').val();
            let name = $('input[name="name"]').val();

            $.ajax({
                url: '',
                type: "GET",
                data: {
                    date_range,
                    name
                },
                success: function(response) {
                    renderTable(response);
                },
                error: function() {
                    alert("Có lỗi xảy ra, vui lòng thử lại.");
                },
            });
        })

        function renderTable(data) {
            let tbody = '';

            data.forEach((debt, index) => {
                tbody += `
            <tr>
                <td>${index + 1}</td>
                <td class="text-start">${debt.client_phone} - ${debt.client_name}</td>
                <td class="text-end">${debt.opening_debit != 0 ? formatNumber(debt.opening_debit) : ''}</td>
                <td class="text-end">${debt.opening_credit != 0 ? formatNumber(debt.opening_credit) : ''}</td>
                <td class="text-end">${debt.period_debit != 0 ? formatNumber(debt.period_debit) : ''}</td>
                <td class="text-end">${debt.period_credit != 0 ? formatNumber(debt.period_credit) : ''}</td>
                <td class="text-end">${debt.ending_debit != 0 ? formatNumber(debt.ending_debit) : ''}</td>
                <td class="text-end">${debt.ending_credit != 0 ? formatNumber(debt.ending_credit) : ''}</td>
            </tr>`;
            });

            $('#customerDebtTable tbody').html(tbody);
        }
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endpush
