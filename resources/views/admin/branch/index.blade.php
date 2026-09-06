@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Danh sách chi nhánh']]" />

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Danh sách chi nhánh</h5>
                        <button class="btn btn-primary" id="show-modal"
                            data-has-available-admin-store="{{ $adminStoreUsers->isNotEmpty() ? '1' : '0' }}">
                            Thêm mới
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" id="branch-actions-button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Thao tác
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" id="bulk-delete">
                                            <i class="fa-solid fa-trash me-2"></i> Xóa đã chọn
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" id="bulk-status">
                                            <i class="fa-solid fa-toggle-on me-2"></i> Thay đổi trạng thái
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-end align-items-center">
                                <input type="text" name="search" class="form-control me-2" style="width: 300px;"
                                    placeholder="Nhập tên chi nhánh">

                                <button type="button" class="btn" id="btn-reset"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>

                        <div id="branch-table-wrapper">
                            @include('admin.branch.table', compact('branchs'))
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Modal -->
    <div class="modal fade" id="branchModal" tabindex="-1" aria-labelledby="branchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-extrabold" id="branchModalLabel">Chi tiết chi nhánh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <form id="branchForm" data-method="POST" data-id="">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tên chi nhánh</label>
                                <input type="text" class="form-control" name="name" id="branch-name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Admin Store</label>
                                @if (auth()->user()?->isAdministrator())
                                    <select class="form-select" name="admin_store_user_id" id="branch-admin_store_user_id" required>
                                        <option value="">Chọn Admin Store</option>
                                        @foreach ($adminStoreUsers as $adminStoreUser)
                                            <option value="{{ $adminStoreUser->id }}">{{ $adminStoreUser->name }} ({{ $adminStoreUser->email }})</option>
                                        @endforeach
                                    </select>
                                    @if ($adminStoreUsers->isEmpty())
                                        <div class="form-text text-warning" id="admin-store-empty-message">Không còn tài khoản Admin Store chưa được gán cửa hàng.</div>
                                    @endif
                                @else
                                    <input type="text" class="form-control" name="manager_name" id="branch-manager_name" disabled>
                                @endif
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Địa chỉ</label>
                                <input type="text" class="form-control" name="address" id="branch-address">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Điện thoại</label>
                                <input type="text" class="form-control" name="phone" id="branch-phone">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" name="email" id="branch-email">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trạng thái</label>
                                <select class="form-select" name="status" id="branch-status">
                                    <option value="1">Hoạt động</option>
                                    <option value="0">Ngừng hoạt động</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" form="branchForm" class="btn btn-primary btn-sm">Lưu thay đổi</button>
                </div>

            </div>
        </div>
    </div>
@endsection


@push('script')
    <script>
        $(document).ready(function() {

            let currentPage = 1;
            const branchIndexUrl = @json(route('admin.branches.index'));
            const branchBulkDeleteUrl = @json(route('admin.branches.bulk-destroy'));
            const branchShowUrl = @json(route('admin.branches.show', ['id' => '__BRANCH_ID__']));
            const branchStoreUrl = @json(route('admin.branches.store'));
            const branchUpdateUrl = @json(route('admin.branches.update', ['id' => '__BRANCH_ID__']));
            const adminStoreCreateUrl = @json(route('admin.users.create'));

            function debounce(fn, delay = 500) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            $('#show-modal').click(function() {
                const hasAvailableAdminStore = $(this).data('has-available-admin-store') === 1

                if (!hasAvailableAdminStore) {
                    const message =
                        'Không thể thêm chi nhánh vì không còn tài khoản Admin Store chưa được gán. Vui lòng tạo một tài khoản Admin Store mới trước.'

                    if (typeof window.Swal?.fire !== 'function') {
                        window.alert(message)
                        return
                    }

                    Swal.fire({
                        icon: 'warning',
                        title: 'Chưa thể thêm chi nhánh',
                        text: message,
                        showCancelButton: true,
                        confirmButtonText: 'Tạo Admin Store',
                        cancelButtonText: 'Đóng'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = adminStoreCreateUrl
                        }
                    })

                    return
                }

                $('#branchModal').modal('show')
                $('#branchForm')[0].reset()
                $('#branch-admin_store_user_id option[data-current-admin-store]').remove()
                $('#branch-admin_store_user_id').prop('disabled', $('#branch-admin_store_user_id option').length <= 1)
                $('#branchForm').attr({
                    'data-method': 'POST',
                    'data-id': ''
                })

            })

            let searchText = ''; // giữ giá trị search hiện tại

            // Click phân trang
            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchBranches(page, searchText);
            });

            // Search input
            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchBranches(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                fetchBranches()
            })

            // Hàm fetch data
            const fetchBranches = (page = 1, search = '') => {
                $.ajax({
                    url: branchIndexUrl,
                    method: 'GET',
                    data: {
                        page,
                        s: search
                    },
                    success: (res) => {
                        // Cập nhật table + pagination
                        $('#branch-table-wrapper').html(res.html);
                        currentPage = page
                    },
                    error: () => {
                        datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                    }
                })
            }

            $(document).on('click', '.btn-delete', function() {
                const $button = $(this)
                const deleteUrl = $(this).data('delete-url')

                confirmBranchDelete(deleteUrl, {}, $button)
            });

            $(document).on('click', '.btn-edit', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: branchShowUrl.replace('__BRANCH_ID__', id),
                    type: 'GET',
                    success: (res) => {
                        const current = res.data.admin_store;
                        const $select = $('#branch-admin_store_user_id');
                        if (current && !$select.find(`option[value="${current.id}"]`).length) {
                            $select.append(new Option(`${current.name} (${current.email})`, current.id, false, false)).find('option:last').attr('data-current-admin-store', '1');
                        }
                        $select.prop('disabled', false);

                        $.each(res.data, function(key, item) {
                            $(`input[name="${key}"], select[name="${key}"]`).val(item)
                            $('#branchForm').attr('data-method', 'PUT')
                        })
                        $('#branchForm').attr('data-id', id)

                        $('#branchModal').modal('show')
                    },
                    error: (xhr) => {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            renderValidationErrors($form, xhr.responseJSON.errors)
                            datgin.warning(xhr.responseJSON.message || 'Vui long kiem tra lai thong tin.')
                            return
                        }

                        datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                    }
                })

            })

            $('#bulk-delete').click(function(e) {
                e.preventDefault()
                const ids = $('.checked-item:checked').map((i, el) => $(el).val()).get()

                if (ids.length <= 0) return datgin.warning('Vui lòng chọn ít nhất 1 hàng!')

                confirmBranchDelete(branchBulkDeleteUrl, {
                    ids
                }, $('#branch-actions-button'))
            })

            function showBranchDeleteNotification(type, message) {
                try {
                    if (typeof window.datgin?.[type] === 'function') {
                        window.datgin[type](message)
                        return
                    }
                } catch (error) {
                    console.error('Branch delete notification failed.', error)
                }

                Swal.fire({
                    icon: type === 'success' ? 'success' : 'error',
                    title: type === 'success' ? 'Đã xóa chi nhánh' : 'Không thể xóa chi nhánh',
                    text: message
                })
            }

            function confirmBranchDelete(url, data = {}, $trigger = $()) {
                const isBulkDelete = Array.isArray(data.ids)

                Swal.fire({
                    title: "Xác nhận xóa?",
                    text: isBulkDelete
                        ? "Bạn có chắc muốn xóa các chi nhánh đã chọn?"
                        : "Bạn có chắc muốn xóa chi nhánh này?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Vâng, xóa ngay!",
                    cancelButtonText: "Hủy"
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return
                    }

                    const originalButtonState = {
                        disabled: $trigger.prop('disabled'),
                        html: $trigger.html(),
                        opacity: $trigger[0]?.style.opacity ?? '',
                        ariaBusy: $trigger.attr('aria-busy')
                    }

                    $.ajax({
                        url,
                        method: 'DELETE',
                        data,
                        timeout: 15000,
                        beforeSend: () => {
                            if (!$trigger.length) return

                            $trigger
                                .prop('disabled', true)
                                .attr('aria-busy', 'true')
                                .css('opacity', '0.65')
                                .html('<i class="fa-solid fa-spinner fa-spin"></i>')
                        },
                        success: (res) => {
                            showBranchDeleteNotification('success', res.message)
                            $('input[type="checkbox"]').prop('checked', false)
                            fetchBranches(currentPage, searchText)
                        },
                        error: (xhr) => {
                            const response = xhr.responseJSON
                            const message = response?.message ||
                                response?.errors?.branch?.[0] ||
                                'Không thể xóa chi nhánh. Vui lòng kiểm tra dữ liệu liên quan.'

                            showBranchDeleteNotification('error', message)
                        },
                        complete: () => {
                            if (!$trigger.length) return

                            $trigger
                                .prop('disabled', originalButtonState.disabled)
                                .html(originalButtonState.html)
                                .css('opacity', originalButtonState.opacity)

                            if (originalButtonState.ariaBusy === undefined) {
                                $trigger.removeAttr('aria-busy')
                            } else {
                                $trigger.attr('aria-busy', originalButtonState.ariaBusy)
                            }
                        }
                    })
                })
            }

            $('#bulk-status').click(function() {
                const ids = $('.checked-item:checked').map((i, el) => $(el).val()).get()

                if (ids.length <= 0) return datgin.warning('Vui lòng chọn ít nhất 1 hàng!')

                handleChangeStatus(ids)
            })

            function handleChangeStatus(ids) {
                Swal.fire({
                    title: "Xác nhận thay đổi trạng thái?",
                    text: "Bạn có chắc chắn muốn cập nhật trạng thái cho các chi nhánh đã chọn?",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Vâng, cập nhật!",
                    cancelButtonText: "Hủy"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: @json(route('admin.branches.status.update')),
                            method: 'PATCH',
                            data: {
                                ids
                            },
                            success: (res) => {
                                datgin.success(res.message)
                                fetchBranches()
                                $('input[type="checkbox"]').prop('checked', false);
                            },
                            error: (xhr) => {
                                datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                            }
                        })
                    }
                });
            }

            $('#branchForm').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this)
                clearValidationErrors($form)

                let formData = $form.serializeArray()

                let method = $form.attr('data-method')

                method === 'PUT' && formData.push({
                    name: '_method',
                    value: 'PUT'
                })

                $.ajax({
                    url: method === 'PUT'
                        ? branchUpdateUrl.replace('__BRANCH_ID__', $form.attr('data-id'))
                        : branchStoreUrl,
                    type: 'POST',
                    data: formData,
                    success: (res) => {
                        datgin.success(res.message)
                        $form[0].reset()
                        $('#branchModal').modal('hide')
                        fetchBranches(method === 'PUT' ? currentPage : 1)
                    },
                    error: (xhr) => {
                        let message = xhr.responseJSON.message ||
                            'Đã có lỗi xảy ra. Vui lòng thử lại sau!'
                        datgin.error(message);
                    }
                })
            })

        });
    </script>
@endpush

@push('style')
    <style>
        select.form-select {
            padding: .5rem 2.25rem .475rem .5rem !important;
        }
    </style>
@endpush
