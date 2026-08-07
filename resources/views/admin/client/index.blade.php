@extends('admin.layout.index')
@section('content')
    <style>
        /* Client list only: keep responsive fixes isolated from other admin pages. */
        .client-page,
        .client-page .page-inner,
        .client-page #table-wrapper {
            min-width: 0;
            max-width: 100%;
        }

        .client-page .client-pagination-mobile-label,
        .client-page .pagination-arrow-mobile {
            display: none;
        }

        .client-page .client-table-hint {
            display: none;
        }

        .client-page .client-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .client-page {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding-left: 0 !important;
                padding-right: 0 !important;
                overflow-x: visible;
            }

            .client-page > .breadcrumb,
            .client-page > nav {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .client-page > .row {
                margin-left: 0;
                margin-right: 0;
            }

            .client-page > .row > .col-md-12 {
                padding-left: 0;
                padding-right: 0;
                min-width: 0;
            }

            .client-page .card,
            .client-page .card-body,
            .client-page .card-header {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .client-page .client-toolbar {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 8px;
                align-items: stretch;
                justify-content: normal !important;
                padding: 12px;
            }

            .client-page .client-toolbar__main {
                display: contents !important;
                min-width: 0;
            }

            .client-page .client-toolbar__search {
                display: flex !important;
                grid-column: 1 / -1;
                grid-row: 1;
                width: 100%;
                min-width: 0;
                gap: 8px;
                align-items: stretch !important;
                justify-content: normal !important;
            }

            .client-page .client-toolbar__search input {
                flex: 1 1 auto;
                width: 100% !important;
                min-width: 0;
                max-width: none;
                margin-right: 0 !important;
            }

            .client-page .client-toolbar__actions {
                display: flex !important;
                grid-column: 1;
                grid-row: 2;
                width: 100%;
                min-width: 0;
                max-width: none;
            }

            .client-page .client-toolbar__actions > .dropdown-toggle,
            .client-page .client-export-btn,
            .client-page #btn-reset {
                height: 40px;
                min-height: 40px;
                white-space: nowrap;
            }

            .client-page .client-toolbar__actions > .dropdown-toggle {
                flex: 1 1 auto;
                width: 100%;
                min-width: 0;
                overflow: visible;
                text-overflow: clip;
            }

            .client-page .client-export-btn {
                display: inline-flex !important;
                grid-column: 2;
                grid-row: 2;
                align-items: center;
                justify-content: center;
                gap: 6px;
                width: 100%;
                min-width: 0;
                max-width: none;
                margin-left: 0 !important;
                padding-left: 10px;
                padding-right: 10px;
                overflow: visible;
                text-overflow: clip;
            }

            .client-page #btn-reset {
                flex: 0 0 42px;
                width: 42px;
                min-width: 42px;
                max-width: 42px;
                padding: 0;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
            }

            .client-page #table-wrapper {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .client-page .client-table-hint {
                display: block;
                margin: 0 0 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 18px;
            }

            .client-page .client-table-scroll {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .client-page .client-table-scroll > .client-table {
                width: 100%;
                min-width: 1140px;
                table-layout: fixed;
                margin-top: 0 !important;
            }

            .client-page .client-table th,
            .client-page .client-table td {
                vertical-align: middle;
                padding: 10px 12px;
            }

            .client-page .client-table th:nth-child(1),
            .client-page .client-table td:nth-child(1) {
                width: 52px;
            }

            .client-page .client-table th:nth-child(2),
            .client-page .client-table td:nth-child(2) {
                width: 150px;
                white-space: nowrap;
            }

            .client-page .client-table th:nth-child(3),
            .client-page .client-table td:nth-child(3) {
                width: 190px;
                min-width: 190px;
            }

            .client-page .client-table th:nth-child(4),
            .client-page .client-table td:nth-child(4) {
                width: 150px;
                white-space: nowrap;
            }

            .client-page .client-table th:nth-child(5),
            .client-page .client-table td:nth-child(5) {
                width: 220px;
                white-space: normal;
                word-break: break-word;
                overflow-wrap: anywhere;
            }

            .client-page .client-table th:nth-child(6),
            .client-page .client-table td:nth-child(6) {
                width: 260px;
                min-width: 260px;
                white-space: normal;
                word-break: normal;
                overflow-wrap: break-word;
            }

            .client-page .client-table th:nth-child(7),
            .client-page .client-table td:nth-child(7) {
                width: 120px;
                white-space: nowrap;
            }

            .client-page .client-row-actions {
                display: inline-flex;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: center;
                gap: 5px;
                white-space: nowrap;
            }

            .client-page .client-row-actions .btn {
                width: 36px;
                height: 36px;
                min-width: 36px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .client-page #table-wrapper #pagination .pagination {
                flex-wrap: nowrap;
                gap: 6px !important;
                margin-bottom: 0;
            }

            .client-page #table-wrapper #pagination .client-pagination-page,
            .client-page #table-wrapper #pagination .client-pagination-ellipsis {
                display: none;
            }

            .client-page #table-wrapper #pagination .client-pagination-mobile-label {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 38px;
                padding: 0 4px;
                color: #495057;
                font-size: 14px;
                white-space: nowrap;
            }

            .client-page #table-wrapper #pagination .pagination-arrow-desktop {
                display: none;
            }

            .client-page #table-wrapper #pagination .pagination-arrow-mobile {
                display: inline;
            }
        }

        @media (max-width: 575.98px) {
            .client-page {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }
    </style>

    <div class="page-inner client-page">

        <x-breadcrumb :items="[['label' => 'Khách hàng']]" />

        <div class="row client-page__row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center client-toolbar">
                        <div class="d-flex justify-content-between align-items-center gap-2 client-toolbar__main">
                            <div class="btn-group client-toolbar__actions">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Thao tác
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" id="bulk-delete">
                                            <i class="fa-solid fa-ban me-2"></i> Ngừng hoạt động đã chọn
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-end align-items-center client-toolbar__search">
                                <input type="text" name="search" class="form-control me-2" style="width: 300px;"
                                    placeholder="Tìm kiếm...">

                                <button type="button" class="btn" id="btn-reset" title="Làm mới" aria-label="Làm mới"> <i
                                        class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary client-export-btn" id="btn-export">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>


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
            const clientIndexUrl = @json(route('admin.client.index'));
            const clientExportUrl = @json(route('admin.client.export'));

            const $tableWrapper = $('#table-wrapper');
            const $searchInput = $('input[name="search"]');

            let searchText = '';
            let currentRequest = null;

            function debounce(callback, delay = 500) {
                let timer;

                return function(...args) {
                    clearTimeout(timer);

                    timer = setTimeout(() => {
                        callback.apply(this, args);
                    }, delay);
                };
            }

            function fetchClients(page = 1) {
                if (currentRequest) {
                    currentRequest.abort();
                }

                $tableWrapper.css({
                    opacity: 0.55,
                    pointerEvents: 'none'
                });

                currentRequest = $.ajax({
                    url: clientIndexUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        page: page,
                        s: searchText
                    },

                    success: function(response) {
                        if (!response || typeof response.html === 'undefined') {
                            $tableWrapper.html(
                                '<div class="alert alert-warning mb-0">' +
                                'Máy chủ không trả về dữ liệu bảng.' +
                                '</div>'
                            );

                            return;
                        }

                        $tableWrapper.html(response.html);
                    },

                    error: function(xhr, textStatus) {
                        if (textStatus === 'abort') {
                            return;
                        }

                        const message =
                            xhr.responseJSON?.message ??
                            'Không thể tải danh sách khách hàng.';

                        $tableWrapper.html(
                            '<div class="alert alert-danger mb-0">' +
                            message +
                            '</div>'
                        );

                        console.error(xhr.responseText);
                    },

                    complete: function() {
                        currentRequest = null;

                        $tableWrapper.css({
                            opacity: 1,
                            pointerEvents: 'auto'
                        });
                    }
                });
            }

            $searchInput.on('input', debounce(function() {
                searchText = $(this).val().trim();
                fetchClients(1);
            }));

            $(document).on(
                'click',
                '#table-wrapper .pagination a.page-link',
                function(event) {
                    event.preventDefault();

                    const href = $(this).attr('href');

                    if (!href) {
                        return;
                    }

                    const url = new URL(href, window.location.origin);
                    const page = Number(url.searchParams.get('page')) || 1;

                    fetchClients(page);
                }
            );

            $('#btn-reset').on('click', function() {
                searchText = '';
                $searchInput.val('');
                fetchClients(1);
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');

                handleDestroy(function() {
                    fetchClients(1);
                }, 'Client', id);
            });

            $('#bulk-delete').on('click', function(event) {
                event.preventDefault();

                handleDestroy(function() {
                    fetchClients(1);
                }, 'Client');
            });

            $('#btn-export').on('click', function() {
                const exportUrl = new URL(
                    clientExportUrl,
                    window.location.origin
                );

                if (searchText) {
                    exportUrl.searchParams.set('s', searchText);
                }

                window.location.href = exportUrl.toString();
            });

            fetchClients(1);
        });
    </script>
@endpush
