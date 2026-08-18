@extends('admin.layout.index')

@section('content')
    <div class="page-inner customer-debt-page">
        <x-breadcrumb :items="[['label' => 'Công nợ khách hàng']]" />
        {{-- <div class="page-header">
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
                    <span class="text-muted">CÔNG NỢ KHÁCH HÀNG</span>
                </li>
            </ul>
        </div> --}}

        <div class="d-flex justify-content-end mb-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.debts.customer.collections.index') }}">
                <i class="bi bi-clock-history"></i> Lịch sử thu công nợ
            </a>
        </div>

        <div class="card customer-debt-filter-card p-3 mb-3 shadow-sm">
            <form id="customerDebtFilterForm" class="customer-debt-filter" method="GET"
                action="{{ route('admin.debts.customer') }}">
                <!-- Lọc ngày sang trái -->
                <div class="customer-debt-date">
                    <input type="text" id="dateFilter" class="form-control"
                        value="{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}"
                        placeholder="Chọn khoảng ngày">
                    <input type="hidden" id="fromDate" name="from_date" value="{{ $startDate }}">
                    <input type="hidden" id="toDate" name="to_date" value="{{ $endDate }}">
                </div>

                <!-- Tên khách hàng và nút Lọc sang phải -->
                <div class="customer-debt-search">
                    <input type="text" class="form-control customer-debt-name" name="name"
                        value="{{ request()->query('name', '') }}" placeholder="Tên khách hàng">
                    <button type="submit" id="filter" class="btn btn-primary customer-debt-filter-button">
                        <i class="bi bi-search"></i> <span>Lọc</span>
                    </button>
                </div>
            </form>
        </div>


        <div class="table-responsive customer-debt-table-wrap">
            <table class="table table-bordered table-hover align-middle text-center mb-0 customer-debt-table" id="customerDebtTable">
                <colgroup>
                    <col class="col-stt">
                    <col class="col-customer">
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
                        <th rowspan="3">Khách hàng</th>
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
                            <span class="header-formula">3 + 5 - 4 - 6</span>
                        </th>
                        <th>
                            <span class="heading-nowrap">Có (Phải trả)</span>
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
                    @forelse($clientDebts as $index => $debt)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-start customer-cell">
                                <a class="customer-name text-primary text-decoration-none"
                                    href="{{ route('admin.order.index', [
                                        'client_id' => $debt->client_id,
                                        'outstanding_only' => 1,
                                    ]) }}">
                                    {{ $debt->client_name }}
                                </a>
                                <span class="customer-phone">SĐT: {{ $debt->client_phone ?: '—' }}</span>
                            </td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->opening_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->opening_credit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->period_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->period_credit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->ending_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->ending_credit) }}</td>
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
            formSelector: '#customerDebtFilterForm',
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
        .customer-debt-page,
        .customer-debt-page * {
            box-sizing: border-box;
        }

        .customer-debt-page {
            max-width: 100%;
            overflow-x: hidden;
        }

        .customer-debt-page .customer-debt-filter-card,
        .customer-debt-page .customer-debt-table-wrap {
            width: 100%;
            max-width: 100%;
        }

        .customer-debt-page .customer-debt-filter {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .customer-debt-page .customer-debt-date,
        .customer-debt-page .customer-debt-search {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .customer-debt-page .customer-debt-search {
            gap: 8px;
        }

        .customer-debt-page .customer-debt-filter-button {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .customer-debt-page .customer-debt-table-wrap {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .customer-debt-page .customer-debt-table {
            width: 100%;
            min-width: 1150px;
            max-width: none;
            table-layout: fixed;
        }

        .customer-debt-page .customer-debt-table col.col-stt {
            width: 50px;
        }

        .customer-debt-page .customer-debt-table col.col-customer {
            width: 220px;
        }

        .customer-debt-page .customer-debt-table col.col-money {
            width: 140px;
        }

        .customer-debt-page .customer-debt-table col.col-ending {
            width: 160px;
        }

        .customer-debt-page .customer-debt-table th,
        .customer-debt-page .customer-debt-table td {
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: normal;
            padding-top: 0.45rem;
            padding-bottom: 0.45rem;
        }

        .customer-debt-page .customer-debt-table th {
            text-align: center;
        }

        .customer-debt-page .heading-nowrap,
        .customer-debt-page .header-formula,
        .customer-debt-page .money-cell {
            white-space: nowrap;
        }

        .customer-debt-page .header-formula {
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }

        .customer-debt-page .customer-cell {
            min-width: 220px;
            white-space: nowrap;
        }

        .customer-debt-page .customer-name,
        .customer-debt-page .customer-phone {
            display: inline;
            white-space: inherit;
        }

        .customer-debt-page .customer-name {
            font-weight: 600;
        }

        .customer-debt-page .customer-name:hover {
            text-decoration: underline !important;
        }

        .customer-debt-page .customer-phone {
            margin-left: 0.75rem;
            color: #6c757d;
            font-weight: 400;
        }

        .customer-debt-page .money-cell {
            text-align: right;
        }

        .customer-debt-page .debt-total-row th,
        .customer-debt-page .debt-total-row td {
            background-color: #f3f4f6;
            border-top: 2px solid #adb5bd;
            font-weight: 700;
        }

        .customer-debt-page .pagination {
            margin-bottom: 0;
        }

        @media (max-width: 767.98px) {
            .customer-debt-page {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px;
                padding-left: 10px;
            }

            .customer-debt-page .breadcrumb,
            .customer-debt-page .breadcrumbs {
                margin-left: 0;
            }

            .customer-debt-page .customer-debt-filter-card {
                margin-right: auto;
                margin-left: auto;
            }

            .customer-debt-page .customer-debt-filter {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .customer-debt-page .customer-debt-date,
            .customer-debt-page .customer-debt-search {
                width: 100%;
                margin-bottom: 0;
            }

            .customer-debt-page .customer-debt-date .form-control,
            .customer-debt-page .customer-debt-search .form-control,
            .customer-debt-page .customer-debt-filter-button {
                min-height: 40px;
            }

            .customer-debt-page .customer-debt-search {
                gap: 8px;
            }

            .customer-debt-page .customer-debt-name {
                flex: 1 1 auto;
                min-width: 0;
                margin-right: 0 !important;
            }

            .customer-debt-page .customer-debt-filter-button {
                flex: 0 0 auto;
                padding-right: 14px;
                padding-left: 14px;
            }

            .customer-debt-page .customer-debt-table-wrap {
                margin-right: auto;
                margin-left: auto;
            }

            .customer-debt-page .customer-debt-table {
                min-width: 1150px;
            }

            .customer-debt-page .customer-debt-table th,
            .customer-debt-page .customer-debt-table td {
                padding-right: 10px;
                padding-left: 10px;
            }

            .customer-debt-page .customer-debt-table th:nth-child(1),
            .customer-debt-page .customer-debt-table td:nth-child(1) {
                min-width: 50px;
            }

            .customer-debt-page .customer-debt-table th:nth-child(2),
            .customer-debt-page .customer-debt-table td:nth-child(2) {
                min-width: 220px;
            }

            .customer-debt-page .customer-debt-table tbody td:nth-child(n+3) {
                min-width: 140px;
            }

            .customer-debt-page .customer-debt-table tbody td:nth-child(7),
            .customer-debt-page .customer-debt-table tbody td:nth-child(8) {
                min-width: 160px;
            }

            .customer-debt-page nav[role="navigation"],
            .customer-debt-page .pagination {
                justify-content: center;
            }
        }

        @media (max-width: 340px) {
            .customer-debt-page .customer-debt-search {
                flex-wrap: wrap;
            }

            .customer-debt-page .customer-debt-filter-button {
                width: 100%;
            }
        }
    </style>
@endpush
