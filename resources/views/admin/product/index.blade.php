@extends('admin.layout.index')

@push('style')
    <style>
        .product-actions-column {
            min-width: 120px;
            white-space: nowrap;
        }

        .product-actions {
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }

        .product-action-btn {
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
        }

        .product-action-btn i {
            font-size: 13px;
        }

        .product-toolbar {
            gap: 12px;
        }

        .product-toolbar__main,
        .product-toolbar__actions,
        .product-search-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-search-input {
            width: 300px;
        }

        .product-toolbar .btn {
            white-space: nowrap;
        }

        .product-table-hint {
            display: none;
        }

        .product-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .product-table {
            min-width: 980px;
        }

        @media (max-width: 767.98px) {
            .product-toolbar {
                display: flex !important;
                flex-wrap: wrap;
                align-items: stretch !important;
                justify-content: flex-start !important;
                gap: 8px;
                overflow-x: hidden;
            }

            .product-toolbar__main,
            .product-toolbar__actions {
                display: contents;
            }

            .product-search-group {
                order: 1;
                flex: 0 0 100%;
                width: 100%;
                min-width: 0;
                gap: 8px;
            }

            .product-search-input {
                flex: 1 1 auto;
                width: auto !important;
                min-width: 0;
                height: 40px;
            }

            .product-reset-btn {
                flex: 0 0 40px;
                width: 40px;
                height: 40px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .product-bulk-action {
                order: 2;
                flex: 0 0 calc(38% - 4px);
                min-width: 0;
            }

            .product-bulk-action > .btn {
                width: 100%;
                height: 40px;
                padding-left: 10px;
                padding-right: 10px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .product-add-btn {
                order: 2;
                flex: 1 1 calc(62% - 4px);
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding-left: 12px;
                padding-right: 12px;
            }

            .product-import-btn,
            .product-export-btn {
                order: 3;
                flex: 1 1 calc(50% - 4px);
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding-left: 10px;
                padding-right: 10px;
            }

            .product-table-hint {
                display: block;
                margin: 0 0 8px;
                color: #6c757d;
                font-size: 12px;
            }

            .product-table {
                min-width: 980px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-inner">

        <x-breadcrumb :items="[['label' => 'Sản phẩm']]" />

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center product-toolbar">
                        <div class="product-toolbar__main">
                            <div class="btn-group product-bulk-action">
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

                            <div class="product-search-group">
                                <input type="text" name="search" class="form-control product-search-input"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn product-reset-btn" id="btn-reset"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <div class="product-toolbar__actions">
                            <a href="/admin/company/create" class="btn btn-outline-secondary product-import-btn">
                                <i class="fas fa-file-import"></i> Import
                            </a>
                            <a href="/admin/company/create" class="btn btn-outline-secondary product-export-btn">
                                <i class="fas fa-file-export"></i> Export
                            </a>
                            <a href="/admin/products/create" class="btn btn-primary product-add-btn" id="show-modal"><i
                                    class="fa-solid fa-plus"></i>
                                Thêm mới</a>
                        </div>
                    </div>
                    <div class="card-body">


                        <p class="product-table-hint">Vuốt ngang để xem đầy đủ bảng</p>
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

            const initTableTooltips = () => {
                if (!window.bootstrap || !bootstrap.Tooltip) return

                document.querySelectorAll('#table-wrapper [data-bs-toggle="tooltip"]').forEach((el) => {
                    bootstrap.Tooltip.getOrCreateInstance(el)
                })
            }

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchProducts(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchProducts(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchProducts()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
            })

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchProducts(1, searchText)
                }, 'Product', id)
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchProducts(1, searchText)
                }, 'Product')
            })

            $('#bulk-status').click(function() {
                handleChangeStatus(function() {
                    fetchProducts(currentPage, searchText)
                }, 'Product')
            })

            const fetchProducts = (page = 1, search) => {

                $.ajax({
                    url: window.location.pathname,
                    method: 'GET',
                    data: {
                        page,
                        s: search
                    },
                    success: (res) => {
                        $('#table-wrapper').html(res.data.html)
                        initTableTooltips()
                        currentPage = page
                    },
                    error: (xhr) => {

                    },
                })
            }

            fetchProducts()
        })
    </script>
@endpush
