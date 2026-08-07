@extends('admin.layout.index')

@section('content')
    <div class="page-inner accounting-account-page">
        <div class="page-header accounting-account-breadcrumb">
            <x-breadcrumb :items="[['label' => 'DANH SÁCH TÀI KHOẢN KẾ TOÁN']]" />
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
                    <span class="text-muted">DANH SÁCH TÀI KHOẢN KẾ TOÁN</span>
                </li>
            </ul> --}}
        </div>

        <div class="card accounting-account-card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between accounting-account-toolbar">
                    <div class="d-flex align-items-center gap-2 accounting-account-actions">
                        <button type="button" class="btn btn-success btn-sm fs-6 accounting-account-add-btn" data-bs-toggle="modal"
                            data-bs-target="#addAccountModal">
                            <i class="ti ti-circle-plus"></i> Thêm mới
                        </button>

                        <div class="dropdown accounting-account-bulk-actions">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle accounting-account-bulk-btn" type="button"
                                id="operationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Thao tác
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="operationDropdown">
                                {{-- <li>
                                    <a class="dropdown-item" href="#" id="exportExcel"><i
                                            class="far fa-file-excel me-2"></i>Xuất excel</a>
                                </li> --}}
                                <li>
                                    <a class="dropdown-item text-danger" href="#" id="delete-selected"> <i
                                            class="far fa-trash-alt me-2"></i>Xóa các dòng đã
                                        chọn</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="w-25 accounting-account-search">
                        <input type="text" class="form-control accounting-account-search-input" id="searchInput" placeholder="Tìm kiếm mã hoặc tên...">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive accounting-account-table-scroll">
                    <table id="myTable" class="table table-hover table-striped accounting-account-table">
                        <thead>
                            <tr class="text-center">
                                <th class="account-col-check" style="width:5%"><input type="checkbox" id="checked-all"></th>
                                <th class="account-col-index" style="width:5%">#</th>
                                <th class="account-col-code" style="width:15%">Code</th>
                                <th class="account-col-name">Tên</th>
                                <th class="account-col-default" style="width:15%">Là tài khoản mặc định?</th>
                                <th class="account-col-status" style="width:15%">Tình trạng</th>
                                <th class="account-col-creator" style="width:15%">Người tạo</th>
                                <th class="account-col-action" style="width:5%"><i class="fas fa-cog"></i></th>
                            </tr>
                        </thead>

                        <tbody>
                        </tbody>

                    </table>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="myForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-medium">Thêm tài khoản kế toán</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 position-relative">
                            <label for="parent_search" class="form-label">Tài khoản cha</label>
                            <input type="text" id="parent_search" class="form-control"
                                placeholder="Nhập mã hoặc tên tài khoản cha...">
                            <input type="hidden" id="parent_id" name="parent_id">
                            <div id="parent_results" class="list-group position-absolute w-100"
                                style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label">Mã tài khoản</label>
                            <input type="text" name="code" id="code" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên tài khoản</label>
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Tình trạng</label>
                            <label class="switch">
                                <input name="status" type="checkbox" value="1" checked="">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
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

            let typingTimer;
            const doneTypingInterval = 300; // ms

            $('#parent_search').on('keyup', function() {
                clearTimeout(typingTimer);
                const query = $(this).val();

                if (query.length >= 3) {
                    typingTimer = setTimeout(function() {
                        $.ajax({
                            url: '{{ route('admin.accounts.search') }}',
                            data: {
                                q: query
                            },
                            success: function(data) {
                                let resultBox = $('#parent_results');
                                resultBox.empty();

                                if (data.length > 0) {
                                    data.forEach(function(item) {
                                        resultBox.append(
                                            `<a href="#" class="list-group-item list-group-item-action" data-id="${item.id}" data-text="${item.code} - ${item.name}">
                                                ${item.code} - ${item.name}
                                            </a>`
                                        );
                                    });
                                    resultBox.show();
                                } else {
                                    resultBox.append(
                                        `<div class="list-group-item text-muted text-center">
                                            Không tìm thấy kết quả
                                        </div>`
                                    );
                                    resultBox.show();
                                }
                            }
                        });
                    }, doneTypingInterval);
                } else {
                    $('#parent_results').hide();
                }
            });

            $('#parent_results').on('click', 'a', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const text = $(this).data('text');

                $('#parent_id').val(id);
                $('#parent_search').val(text);
                $('#parent_results').hide();
            });

            // Ẩn khi click ra ngoài
            $(document).click(function(e) {
                if (!$(e.target).closest('#parent_search, #parent_results').length) {
                    $('#parent_results').hide();
                }
            });

            $('#myForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content')
                    },
                    success: (res) => {
                        Toast.fire({
                            icon: "success",
                            title: res.message
                        });

                        reloadAccounts();
                        $('#addAccountModal').modal('hide');
                    },
                    error: (xhr) => {
                        Toast.fire({
                            icon: "error",
                            title: xhr.responseJSON.message ||
                                'Đã có lỗi xảy ra, vui lòng thử lại sau!'
                        });
                    }
                })
            })

            function reloadAccounts() {
                const keyword = $('#searchInput').val();

                $.ajax({
                    url: "/admin/accounts/ajax/list",
                    type: "GET",
                    data: {
                        keyword: keyword
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#myTable tbody').html(res.html);
                        }
                    },
                    error: function(xhr) {
                        Notifications('Tải dữ liệu thất bại', "error");
                    },
                });
            }

            let debounceTimer;
            $('#searchInput').on('keyup', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    reloadAccounts();
                }, 300); // 300ms delay
            });


            $('#addAccountModal').on('hidden.bs.modal', function() {
                $('#myForm')[0].reset();
                $('#parent_id').val('');
                $('#parent_search').val('');
                $('#myForm input[name="_method"]').remove();
                $('#myForm input[name="id"]').remove();
            });

            $(document).on('click', '.btn-edit-account', function() {
                let id = $(this).data('id');
                let code = $(this).data('code');
                let name = $(this).data('name');
                let status = $(this).data('status');
                let parentId = $(this).data('parent-id');
                let parentName = $(this).data('parent-name') || '';

                // Điền dữ liệu cũ vào modal
                $('#code').val(code);
                $('#name').val(name);
                $('#parent_id').val(parentId);
                $('#parent_search').val(parentName);

                // Set trạng thái
                if (status == 1) {
                    $('input[name="status"]').prop('checked', true);
                } else {
                    $('input[name="status"]').prop('checked', false);
                }

                // Thêm input _method = PUT nếu chưa có
                if ($('#myForm input[name="_method"]').length === 0) {
                    $('#myForm').append('<input type="hidden" name="_method" value="PUT">');
                    $('#myForm').append(`<input type="hidden" name="id" value="${id}">`);
                }

                // Mở modal
                $('#addAccountModal').modal('show');
            });


            $(document).on('click', '.btn-add-child', function() {
                let parentId = $(this).data('id');
                let parentName = $(this).data('name');

                // Gán giá trị vào input ẩn và hiển thị
                $('#parent_id').val(parentId);
                $('#parent_search').val(parentName);

                // Mở modal
                $('#addAccountModal').modal('show');
            });

            // Khi click vào checkbox "checked-all"
            $('#checked-all').on('change', function() {
                let isChecked = $(this).is(':checked');
                $('.item-checkbox').prop('checked', isChecked);
            });

            // Khi click vào từng checkbox hàng
            $(document).on('change', '.item-checkbox', function() {
                let totalCheckboxes = $('.item-checkbox').length;
                let checkedCheckboxes = $('.item-checkbox:checked').length;

                if (totalCheckboxes === checkedCheckboxes) {
                    $('#checked-all').prop('checked', true);
                } else {
                    $('#checked-all').prop('checked', false);
                }
            });

            $(document).on('click', '#delete-selected', function() {
                let ids = $('.item-checkbox:checked').map(function() {
                    return $(this).data('id');
                }).get();

                if (ids.length === 0) {
                    Notifications('Vui lòng chọn ít nhất một tài khoản để xoá.', 'warning');
                    return;
                }

                Swal.fire({
                    title: "Bạn có chắc chắn muốn xóa?",
                    text: "Hành động này sẽ không thể hoàn tác!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Đồng ý, xóa!",
                    cancelButtonText: "Hủy",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '',
                            type: 'DELETE',
                            data: {
                                ids: ids,
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(res) {
                                Toast.fire({
                                    icon: "success",
                                    title: res.message
                                });
                                reloadAccounts();

                            },
                            error: function(xhr) {
                                Toast.fire({
                                    icon: "error",
                                    title: xhr.responseJSON.message
                                });
                            },
                        });
                    } else {
                        $('.item-checkbox, #checked-all').prop('checked', false)
                    }
                })

            });

            $('#exportExcel').on('click', function(e) {
                e.preventDefault();

                window.location.href = '/admin/accounting-accounts/export';
            });

            reloadAccounts()
        });
    </script>
@endpush


@push('style')
    <style>
        .accounting-account-page #parent_results .list-group-item:hover {
            background-color: rgba(108, 122, 145);
            color: #ffffff !important
        }

        .accounting-account-page .accounting-account-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .accounting-account-page .account-code-wrap,
        .accounting-account-page .account-name-wrap {
            padding-left: calc(var(--account-level, 0) * 16px);
        }

        .accounting-account-page .account-action-dropdown .dropdown-menu {
            z-index: 1060;
        }

        @media (max-width: 767.98px) {
            .accounting-account-page {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding-right: 0 !important;
                padding-left: 0 !important;
                margin-right: auto;
                margin-left: auto;
                overflow-x: visible;
            }

            .accounting-account-page .accounting-account-breadcrumb,
            .accounting-account-page .accounting-account-card {
                width: calc(100% - 20px);
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
            }

            .accounting-account-page .accounting-account-breadcrumb {
                padding-right: 0;
                padding-left: 0;
            }

            .accounting-account-page .accounting-account-breadcrumb > nav,
            .accounting-account-page .accounting-account-breadcrumb .breadcrumb,
            .accounting-account-page .accounting-account-breadcrumb .breadcrumbs {
                width: 100%;
                max-width: 100%;
                margin-right: 0;
                margin-left: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .accounting-account-page .accounting-account-card,
            .accounting-account-page .card-header,
            .accounting-account-page .card-body,
            .accounting-account-page .accounting-account-table-scroll {
                min-width: 0;
            }

            .accounting-account-page .card-header {
                padding: 12px;
                overflow-x: visible;
            }

            .accounting-account-page .accounting-account-toolbar {
                display: flex !important;
                flex-wrap: wrap;
                align-items: stretch !important;
                gap: 8px;
                width: 100%;
                min-width: 0;
            }

            .accounting-account-page .accounting-account-search {
                order: 1;
                flex: 0 0 100%;
                width: 100% !important;
                max-width: 100%;
                min-width: 0;
            }

            .accounting-account-page .accounting-account-search-input {
                width: 100%;
                min-width: 0;
                height: 40px;
            }

            .accounting-account-page .accounting-account-actions {
                order: 2;
                display: grid !important;
                grid-template-columns: minmax(0, 1.55fr) minmax(118px, 1fr);
                gap: 8px !important;
                width: 100%;
                min-width: 0;
            }

            .accounting-account-page .accounting-account-add-btn,
            .accounting-account-page .accounting-account-bulk-btn {
                width: 100%;
                min-width: 0;
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding-right: 10px;
                padding-left: 10px;
                white-space: nowrap;
            }

            .accounting-account-page .accounting-account-bulk-actions {
                min-width: 0;
            }

            .accounting-account-page .card-body {
                padding: 12px;
                overflow-x: visible;
            }

            .accounting-account-page .accounting-account-table {
                min-width: 1026px;
                margin-bottom: 0;
                table-layout: auto;
            }

            .accounting-account-page .accounting-account-table th,
            .accounting-account-page .accounting-account-table td {
                vertical-align: middle;
            }

            .accounting-account-page .accounting-account-table .account-col-check {
                width: 44px !important;
                min-width: 44px;
                max-width: 44px;
                text-align: center;
                white-space: nowrap;
            }

            .accounting-account-page .accounting-account-table .account-col-index {
                width: 60px !important;
                min-width: 60px;
                max-width: 60px;
                white-space: nowrap;
            }

            .accounting-account-page .accounting-account-table .account-col-code {
                width: 110px !important;
                min-width: 110px;
                max-width: 110px;
                white-space: nowrap;
            }

            .accounting-account-page .accounting-account-table .account-col-name {
                width: 280px !important;
                min-width: 280px;
                max-width: 280px;
                word-break: normal;
                overflow-wrap: break-word;
            }

            .accounting-account-page .accounting-account-table .account-col-default {
                width: 180px !important;
                min-width: 180px;
                max-width: 180px;
                text-align: center;
            }

            .accounting-account-page .accounting-account-table .account-col-status {
                width: 130px !important;
                min-width: 130px;
                max-width: 130px;
                text-align: center;
                white-space: nowrap;
            }

            .accounting-account-page .accounting-account-table .account-col-creator {
                width: 150px !important;
                min-width: 150px;
                max-width: 150px;
                text-align: center;
                white-space: nowrap;
            }

            .accounting-account-page .accounting-account-table .account-col-action {
                width: 72px !important;
                min-width: 72px;
                max-width: 72px;
                text-align: center;
                white-space: nowrap;
            }

            .accounting-account-page .account-code-wrap {
                display: inline-block;
                white-space: nowrap;
            }

            .accounting-account-page .account-name-wrap {
                display: flex;
                align-items: flex-start;
                gap: 5px;
                min-width: 0;
            }

            .accounting-account-page .account-name-text {
                min-width: 0;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
                white-space: normal;
                word-break: normal;
                overflow-wrap: break-word;
            }

            .accounting-account-page .account-name-wrap > i {
                flex: 0 0 auto;
                margin-top: 2px;
            }

            .accounting-account-page .account-boolean-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 20px;
                min-height: 20px;
            }

            .accounting-account-page .account-action-btn {
                width: 38px;
                height: 38px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                white-space: nowrap;
            }

            .accounting-account-page .account-action-dropdown .dropdown-menu {
                min-width: 190px;
            }
        }
    </style>
@endpush
