@extends('admin.layout.index')

@section('content')
    <div class="page-inner">

        <x-breadcrumb :items="[['label' => 'Thu chi tiền mặt']]" />

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif


        <div class="card">
            <div class="card-body">
                <div class="filter-section">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex gap-2">

                            {{-- <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    id="actionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Thao tác
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="actionDropdown">
                                    <li>
                                        <a class="dropdown-item" href="#" id="print-selected">
                                            <i class="fas fa-print me-1"></i> In phiếu đã chọn
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item text-danger" href="#" id="delete-selected">
                                            <i class="fas fa-trash-alt me-1"></i> Xóa đã chọn
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="#" id="import-excel">
                                            <i class="fas fa-file-import me-1"></i> Import Excel
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item"
                                            href="/admin/cash-transactions/download-sample-cash-transaction"
                                            id="download-sample">
                                            <i class="fas fa-file-download me-1"></i> Tải file mẫu
                                        </a>
                                    </li>
                                </ul>
                            </div> --}}

                            <input type="text" id="dateFilter" class="form-control" placeholder="Chọn khoảng ngày">
                        </div>
                        <div class="row g-3 justify-content-end align-items-center">
                            <a href="/admin/transactions/cash/save" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i>
                                Thêm mới
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="checked-all">
                                </th>
                                <th>ID | Ngày</th>
                                <th>Tài khoản</th>
                                <th>Tài khoản đối ứng</th>
                                <th>Đối tượng</th>
                                <th>Thu</th>
                                <th>Chi</th>
                                <th>Người tạo</th>
                                <th>File chứng từ</th>
                                <th class="text-center" style="width: 5%">
                                    <i class="fas fa-cog"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Import Excel -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="import-excel-form" action="/admin/cash-transactions/import" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importExcelModalLabel">Import Cash Transactions từ Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="excel-file" class="form-label">Chọn file Excel</label>
                            <input type="file" class="form-control" id="excel-file" name="file"
                                accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <iframe id="print-iframe" style="display: none;"></iframe>
@endsection


@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        $(document).ready(function() {
            document.getElementById('import-excel')?.addEventListener('click', function(e) {
                e.preventDefault();
                var modal = new bootstrap.Modal(document.getElementById('importExcelModal'));
                modal.show();
            });

            let start = moment().subtract(1, 'month');
            let end = moment();

            $('#dateFilter').daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: true,
                locale: {
                    format: 'DD/MM/YYYY',
                    cancelLabel: 'Hủy',
                    applyLabel: 'Áp dụng',
                    customRangeLabel: 'Tùy chọn',
                    daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                    monthNames: [
                        'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                        'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
                    ],
                    firstDay: 1
                },
                ranges: {
                    'Hôm nay': [moment(), moment()],
                    'Ngày mai': [moment().add(1, 'days'), moment().add(1, 'days')],
                    'Tuần này': [moment().startOf('week'), moment().endOf('week')],
                    'Tuần sau': [moment().add(1, 'week').startOf('week'), moment().add(1, 'week').endOf(
                        'week')],
                    'Tháng này': [moment().startOf('month'), moment().endOf('month')],
                    'Tháng sau': [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf(
                        'month')]
                }
            });

            // Hiển thị mặc định trên input khi load
            $('#dateFilter').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

            $('#dateFilter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                    'DD/MM/YYYY'));
                loadCashTransactions()
            });

            $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                loadCashTransactions()
            });

            // Khi click vào checkbox "checked-all"
            $('#checked-all').on('change', function() {
                let isChecked = $(this).is(':checked');
                $('.item-checkbox').prop('checked', isChecked);
            });

            // Khi click vào từng checkbox hàng
            $(document).on('change', '.item-checkbox', function() {
                let totalCheckboxes = $('.item-checkbox').length;
                let checkedCheckboxes = $('.item-checkbox:checked').length;

                if (totalCheckboxes === checkedCheckboxes) {
                    $('#checked-all').prop('checked', true);
                } else {
                    $('#checked-all').prop('checked', false);
                }
            });

            loadCashTransactions()

        });

        $(document).on('click', '#delete-selected', function() {
            let ids = $('.item-checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();

            if (ids.length === 0) {
                Notifications('Vui lòng chọn ít nhất một tài khoản để xoá.', 'warning');
                return;
            }

            Swal.fire({
                title: "Bạn có chắc chắn muốn xóa?",
                text: "Hành động này sẽ không thể hoàn tác!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Đồng ý, xóa!",
                cancelButtonText: "Hủy",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/admin/cash-transactions/destroy',
                        type: 'DELETE',
                        data: {
                            ids: ids,
                        },
                        beforeSend: function() {
                            $("#loadingSpinner").fadeIn();
                        },
                        success: function(res) {
                            if (res.success) {
                                Notifications(res.message, 'success');
                                loadCashTransactions();
                            } else {
                                Notifications('Có lỗi xảy ra, vui lòng thử lại.',
                                    'error');
                            }
                        },
                        error: function() {
                            Notifications('Có lỗi xảy ra, vui lòng thử lại.',
                                'error');
                            $("#loadingSpinner").fadeOut();

                        },
                        complete: function() {
                            $("#loadingSpinner").fadeOut();

                        }
                    });
                } else {
                    $('.item-checkbox, #checked-all').prop('checked', false)
                }
            })
        });

        $('#print-selected').on('click', function(e) {
            e.preventDefault();

            let selectedIds = [];
            $('.item-checkbox:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });

            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất 1 phiếu để in.');
                return;
            }

            $.ajax({
                url: '/admin/cash-transactions/print-multiple',
                method: 'POST',
                data: {
                    ids: selectedIds,
                },
                success: function(response) {
                    let printIframe = document.getElementById('print-iframe');
                    let printDocument = printIframe.contentDocument || printIframe.contentWindow
                        .document;

                    printDocument.open();
                    printDocument.write(response);
                    printDocument.close();

                    // Đợi iframe render xong mới in
                    printIframe.onload = function() {
                        printIframe.contentWindow.focus();
                        printIframe.contentWindow.print();
                    };
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Đã xảy ra lỗi khi in phiếu.');
                }
            });

        });

        $(document).on('click', '.action-print', function() {
            let transactionId = $(this).closest('tr').find('.item-checkbox').data('id');

            if (!transactionId) {
                alert('Không tìm thấy ID phiếu.');
                return;
            }

            $.ajax({
                url: '/admin/cash-transactions/print-multiple',
                method: 'POST',
                data: {
                    ids: [transactionId],
                },
                success: function(response) {
                    let printIframe = document.getElementById('print-iframe');
                    let printDocument = printIframe.contentDocument || printIframe.contentWindow
                        .document;

                    printDocument.open();
                    printDocument.write(response);
                    printDocument.close();

                    printIframe.onload = function() {
                        printIframe.contentWindow.focus();
                        printIframe.contentWindow.print();
                    };
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Đã xảy ra lỗi khi in phiếu.');
                }
            });
        });


        // Toggle action menu
        $(document).on('click', '.action-toggle-btn', function(e) {

            e.stopPropagation();
            const $menu = $(this).siblings('.action-menu');

            $('.action-menu').not($menu).hide();
            $menu.toggle();
        });

        function loadCashTransactions() {

            let filters = {
                date_range: $('#dateFilter').val(),
            };

            $.ajax({
                url: "/admin/transactions/cash/ajax/list",
                type: "GET",
                data: filters,

                success: function(res) {
                    if (res.success) {
                        $('table tbody').html(res.html);
                    }
                },
                error: function(xhr) {
                    Toast.fire({
                        icon: "error",
                        title: xhr.responseJSON.message
                    });
                }
            });
        }

        function debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        $('#amountFilter').on('input', function() {
            debouncedFilter();
        });

        // Close when clicking outside
        $(document).on('click', function() {
            $('.action-menu').hide();
        });

        // Action handlers
        $(document).on('click', '.action-print', function() {
            const id = $(this).closest('tr').data('id');
        });

        $(document).on('click', '.action-edit', function() {
            const url = $(this).data('url');

            window.location.href = url
        });

        $(document).on('click', '.action-delete', function() {
            const id = $(this).closest('tr').data('id');
            deleteReceipt(id);
        });
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 8px;
        }

        .table td {
            padding: 12px 8px;
            font-size: 14px;
            vertical-align: middle;
        }

        .form-select,
        .form-control {
            font-size: 14px;
        }

        .btn-sm {
            font-size: 13px;
        }

        .action-icons {
            display: flex;
            gap: 8px;
        }

        .action-icons .btn {
            padding: 4px 8px;
            font-size: 12px;
        }

        .action-menu {
            top: 100%;
            right: 0;
            background: white;
            padding: 0;
        }

        .action-menu li {
            padding: 8px 12px;
        }

        .action-menu li:hover {
            background-color: #f1f1f1;
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>
@endpush
