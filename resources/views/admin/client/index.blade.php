@extends('admin.layout.index')
@section('content')
    <div class="page-inner">

        <x-breadcrumb :items="[['label' => 'Khách hàng']]" />

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
                                            <i class="fa-solid fa-ban me-2"></i> Ngừng hoạt động đã chọn
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
                        <button type="button" class="btn btn-primary" id="btn-export">
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
