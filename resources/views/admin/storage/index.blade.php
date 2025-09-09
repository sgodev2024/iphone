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
