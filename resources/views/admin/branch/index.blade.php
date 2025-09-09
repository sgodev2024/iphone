@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <div class="page-header">
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
                    <a href="#">Danh sách chi nhánh</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Danh sách chi nhánh</h5>
                        <button class="btn btn-primary" id="show-modal">Thêm
                            mới</button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
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
                                <label class="form-label fw-bold">Người quản lý</label>
                                <input type="text" class="form-control" name="manager_name" id="branch-manager_name">
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

            let currentPage = 1

            function debounce(fn, delay = 500) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            $('#show-modal').click(function() {
                $('#branchModal').modal('show')
                $('#branchForm')[0].reset()
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
                    url: window.location.pathname, // chỉ lấy path, bỏ query cũ
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
                let id = $(this).data('id');
                handleDestroy([id])
            });

            $(document).on('click', '.btn-edit', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: `/admin/branchs/${id}/show`,
                    type: 'GET',
                    success: (res) => {

                        $.each(res.data, function(key, item) {
                            $(`input[name="${key}"], select[name="${key}"]`).val(item)
                            $('#branchForm').attr('data-method', 'PUT')
                        })
                        $('#branchForm').attr('data-id', id)

                        $('#branchModal').modal('show')
                    },
                    error: (xhr) => {
                        datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                    }
                })

            })

            $('#bulk-delete').click(function() {
                const ids = $('.checked-item:checked').map((i, el) => $(el).val()).get()

                if (ids.length <= 0) return datgin.warning('Vui lòng chọn ít nhất 1 hàng!')

                handleDestroy(ids)
            })

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
                            url: '/admin/branchs/change-status',
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

                let formData = $(this).serializeArray()

                let method = $(this).attr('data-method')

                method === 'PUT' && formData.push({
                    name: '_method',
                    value: 'PUT'
                })

                $.ajax({
                    url: '/admin/branchs' + (method === 'PUT' ? '/' + $(this).attr('data-id') : ''),
                    type: 'POST',
                    data: formData,
                    success: (res) => {
                        datgin.success(res.message)
                        $(this)[0].reset()
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
