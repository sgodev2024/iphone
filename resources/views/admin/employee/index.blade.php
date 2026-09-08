@extends('admin.layout.index')

@push('style')
    <style>
        #table-wrapper {
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }

        #table-wrapper > .table-responsive {
            display: block;
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #table-wrapper .table {
            width: 100% !important;
            min-width: 1550px;
            table-layout: auto;
        }

        #table-wrapper .table thead th {
            white-space: nowrap;
        }

        #table-wrapper .table th:nth-child(1),
        #table-wrapper .table td:nth-child(1) {
            width: 50px !important;
            min-width: 50px;
        }

        #table-wrapper .table th:nth-child(2),
        #table-wrapper .table td:nth-child(2) {
            width: 70px !important;
            min-width: 70px;
        }

        #table-wrapper .table th:nth-child(3),
        #table-wrapper .table td:nth-child(3) {
            width: 130px !important;
            min-width: 130px;
        }

        #table-wrapper .table th:nth-child(4),
        #table-wrapper .table td:nth-child(4) {
            width: 200px !important;
            min-width: 200px;
        }

        #table-wrapper .table th:nth-child(5),
        #table-wrapper .table td:nth-child(5) {
            width: 130px !important;
            min-width: 130px;
        }

        #table-wrapper .table th:nth-child(6),
        #table-wrapper .table td:nth-child(6) {
            width: 260px !important;
            min-width: 260px;
        }

        #table-wrapper .table th:nth-child(7),
        #table-wrapper .table td:nth-child(7) {
            width: 150px !important;
            min-width: 150px;
        }

        #table-wrapper .table th:nth-child(8),
        #table-wrapper .table td:nth-child(8) {
            width: 220px !important;
            min-width: 220px;
            white-space: normal;
            word-break: normal;
            overflow-wrap: normal;
        }

        #table-wrapper .table td:nth-child(8) > div {
            white-space: nowrap;
        }

        #table-wrapper .table th:nth-child(9),
        #table-wrapper .table td:nth-child(9) {
            width: 130px !important;
            min-width: 130px;
        }

        #table-wrapper .table th:nth-child(10),
        #table-wrapper .table td:nth-child(10) {
            width: 170px !important;
            min-width: 170px;
            white-space: nowrap;
        }

        #table-wrapper .table td:nth-child(10) .d-flex {
            flex-wrap: nowrap !important;
        }

        #table-wrapper .table td:nth-child(10) .btn {
            flex: 0 0 auto;
        }
    </style>
@endpush

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => $title]]" />

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Thao tác
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" id="bulk-delete">
                                            <i class="fa-solid fa-user-slash me-2"></i> Ngừng hoạt động đã chọn
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="d-flex justify-content-end align-items-center">
                                <input type="text" name="search" class="form-control me-2" style="width: 300px;"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn" id="btn-reset"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <a href="/admin/{{ Str::afterLast(request()->path(), '/') }}/create" class="btn btn-primary"
                            id="show-modal"><i class="fa-solid fa-plus"></i> Thêm mới</a>
                    </div>
                    <div class="card-body">


                        <div id="table-wrapper">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(function() {
            let currentPage = 1;
            let searchText = '';
            let resetCooldown = false

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchUsers(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchUsers(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchUsers()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
                
            })

            $(document).on('click', '.btn-employee-status', function() {
                const $button = $(this);
                const originalHtml = $button.html();
                const originalOpacity = $button.css('opacity');
                const targetStatus = $button.data('target-status');

                Swal.fire({
                    title: $button.data('confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: targetStatus === 'inactive' ? '#ffc107' : '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: $button.attr('title'),
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $button.prop('disabled', true).css('opacity', '0.65')
                        .html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');

                    $.ajax({
                        url: $button.data('url'),
                        method: 'PATCH',
                        data: { status: targetStatus },
                        success: (res) => {
                            datgin.success(res.message || 'Cập nhật trạng thái tài khoản thành công.');
                            fetchUsers(currentPage, searchText);
                        },
                        error: (xhr) => {
                            datgin.error(xhr.responseJSON?.message ||
                                'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại sau.');
                        },
                        complete: () => {
                            $button.prop('disabled', false).css('opacity', originalOpacity).html(originalHtml);
                        }
                    });
                });
            });

            $(document).on('click', '.btn-delete-employee', function() {
                const $button = $(this);
                const originalHtml = $button.html();
                const originalOpacity = $button.css('opacity');

                Swal.fire({
                    title: 'Bạn có chắc muốn xóa tài khoản này?',
                    text: 'Chỉ có thể xóa nếu tài khoản chưa được gán chi nhánh và chưa phát sinh nghiệp vụ trong hệ thống.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Xóa tài khoản',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $button
                        .prop('disabled', true)
                        .css('opacity', '0.65')
                        .html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');

                    $.ajax({
                        url: $button.data('url'),
                        method: 'DELETE',
                        success: (res) => {
                            datgin.success(res.message || 'Xóa tài khoản thành công.');
                            fetchUsers(currentPage, searchText);
                        },
                        error: (xhr) => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Không thể xóa tài khoản',
                                text: xhr.responseJSON?.message ||
                                    'Không thể xóa tài khoản. Vui lòng kiểm tra dữ liệu liên quan.'
                            });
                        },
                        complete: () => {
                            $button
                                .prop('disabled', false)
                                .css('opacity', originalOpacity)
                                .html(originalHtml);
                        }
                    });
                });
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchUsers(1, searchText)
                }, 'User', null, {
                    action: 'deactivate'
                })
            })

            $('#bulk-status').click(function() {
                handleChangeStatus(function() {
                    fetchUsers(currentPage, searchText)
                }, 'User')
            })

            const fetchUsers = (page = 1, search) => {

                $.ajax({
                    url: window.location.pathname,
                    method: 'GET',
                    data: {
                        page,
                        s: search
                    },
                    success: (res) => {
                        $('#table-wrapper').html(res.html)
                        currentPage = page
                    },
                    error: (xhr) => {

                    },
                })
            }

            fetchUsers()
        })
    </script>
@endpush
