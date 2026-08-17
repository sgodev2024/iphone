@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <div class="form-container">
            <form id="myForm">
                @if (!empty($transaction))
                    @method('PUT')
                @endif

                <input type="hidden" name="transaction_id" value="{{ optional($transaction)->id }}">
                <input type="hidden" name="entry_id" value="{{ optional($mainEntry)->id }}">
                @if ($type === 'cash' && empty($transaction))
                    <input type="hidden" id="collection-idempotency-key" name="idempotency_key">
                    <div class="border-bottom p-3 bg-light cash-unified-header">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label required" for="cash-transaction-type">Loại giao dịch</label>
                                <select class="form-select" id="cash-transaction-type" name="direction" required>
                                    <option value="receipt">Thu tiền</option>
                                    <option value="payment">Chi tiền</option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label required" for="cash-operation">Nghiệp vụ</label>
                                <select class="form-select" id="cash-operation" name="operation" required>
                                    <option value="generic_receipt" data-transaction-type="receipt">Thu tiền thông thường</option>
                                    @if (auth()->user()?->hasPermission('receipt.create'))
                                        <option value="customer_debt_collection" data-transaction-type="receipt">Thu công nợ khách hàng</option>
                                    @endif
                                    <option value="generic_payment" data-transaction-type="payment">Chi tiền thông thường</option>
                                    @if (auth()->user()?->hasPermission('expense.create'))
                                        <option value="supplier_debt_payment" data-transaction-type="payment">Trả công nợ nhà cung cấp</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                @elseif ($type === 'bank' && empty($transaction))
                    <input type="hidden" id="collection-idempotency-key" name="idempotency_key">
                    <div class="border-bottom p-3 bg-light">
                        <label class="form-label required" for="entry-mode">Loại phiếu</label>
                        <select class="form-select" id="entry-mode" name="entry_mode" required>
                            <option value="generic">Phiếu {{ $type === 'cash' ? 'tiền mặt' : 'ngân hàng' }} thông thường
                            </option>
                            @if (auth()->user()?->hasPermission('receipt.create'))
                                <option value="customer_debt_collection">Thu công nợ khách hàng</option>
                            @endif
                        </select>
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-8 pe-0">
                        <div class="section-header">
                            <i class="fas fa-info-circle"></i>
                            Thông tin
                        </div>
                        <div class="section-content">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="form-label" id="transaction-date-label">Ngày thu chi</label>
                                    <input type="date" class="form-control" name="transaction_date"
                                        value="{{ optional($transaction)->transaction_date ? optional($transaction)->transaction_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                                        data-today="{{ now()->format('Y-m-d') }}" required>
                                </div>

                                <div class="col-lg-6 generic-only-field" id="generic-object-type-field">
                                    <label class="form-label required">Loại đối tượng</label>
                                    <select name="obj_type" id="object-type" class="form-select" required>
                                        <option value="">Chọn loại đối tượng</option>
                                        <option value="client" @selected(optional($contraEntry)->tableable_type === 'App\Models\Client')>Khách hàng</option>
                                        <option value="supplier" @selected(optional($contraEntry)->tableable_type === 'App\Models\Supplier')>Nhà cung cấp</option>
                                    </select>
                                </div>

                                <div class="col-md-6 generic-only-field" id="generic-account-field">
                                    <label class="form-label required">Tài khoản
                                        {{ $type === 'cash' ? 'tiền mặt' : 'ngân hàng' }}</label>
                                    <select class="form-select" name="account_id" id="account_id" required>
                                        <option value="">Chọn tài khoản</option>
                                        @foreach ($moneyAccounts as $moneyAccount)
                                            <option value="{{ $moneyAccount->id }}" @selected(optional($mainEntry)->account_id == $moneyAccount->id)>
                                                {{ "$moneyAccount->code - $moneyAccount->name" }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if ($type === 'cash' && empty($transaction))
                                    <div class="col-md-6" id="cash-generic-account-field">
                                        <label class="form-label">Tài khoản tiền mặt</label>
                                        <input type="text" class="form-control" readonly
                                            value="{{ $canonicalCashAccount ? "$canonicalCashAccount->code - $canonicalCashAccount->name" : 'Chưa cấu hình tài khoản 111 đang hoạt động' }}">
                                        <div class="form-text">Tài khoản 111 do hệ thống xác định; phiếu thông thường chưa hạch toán đối ứng.</div>
                                    </div>
                                @endif

                                @if (in_array($type, ['cash', 'bank'], true) && empty($transaction))
                                    <div class="col-md-6 collection-only-field d-none" id="collection-account-field">
                                        @if ($type === 'cash')
                                            <label class="form-label">Tài khoản tiền mặt canonical</label>
                                            <input type="text" class="form-control" readonly
                                                value="{{ $canonicalCashAccount ? "$canonicalCashAccount->code - $canonicalCashAccount->name" : 'Chưa cấu hình tài khoản 111 đang hoạt động' }}">
                                            <div class="form-text">Tài khoản 111 do hệ thống tự xác định khi ghi sổ.</div>
                                        @else
                                            <label class="form-label required" for="collection-money-account">Tài khoản ngân
                                                hàng nhận tiền</label>
                                            <select class="form-select" id="collection-money-account">
                                                <option value="">Chọn tài khoản ngân hàng</option>
                                                @foreach ($collectionMoneyAccounts as $collectionMoneyAccount)
                                                    <option value="{{ $collectionMoneyAccount->id }}"
                                                        data-code="{{ $collectionMoneyAccount->code }}"
                                                        data-name="{{ $collectionMoneyAccount->name }}">
                                                        {{ "$collectionMoneyAccount->code - $collectionMoneyAccount->name" }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Chỉ gồm tài khoản đang hoạt động trực tiếp dưới TK112.
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="col-md-6" id="cash-object-field">
                                    <div class="position-relative">
                                        <label class="form-label required" id="object-label">Đối tượng</label>
                                        <input type="text" id="object_code" class="form-control"
                                            placeholder="Nhập 3 ký tự để tìm đối tượng"
                                            value="{{ !empty($contraEntry) ? $contraEntry->tableable->name . ' - ' . $contraEntry->tableable->phone : '' }}"
                                            required>
                                        <input type="hidden" name="obj_id" id="object_id"
                                            value="{{ optional($contraEntry)->tableable_id }}">
                                        <div id="object-search-result"
                                            class="border bg-white position-absolute w-100 shadow-sm"
                                            style="z-index: 9999; display: none; max-height: 200px; overflow-y: auto;">
                                            <!-- Kết quả sẽ render tại đây -->
                                        </div>
                                    </div>
                                </div>

                                @if ($type === 'bank')
                                    <div class="col-md-6 generic-only-field" id="generic-voucher-type-field">
                                        <label class="form-label required">Loại phiếu</label>
                                        <select class="form-select" name="type" id="type" required>
                                            <option value="">Chọn loại phiếu</option>
                                            <option value="debit_notice" @selected(optional($transaction)->type === 'debit_notice')>Báo nợ (Rút tiền)
                                            </option>
                                            <option value="credit_notice" @selected(optional($transaction)->type === 'credit_notice')>Báo có (Nộp tiền)
                                            </option>
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="type" id="type"
                                        value="{{ optional($transaction)->type === 'expense' ? 'expense' : 'income' }}">
                                @endif

                                <div class="col-md-6 generic-only-field">
                                    <label class="form-label">Loại chứng từ</label>
                                    <input type="text" name="document_type" placeholder="ví dụ: Đơn hàng"
                                        class="form-control" value="{{ optional($transaction)->document_type }}">
                                </div>

                                <div class="col-md-6 generic-only-field">
                                    <label class="form-label">ID chứng từ</label>
                                    <input type="text" name="reference_number"
                                        value="{{ optional($transaction)->reference_number }}"
                                        placeholder="Nhập ID chứng từ" class="form-control">
                                </div>

                                @if (in_array($type, ['cash', 'bank'], true) && empty($transaction))
                                    <div class="col-12 collection-only-field d-none" id="customer-debt-panel"
                                        data-client-search-url="{{ route('admin.debts.customer.collections.clients') }}"
                                        data-preview-url="{{ route('admin.debts.customer.collections.preview', ['clientId' => '__CLIENT__']) }}"
                                        data-store-url="{{ route('admin.debts.customer.collections.store') }}"
                                        data-payment-method="{{ $type === 'cash' ? 'cash' : 'bank_transfer' }}"
                                        data-account-ready="{{ $type === 'cash' ? ($canonicalCashAccount ? '1' : '0') : ($collectionMoneyAccounts->isNotEmpty() ? '1' : '0') }}">
                                        <h6 class="mb-2">CÔNG NỢ KHÁCH HÀNG</h6>
                                        <div id="debt-status" class="mb-3">
                                            Chọn khách hàng để tải công nợ canonical từ ledger.
                                        </div>
                                        <div id="debt-content" class="d-none">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>Tổng có thể thu tại ngày đã chọn</strong>
                                                <strong class="text-primary fs-5" id="debt-total">0 ₫</strong>
                                            </div>
                                            {{-- <div class="small text-muted mb-2">Ưu tiên thu đơn cũ nhất trước (FIFO).</div> --}}
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Đơn hàng</th>
                                                            <th>Ngày bán</th>
                                                            <th class="text-end">Giá trị</th>
                                                            <th class="text-end">Đã thu</th>
                                                            <th class="text-end">Còn nợ</th>
                                                            <th class="text-end">Sẽ thu</th>
                                                            <th class="text-end">Sau thu</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="debt-items"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($type === 'cash' && empty($transaction))
                                    <div class="col-12 d-none" id="supplier-debt-panel"
                                        data-company-search-url="{{ route('admin.transactions.cash.supplier-companies') }}"
                                        data-imports-url="{{ route('admin.transactions.cash.supplier-imports', ['companyId' => '__COMPANY__']) }}"
                                        data-store-url="{{ route('admin.transactions.cash.supplier-payment') }}">
                                        <h6 class="mb-2">CÔNG NỢ NHÀ CUNG CẤP</h6>
                                        <div id="supplier-debt-status" class="mb-3">Chọn nhà cung cấp để tải công nợ
                                            canonical.</div>
                                        <div id="supplier-debt-content" class="d-none">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>Tổng còn phải trả</strong>
                                                <strong class="text-primary fs-5" id="supplier-debt-total">0 ₫</strong>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Phiếu nhập</th>
                                                            <th>Ngày nhập</th>
                                                            <th class="text-end">Tổng nhập</th>
                                                            <th class="text-end">Đã trả</th>
                                                            <th class="text-end">Còn phải trả</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="supplier-debt-items"></tbody>
                                                </table>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label required" for="supplier-import-id">Phiếu nhập cần
                                                    trả</label>
                                                <select class="form-select" id="supplier-import-id">
                                                    <option value="">Chọn phiếu nhập</option>
                                                </select>
                                                <div class="form-text">Thanh toán theo từng phiếu nhập theo semantics hiện
                                                    tại của SupplierPaymentService.</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-12" id="file-upload-section">
                                    <div class="file-upload-area">
                                        @if (!empty($transaction) && $transaction->attachment)
                                            <div class="mb-2 d-flex justify-content-center align-items-center gap-2">
                                                <a href="{{ asset("storage/$transaction->attachment") }}" target="_blank"
                                                    class="btn btn-sm btn-primary text-white text-decoration-none">
                                                    <i class="bi bi-file-earmark-text me-1"></i>
                                                    Xem file đính kèm
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    id="removeAttachmentBtn">
                                                    <i class="bi bi-x-circle"></i> Xoá file
                                                </button>
                                            </div>
                                        @endif
                                        <div id="filePreviewArea" class="mb-2"></div>
                                        <div class="file-upload-text">
                                            Chọn file jpg, jpeg, png, webp hoặc pdf &lt;= 2MB
                                        </div>
                                        <button type="button" class="btn btn-file" id="triggerFileInput">
                                            <i class="bi bi-upload me-1"></i>
                                            Chọn File
                                        </button>
                                        <input type="file" class="d-none" name="attachment" id="fileInput"
                                            accept=".jpg,.jpeg,.png,.webp,.pdf">
                                        <input type="hidden" name="remove_attachment" id="removeAttachment"
                                            value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 section-divider ps-0">
                        <div class="section-header">
                            <i class="fas fa-credit-card"></i>
                            Thanh toán
                        </div>
                        <div class="section-content">
                            <div class="mb-3">
                                <label class="form-label required" id="amount-label">Số tiền (VND)</label>
                                <input type="text" name="amount" id="amount"
                                    class="form-control usd-price-format"
                                    value="{{ $mainEntry ? ($mainEntry->debit_amount > 0 ? formatPrice($mainEntry->debit_amount) : formatPrice($mainEntry->credit_amount)) : '' }}"
                                    placeholder="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="description" class="form-control" rows="3">{{ optional($transaction)->description }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $backUrl = request()->is('admin/transactions/cash*')
                        ? '/admin/transactions/cash'
                        : '/admin/transactions/bank';
                @endphp

                <div class="border-top p-3">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i>
                            Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm" id="submit-button">
                            <i class="bi bi-check-circle me-1"></i>
                            Lưu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });
            const isUnifiedCash = @json($type === 'cash' && empty($transaction));
            const debtPanel = $('#customer-debt-panel');
            const supplierPanel = $('#supplier-debt-panel');
            const collectionPaymentMethod = debtPanel.data('payment-method') || 'cash';
            const isBankCollection = collectionPaymentMethod === 'bank_transfer';
            const collectionState = {
                canSubmit: false,
                submitting: false,
                previewTimer: null,
                previewSequence: 0
            };
            const supplierState = {
                canSubmit: false,
                submitting: false,
                loadSequence: 0
            };

            function isCollectionMode() {
                return isUnifiedCash ?
                    $('#cash-operation').val() === 'customer_debt_collection' :
                    $('#entry-mode').val() === 'customer_debt_collection';
            }

            function isSupplierPaymentMode() {
                return isUnifiedCash && $('#cash-operation').val() === 'supplier_debt_payment';
            }

            function isGenericMode() {
                return isUnifiedCash ?
                    !isCollectionMode() && !isSupplierPaymentMode() :
                    !isCollectionMode();
            }

            function uuid() {
                if (window.crypto?.randomUUID) return window.crypto.randomUUID();

                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(character) {
                    const random = Math.random() * 16 | 0;
                    const value = character === 'x' ? random : (random & 0x3 | 0x8);
                    return value.toString(16);
                });
            }

            function rawAmount() {
                return ($('#amount').val() || '').replace(/\./g, '').replace(/\D/g, '');
            }

            function money(value) {
                return `${new Intl.NumberFormat('vi-VN').format(Number.parseInt(value || 0, 10) || 0)} ₫`;
            }

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            function setSubmitState() {
                const bankAccountMissing = isCollectionMode() && isBankCollection &&
                    !$('#collection-money-account').val();
                const supplierImportMissing = isSupplierPaymentMode() && !$('#supplier-import-id').val();
                const disabled = collectionState.submitting ||
                    supplierState.submitting ||
                    (isCollectionMode() && (!collectionState.canSubmit || bankAccountMissing)) ||
                    (isSupplierPaymentMode() && (!supplierState.canSubmit || supplierImportMissing));
                $('#submit-button').prop('disabled', disabled);
            }

            function setDebtStatus(message, style = 'secondary', disableAmount = true) {
                collectionState.canSubmit = false;
                if (isCollectionMode()) $('#amount').prop('disabled', disableAmount);
                $('#debt-status')
                    .removeClass('alert-secondary alert-info alert-success alert-warning alert-danger')
                    // .addClass(`alert-${style}`)
                    .text(message);
                $('#debt-content').addClass('d-none');
                setSubmitState();
            }

            function setSupplierStatus(message) {
                supplierState.canSubmit = false;
                $('#supplier-debt-status').text(message);
                $('#supplier-debt-content').addClass('d-none');
                setSubmitState();
            }

            function resetUnifiedContext() {
                collectionState.previewSequence++;
                collectionState.canSubmit = false;
                supplierState.loadSequence++;
                supplierState.canSubmit = false;
                $('#object_code').val('');
                $('#object_id').val('');
                $('#object-search-result').hide().empty();
                $('#amount').val('').prop('disabled', false);
                $('[name="document_type"], [name="reference_number"], [name="description"]').val('');
                $('#object-type').val('').trigger('change.select2');
                $('#debt-items, #supplier-debt-items').empty();
                $('#debt-content, #supplier-debt-content').addClass('d-none');
                $('#supplier-import-id').html('<option value="">Chọn phiếu nhập</option>');
                $('#collection-idempotency-key').val(uuid());
                if (fileInput) fileInput.value = '';
                if (filePreviewArea) filePreviewArea.innerHTML = '';
            }

            function syncCashOperationOptions() {
                const transactionType = $('#cash-transaction-type').val();
                const $operation = $('#cash-operation');
                const current = $operation.val();
                const operationTemplates = $operation.data('operation-templates') ||
                    $operation.find('option').map(function() {
                        return {
                            value: this.value,
                            label: this.textContent,
                            transactionType: this.dataset.transactionType
                        };
                    }).get();
                const validOptions = operationTemplates.filter(option =>
                    option.transactionType === transactionType
                );
                const next = validOptions.some(option => option.value === current) ? current :
                    validOptions[0]?.value || '';

                $operation.data('operation-templates', operationTemplates);
                $operation.empty();
                validOptions.forEach(option => {
                    const element = new Option(option.label, option.value, false, option.value === next);
                    element.dataset.transactionType = option.transactionType;
                    $operation.append(element);
                });
                $operation.val(next).trigger('change.select2');
            }

            function applyUnifiedCashMode() {
                const operation = $('#cash-operation').val() || 'generic_receipt';
                const collection = operation === 'customer_debt_collection';
                const supplierPayment = operation === 'supplier_debt_payment';
                const generic = !collection && !supplierPayment;
                const transactionType = $('#cash-transaction-type').val() || 'receipt';

                resetUnifiedContext();

                $('.generic-only-field').toggleClass('d-none', !generic);
                $('.collection-only-field').toggleClass('d-none', !collection);
                $('#supplier-debt-panel').toggleClass('d-none', !supplierPayment);
                $('#generic-object-type-field, #generic-account-field').addClass('d-none');
                $('#cash-generic-account-field').toggleClass('d-none', !generic);
                $('#cash-object-field').toggleClass('d-none', generic);
                $('#file-upload-section').toggleClass('d-none', supplierPayment);
                $('#transaction-date-label').text(supplierPayment ? 'Ngày trả công nợ' : collection ?
                    'Ngày thu công nợ' : 'Ngày thu chi');
                $('#object-label').text(supplierPayment ? 'Nhà cung cấp' : collection ? 'Khách hàng' : 'Đối tượng');
                $('#amount-label').text(supplierPayment ? 'Số tiền trả (VND)' : collection ? 'Số tiền thu (VND)' :
                    transactionType === 'payment' ? 'Số tiền chi (VND)' : 'Số tiền thu (VND)');
                $('#object_code').attr('placeholder', supplierPayment ?
                    'Nhập ít nhất 2 ký tự để tìm nhà cung cấp' :
                    collection ? 'Nhập ít nhất 2 ký tự để tìm khách hàng' : 'Nhập 3 ký tự để tìm đối tượng');
                $('#object-type').prop('required', false);
                $('#object_code').prop('required', !generic);
                $('#account_id').prop('required', false);
                $('[name="transaction_date"]')
                    .attr('max', $('[name="transaction_date"]').data('today'));
                $('#type').val(transactionType === 'payment' ? 'expense' : 'income');
                $('#amount').prop('disabled', collection && !$('#debt-content').is(':visible'));

                if (collection) {
                    $('#object-type').val('client');
                    if (debtPanel.data('account-ready').toString() !== '1') {
                        setDebtStatus('Không tìm thấy tài khoản 111 đang hoạt động. Không thể thu công nợ.',
                            'danger');
                    } else if (!$('#object_id').val()) {
                        setDebtStatus('Chọn khách hàng để tải công nợ canonical từ ledger.');
                    }
                } else if (supplierPayment) {
                    $('#object-type').val('');
                    if (!$('#object_id').val()) {
                        setSupplierStatus('Chọn nhà cung cấp để tải công nợ canonical.');
                    }
                } else {
                    collectionState.canSubmit = true;
                    supplierState.canSubmit = false;
                    $('#object-type, #object_id, #object_code').val('');
                    $('#amount').prop('disabled', false);
                }

                setSubmitState();
            }

            function applyMode() {
                if (isUnifiedCash) {
                    applyUnifiedCashMode();
                    return;
                }

                const collection = isCollectionMode();
                $('.generic-only-field').toggleClass('d-none', collection);
                $('.collection-only-field').toggleClass('d-none', !collection);
                $('#transaction-date-label').text(collection ? 'Ngày thu công nợ' : 'Ngày thu chi');
                $('#object-label').text(collection ? 'Khách hàng' : 'Đối tượng');
                $('#amount-label').text(collection ? 'Số tiền thu (VND)' : 'Số tiền (VND)');
                $('#object_code').attr('placeholder', collection ?
                    'Nhập ít nhất 2 ký tự để tìm khách hàng' :
                    'Nhập 3 ký tự để tìm đối tượng');
                $('[name="transaction_date"]')
                    .attr('max', collection ? $('[name="transaction_date"]').data('today') : null);

                $('.generic-only-field :input').each(function() {
                    if ($(this).prop('required')) $(this).data('generic-required', true);
                    $(this).prop('required', !collection && $(this).data('generic-required') === true);
                });

                $('#object_code').val('');
                $('#object_id').val('');
                $('#object-search-result').hide().empty();
                $('#collection-money-account').val('').trigger('change.select2');

                if (collection) {
                    $('#object-type').val('client').trigger('change.select2');
                    $('#type').val(isBankCollection ? 'credit_notice' : 'income').trigger('change.select2');
                    $('#collection-idempotency-key').val(uuid());

                    if (debtPanel.data('account-ready').toString() !== '1') {
                        setDebtStatus(isBankCollection ?
                            'Không có tài khoản ngân hàng hợp lệ trực tiếp dưới TK112.' :
                            'Không tìm thấy tài khoản 111 đang hoạt động. Không thể thu công nợ.', 'danger');
                    } else {
                        setDebtStatus('Chọn khách hàng để tải công nợ canonical từ ledger.');
                    }
                } else {
                    collectionState.canSubmit = true;
                    $('#amount').prop('disabled', false);
                    $('#object-type').val('').trigger('change.select2');
                    setSubmitState();
                }
            }

            function formatPrice($input) {
                let originalValue = $input.val();
                let cursorPos = $input.prop("selectionStart");

                let value = originalValue.replace(/\D/g, "");
                let newValue = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

                $input.val(newValue);

                if (cursorPos !== null) {
                    let oldDots = (originalValue.slice(0, cursorPos).match(/\./g) || []).length;
                    let newDots = (newValue.slice(0, cursorPos + (newValue.length - originalValue.length)).match(
                        /\./g) || []).length;
                    let newCursorPos = cursorPos + (newDots - oldDots);
                    newCursorPos = Math.min(newCursorPos, newValue.length);
                    $input[0].setSelectionRange(newCursorPos, newCursorPos);
                }
            }

            $(document).on("input", ".usd-price-format", function(e) {
                if (e.originalEvent.inputType === "insertText" && e.originalEvent.data === ".") return;
                formatPrice($(this));

                if (isCollectionMode()) scheduleDebtPreview();
                if (isSupplierPaymentMode()) updateSupplierSubmitState();
            });

            $(document).on("blur", ".usd-price-format", function() {
                formatPrice($(this));
            });

            $('#object-type, #account_id, #collection-money-account, #cash-transaction-type, #cash-operation, #supplier-import-id, select#type')
                .select2({
                    placeholder: function() {
                        return $(this).attr('placeholder') || "Chọn một tùy chọn";
                    },
                    allowClear: false,
                    width: '100%'
                });

            let typingTimer;
            const doneTypingInterval = 500;

            $('#object-type').on('change', function() {
                if (isCollectionMode() || isSupplierPaymentMode()) return;
                $('#object_code').val('');
                $('#object_id').val('');
                $('#object-search-result').hide();
            });

            $('#object_code').on('input', function() {
                clearTimeout(typingTimer);
                let keyword = $(this).val().trim();
                const collection = isCollectionMode();
                const supplierPayment = isSupplierPaymentMode();
                let type = collection ? 'client' : supplierPayment ? 'company' : $('#object-type').val();
                const minimumLength = collection || supplierPayment ? 2 : 3;
                $('#object_id').val('');

                if (collection) {
                    setDebtStatus('Chọn một khách hàng hợp lệ trong kết quả tìm kiếm.');
                } else if (supplierPayment) {
                    setSupplierStatus('Chọn một nhà cung cấp hợp lệ trong kết quả tìm kiếm.');
                }

                if (keyword.length >= minimumLength && type) {
                    typingTimer = setTimeout(() => {
                        $.ajax({
                            url: collection ? debtPanel.data('client-search-url') :
                                supplierPayment ?
                                supplierPanel.data('company-search-url') :
                                '/admin/transactions/cash/search',
                            data: {
                                type,
                                keyword
                            },
                            success: function(res) {
                                let html = '';
                                if (res.length > 0) {
                                    res.forEach(item => {
                                        const name = escapeHtml(item.name);
                                        const phone = escapeHtml(item.phone ||
                                            'Không có SĐT');
                                        const code = escapeHtml(item.code ||
                                            '');
                                        html += `<div class="p-2 border-bottom object-item" style="cursor: pointer;" data-id="${Number(item.id)}" data-phone="${phone}" data-name="${name}">
                                            <strong>${name}</strong> - ${phone}${code ? ` <span class="text-muted">(${code})</span>` : ''}
                                        </div>`;
                                    });
                                } else {
                                    html =
                                        `<div class="p-2 text-muted text-center">Không tìm thấy dữ liệu phù hợp</div>`;
                                }
                                $('#object-search-result').html(html).show();
                            },
                            error: function() {
                                $('#object-search-result').html(
                                    '<div class="p-2 text-muted text-center">Lỗi khi tìm kiếm</div>'
                                ).show();
                            }
                        });
                    }, doneTypingInterval);
                } else {
                    $('#object-search-result').hide();
                }
            });

            $(document).on('click', '.object-item', function() {
                let name = $(this).data('name');
                let phone = $(this).data('phone');
                let id = $(this).data('id');
                $('#object_code').val(`${name} - ${phone}`);
                $('#object_id').val(id).trigger('change'); // Đảm bảo giá trị được cập nhật
                $('#object-search-result').hide();

                if (isCollectionMode()) loadDebtPreview();
                if (isSupplierPaymentMode()) loadSupplierImports();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#object-search-result, #object_code').length) {
                    $('#object-search-result').hide();
                }
            });

            let basePath = window.location.pathname.includes('/transactions/bank') ?
                '/admin/transactions/bank' :
                '/admin/transactions/cash';
            let url =
                '{{ !empty($transaction) && !empty($mainEntry) && !empty($contraEntry) ? 'update' : 'store' }}';
            let fullUrl = `${basePath}/${url}`;

            function scheduleDebtPreview() {
                clearTimeout(collectionState.previewTimer);
                collectionState.previewTimer = setTimeout(loadDebtPreview, 350);
            }

            function loadDebtPreview() {
                if (!isCollectionMode()) return;

                const clientId = $('#object_id').val();
                const collectionDate = $('[name="transaction_date"]').val();
                const previewSequence = ++collectionState.previewSequence;

                if (!clientId) {
                    setDebtStatus('Chọn khách hàng để tải công nợ canonical từ ledger.');
                    return;
                }

                if (!collectionDate || collectionDate > $('[name="transaction_date"]').attr('max')) {
                    setDebtStatus('Ngày thu không được lớn hơn ngày hiện tại.', 'danger');
                    return;
                }

                setDebtStatus('Đang đối chiếu công nợ canonical...', 'info', false);
                const previewUrl = debtPanel.data('preview-url').replace('__CLIENT__', clientId);
                const data = {
                    collection_date: collectionDate
                };
                const amount = rawAmount();
                if (amount) data.amount = amount;

                $.ajax({
                    url: previewUrl,
                    data,
                    success: function(response) {
                        if (previewSequence === collectionState.previewSequence &&
                            clientId === $('#object_id').val()) {
                            renderDebtPreview(response);
                        }
                    },
                    error: function(xhr) {
                        if (previewSequence !== collectionState.previewSequence ||
                            clientId !== $('#object_id').val()) return;
                        const reconciliationBlocked = Boolean(xhr.responseJSON?.errors?.reconciliation);
                        const message = reconciliationBlocked ?
                            'Dữ liệu công nợ cần được đối chiếu trước khi thu.' :
                            xhr.responseJSON?.blocked_reason || xhr.responseJSON?.message ||
                            Object.values(xhr.responseJSON?.errors || {}).flat()[0] ||
                            'Không thể đối chiếu công nợ khách hàng.';
                        setDebtStatus(message, 'danger', reconciliationBlocked);
                    }
                });
            }

            function renderDebtPreview(data) {
                if (!data.can_collect) {
                    setDebtStatus(data.blocked_reason || 'Khách hàng không có công nợ có thể thu.', 'warning');
                    return;
                }

                const allocationByOrder = {};
                (data.preview_allocations || []).forEach(item => {
                    allocationByOrder[item.order_id] = item;
                });
                const rows = (data.items || []).map(item => {
                    const allocation = allocationByOrder[item.id];

                    return `
                    <tr>
                        <td>${escapeHtml(item.code || `#${item.id}`)}</td>
                        <td>${escapeHtml(item.sale_date)}</td>
                        <td class="text-end">${money(item.total)}</td>
                        <td class="text-end">${money(item.paid)}</td>
                        <td class="text-end fw-semibold">${money(item.remaining)}</td>
                        <td class="text-end text-primary">${allocation ? money(allocation.allocated_amount) : '—'}</td>
                        <td class="text-end">${allocation ? money(allocation.remaining_after) : money(item.remaining)}</td>
                    </tr>
                `;
                }).join('');

                $('#debt-total').text(money(data.collectible_total));
                $('#debt-items').html(rows);
                $('#debt-content').removeClass('d-none');
                $('#amount').prop('disabled', false);

                const amount = rawAmount();
                collectionState.canSubmit = Boolean(amount) && Number(amount) > 0;
                $('#debt-status')
                    .removeClass('alert alert-secondary alert-info alert-warning alert-danger alert-success')
                    .text(amount ?
                        'Đã đối chiếu ledger. Phân bổ FIFO dự kiến được hiển thị bên dưới.' :
                        'Đã đối chiếu ledger. Nhập số tiền để xem phân bổ FIFO dự kiến.');
                setSubmitState();
            }

            function updateSupplierSubmitState() {
                supplierState.canSubmit = Boolean($('#supplier-import-id').val()) && Number(rawAmount()) > 0;
                setSubmitState();
            }

            function loadSupplierImports() {
                if (!isSupplierPaymentMode()) return;

                const companyId = $('#object_id').val();
                const sequence = ++supplierState.loadSequence;
                if (!companyId) {
                    setSupplierStatus('Chọn nhà cung cấp để tải công nợ canonical.');
                    return;
                }

                $('#supplier-debt-status').text('Đang đối chiếu công nợ TK331...');
                const url = supplierPanel.data('imports-url').replace('__COMPANY__', companyId);
                $.getJSON(url)
                    .done(function(items) {
                        if (sequence !== supplierState.loadSequence || companyId !== $('#object_id').val())
                            return;
                        renderSupplierImports(items);
                    })
                    .fail(function(xhr) {
                        if (sequence !== supplierState.loadSequence || companyId !== $('#object_id').val())
                            return;
                        const message = xhr.responseJSON?.message ||
                            Object.values(xhr.responseJSON?.errors || {}).flat()[0] ||
                            'Không thể đối chiếu công nợ nhà cung cấp.';
                        setSupplierStatus(message);
                    });
            }

            function renderSupplierImports(items) {
                const rows = (items || []).map(item => `
                    <tr>
                        <td>${escapeHtml(item.code || `#${item.id}`)}</td>
                        <td>${escapeHtml(item.purchase_date || '')}</td>
                        <td class="text-end">${money(item.total)}</td>
                        <td class="text-end">${money(item.paid)}</td>
                        <td class="text-end fw-semibold">${money(item.remaining)}</td>
                    </tr>
                `).join('');
                const options = (items || []).map(item =>
                    `<option value="${Number(item.id)}" data-remaining="${Number(item.remaining)}">${escapeHtml(item.code || `#${item.id}`)} · Còn ${money(item.remaining)}</option>`
                ).join('');
                const total = (items || []).reduce((sum, item) => sum + Number(item.remaining || 0), 0);

                $('#supplier-debt-total').text(money(total));
                $('#supplier-debt-items').html(rows ||
                    '<tr><td colspan="5" class="text-center text-muted">Nhà cung cấp không còn phiếu nhập có công nợ.</td></tr>'
                    );
                $('#supplier-import-id').html('<option value="">Chọn phiếu nhập</option>' + options).trigger(
                    'change.select2');
                $('#supplier-debt-content').removeClass('d-none');
                $('#supplier-debt-status').text(items?.length ?
                    'Đã đối chiếu ledger TK331. Chọn một phiếu nhập để trả.' : 'Không còn công nợ phải trả.');
                updateSupplierSubmitState();
            }

            $('[name="transaction_date"]').on('change', function() {
                if (isCollectionMode()) loadDebtPreview();
                if (isSupplierPaymentMode()) loadSupplierImports();
            });

            $('#collection-money-account').on('change', function() {
                setSubmitState();
            });

            $('#supplier-import-id').on('change', updateSupplierSubmitState);
            $('#cash-transaction-type').on('change', function() {
                syncCashOperationOptions();
                applyMode();
            });
            $('#cash-operation')
                .off('change.cashOperation')
                .on('change.cashOperation', applyMode);
            $('#entry-mode').on('change', applyMode);

            $('#myForm').on('submit', function(e) {
                e.preventDefault();
                if (collectionState.submitting || supplierState.submitting) return;

                if (isCollectionMode()) {
                    if (!collectionState.canSubmit || !$('#object_id').val() || !rawAmount() ||
                        (isBankCollection && !$('#collection-money-account').val())) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Dữ liệu thu công nợ chưa hợp lệ hoặc chưa đối chiếu xong.'
                        });
                        return;
                    }
                } else if (isSupplierPaymentMode()) {
                    if (!supplierState.canSubmit || !$('#object_id').val() || !$('#supplier-import-id')
                        .val() || !rawAmount()) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Dữ liệu trả công nợ nhà cung cấp chưa hợp lệ hoặc chưa đối chiếu xong.'
                        });
                        return;
                    }
                } else if (!validateForm()) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Vui lòng điền đầy đủ các trường bắt buộc!'
                    });
                    return;
                }

                const collection = isCollectionMode();
                const supplierPayment = isSupplierPaymentMode();
                let formData;
                let requestUrl;

                if (collection) {
                    formData = new FormData();
                    formData.set('client_id', $('#object_id').val());
                    formData.set('amount', rawAmount());
                    formData.set('collection_date', $('[name="transaction_date"]').val());
                    formData.set('payment_method', collectionPaymentMethod);
                    if (isBankCollection) {
                        formData.set('money_account_id', $('#collection-money-account').val());
                    }
                    formData.set('note', $('[name="description"]').val());
                    formData.set('idempotency_key', $('#collection-idempotency-key').val());
                    if (fileInput?.files[0]) formData.set('attachment', fileInput.files[0]);
                    requestUrl = debtPanel.data('store-url');
                } else if (supplierPayment) {
                    formData = new FormData();
                    formData.set('import_coupon_id', $('#supplier-import-id').val());
                    formData.set('amount', rawAmount());
                    formData.set('payment_method', 'cash');
                    formData.set('transaction_date', $('[name="transaction_date"]').val());
                    formData.set('idempotency_key', $('#collection-idempotency-key').val());
                    requestUrl = supplierPanel.data('store-url');
                } else if (isUnifiedCash) {
                    formData = new FormData();
                    formData.set('direction', $('#cash-transaction-type').val());
                    formData.set('operation', $('#cash-operation').val());
                    formData.set('transaction_date', $('[name="transaction_date"]').val());
                    formData.set('amount', rawAmount());
                    formData.set('document_type', $('[name="document_type"]').val());
                    formData.set('reference_number', $('[name="reference_number"]').val());
                    formData.set('description', $('[name="description"]').val());
                    if (fileInput?.files[0]) formData.set('attachment', fileInput.files[0]);
                    requestUrl = fullUrl;
                } else {
                    formData = new FormData(this);
                    const objId = $('#object_id').val();
                    if (!objId) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Vui lòng chọn một đối tượng hợp lệ!'
                        });
                        return;
                    }
                    formData.set('obj_id', objId);
                    $(this).find('.usd-price-format').each(function() {
                        formData.set($(this).attr('name'), $(this).val().replace(/\./g, ''));
                    });
                    requestUrl = fullUrl;
                }

                collectionState.submitting = collection;
                supplierState.submitting = supplierPayment;
                setSubmitState();

                $.ajax({
                    url: requestUrl,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        if (collection) {
                            const details = (res.collection.allocations || []).map(allocation =>
                                `<tr><td>#${Number(allocation.order_id)}</td><td class="text-end">${money(allocation.allocated_amount)}</td></tr>`
                            ).join('');
                            Swal.fire({
                                icon: 'success',
                                title: 'Thu công nợ thành công',
                                html: `<div class="text-start">
                                    <p class="mb-1"><strong>Số phiếu:</strong> ${escapeHtml(res.collection.collection_number)}</p>
                                    <p class="mb-1"><strong>Khách hàng:</strong> ${escapeHtml($('#object_code').val())}</p>
                                    <p class="mb-1"><strong>Phương thức:</strong> ${isBankCollection ? 'Chuyển khoản' : 'Tiền mặt'}</p>
                                    <p class="mb-1"><strong>Tài khoản:</strong> ${escapeHtml(`${res.collection.money_account.code} - ${res.collection.money_account.name}`)}</p>
                                    <p><strong>Tổng thu:</strong> ${money(res.collection.total_amount)}</p>
                                    <table class="table table-sm"><thead><tr><th>Đơn hàng</th><th class="text-end">Phân bổ</th></tr></thead><tbody>${details}</tbody></table>
                                </div>`
                            });
                            $('#amount').val('');
                            $('#collection-idempotency-key').val(uuid());
                            if (fileInput) fileInput.value = '';
                            if (filePreviewArea) filePreviewArea.innerHTML = '';
                            loadDebtPreview();
                        } else if (supplierPayment) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Trả công nợ NCC thành công',
                                html: `<div class="text-start">
                                    <p class="mb-1"><strong>Phiếu nhập:</strong> ${escapeHtml(res.import_coupon?.coupon_code || `#${res.import_coupon?.id || ''}`)}</p>
                                    <p class="mb-1"><strong>Số tiền:</strong> ${money(res.summary?.paid_amount || rawAmount())}</p>
                                    <p><strong>Còn phải trả:</strong> ${money(res.summary?.remaining || res.import_coupon?.debt_amount || 0)}</p>
                                </div>`
                            });
                            $('#amount').val('');
                            $('#collection-idempotency-key').val(uuid());
                            loadSupplierImports();
                        } else if (res.success) {
                            window.location.href = res.redirect;
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: res.message ||
                                    'Đã có lỗi xảy ra, vui lòng thử lại sau!'
                            });
                        }
                    },
                    error: (xhr) => {
                        const errors = xhr.responseJSON?.errors || {};
                        let errorMessage = xhr.responseJSON?.message || 'Có lỗi xảy ra';
                        if (Object.keys(errors).length > 0) {
                            errorMessage = Object.values(errors).flat().join('<br>');
                        }
                        Toast.fire({
                            icon: 'error',
                            title: errorMessage,
                            html: errorMessage
                        });
                    },
                    complete: () => {
                        collectionState.submitting = false;
                        supplierState.submitting = false;
                        setSubmitState();
                    }
                });
            });

            const fileInput = document.getElementById('fileInput');
            const triggerFileInput = document.getElementById('triggerFileInput');
            const filePreviewArea = document.getElementById('filePreviewArea');
            const removeAttachmentBtn = document.getElementById('removeAttachmentBtn');
            const removeAttachment = document.getElementById('removeAttachment');

            triggerFileInput?.addEventListener('click', () => fileInput.click());
            fileInput?.addEventListener('change', () => {
                const file = fileInput.files[0];
                filePreviewArea.innerHTML = '';
                if (!file) return;

                const fileType = file.type;
                if (fileType.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.className = 'img-thumbnail';
                    img.style.maxWidth = '200px';
                    img.onload = () => URL.revokeObjectURL(img.src);
                    filePreviewArea.appendChild(img);
                } else if (fileType === 'application/pdf') {
                    const iframe = document.createElement('iframe');
                    iframe.src = URL.createObjectURL(file);
                    iframe.width = '200';
                    iframe.height = '250';
                    iframe.onload = () => URL.revokeObjectURL(iframe.src);
                    filePreviewArea.appendChild(iframe);
                } else {
                    const div = document.createElement('div');
                    div.innerHTML = `<i class="bi bi-file-earmark-text me-1"></i> ${file.name}`;
                    filePreviewArea.appendChild(div);
                }
            });

            removeAttachmentBtn?.addEventListener('click', () => {
                if (confirm('Bạn có chắc chắn muốn xoá file đính kèm này?')) {
                    removeAttachment.value = '1';
                    removeAttachmentBtn.closest('div').remove();
                }
            });

            function validateForm() {
                let isValid = true;
                const requiredFields = document.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                return isValid;
            }

            if (isUnifiedCash) syncCashOperationOptions();
            applyMode();
        });
    </script>
@endpush

@push('style')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0;
        }

        .section-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            font-size: 16px;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-content {
            padding: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }

        .required {
            color: #dc3545;
        }

        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 14px;
        }

        #cash-transaction-type+.select2-container .select2-selection--single,
        #cash-operation+.select2-container .select2-selection--single,
        #object-type+.select2-container .select2-selection--single,
        #account_id+.select2-container .select2-selection--single,
        #type+.select2-container .select2-selection--single,
        #collection-money-account+.select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: #fff;
        }

        #cash-transaction-type+.select2-container .select2-selection__rendered,
        #cash-operation+.select2-container .select2-selection__rendered,
        #object-type+.select2-container .select2-selection__rendered,
        #account_id+.select2-container .select2-selection__rendered,
        #type+.select2-container .select2-selection__rendered,
        #collection-money-account+.select2-container .select2-selection__rendered {
            line-height: 36px;
        }

        #cash-transaction-type+.select2-container .select2-selection__arrow,
        #cash-operation+.select2-container .select2-selection__arrow,
        #object-type+.select2-container .select2-selection__arrow,
        #account_id+.select2-container .select2-selection__arrow,
        #type+.select2-container .select2-selection__arrow,
        #collection-money-account+.select2-container .select2-selection__arrow {
            height: 36px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #fafafa;
            margin-top: 10px;
        }

        .file-upload-text {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .btn-file {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            padding: 6px 16px;
            font-size: 14px;
            border-radius: 4px;
        }

        .btn-file:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .section-divider {
            border-left: 1px solid #dee2e6;
        }

        .is-invalid {
            border-color: #dc3545;
        }

        @media (max-width: 768px) {
            .section-divider {
                border-left: none;
                border-top: 1px solid #dee2e6;
                margin-top: 20px;
                padding-top: 20px;
            }
        }
    </style>
@endpush
