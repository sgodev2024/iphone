@extends('admin.layout.index')

@section('content')
    <div class="page-inner supplier-debt-page">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'CÔNG NỢ NHÀ CUNG CẤP']]" />
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
                    <span class="text-muted">CÔNG NỢ NHÀ CUNG CẤP</span>
                </li>
            </ul> --}}
        </div>

        <div class="card supplier-debt-filter-card p-3 mb-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center flex-wrap supplier-debt-filter">
                <!-- Lọc ngày sang trái -->
                <div class="d-flex align-items-center mb-2 supplier-debt-date">
                    <input type="text" id="dateFilter" name="date_range" class="form-control"
                        placeholder="Chọn khoảng ngày">
                </div>

                <!-- Tên nhà cung cấp và nút Lọc sang phải -->
                <div class="d-flex align-items-center mb-2 supplier-debt-search">
                    <input type="text" class="form-control me-2 supplier-debt-name" name="name" placeholder="Tên nhà cung cấp">
                    <button type="button" id="filter" class="btn btn-primary supplier-debt-filter-button">
                        <i class="bi bi-search"></i> <span>Lọc</span>
                    </button>
                </div>
            </div>
        </div>


        <div class="table-responsive supplier-debt-table-wrap">
            <table class="table table-bordered table-hover align-middle text-center mb-0 supplier-debt-table" id="supplierDebtTable">
                <colgroup>
                    <col class="col-stt">
                    <col class="col-supplier">
                    <col class="col-money">
                    <col class="col-money">
                    <col class="col-money">
                    <col class="col-money">
                    <col class="col-ending">
                    <col class="col-ending">
                </colgroup>
                <thead class="table-light align-middle">
                    <tr>
                        <th rowspan="3" style="width: 50px;">#</th>
                        <th rowspan="3">Đối tượng</th>
                        <th colspan="2"><span class="heading-nowrap">Số dư đầu kỳ</span></th>
                        <th colspan="2"><span class="heading-nowrap">Phát sinh trong kỳ</span></th>
                        <th colspan="2"><span class="heading-nowrap">Số dư cuối kỳ</span></th>
                    </tr>
                    <tr>
                        <th><span class="heading-nowrap">Nợ (Phải thu)</span></th>
                        <th><span class="heading-nowrap">Có (Phải trả)</span></th>
                        <th><span class="heading-nowrap">Ghi nợ</span></th>
                        <th><span class="heading-nowrap">Ghi có</span></th>
                        <th>
                            <span class="heading-nowrap">Nợ (Phải thu)</span>
                            <span class="header-formula">= 3 + 5 - 4 - 6</span>
                        </th>
                        <th>
                            <span class="heading-nowrap">Có (Phải trả)</span>
                            <span class="header-formula">= 4 + 6 - 3 - 5</span>
                        </th>
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
                    @forelse($supplierDebts as $index => $debt)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-start supplier-cell">
                                <span class="supplier-name">{{ $debt->supplier_name }}</span><br>
                                <span class="supplier-phone">SĐT: {{ $debt->supplier_phone ?: '—' }}</span>
                            </td>
                            <td class="text-end money-cell">{{ formatPrice($debt->opening_debit) }}</td>
                            <td class="text-end money-cell">{{ formatPrice($debt->opening_credit) }}</td>
                            <td class="text-end money-cell">{{ formatPrice($debt->period_debit) }}</td>
                            <td class="text-end money-cell">{{ formatPrice($debt->period_credit) }}</td>
                            <td class="text-end money-cell">{{ formatPrice($debt->ending_debit) }}</td>
                            <td class="text-end money-cell">{{ formatPrice($debt->ending_credit) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu</td>
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
                'Tuần sau': [moment().add(1, 'week').startOf('week'), moment().add(1, 'week').endOf('week')],
                'Tháng này': [moment().startOf('month'), moment().endOf('month')],
                'Tháng sau': [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')]
            }
        });

        $('#dateFilter').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

        $('#dateFilter').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        });

        $('#filter').on('click', function() {
            let date_range = $('input[name="date_range"]').val();
            let name = $('input[name="name"]').val();

            $.ajax({
                url: '', // dùng current URL
                type: "GET",
                data: {
                    date_range,
                    name: name
                },
                success: function(response) {
                    renderTable(response);
                },
                error: function() {
                    alert("Có lỗi xảy ra, vui lòng thử lại.");
                },
            });
        });

        function renderTable(data) {
            let tbody = '';
            if (data.length === 0) {
                tbody = `<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>`;
            } else {
                data.forEach((debt, index) => {
                    const supplierName = escapeHtml(debt.supplier_name || '');
                    const supplierPhone = escapeHtml(debt.supplier_phone || '—');

                    tbody += `
                    <tr>
                        <td>${index + 1}</td>
                        <td class="text-start supplier-cell">
                            <span class="supplier-name">${supplierName}</span><br/>
                            <span class="supplier-phone">SĐT: ${supplierPhone}</span>
                        </td>
                        <td class="text-end money-cell">${formatDebtPrice(debt.opening_debit)}</td>
                        <td class="text-end money-cell">${formatDebtPrice(debt.opening_credit)}</td>
                        <td class="text-end money-cell">${formatDebtPrice(debt.period_debit)}</td>
                        <td class="text-end money-cell">${formatDebtPrice(debt.period_credit)}</td>
                        <td class="text-end money-cell">${formatDebtPrice(debt.ending_debit)}</td>
                        <td class="text-end money-cell">${formatDebtPrice(debt.ending_credit)}</td>
                    </tr>`;
                });
            }

            $('#supplierDebtTable tbody').html(tbody);
        }

        function formatDebtPrice(amount) {
            const value = Number(amount) || 0;

            if (Math.floor(value) === value) {
                return new Intl.NumberFormat('de-DE', {
                    maximumFractionDigits: 0
                }).format(value);
            }

            return new Intl.NumberFormat('de-DE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        @media (max-width: 767.98px) {
            .supplier-debt-page,
            .supplier-debt-page * {
                box-sizing: border-box;
            }

            .supplier-debt-page {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px;
                padding-left: 10px;
                overflow-x: hidden;
            }

            .supplier-debt-page .page-header {
                margin-left: 0;
                margin-right: 0;
            }

            .supplier-debt-page .breadcrumb,
            .supplier-debt-page .breadcrumbs {
                margin-left: 0;
            }

            .supplier-debt-page .supplier-debt-filter-card,
            .supplier-debt-page .supplier-debt-table-wrap {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
            }

            .supplier-debt-page .supplier-debt-filter {
                flex-direction: column;
                align-items: stretch !important;
                gap: 8px;
            }

            .supplier-debt-page .supplier-debt-date,
            .supplier-debt-page .supplier-debt-search {
                width: 100%;
                margin-bottom: 0 !important;
            }

            .supplier-debt-page .supplier-debt-date .form-control,
            .supplier-debt-page .supplier-debt-search .form-control,
            .supplier-debt-page .supplier-debt-filter-button {
                min-height: 40px;
            }

            .supplier-debt-page .supplier-debt-search {
                gap: 8px;
            }

            .supplier-debt-page .supplier-debt-name {
                flex: 1 1 auto;
                min-width: 0;
                margin-right: 0 !important;
            }

            .supplier-debt-page .supplier-debt-filter-button {
                flex: 0 0 auto;
                padding-right: 14px;
                padding-left: 14px;
                white-space: nowrap;
            }

            .supplier-debt-page .supplier-debt-table-wrap {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .supplier-debt-page .supplier-debt-table {
                min-width: 1280px;
                table-layout: auto;
            }

            .supplier-debt-page .supplier-debt-table col.col-stt {
                width: 50px;
            }

            .supplier-debt-page .supplier-debt-table col.col-supplier {
                width: 210px;
            }

            .supplier-debt-page .supplier-debt-table col.col-money {
                width: 140px;
            }

            .supplier-debt-page .supplier-debt-table col.col-ending {
                width: 240px;
            }

            .supplier-debt-page .supplier-debt-table th,
            .supplier-debt-page .supplier-debt-table td {
                padding-right: 10px;
                padding-left: 10px;
                vertical-align: middle;
                word-break: normal;
                overflow-wrap: normal;
            }

            .supplier-debt-page .supplier-debt-table th {
                text-align: center;
            }

            .supplier-debt-page .heading-nowrap,
            .supplier-debt-page .header-formula,
            .supplier-debt-page .money-cell {
                white-space: nowrap;
            }

            .supplier-debt-page .header-formula {
                display: block;
                margin-top: 2px;
                font-weight: 500;
            }

            .supplier-debt-page .supplier-cell {
                min-width: 190px;
            }

            .supplier-debt-page .supplier-name,
            .supplier-debt-page .supplier-phone {
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .supplier-debt-page .money-cell {
                text-align: right;
            }

            .supplier-debt-page nav[role="navigation"],
            .supplier-debt-page .pagination {
                justify-content: center;
            }
        }

        @media (max-width: 767.98px) and (max-width: 340px) {
            .supplier-debt-page .supplier-debt-search {
                flex-wrap: wrap;
            }

            .supplier-debt-page .supplier-debt-filter-button {
                width: 100%;
            }
        }
    </style>
@endpush
