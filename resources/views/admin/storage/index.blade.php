@extends('admin.layout.index')

@section('content')
<div class="page-inner">
    <x-breadcrumb :items="[['label' => 'Kho hàng']]" />

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
                            </ul>
                        </div>

                        <div class="d-flex justify-content-end align-items-center">
                            <input type="search" name="search" class="form-control me-2" style="width: 300px;"
                                placeholder="Tìm kiếm...">

                            <button type="button" class="btn" id="btn-reset"> <i
                                    class="fa-solid fa-rotate"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="show-modal">
                        <i class="fa-solid fa-plus"></i>
                        Thêm mới
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