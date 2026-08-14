@extends('admin.layout.index')

@section('content')
    <div class="page-inner supplier-debt-page">
        <x-breadcrumb :items="[['label' => 'CÔNG NỢ NHÀ CUNG CẤP']]" />

        <div class="card supplier-debt-filter-card p-3 mb-3 shadow-sm">
            <form id="supplierDebtFilterForm" class="supplier-debt-filter" method="GET"
                action="{{ route('admin.debts.supplier') }}">
                <div class="supplier-debt-date">
                    <input type="text" id="dateFilter" class="form-control"
                        value="{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}"
                        placeholder="Chọn khoảng ngày">
                    <input type="hidden" id="fromDate" name="from_date" value="{{ $startDate }}">
                    <input type="hidden" id="toDate" name="to_date" value="{{ $endDate }}">
                </div>

                <div class="supplier-debt-search">
                    <input type="text" class="form-control supplier-debt-name" name="name"
                        value="{{ request()->query('name', '') }}" placeholder="Tên công ty">
                    <button type="submit" id="filter" class="btn btn-primary supplier-debt-filter-button">
                        <i class="bi bi-search"></i> <span>Lọc</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="small text-muted mb-2">
            TK331: Có cuối kỳ = còn phải trả NCC; Nợ cuối kỳ = số đã ứng trước/trả thừa.
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
                        <th rowspan="3">#</th>
                        <th rowspan="3">Công ty</th>
                        <th colspan="2"><span class="heading-nowrap">Số dư đầu kỳ</span></th>
                        <th colspan="2"><span class="heading-nowrap">Phát sinh trong kỳ</span></th>
                        <th colspan="2"><span class="heading-nowrap">Số dư cuối kỳ</span></th>
                    </tr>
                    <tr>
                        <th><span class="heading-nowrap">Nợ đầu kỳ</span><small class="header-help">Ứng trước NCC</small></th>
                        <th><span class="heading-nowrap">Có đầu kỳ</span><small class="header-help">Còn phải trả NCC</small></th>
                        <th><span class="heading-nowrap">Phát sinh Nợ</span><small class="header-help">Đã trả NCC</small></th>
                        <th><span class="heading-nowrap">Phát sinh Có</span><small class="header-help">Tăng nợ do nhập hàng</small></th>
                        <th>
                            <span class="heading-nowrap">Nợ cuối kỳ</span>
                            <span class="header-formula">3 + 5 - 4 - 6</span>
                        </th>
                        <th>
                            <span class="heading-nowrap">Có cuối kỳ</span>
                            <span class="header-formula">4 + 6 - 3 - 5</span>
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
                    @forelse ($supplierDebts as $index => $debt)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-start supplier-cell">
                                <span class="supplier-name">{{ $debt->company_name }}</span>
                                <span class="supplier-phone">SĐT: {{ $debt->company_phone ?: '—' }}</span>
                            </td>
                            <td class="text-end money-cell">{{ formatExactMoney((string) $debt->opening_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney((string) $debt->opening_credit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney((string) $debt->period_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney((string) $debt->period_credit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney((string) $debt->ending_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney((string) $debt->ending_credit) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="debt-total-row">
                        <td></td>
                        <th scope="row" class="text-start">TỔNG CỘNG</th>
                        <td class="text-end money-cell">{{ formatExactMoney($totals['opening_debit']) }}</td>
                        <td class="text-end money-cell">{{ formatExactMoney($totals['opening_credit']) }}</td>
                        <td class="text-end money-cell">{{ formatExactMoney($totals['period_debit']) }}</td>
                        <td class="text-end money-cell">{{ formatExactMoney($totals['period_credit']) }}</td>
                        <td class="text-end money-cell">{{ formatExactMoney($totals['ending_debit']) }}</td>
                        <td class="text-end money-cell">{{ formatExactMoney($totals['ending_credit']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@push('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript"
        src="{{ asset('global/js/debt-date-range-picker.js') }}?v={{ filemtime(public_path('global/js/debt-date-range-picker.js')) }}"></script>
    <script>
        initializeDebtDateRangePicker({
            inputSelector: '#dateFilter',
            formSelector: '#supplierDebtFilterForm',
            fromSelector: '#fromDate',
            toSelector: '#toDate',
            initialStart: @json($startDate),
            initialEnd: @json($endDate),
            today: @json(now()->toDateString())
        });
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .supplier-debt-page,
        .supplier-debt-page * {
            box-sizing: border-box;
        }

        .supplier-debt-page {
            max-width: 100%;
            overflow-x: hidden;
        }

        .supplier-debt-page .supplier-debt-filter-card,
        .supplier-debt-page .supplier-debt-table-wrap {
            width: 100%;
            max-width: 100%;
        }

        .supplier-debt-page .supplier-debt-filter {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .supplier-debt-page .supplier-debt-date,
        .supplier-debt-page .supplier-debt-search {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .supplier-debt-page .supplier-debt-search {
            gap: 8px;
        }

        .supplier-debt-page .supplier-debt-filter-button {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .supplier-debt-page .supplier-debt-table-wrap {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .supplier-debt-page .supplier-debt-table {
            width: 100%;
            min-width: 1150px;
            max-width: none;
            table-layout: auto;
        }

        .supplier-debt-page .supplier-debt-table col.col-stt {
            width: 50px;
        }

        .supplier-debt-page .supplier-debt-table col.col-supplier {
            width: 220px;
        }

        .supplier-debt-page .supplier-debt-table col.col-money {
            width: 140px;
        }

        .supplier-debt-page .supplier-debt-table col.col-ending {
            width: 160px;
        }

        .supplier-debt-page .supplier-debt-table th,
        .supplier-debt-page .supplier-debt-table td {
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: normal;
            padding-top: 0.45rem;
            padding-bottom: 0.45rem;
        }

        .supplier-debt-page .supplier-debt-table th {
            text-align: center;
        }

        .supplier-debt-page .heading-nowrap,
        .supplier-debt-page .header-formula,
        .supplier-debt-page .money-cell {
            white-space: nowrap;
        }

        .supplier-debt-page .header-help {
            display: block;
            margin-top: 2px;
            color: #6c757d;
            font-size: 0.72rem;
            font-weight: 400;
            line-height: 1.2;
            white-space: nowrap;
        }

        .supplier-debt-page .header-formula {
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }

        .supplier-debt-page .supplier-cell {
            min-width: 220px;
            white-space: nowrap;
        }

        .supplier-debt-page .supplier-name,
        .supplier-debt-page .supplier-phone {
            display: inline;
            white-space: inherit;
        }

        .supplier-debt-page .supplier-name {
            font-weight: 600;
        }

        .supplier-debt-page .supplier-phone {
            margin-left: 0.75rem;
            color: #6c757d;
            font-weight: 400;
        }

        .supplier-debt-page .money-cell {
            text-align: right;
        }

        .supplier-debt-page .debt-total-row th,
        .supplier-debt-page .debt-total-row td {
            background-color: #f3f4f6;
            border-top: 2px solid #adb5bd;
            font-weight: 700;
        }

        @media (max-width: 767.98px) {
            .supplier-debt-page {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px;
                padding-left: 10px;
            }

            .supplier-debt-page .supplier-debt-filter {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .supplier-debt-page .supplier-debt-date,
            .supplier-debt-page .supplier-debt-search {
                width: 100%;
                margin-bottom: 0;
            }

            .supplier-debt-page .supplier-debt-date .form-control,
            .supplier-debt-page .supplier-debt-search .form-control,
            .supplier-debt-page .supplier-debt-filter-button {
                min-height: 40px;
            }

            .supplier-debt-page .supplier-debt-name {
                flex: 1 1 auto;
                min-width: 0;
            }

            .supplier-debt-page .supplier-debt-filter-button {
                flex: 0 0 auto;
                padding-right: 14px;
                padding-left: 14px;
            }

            .supplier-debt-page .supplier-debt-table {
                min-width: 1150px;
            }

            .supplier-debt-page .supplier-debt-table th,
            .supplier-debt-page .supplier-debt-table td {
                padding-right: 10px;
                padding-left: 10px;
            }

            .supplier-debt-page .supplier-debt-table th:nth-child(1),
            .supplier-debt-page .supplier-debt-table td:nth-child(1) {
                min-width: 50px;
            }

            .supplier-debt-page .supplier-debt-table th:nth-child(2),
            .supplier-debt-page .supplier-debt-table td:nth-child(2) {
                min-width: 220px;
            }

            .supplier-debt-page .supplier-debt-table tbody td:nth-child(n+3) {
                min-width: 140px;
            }

            .supplier-debt-page .supplier-debt-table tbody td:nth-child(7),
            .supplier-debt-page .supplier-debt-table tbody td:nth-child(8) {
                min-width: 160px;
            }
        }

        @media (max-width: 340px) {
            .supplier-debt-page .supplier-debt-search {
                flex-wrap: wrap;
            }

            .supplier-debt-page .supplier-debt-filter-button {
                width: 100%;
            }
        }
    </style>
@endpush
