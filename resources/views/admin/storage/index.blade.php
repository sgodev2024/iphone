@extends('admin.layout.index')

@push('style')
    <style>
        .storage-page .storage-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .storage-page .storage-mobile-hint {
            display: none;
        }

        @media (max-width: 767.98px) {
            .storage-page {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px !important;
                padding-left: 10px !important;
                overflow-x: visible;
            }

            .storage-page .breadcrumb {
                margin-right: 0;
                margin-left: 0;
            }

            .storage-page .row {
                --bs-gutter-x: 0;
                margin-right: 0;
                margin-left: 0;
            }

            .storage-page .row > * {
                padding-right: 0;
                padding-left: 0;
            }

            .storage-page .storage-card {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                overflow: visible;
            }

            .storage-page .storage-toolbar {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) max-content;
                gap: 8px;
                align-items: center !important;
                padding: 10px;
            }

            .storage-page .storage-toolbar-main {
                display: contents !important;
            }

            .storage-page .storage-search-actions {
                grid-column: 1 / -1;
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 40px;
                gap: 8px;
                order: 1;
                width: 100%;
            }

            .storage-page .storage-search-input {
                width: 100% !important;
                min-width: 0;
                height: 40px;
                margin-right: 0 !important;
            }

            .storage-page .storage-reset-btn,
            .storage-page .storage-bulk-actions .btn,
            .storage-page .storage-add-btn {
                height: 40px;
                white-space: nowrap;
            }

            .storage-page .storage-reset-btn {
                width: 40px;
                min-width: 40px;
                padding: 0;
            }

            .storage-page .storage-bulk-actions {
                grid-column: 1 / 2;
                order: 2;
                min-width: 0;
            }

            .storage-page .storage-bulk-actions .btn {
                width: 100%;
                padding-right: 10px;
                padding-left: 10px;
            }

            .storage-page .storage-add-btn {
                grid-column: 2 / 3;
                order: 3;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 118px;
                padding-right: 10px;
                padding-left: 10px;
                gap: 4px;
            }

            .storage-page .storage-card-body {
                max-width: 100%;
                padding: 10px;
                overflow: visible;
            }

            .storage-page .storage-mobile-hint {
                display: block;
                margin-bottom: 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.4;
            }

            .storage-page .storage-table-region,
            .storage-page .storage-table-scroll {
                width: 100%;
                max-width: 100%;
            }

            .storage-page .storage-table {
                min-width: 840px;
                margin-top: 0.5rem !important;
            }

            .storage-page .storage-table th,
            .storage-page .storage-table td {
                vertical-align: middle;
            }

            .storage-page .storage-table .storage-col-check {
                width: 46px !important;
                min-width: 46px;
                text-align: center;
                white-space: nowrap;
            }

            .storage-page .storage-table .storage-col-id {
                width: 70px !important;
                min-width: 70px;
                white-space: nowrap;
            }

            .storage-page .storage-table .storage-col-date {
                width: 120px !important;
                min-width: 120px;
                white-space: nowrap;
            }

            .storage-page .storage-table .storage-col-name {
                width: 190px;
                min-width: 190px;
            }

            .storage-page .storage-table .storage-col-location {
                width: 280px;
                min-width: 280px;
            }

            .storage-page .storage-table .storage-col-action {
                width: 118px !important;
                min-width: 118px;
                text-align: center;
                white-space: nowrap;
            }

            .storage-page .storage-actions {
                flex-wrap: nowrap !important;
                gap: 6px !important;
                white-space: nowrap;
            }

            .storage-page .storage-actions .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                min-width: 36px;
                height: 36px;
                padding: 0;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-inner storage-page">
        <x-breadcrumb :items="[['label' => 'Kho hàng']]" />

        <div class="row">
            <div class="col-md-12">
                <div class="card storage-card">
                    <div class="card-header d-flex justify-content-between align-items-center storage-toolbar">
                        <div class="d-flex justify-content-between align-items-center gap-2 storage-toolbar-main">
                            <div class="btn-group storage-bulk-actions">
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
                                </ul>
                            </div>

                            <div class="d-flex justify-content-end align-items-center storage-search-actions">
                                <input type="search" name="search" class="form-control me-2 storage-search-input" style="width: 300px;"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn storage-reset-btn" id="btn-reset" title="Làm mới"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary storage-add-btn" id="show-modal">
                            <i class="fa-solid fa-plus"></i>
                            Thêm mới
                        </button>
                    </div>
                    <div class="card-body storage-card-body">


                        <div class="storage-mobile-hint">Vuốt ngang để xem đầy đủ bảng</div>
                        <div id="table-wrapper" class="storage-table-region">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="storageModal" tabindex="-1" aria-labelledby="storageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-extrabold" id="storageModalLabel">Thêm mới / Cập nhật</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <form id="myForm" data-method="POST" data-id="">

                        <div class="row g-3">

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Tên kho</label>
                                <input type="text" class="form-control" name="name" id="storage-name">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Địa chỉ</label>
                                <textarea class="form-control" name="location" id="storage-location"></textarea>
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
            let currentPage = 1;
            let searchText = '';
            let resetCooldown = false

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchStorages(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchStorages(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchStorages()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
            })

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchStorages(1, searchText)
                }, 'Storage', id)
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchStorages(1, searchText)
                }, 'Storage')
            })

            $('#show-modal').click(function() {
                $('#storageModal').modal('show')

                $('#myForm')[0].reset()
                $('#myForm').attr({
                    'data-method': 'POST',
                    'data-id': ''
                })
            })

            $(document).on('click', '.btn-show', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: `/admin/storage/${id}`,
                    type: 'GET',
                    success: (res) => {
                        $.each(res.data, function(key, item) {

                            $(`input[name="${key}"], textarea[name="${key}"]`).val(
                                item);

                            $('#myForm').attr('data-method', 'PUT')
                        })
                        $('#myForm').attr('data-id', id)

                        $('#storageModal').modal('show')
                    },
                    error: (xhr) => {
                        datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                    }
                })

            })

            $('#myForm').on('submit', function(e) {
                e.preventDefault()
                let form = $(this);
                clearValidationErrors(form);
                let formData = form.serializeArray();
                let method = form.attr('data-method')
                let id = form.attr('data-id')

                method === 'PUT' && formData.push({
                    name: '_method',
                    value: 'PUT'
                })

                let url = `/admin/storage/${id ? id : ''}`

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    success: (res) => {
                        $('#storageModal').modal('hide');
                        $('#btn-reset').trigger('click');
                        datgin.success(res.message);
                    },
                    error: (xhr) => {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            renderValidationErrors(form, xhr.responseJSON.errors);
                            datgin.warning(xhr.responseJSON.message || 'Vui long kiem tra lai thong tin.');
                            return;
                        }

                        datgin.error(xhr.responseJSON.message ||
                            'Đã có lỗi xảy ra. Vui lòng thử lại sau!')
                    }
                });
            })

            const fetchStorages = (page = 1, search) => {

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

            fetchStorages()
        })
    </script>
@endpush
