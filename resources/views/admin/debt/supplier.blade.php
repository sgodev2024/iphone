@extends('admin.layout.index')

@section('content')
    <div class="page-inner supplier-debt-page">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'CÔNG NỢ NHÀ CUNG CẤP']]" />
        </div>

        <div class="card p-3 mb-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center flex-wrap supplier-debt-filter">
                <div class="d-flex align-items-center mb-2 supplier-debt-date">
                    <input type="text" id="dateFilter" name="date_range" class="form-control"
                        value="{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}"
                        placeholder="Chọn khoảng ngày">
                </div>

                <div class="d-flex align-items-center mb-2 supplier-debt-search">
                    <input type="text" class="form-control me-2 supplier-debt-name" name="name"
                        placeholder="Tên công ty">
                    <button type="button" id="filter" class="btn btn-primary">
                        <i class="bi bi-search"></i> Lọc
                    </button>
                </div>
            </div>
        </div>

        <div class="small text-muted mb-2">
            TK331: Có cuối kỳ là số còn phải trả nhà cung cấp; Nợ cuối kỳ là số ứng trước/trả thừa.
        </div>

        <div class="table-responsive supplier-debt-table-wrap">
            <table class="table table-bordered table-hover align-middle text-center mb-0" id="supplierDebtTable">
                <thead class="table-light align-middle">
                    <tr>
                        <th rowspan="2" style="width: 50px;">#</th>
                        <th rowspan="2">Công ty</th>
                        <th colspan="2">Số dư đầu kỳ</th>
                        <th colspan="2">Phát sinh trong kỳ</th>
                        <th colspan="2">Số dư cuối kỳ</th>
                    </tr>
                    <tr>
                        <th>Nợ đầu kỳ</th>
                        <th>Có đầu kỳ</th>
                        <th>Phát sinh Nợ</th>
                        <th>Phát sinh Có</th>
                        <th>Nợ cuối kỳ</th>
                        <th>Có cuối kỳ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplierDebts as $index => $debt)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-start supplier-cell">
                                <span class="supplier-name">{{ $debt->company_name }}</span><br>
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
            </table>
        </div>
    </div>
@endsection

@push('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        const initialStart = moment('{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}', 'DD/MM/YYYY');
        const initialEnd = moment('{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}', 'DD/MM/YYYY');

        $('#dateFilter').daterangepicker({
            startDate: initialStart,
            endDate: initialEnd,
            autoUpdateInput: true,
            locale: {
                format: 'DD/MM/YYYY',
                cancelLabel: 'Hủy',
                applyLabel: 'Áp dụng',
                firstDay: 1
            }
        });

        $('#filter').on('click', function() {
            $.ajax({
                url: '',
                type: 'GET',
                data: {
                    date_range: $('input[name="date_range"]').val(),
                    name: $('input[name="name"]').val()
                },
                success: renderTable,
                error: function() {
                    alert('Có lỗi xảy ra, vui lòng thử lại.');
                }
            });
        });

        function renderTable(data) {
            let tbody = '';

            if (data.length === 0) {
                tbody = '<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>';
            } else {
                data.forEach((debt, index) => {
                    const companyName = escapeHtml(debt.company_name || '');
                    const companyPhone = escapeHtml(debt.company_phone || '—');

                    tbody += `
                        <tr>
                            <td>${index + 1}</td>
                            <td class="text-start supplier-cell">
                                <span class="supplier-name">${companyName}</span><br>
                                <span class="supplier-phone">SĐT: ${companyPhone}</span>
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
            let value = String(amount ?? '0').trim();
            const negative = value.startsWith('-');
            value = value.replace(/^[+-]/, '');

            let [whole, fraction = ''] = value.split('.');
            whole = (whole || '0').replace(/^0+(?=\d)/, '');
            fraction = fraction.padEnd(2, '0').slice(0, 2);
            whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            return `${negative && (whole !== '0' || fraction !== '00') ? '-' : ''}${whole}`
                + (fraction === '00' ? '' : `,${fraction}`);
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
        .supplier-debt-table-wrap { overflow-x: auto; }
        .supplier-debt-table-wrap .money-cell { white-space: nowrap; }
        .supplier-cell { min-width: 220px; }
        .supplier-name, .supplier-phone { white-space: nowrap; }
        @media (max-width: 767.98px) {
            .supplier-debt-page { padding: 0 10px; }
            .supplier-debt-filter { gap: 8px; }
            .supplier-debt-date, .supplier-debt-search { width: 100%; margin-bottom: 0 !important; }
            .supplier-debt-search .supplier-debt-name { flex: 1 1 auto; min-width: 0; }
            .supplier-debt-table { min-width: 1050px; }
        }
    </style>
@endpush
