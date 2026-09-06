@extends('admin.layout.index')

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

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchUsers(1, searchText)
                }, 'User', id, {
                    action: 'deactivate'
                })
            });

            $(document).on('click', '.btn-delete-employee', function() {
                const $button = $(this);
                const originalHtml = $button.html();
                const originalOpacity = $button.css('opacity');

                Swal.fire({
                    title: 'Bạn có chắc muốn xóa nhân viên này?',
                    text: 'Chỉ có thể xóa nếu nhân viên chưa phát sinh nghiệp vụ trong hệ thống.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Xóa nhân viên',
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
                            datgin.success(res.message || 'Xóa nhân viên thành công.');
                            fetchUsers(currentPage, searchText);
                        },
                        error: (xhr) => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Không thể xóa nhân viên',
                                text: xhr.responseJSON?.message ||
                                    'Không thể xóa nhân viên. Vui lòng kiểm tra dữ liệu liên quan.'
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
