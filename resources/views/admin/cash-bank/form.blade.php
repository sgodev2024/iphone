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

                <div class="row">
                    <div class="col-lg-8 pe-0">
                        <div class="section-header">
                            <i class="fas fa-info-circle"></i>
                            Thông tin
                        </div>
                        <div class="section-content">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="form-label">Ngày thu chi</label>
                                    <input type="date" class="form-control" name="transaction_date"
                                        value="{{ optional($transaction)->transaction_date ? optional($transaction)->transaction_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                                        required>
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label required">Loại đối tượng</label>
                                    <select name="obj_type" id="object-type" class="form-select" required>
                                        <option value="">Chọn loại đối tượng</option>
                                        <option value="client" @selected(optional($contraEntry)->tableable_type === 'App\Models\Client')>Khách hàng</option>
                                        <option value="supplier" @selected(optional($contraEntry)->tableable_type === 'App\Models\Supplier')>Nhà cung cấp</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">Tài khoản tiền mặt</label>
                                    <select class="form-select" name="account_id" id="account_id" required>
                                        <option value="">Chọn tài khoản</option>
                                        @foreach ($moneyAccounts as $moneyAccount)
                                            <option value="{{ $moneyAccount->id }}" @selected(optional($mainEntry)->account_id == $moneyAccount->id)>
                                                {{ "$moneyAccount->code - $moneyAccount->name" }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <div class="position-relative">
                                        <label class="form-label required">Đối tượng</label>
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

                                <div class="col-md-6">
                                    <label class="form-label required">Loại phiếu</label>
                                    <select class="form-select" name="type" id="type" required>
                                        <option value="">Chọn loại phiếu</option>
                                        @if ($type === 'cash')
                                            <option value="income" @selected(optional($transaction)->type === 'income')>Phiếu thu</option>
                                            <option value="expense" @selected(optional($transaction)->type === 'expense')>Phiếu chi</option>
                                        @else
                                            <option value="debit_notice" @selected(optional($transaction)->type === 'debit_notice')>Báo nợ (Rút tiền)
                                            </option>
                                            <option value="credit_notice" @selected(optional($transaction)->type === 'credit_notice')>Báo có (Nộp tiền)
                                            </option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Loại chứng từ</label>
                                    <input type="text" name="document_type" placeholder="ví dụ: Đơn hàng"
                                        class="form-control" value="{{ optional($transaction)->document_type }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">ID chứng từ</label>
                                    <input type="text" name="reference_number"
                                        value="{{ optional($transaction)->reference_number }}"
                                        placeholder="Nhập ID chứng từ" class="form-control">
                                </div>

                                <div class="col-12">
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
                                            Chọn file jpg, jpeg, gif, png, doc,... &lt;= 8MB
                                        </div>
                                        <button type="button" class="btn btn-file" id="triggerFileInput">
                                            <i class="bi bi-upload me-1"></i>
                                            Chọn File
                                        </button>
                                        <input type="file" class="d-none" name="attachment" id="fileInput"
                                            accept=".jpg,.jpeg,.gif,.png,.doc,.docx,.pdf" maxlength="8">
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
                                <label class="form-label required">Số tiền (VND)</label>
                                <input type="text" name="amount" class="form-control usd-price-format"
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
                        <button type="submit" class="btn btn-primary btn-sm">
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
            });

            $(document).on("blur", ".usd-price-format", function() {
                formatPrice($(this));
            });

            $('#object-type, #type, #account_id').select2({
                placeholder: function() {
                    return $(this).attr('placeholder') || "Chọn một tùy chọn";
                },
                allowClear: true,
                width: '100%'
            });

            let typingTimer;
            const doneTypingInterval = 500;

            $('#object-type').on('change', function() {
                $('#object_code').val('');
                $('#object_id').val('');
                $('#object-search-result').hide();
            });

            $('#object_code').on('keyup', function() {
                clearTimeout(typingTimer);
                let keyword = $(this).val().trim();
                let type = $('#object-type').val();

                if (keyword.length >= 3 && type) {
                    typingTimer = setTimeout(() => {
                        $.ajax({
                            url: '/admin/transactions/cash/search',
                            data: {
                                type,
                                keyword
                            },
                            success: function(res) {
                                let html = '';
                                if (res.length > 0) {
                                    res.forEach(item => {
                                        html += `<div class="p-2 border-bottom object-item" style="cursor: pointer;" data-id="${item.id}" data-phone="${item.phone}" data-name="${item.name}">
                                            ${item.name} - ${item.phone}
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

            $('#myForm').on('submit', function(e) {
                e.preventDefault();
                if (!validateForm()) {
                    alert('Vui lòng điền đầy đủ các trường bắt buộc!');
                    return;
                }

                let formData = new FormData(this);
                // Đảm bảo obj_id được bao gồm
                let objId = $('#object_id').val();
                if (!objId) {
                    alert('Vui lòng chọn một đối tượng hợp lệ!');
                    return;
                }
                formData.set('obj_id', objId);

                $(this).find('.usd-price-format').each(function() {
                    const name = $(this).attr('name');
                    const rawValue = $(this).val().replace(/\./g, '');
                    formData.set(name, rawValue);
                });

                $.ajax({
                    url: fullUrl,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        if (res.success) {
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
