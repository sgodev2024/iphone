@extends('admin.layout.index')

@section('content')
    <div class="page-inner company-page">

        <div class="company-breadcrumb">
            <x-breadcrumb :items="[['label' => 'Nhà cung cấp']]" />
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center company-toolbar">
                        <div class="d-flex justify-content-between align-items-center gap-2 company-toolbar__controls">
                            <div class="btn-group company-bulk-actions">
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

                            <div class="d-flex justify-content-end align-items-center company-search-row">
                                <input type="text" name="search" class="form-control me-2 company-search-input" style="width: 300px;"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn company-refresh-btn" id="btn-reset" title="Làm mới"
                                    aria-label="Làm mới"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <a href="/admin/company/create" class="btn btn-primary company-add-btn" id="show-modal"><i
                                class="fa-solid fa-plus"></i>
                            Thêm mới</a>
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

@push('style')
    <style>
        .company-page .company-table-hint {
            display: none;
        }

        .company-page .company-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .company-page {
                width: 100%;
                padding-right: 0 !important;
                padding-left: 0 !important;
                margin-right: auto;
                margin-left: auto;
                overflow-x: visible;
            }

            .company-page > .row {
                --bs-gutter-x: 0;
                width: 100%;
                margin-right: 0;
                margin-left: 0;
            }

            .company-page > .row > [class*="col-"] {
                padding-right: 0;
                padding-left: 0;
            }

            .company-page .card {
                width: calc(100% - 20px);
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
            }

            .company-page .company-breadcrumb {
                width: calc(100% - 20px);
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 0;
                padding-left: 0;
            }

            .company-page .company-breadcrumb > nav,
            .company-page .company-breadcrumb .breadcrumb {
                width: 100%;
                max-width: 100%;
                margin-right: 0;
                margin-left: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .company-page,
            .company-page .row,
            .company-page .col-md-12,
            .company-page .card,
            .company-page .card-header,
            .company-page .card-body,
            .company-page #table-wrapper {
                max-width: 100%;
                min-width: 0;
            }

            .company-page .card-header.company-toolbar {
                flex-wrap: wrap;
                align-items: stretch !important;
                gap: 8px;
                padding: 12px;
                overflow-x: visible;
            }

            .company-page .company-toolbar__controls {
                display: contents !important;
            }

            .company-page .company-search-row {
                order: 1;
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 40px;
                gap: 8px;
                width: 100%;
                min-width: 0;
            }

            .company-page .company-search-input {
                width: 100% !important;
                min-width: 0;
                height: 40px;
                margin-right: 0 !important;
            }

            .company-page .company-refresh-btn {
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

            .company-page .company-bulk-actions {
                order: 2;
                flex: 1 1 0;
                min-width: 0;
            }

            .company-page .company-bulk-actions > .btn,
            .company-page .company-add-btn {
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                white-space: nowrap;
            }

            .company-page .company-bulk-actions > .btn {
                width: 100%;
            }

            .company-page .company-add-btn {
                order: 3;
                flex: 0 0 auto;
                max-width: 48%;
                padding-right: 12px;
                padding-left: 12px;
            }

            .company-page .company-table-hint {
                display: block;
                margin-bottom: 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.35;
            }

            .company-page .company-table-scroll .company-table {
                min-width: 980px;
                margin-top: 0 !important;
            }

            .company-page .company-table th,
            .company-page .company-table td {
                vertical-align: middle;
            }

            .company-page .company-col-date,
            .company-page .company-col-status,
            .company-page .company-col-actions,
            .company-page .company-row-actions,
            .company-page .company-table .badge {
                white-space: nowrap;
            }

            .company-page .company-col-info {
                min-width: 280px;
                max-width: 320px;
            }

            .company-page .company-contact-line {
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .company-page .company-col-address {
                min-width: 260px;
                max-width: 320px;
                white-space: normal;
                overflow-wrap: break-word;
            }

            .company-page .company-row-actions {
                flex-wrap: nowrap;
                min-width: 82px;
            }

            .company-page .company-action-btn {
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

                fetchCompanies(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchCompanies(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchCompanies()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
            })

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchCompanies(1, searchText)
                }, 'Company', id)
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchCompanies(1, searchText)
                }, 'Company')
            })

            $('#bulk-status').click(function() {
                handleChangeStatus(function() {
                    fetchCompanies(currentPage, searchText)
                }, 'Company')
            })

            const fetchCompanies = (page = 1, search) => {

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

            fetchCompanies()
        })
    </script>
@endpush
