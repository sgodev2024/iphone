@extends('admin.layout.index')

@section('content')
    <div class="page-inner">

        <x-breadcrumb :items="[['label' => 'Danh mục', 'url' => route('admin.category.index')]]" />

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
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn" id="btn-reset"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <button class="btn btn-primary" id="show-modal"><i class="fa-solid fa-plus"></i> Thêm mới</button>
                    </div>
                    <div class="card-body">


                        <div id="table-wrapper">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-extrabold" id="categoryModalLabel">Thêm mới / Cập nhật</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <form id="myForm" data-method="POST" data-id="">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tên danh mục</label>
                                <input type="text" class="form-control" name="name" id="category-name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trạng thái</label>
                                <select class="form-select" name="status" id="category-status">
                                    <option value="1">Hoạt động</option>
                                    <option value="0">Ngừng hoạt động</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Mô tả</label>
                                <textarea class="form-control" name="description" id="category-description"></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" form="myForm" class="btn btn-primary btn-sm">Lưu thay đổi</button>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(function() {

            let currentPage = 1
            let searchText = '';

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchCategories(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchCategories(1, searchText); // reset về page 1 khi search
            }));

            let resetCooldown = false

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchCategories()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
            })

            $('#show-modal').click(function() {
                $('#categoryModal').modal('show')

                $('#myForm')[0].reset()
                $('#myForm').attr({
                    'data-method': 'POST',
                    'data-id': ''
                })
            })

            $(document).on('click', '.btn-show', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: `/admin/category/${id}`,
                    type: 'GET',
                    success: (res) => {
                        $.each(res.data, function(key, item) {

                            if (key === 'status') {
                                $(`select[name="${key}"]`).val(item ? 1 : 0);
                            }

                            $(`input[name="${key}"]`).val(item)
                            $('#myForm').attr('data-method', 'PUT')
                        })
                        $('#myForm').attr('data-id', id)

                        $('#categoryModal').modal('show')
                    },
                    error: (xhr) => {
                        datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                    }
                })

            })

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchCategories(1, searchText)
                }, 'Categories', id)
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchCategories(1, searchText)
                }, 'Categories')
            })

            $('#bulk-status').click(function() {
                handleChangeStatus(function() {
                    fetchCategories(currentPage, searchText)
                }, 'Categories')
            })

            $('#myForm').on('submit', function(e) {
                e.preventDefault()
                let form = $(this);
                let formData = form.serializeArray();
                let method = form.attr('data-method')
                let id = form.attr('data-id')

                method === 'PUT' && formData.push({
                    name: '_method',
                    value: 'PUT'
                })

                let url = `/admin/category/${id ? id : ''}`

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    success: (res) => {
                        $('#categoryModal').modal('hide');
                        $('#btn-reset').trigger('click');
                        datgin.success(res.message);
                    },
                    error: (xhr) => {
                        datgin.error(xhr.responseJSON.message ||
                            'Đã có lỗi xảy ra. Vui lòng thử lại sau!')
                    }
                });
            })

            const fetchCategories = (page = 1, search) => {

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

            fetchCategories()
        })
    </script>
@endpush
