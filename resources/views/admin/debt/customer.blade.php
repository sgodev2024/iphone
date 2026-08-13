@extends('admin.layout.index')

@section('content')
    @php($canCollectDebt = auth()->user()?->hasPermission('receipt.create') ?? false)
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
                    @if ($canCollectDebt)
                        <col class="col-action">
                    @endif
                </colgroup>
                <thead class="table-light align-middle">
                    <tr>
                        <th rowspan="3">#</th>
                        <th rowspan="3">Khách hàng</th>
                        <th colspan="2"><span class="heading-nowrap">Số dư đầu kỳ</span></th>
                        <th colspan="2"><span class="heading-nowrap">Phát sinh trong kỳ</span></th>
                        <th colspan="2"><span class="heading-nowrap">Số dư cuối kỳ</span></th>
                        @if ($canCollectDebt)
                            <th rowspan="3">Thao tác</th>
                        @endif
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
                                <span class="customer-name">{{ $debt->client_name }}</span>
                                <span class="customer-phone">SĐT: {{ $debt->client_phone ?: '—' }}</span>
                            </td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->opening_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->opening_credit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->period_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->period_credit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->ending_debit) }}</td>
                            <td class="text-end money-cell">{{ formatExactMoney($debt->ending_credit) }}</td>
                            @if ($canCollectDebt)
                                @php($hasCollectibleDebt = \App\Support\DecimalAmount::compare((string) $debt->ending_debit, '0.00') > 0)
                                <td class="customer-debt-action-cell">
                                    <button type="button"
                                        class="btn btn-sm {{ $hasCollectibleDebt ? 'btn-primary' : 'btn-secondary' }} collect-debt-button"
                                        data-client-id="{{ $debt->client_id }}"
                                        data-client-name="{{ $debt->client_name }}"
                                        @disabled(! $hasCollectibleDebt)
                                        @if (! $hasCollectibleDebt) title="Khách hàng không còn công nợ phải thu" aria-disabled="true" @endif>
                                        Thu nợ
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canCollectDebt ? 9 : 8 }}" class="text-center">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canCollectDebt)
            <div class="modal fade" id="customerDebtPaymentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Thu công nợ — <span id="debtPaymentClientName"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <form id="customerDebtPaymentForm">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="debtPaymentOrder">Đơn còn nợ</label>
                                        <select id="debtPaymentOrder" class="form-control" required></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="debtPaymentAmountDisplay">Số tiền thu</label>
                                        <div class="input-group">
                                            <input id="debtPaymentAmountDisplay" type="text" class="form-control text-end"
                                                inputmode="numeric" autocomplete="off" required>
                                            <span class="input-group-text">đ</span>
                                        </div>
                                        <input id="debtPaymentAmount" type="hidden">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="debtPaymentMethod">Phương thức</label>
                                        <select id="debtPaymentMethod" class="form-control" required>
                                            <option value="cash">Tiền mặt</option>
                                            <option value="bank_transfer">Chuyển khoản</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="debtPaymentDate">Ngày thu</label>
                                        <input id="debtPaymentDate" type="date" class="form-control"
                                            value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div id="debtPaymentBankWrap" class="col-md-6 d-none">
                                        <label class="form-label" for="debtPaymentBank">Tài khoản ngân hàng</label>
                                        <select id="debtPaymentBank" class="form-control"></select>
                                    </div>
                                    <div class="col-12">
                                        <small id="debtPaymentRemaining" class="form-text text-muted"></small>
                                    </div>
                                </div>
                                <div id="debtPaymentError" class="alert alert-danger d-none mt-3 mb-0"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button id="debtPaymentSubmit" type="submit" class="btn btn-primary">Xác nhận thu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection

@push('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript"
        src="{{ asset('global/js/debt-date-range-picker.js') }}?v={{ filemtime(public_path('global/js/debt-date-range-picker.js')) }}"></script>

    <script>
        const canCollectDebt = @json($canCollectDebt);
        const debtPaymentOptionsUrl = @json(route('admin.debts.customer.payment-options', ['clientId' => '__CLIENT__']));
        const debtPaymentStoreUrl = @json(route('admin.debts.customer.payments.store'));
        let debtPaymentOrders = [];
        let debtPaymentIdempotencyKey = null;
        let debtPaymentToday = @json(now()->toDateString());
        const customerDebtPaymentModalElement = document.getElementById('customerDebtPaymentModal');
        const customerDebtPaymentModal = customerDebtPaymentModalElement
            ? bootstrap.Modal.getOrCreateInstance(customerDebtPaymentModalElement)
            : null;

        initializeDebtDateRangePicker({
            inputSelector: '#dateFilter',
            formSelector: '#customerDebtFilterForm',
            fromSelector: '#fromDate',
            toSelector: '#toDate',
            initialStart: @json($startDate),
            initialEnd: @json($endDate),
            today: @json(now()->toDateString())
        });

        function formatDebtPrice(amount) {
            const match = String(amount ?? '0').trim().match(/^([+-]?)(\d+)(?:\.(\d{1,2}))?$/);
            if (!match) return '0';

            const whole = (match[2].replace(/^0+(?=\d)/, '') || '0');
            const fraction = (match[3] || '').padEnd(2, '0');
            const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const sign = match[1] === '-' && (whole !== '0' || fraction !== '00') ? '-' : '';

            return sign + grouped + (fraction === '00' ? '' : ',' + fraction);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function newDebtPaymentKey() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(character) {
                const random = Math.random() * 16 | 0;
                const value = character === 'x' ? random : (random & 0x3 | 0x8);
                return value.toString(16);
            });
        }

        function selectedDebtPaymentOrder() {
            const orderId = Number($('#debtPaymentOrder').val());
            return debtPaymentOrders.find(order => Number(order.id) === orderId) || null;
        }

        function moneyDigits(value) {
            const digits = String(value ?? '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');

            return digits || '';
        }

        function formatMoneyDigits(value) {
            const digits = moneyDigits(value);

            return digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        }

        function compareMoneyDigits(left, right) {
            const normalizedLeft = moneyDigits(left) || '0';
            const normalizedRight = moneyDigits(right) || '0';

            return normalizedLeft.length - normalizedRight.length || normalizedLeft.localeCompare(normalizedRight);
        }

        function syncDebtPaymentAmount() {
            const display = $('#debtPaymentAmountDisplay');
            const max = String(display.data('max') || '0');
            let amount = moneyDigits(display.val());

            if (amount && max !== '0' && compareMoneyDigits(amount, max) > 0) {
                amount = max;
            }

            display.val(formatMoneyDigits(amount));
            $('#debtPaymentAmount').val(amount);
            $('#debtPaymentSubmit').prop('disabled', !amount || amount === '0');
        }

        function syncDebtPaymentOrder() {
            const order = selectedDebtPaymentOrder();
            const remaining = moneyDigits(order?.remaining);
            $('#debtPaymentAmountDisplay').data('max', remaining).val(formatMoneyDigits(remaining));
            $('#debtPaymentAmount').val(remaining);
            $('#debtPaymentRemaining').text(order ? `Còn nợ theo ledger: ${formatMoneyDigits(remaining)} đ` : '');
            $('#debtPaymentSubmit').prop('disabled', !remaining || remaining === '0');
        }

        function syncDebtPaymentDate(today) {
            debtPaymentToday = String(today || debtPaymentToday);
            $('#debtPaymentDate').attr('max', debtPaymentToday).val(debtPaymentToday);
        }

        $(document).on('click', '.collect-debt-button', function() {
            if (this.disabled || !customerDebtPaymentModal) {
                return;
            }

            const clientId = Number($(this).data('client-id'));
            debtPaymentIdempotencyKey = newDebtPaymentKey();
            $('#debtPaymentClientName').text($(this).data('client-name'));
            $('#debtPaymentError').addClass('d-none').text('');

            $.get(debtPaymentOptionsUrl.replace('__CLIENT__', clientId))
                .done(function(response) {
                    debtPaymentOrders = response.orders || [];
                    $('#debtPaymentOrder').html(debtPaymentOrders.map(order =>
                        `<option value="${Number(order.id)}">${escapeHtml(order.code || `#${order.id}`)} — còn ${formatDebtPrice(order.remaining)}</option>`
                    ).join(''));
                    $('#debtPaymentBank').html('<option value="">--- Chọn tài khoản ---</option>' +
                        (response.bank_accounts || []).map(account =>
                            `<option value="${Number(account.id)}">${escapeHtml(account.code)} - ${escapeHtml(account.name)}</option>`
                        ).join(''));
                    syncDebtPaymentDate(response.today);
                    syncDebtPaymentOrder();

                    if (!debtPaymentOrders.length) {
                        alert('Khách hàng không có order hợp lệ còn công nợ.');
                        return;
                    }

                    customerDebtPaymentModal.show();
                })
                .fail(function(xhr) {
                    alert(xhr.responseJSON?.message || 'Không thể tải danh sách order còn nợ.');
                });
        });

        $('#debtPaymentOrder').on('change', syncDebtPaymentOrder);
        $('#debtPaymentAmountDisplay').on('input', syncDebtPaymentAmount);
        $('#debtPaymentMethod').on('change', function() {
            $('#debtPaymentBankWrap').toggleClass('d-none', this.value !== 'bank_transfer');
        });

        customerDebtPaymentModalElement?.addEventListener('hidden.bs.modal', function() {
            debtPaymentOrders = [];
            debtPaymentIdempotencyKey = null;
            $('#customerDebtPaymentForm')[0].reset();
            $('#debtPaymentAmount, #debtPaymentAmountDisplay, #debtPaymentRemaining').val('').text('');
            $('#debtPaymentOrder, #debtPaymentBank').empty();
            $('#debtPaymentBankWrap').addClass('d-none');
            $('#debtPaymentError').addClass('d-none').text('');
            $('#debtPaymentSubmit').prop('disabled', false);
        });

        $('#customerDebtPaymentForm').on('submit', function(event) {
            event.preventDefault();
            syncDebtPaymentAmount();
            const method = $('#debtPaymentMethod').val();
            const amount = $('#debtPaymentAmount').val();
            const transactionDate = $('#debtPaymentDate').val();
            const latestAllowedDate = $('#debtPaymentDate').attr('max') || debtPaymentToday;
            if (!amount || amount === '0') {
                $('#debtPaymentError').removeClass('d-none').text('Vui lòng nhập số tiền thu hợp lệ.');
                return;
            }
            if (!transactionDate) {
                $('#debtPaymentError').removeClass('d-none').text('Vui lòng chọn ngày thu.');
                return;
            }
            if (transactionDate > latestAllowedDate) {
                $('#debtPaymentError').removeClass('d-none').text('Ngày thu không được lớn hơn ngày hiện tại.');
                return;
            }
            const submit = $('#debtPaymentSubmit').prop('disabled', true);
            $('#debtPaymentError').addClass('d-none').text('');

            $.ajax({
                url: debtPaymentStoreUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()) },
                data: {
                    order_id: Number($('#debtPaymentOrder').val()),
                    amount: amount,
                    payment_method: method,
                    bank_account_id: method === 'bank_transfer' ? Number($('#debtPaymentBank').val()) : null,
                    transaction_date: transactionDate,
                    idempotency_key: debtPaymentIdempotencyKey,
                },
            }).done(function(response) {
                customerDebtPaymentModal.hide();
                alert(response.message);
                window.location.reload();
            }).fail(function(xhr) {
                $('#debtPaymentError').removeClass('d-none').text(
                    xhr.responseJSON?.errors?.transaction_date?.[0]
                        || xhr.responseJSON?.message
                        || 'Không thể thu công nợ.'
                );
                submit.prop('disabled', false);
            });
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
            max-width: 100%;
            table-layout: fixed;
        }

        .customer-debt-page .customer-debt-table col.col-stt {
            width: 50px;
        }

        .customer-debt-page .customer-debt-table col.col-customer {
            width: 210px;
        }

        .customer-debt-page .customer-debt-table col.col-money {
            width: 140px;
        }

        .customer-debt-page .customer-debt-table col.col-ending {
            width: 240px;
        }

        .customer-debt-page .customer-debt-table col.col-action {
            width: 120px !important;
            max-width: 120px;
        }

        .customer-debt-page .customer-debt-table th,
        .customer-debt-page .customer-debt-table td {
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: normal;
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
            min-width: 190px;
        }

        .customer-debt-page .customer-name,
        .customer-debt-page .customer-phone {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .customer-debt-page .money-cell {
            text-align: right;
        }

        .customer-debt-page .customer-debt-action-cell {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
            text-align: center;
            white-space: nowrap;
        }

        .customer-debt-page .collect-debt-button:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .customer-debt-page #customerDebtPaymentModal .modal-content {
            border: 0;
            border-radius: 0.5rem;
        }

        .customer-debt-page #customerDebtPaymentModal .modal-header,
        .customer-debt-page #customerDebtPaymentModal .modal-footer {
            padding: 1rem 1.25rem;
        }

        .customer-debt-page #customerDebtPaymentModal .modal-body {
            padding: 1.25rem;
        }

        .customer-debt-page #customerDebtPaymentModal .form-label {
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        .customer-debt-page #customerDebtPaymentModal .form-control,
        .customer-debt-page #customerDebtPaymentModal .input-group-text {
            min-height: 40px;
        }

        .customer-debt-page #customerDebtPaymentModal #debtPaymentRemaining {
            display: block;
            font-weight: 500;
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
                min-width: 1280px;
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
                min-width: 210px;
            }

            .customer-debt-page .customer-debt-table tbody td:nth-child(n+3) {
                min-width: 140px;
            }

            .customer-debt-page .customer-debt-table tbody td:nth-child(7),
            .customer-debt-page .customer-debt-table tbody td:nth-child(8) {
                min-width: 240px;
            }

            .customer-debt-page .customer-debt-action-cell {
                min-width: 120px;
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
