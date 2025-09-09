@extends('admin.layout.index')


@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Nhập công nợ đầu kỳ']]" />

        <form id="myForm" enctype="multipart/form-data">

            <div class="row">
                <div class="gap-3 col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-body">
                                <div class="row g-3">

                                    <div class="position-relative col-md-6">
                                        <label for="transaction_date" class="form-label">Ngày thu chi</label>
                                        <input type="date" placeholder="Chọn ngày thu chi" name="transaction_date"
                                            id="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                                    </div>

                                    <div class="position-relative col-md-6">
                                        <label class="form-label required">Loại đối tượng</label>
                                        <select name="object_type" id="object-type" class="form-select">
                                            <option value=""></option>
                                            <option value="client">Khách hàng</option>
                                            <option value="supplier">Nhà cung cấp</option>
                                        </select>
                                    </div>

                                    <div class="position-relative col-md-6">
                                        <label class="form-label required">Loại phiếu</label>
                                        <select class="form-select" name="type" id="type">
                                            <option value=""></option>
                                            <option value="income">Phiếu thu</option>
                                            <option value="expense">Phiếu trả</option>
                                        </select>
                                    </div>

                                    <div class=" col-md-6">
                                        <div class="position-relative">
                                            <label class="form-label">Đối tượng</label>

                                            <input type="text" id="object_code" class="form-control"
                                                placeholder="Nhập 3 ký tự để tìm đối tượng" value="">

                                            <input type="hidden" name="object_id" value="">

                                            <div id="object-search-result"
                                                class="border bg-white position-absolute w-100 shadow-sm"
                                                style="z-index: 9999; display: none;">
                                                <!-- Kết quả sẽ render tại đây -->
                                            </div>
                                        </div>

                                    </div>

                                    <div class="position-relative col-md-6">
                                        <label for="amount" class="form-label required">Số tiền</label>
                                        <input type="text" placeholder="Nhập số tiền" name="amount" id="amount"
                                            class="form-control usd-price-format">
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="col-md-3 d-flex flex-column-reverse flex-md-column mb-md-0 mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title fs-6 fw-bold">Xuất bản</h4>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary btn-sm fs-6" id="submitRequestBtn">
                                <svg class="icon icon-left svg-icon-ti-ti-device-floppy" xmlns="http://www.w3.org/2000/svg"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"></path>
                                    <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                    <path d="M14 4l0 4l-6 0l0 -4"></path>
                                </svg>
                                Lưu
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title fs-6 fw-bold">Ghi chú</h4>
                        </div>
                        <div class="card-body">
                            <textarea name="description" rows="3" class="form-control" id="description" placeholder="Nhập ghi chú"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

            $('#object-type').select2({
                placeholder: "Chọn loại đối tượng",
                allowClear: true,
                width: '100%'
            });

            $('#type').select2({
                placeholder: "Chọn loại đối tượng",
                allowClear: true,
                width: '100%'
            });

            let typingTimer;
            let doneTypingInterval = 500;

            $('#object_code').on('keyup', function() {
                clearTimeout(typingTimer);
                let keyword = $(this).val();
                let type = $('#object-type').val();

                if (keyword.length >= 3 && type) {
                    typingTimer = setTimeout(function() {
                        $.ajax({
                            url: '/admin/transactions/cash/search',
                            data: {
                                type: type,
                                keyword: keyword
                            },
                            success: function(res) {
                                let html = '';
                                if (res.length > 0) {
                                    res.forEach(item => {
                                        html += `<div class="p-2 border-bottom object-item" style="cursor: pointer;" data-id="${item.id}" data-phone="${item.phone}" data-code="${item.code}" data-name="${item.name}">
                                            ${item.name} - ${item.phone}
                                        </div>`;
                                    });
                                } else {
                                    html =
                                        `<div class="p-2 text-muted text-center">Không tìm thấy dữ liệu phù hợp</div>`;
                                }
                                $('#object-search-result').html(html).show();
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
                $('#object_code').val(name + ' - ' + phone);
                $('input[name="object_id"]').val(id);
                $('#object-search-result').hide();
            });

            $('#myForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                $(this).find('.usd-price-format').each(function() {
                    const name = $(this).attr("name");
                    const rawValue = $(this).val().replace(/\./g, "");
                    formData.set(name, rawValue);
                });

                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: (res) => {

                        window.location.href = res.data

                    },
                    error: (xhr) => {
                        Toast.fire({
                            icon: "error",
                            title: xhr.responseJSON.message ||
                                'Đã có lỗi xảy ra, vui lòng thử lại sau!'
                        });
                    }
                });
            });

            function formatPrice($input) {
                let originalValue = $input.val();
                let cursorPos = $input.prop("selectionStart");

                // Xoá tất cả ký tự không phải số
                let value = originalValue.replace(/\D/g, "");

                // Format lại theo dấu chấm ngăn cách hàng nghìn
                let newValue = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

                // Gán giá trị mới vào input
                $input.val(newValue);

                // Tính lại vị trí con trỏ
                if (cursorPos !== null) {
                    // Đếm số dấu chấm trước và sau khi format
                    let oldDots = (originalValue.slice(0, cursorPos).match(/\./g) || []).length;
                    let newDots = (newValue.slice(0, cursorPos + (newValue.length - originalValue.length)).match(
                        /\./g) || []).length;

                    let newCursorPos = cursorPos + (newDots - oldDots);
                    newCursorPos = Math.min(newCursorPos, newValue.length);

                    // Đặt lại vị trí con trỏ
                    $input[0].setSelectionRange(newCursorPos, newCursorPos);
                }
            }


            $(document).on("input", ".usd-price-format", function(e) {
                if (
                    e.originalEvent.inputType === "insertText" &&
                    e.originalEvent.data === "."
                ) {
                    return;
                }
                formatPrice($(this));
            });

            // Format lại khi mất focus (blur)
            $(document).on("blur", ".usd-price-format", function() {
                formatPrice($(this));
            });
        })
    </script>
@endpush

@push('style')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
