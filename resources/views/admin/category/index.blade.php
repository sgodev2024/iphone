@extends('admin.layout.index')

@push('style')
    <style>
        @media (max-width: 767.98px) {
            .category-index-page {
                max-width: 100%;
                overflow-x: hidden;
            }

            .category-index-page .card,
            .category-index-page .card-body {
                max-width: 100%;
            }

            .category-index-page .category-toolbar {
                flex-wrap: wrap;
                gap: 8px;
                align-items: stretch !important;
            }

            .category-index-page .category-toolbar-start {
                display: contents !important;
            }

            .category-index-page .category-search {
                order: 1;
                width: 100%;
                flex: 0 0 100%;
                gap: 8px;
            }

            .category-index-page .category-search input[name="search"] {
                width: auto !important;
                min-width: 0;
                flex: 1 1 auto;
                margin-right: 0 !important;
            }

            .category-index-page #btn-reset {
                width: 40px;
                height: 38px;
                flex: 0 0 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
            }

            .category-index-page .category-actions {
                order: 2;
                flex: 0 0 auto;
            }

            .category-index-page .category-actions > .btn {
                min-height: 38px;
            }

            .category-index-page .category-add {
                order: 3;
                min-width: 132px;
                min-height: 38px;
                flex: 1 1 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                white-space: nowrap;
            }

            .category-index-page .category-table-hint {
                display: block;
                margin-bottom: 8px;
                color: #6c757d;
                font-size: 13px;
            }

            .category-index-page .category-table-scroll {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .category-index-page .category-table {
                min-width: 920px;
                table-layout: auto;
            }

            .category-index-page .category-table th,
            .category-index-page .category-table td {
                overflow: visible;
                vertical-align: middle;
            }

            .category-index-page .category-table th:nth-child(1),
            .category-index-page .category-table td:nth-child(1) {
                width: 52px;
                min-width: 52px;
                white-space: nowrap;
            }

            .category-index-page .category-table th:nth-child(2),
            .category-index-page .category-table td:nth-child(2) {
                width: 150px;
                min-width: 150px;
                white-space: nowrap;
            }

            .category-index-page .category-table th:nth-child(3),
            .category-index-page .category-table td:nth-child(3) {
                min-width: 180px;
            }

            .category-index-page .category-table th:nth-child(4),
            .category-index-page .category-table td:nth-child(4) {
                min-width: 260px;
            }

            .category-index-page .category-table th:nth-child(5),
            .category-index-page .category-table td:nth-child(5) {
                width: 150px;
                min-width: 150px;
                white-space: nowrap;
            }

            .category-index-page .category-table th:nth-child(6),
            .category-index-page .category-table td:nth-child(6) {
                width: 128px;
                min-width: 128px;
                white-space: nowrap;
            }

            .category-index-page .category-table .category-row-actions {
                flex-wrap: nowrap;
            }

            .category-index-page .category-table .category-row-actions .btn {
                width: 34px;
                height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                flex: 0 0 34px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-inner category-index-page">

        <x-breadcrumb :items="[['label' => 'Danh mục', 'url' => route('admin.category.index')]]" />

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center category-toolbar">
                        <div class="d-flex justify-content-between align-items-center gap-2 category-toolbar-start">
                            <div class="btn-group category-actions">
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

                            <div class="d-flex justify-content-end align-items-center category-search">
                                <input type="text" name="search" class="form-control me-2" style="width: 300px;"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn" id="btn-reset"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <button class="btn btn-primary category-add" id="show-modal"><i class="fa-solid fa-plus"></i> Thêm mới</button>
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
            const categoryBaseUrl = @json(url('/admin/category'));

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
                clearValidationErrors($('#myForm'))
                $('#myForm').attr({
                    'data-method': 'POST',
                    'data-id': ''
                })
            })

            $(document).on('click', '.btn-show', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: `${categoryBaseUrl}/${id}`,
                    type: 'GET',
                    success: (res) => {
                        clearValidationErrors($('#myForm'))

                        $.each(res.data, function(key, item) {
                            const $field = $(`[name="${key}"]`)

                            if (key === 'status') {
                                $field.val(item ? 1 : 0);
                                return;
                            }

                            $field.val(item ?? '')
                        })
                        $('#myForm').attr('data-method', 'PUT')
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
                clearValidationErrors(form)

                let formData = form.serializeArray();
                let method = form.attr('data-method')
                let id = form.attr('data-id')

                method === 'PUT' && formData.push({
                    name: '_method',
                    value: 'PUT'
                })

                let url = id ? `${categoryBaseUrl}/${id}` : categoryBaseUrl

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
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            renderValidationErrors(form, xhr.responseJSON.errors)
                            datgin.warning(xhr.responseJSON.message ||
                                'Vui lòng kiểm tra lại thông tin.')
                            return
                        }

                        datgin.error(xhr.responseJSON?.message ||
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
