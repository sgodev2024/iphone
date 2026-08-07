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

<div
    class="modal fade"
    id="storageImeiModal"
    tabindex="-1"
    aria-labelledby="storageImeiModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5
                    class="modal-title fw-bold"
                    id="storageImeiModalLabel"
                >
                    Danh sách IMEI
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Đóng"
                ></button>
            </div>

            <div class="modal-body" id="storage-imei-modal-body">
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >
                    Đóng
                </button>
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
    let resetCooldown = false;
    let currentView = 'storages';
    let currentStorageId = null;

    const fetchStorages = (page = 1, search = '') => {
        currentView = 'storages';
        currentStorageId = null;
        currentPage = Number(page) || 1;

        $.ajax({
            url: window.location.pathname,
            method: 'GET',
            data: {
                page: currentPage,
                s: search
            },
            success: (res) => {
                $('#table-wrapper').html(res.html);
            },
            error: (xhr) => {
                datgin.error(
                    xhr.responseJSON?.message ||
                    'Không thể tải danh sách kho.'
                );
            }
        });
    };

    const fetchStorageInventory = (
        storageId,
        page = 1,
        search = ''
    ) => {
        currentView = 'inventory';
        currentStorageId = Number(storageId);
        currentPage = Number(page) || 1;

        $.ajax({
            url: window.location.pathname,
            method: 'GET',
            data: {
                inventory: currentStorageId,
                page: currentPage,
                s: search
            },
            success: (res) => {
                $('#table-wrapper').html(res.html);
            },
            error: (xhr) => {
                datgin.error(
                    xhr.responseJSON?.message ||
                    'Không thể tải danh sách sản phẩm tồn kho.'
                );
            }
        });
    };

    $(document).on('click', '.btn-storage-inventory', function(e) {
        e.preventDefault();

        const storageId = Number($(this).data('storage-id'));

        if (!storageId) {
            return;
        }

        searchText = '';
        $('input[name="search"]').val('');

        fetchStorageInventory(storageId, 1, '');
    });

    $(document).on('click', '#back-to-storages', function(e) {
        e.preventDefault();

        searchText = '';
        $('input[name="search"]').val('');

        fetchStorages(1, '');
    });

    $(document).on('click', 'a.page-link', function(e) {
        e.preventDefault();

        const href = $(this).attr('href');

        if (!href) {
            return;
        }

        const url = new URL(href, window.location.origin);
        const page = url.searchParams.get('page') || 1;

        if (currentView === 'inventory' && currentStorageId) {
            fetchStorageInventory(
                currentStorageId,
                page,
                searchText
            );

            return;
        }

        fetchStorages(page, searchText);
    });

    $('input[name="search"]').on('input', debounce(function() {
        searchText = $(this).val().trim();

        if (currentView === 'inventory' && currentStorageId) {
            fetchStorageInventory(
                currentStorageId,
                1,
                searchText
            );

            return;
        }

        fetchStorages(1, searchText);
    }));

    $('#btn-reset').click(function() {
        if (resetCooldown) {
            return;
        }

        resetCooldown = true;
        searchText = '';

        $('input[name="search"]').val('');

        if (currentView === 'inventory' && currentStorageId) {
            fetchStorageInventory(currentStorageId, 1, '');
        } else {
            fetchStorages(1, '');
        }

        setTimeout(() => {
            resetCooldown = false;
        }, 1500);
    });

    $('#show-modal').on('click', function() {
    const form = $('#myForm');

    form[0].reset();
    clearValidationErrors(form);

    form.attr({
        'data-method': 'POST',
        'data-id': ''
    });

    $('#storageModalLabel').text('Thêm mới kho');
    $('#storageModal').modal('show');
});

$(document).on('click', '.btn-show', function() {
    const id = $(this).data('id');
    const form = $('#myForm');

    clearValidationErrors(form);

    $.ajax({
        url: `/admin/storage/${id}`,
        method: 'GET',

        success: function(res) {
            form.find('input[name="name"]').val(res.data.name ?? '');
            form.find('textarea[name="location"]').val(res.data.location ?? '');

            form.attr({
                'data-method': 'PUT',
                'data-id': id
            });

            $('#storageModalLabel').text('Cập nhật kho');
            $('#storageModal').modal('show');
        },

        error: function(xhr) {
            datgin.error(
                xhr.responseJSON?.message ||
                'Không thể lấy thông tin kho.'
            );
        }
    });
});

$('#myForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);
    const method = form.attr('data-method');
    const id = form.attr('data-id');

    clearValidationErrors(form);

    const formData = form.serializeArray();

    if (method === 'PUT') {
        formData.push({
            name: '_method',
            value: 'PUT'
        });
    }

    const url = id
        ? `/admin/storage/${id}`
        : '/admin/storage';

    $.ajax({
        url: url,
        method: 'POST',
        data: formData,

        success: function(res) {
            $('#storageModal').modal('hide');

            searchText = '';
            $('input[name="search"]').val('');

            fetchStorages(1, '');

            datgin.success(res.message);
        },

        error: function(xhr) {
            if (
                xhr.status === 422 &&
                xhr.responseJSON?.errors
            ) {
                renderValidationErrors(
                    form,
                    xhr.responseJSON.errors
                );

                datgin.warning(
                    xhr.responseJSON.message ||
                    'Vui lòng kiểm tra lại thông tin.'
                );

                return;
            }

            datgin.error(
                xhr.responseJSON?.message ||
                'Đã có lỗi xảy ra. Vui lòng thử lại sau!'
            );
        }
    });
});

$(document).on('click', '.btn-delete', function() {
    const id = $(this).data('id');

    handleDestroy(function() {
        fetchStorages(currentPage, searchText);
    }, 'Storage', id);
});

$('#bulk-delete').on('click', function(e) {
    e.preventDefault();

    handleDestroy(function() {
        fetchStorages(1, searchText);
    }, 'Storage');
});

    fetchStorages();
});

$(document).on(
    'click',
    '.btn-view-storage-imeis',
    function(e) {
        e.preventDefault();

        const url = $(this).data('url');

        if (!url) {
            return;
        }

        $('#storage-imei-modal-body').html(`
            <div class="d-flex justify-content-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">
                        Đang tải...
                    </span>
                </div>
            </div>
        `);

        $('#storageImeiModal').modal('show');

        $.ajax({
            url: url,
            method: 'GET',

            success: function(res) {
                $('#storage-imei-modal-body').html(
                    res.html
                );
            },

            error: function(xhr) {
                $('#storageImeiModal').modal('hide');

                datgin.error(
                    xhr.responseJSON?.message ||
                    'Không thể tải danh sách IMEI.'
                );
            }
        });
    }
);
</script>
@endpush