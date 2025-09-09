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
                                            <i class="fa-solid fa-trash me-2"></i> Xóa đã chọn
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
            let currentPage = 1;
            let searchText = '';
            let resetCooldown = false

            $(document).on('click', 'a.page-link', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let page = new URL(url).searchParams.get("page");

                fetchClients(page, searchText);
            });

            $('input[name="search"]').on('input', debounce(function() {
                searchText = $(this).val();
                fetchClients(1, searchText); // reset về page 1 khi search
            }));

            $('#btn-reset').click(function() {
                if (resetCooldown) return // đang cooldown thì bỏ qua

                resetCooldown = true
                fetchClients()
                $('input[name="search"]').val('')

                setTimeout(() => resetCooldown = false, 1500) // 1.5s sau mới cho bấm lại
            })

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                handleDestroy(function() {
                    fetchClients(1, searchText)
                }, 'Client', id)
            });

            $('#bulk-delete').click(function() {
                handleDestroy(function() {
                    fetchClients(1, searchText)
                }, 'Client')
            })

            const fetchClients = (page = 1, search) => {

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

            $('#btn-export').on('click', function() {
                $.ajax({
                    url: "/admin/client/export",
                    method: "GET",
                    xhrFields: {
                        responseType: 'blob' // quan trọng để nhận file binary
                    },
                    success: function(data) {
                        var blob = new Blob([data], {
                            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        });
                        var url = window.URL.createObjectURL(blob);

                        // Lấy ngày hiện tại
                        var today = new Date();
                        var day = String(today.getDate()).padStart(2, '0');
                        var month = String(today.getMonth() + 1).padStart(2,
                        '0'); // tháng bắt đầu từ 0
                        var year = today.getFullYear();

                        var filename = "danh_sach_khach_hang_" + day + "_" + month + "_" +
                            year + ".xlsx";

                        var a = document.createElement('a');
                        a.href = url;
                        a.download = filename; // tên file tuỳ chỉnh
                        document.body.appendChild(a);
                        a.click();
                        a.remove();

                        window.URL.revokeObjectURL(url);
                    },
                    error: function(xhr) {
                        alert("Có lỗi khi export Excel!");
                    }
                });
            });

            fetchClients()
        })
    </script>
@endpush
