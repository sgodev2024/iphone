@extends('admin.layout.index')

@section('content')
    <div class="page-inner brand-page">
        <div class="brand-breadcrumb">
            <x-breadcrumb :items="[['label' => 'Thương hiệu', 'url' => route('admin.brand.index')]]" />
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center brand-toolbar">
                        <div class="d-flex justify-content-between align-items-center gap-2 brand-toolbar__controls">
                            <div class="btn-group brand-bulk-actions">
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

                            <div class="d-flex justify-content-end align-items-center brand-search-row">
                                <input type="text" name="search" class="form-control me-2 brand-search-input" style="width: 300px;"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn brand-refresh-btn" id="btn-reset" title="Làm mới"
                                    aria-label="Làm mới"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <a href="/admin/brand/create" class="btn btn-primary brand-add-btn" id="show-modal"><i
                                class="fa-solid fa-plus"></i> Thêm mới</a>
                    </div>
                    <div class="card-body">


                        <div class="brand-table-hint">Vuốt ngang để xem đầy đủ bảng</div>
                        <div id="table-wrapper">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .brand-page .brand-table-hint {
            display: none;
        }

        .brand-page .brand-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .brand-page {
                width: 100%;
                padding-right: 0 !important;
                padding-left: 0 !important;
                margin-right: auto;
                margin-left: auto;
            }

            .brand-page > .row {
                --bs-gutter-x: 0;
                width: 100%;
                margin-right: 0;
                margin-left: 0;
            }

            .brand-page > .row > [class*="col-"] {
                padding-right: 0;
                padding-left: 0;
            }

            .brand-page .card {
                width: calc(100% - 20px);
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
            }

            .brand-page .brand-breadcrumb {
                width: calc(100% - 20px);
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 0;
                padding-left: 0;
            }

            .brand-page .brand-breadcrumb > nav,
            .brand-page .brand-breadcrumb .breadcrumb {
                width: 100%;
                max-width: 100%;
                margin-right: 0;
                margin-left: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .brand-page .brand-breadcrumb .breadcrumb {
                flex-wrap: nowrap;
                overflow-x: hidden;
            }

            .brand-page,
            .brand-page .row,
            .brand-page .col-md-12,
            .brand-page .card,
            .brand-page .card-header,
            .brand-page .card-body,
            .brand-page #table-wrapper {
                max-width: 100%;
                min-width: 0;
            }

            .brand-page .card-header.brand-toolbar {
                flex-wrap: wrap;
                align-items: stretch !important;
                gap: 8px;
                padding: 12px;
                overflow-x: visible;
            }

            .brand-page .brand-toolbar__controls {
                display: contents !important;
            }

            .brand-page .brand-search-row {
                order: 1;
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 40px;
                gap: 8px;
                width: 100%;
                min-width: 0;
            }

            .brand-page .brand-search-input {
                width: 100% !important;
                min-width: 0;
                height: 40px;
                margin-right: 0 !important;
            }

            .brand-page .brand-refresh-btn {
                width: 40px;
                height: 40px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #dee2e6;
                white-space: nowrap;
                flex: 0 0 40px;
            }

            .brand-page .brand-bulk-actions {
                order: 2;
                flex: 1 1 0;
                min-width: 0;
            }

            .brand-page .brand-bulk-actions > .btn,
            .brand-page .brand-add-btn {
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                white-space: nowrap;
            }

            .brand-page .brand-bulk-actions > .btn {
                width: 100%;
            }

            .brand-page .brand-add-btn {
                order: 3;
                flex: 0 0 auto;
                max-width: 48%;
                padding-right: 12px;
                padding-left: 12px;
            }

            .brand-page .brand-table-hint {
                display: block;
                margin-bottom: 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.35;
            }

            .brand-page .brand-table-scroll .brand-table {
                min-width: 920px;
                margin-top: 0 !important;
            }

            .brand-page .brand-table th,
            .brand-page .brand-table td {
                vertical-align: middle;
            }

            .brand-page .brand-col-date,
            .brand-page .brand-col-status,
            .brand-page .brand-col-actions,
            .brand-page .brand-row-actions,
            .brand-page .brand-table .badge {
                white-space: nowrap;
            }

            .brand-page .brand-col-desc {
                min-width: 220px;
            }

            .brand-page .brand-description {
                max-width: 240px;
            }

            .brand-page .brand-logo {
                width: 50px;
                height: 50px;
                object-fit: contain;
            }

            .brand-page .brand-row-actions {
                flex-wrap: nowrap;
                min-width: 82px;
            }

            .brand-page .brand-action-btn {
                width: 36px;
                height: 36px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 36px;
            }
        }
    </style>
@endpush


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

                fetchBrands(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchBrands(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchBrands()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
            })

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchBrands(1, searchText)
                }, 'Brand', id)
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchBrands(1, searchText)
                }, 'Brand')
            })

            $('#bulk-status').click(function() {
                handleChangeStatus(function() {
                    fetchBrands(currentPage, searchText)
                }, 'Brand')
            })

            const fetchBrands = (page = 1, search) => {

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

            fetchBrands()
        })
    </script>
@endpush
