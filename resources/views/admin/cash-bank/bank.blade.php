@extends('admin.layout.index')

@section('content')
    <div class="page-inner bank-transaction-page">

        <x-breadcrumb :items="[['label' => 'Thu chi ngân hàng']]" />

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
                    <div class="d-flex align-items-center justify-content-between bank-toolbar">
                        <div class="d-flex gap-2 bank-date-filter">

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
                        <div class="row g-3 justify-content-end align-items-center bank-create-action">
                            <a href="/admin/transactions/bank/save" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i>
                                Thêm mới
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bank-scroll-hint d-md-none">Vuốt ngang để xem đầy đủ giao dịch</div>
                <div class="table-responsive bank-table-scroll">
                    <table class="table table-hover table-bordered mb-0 bank-transactions-table">
                        <thead>
                            <tr>
                                <th class="cash-col-id text-center">ID</th>
                                <th class="cash-col-date">Ngày</th>
                                <th class="cash-col-account">Tài khoản</th>
                                <th class="cash-col-operation">Nghiệp vụ</th>
                                <th class="cash-col-contra">Tài khoản đối ứng</th>
                                <th class="cash-col-party">Đối tượng</th>
                                <th class="cash-col-document">Chứng từ</th>
                                <th class="cash-col-description">Nội dung</th>
                                <th class="cash-col-money text-end">Thu</th>
                                <th class="cash-col-money text-end">Chi</th>
                                <th class="cash-col-status">Trạng thái</th>
                                <th class="cash-col-creator">Người tạo</th>
                                <th class="cash-col-file">File chứng từ</th>
                                <th class="cash-col-action text-center" style="width: 5%">
                                    <i class="fas fa-cog"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <div id="bank-pagination-area" class="bank-pagination-area"></div>
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
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {

            // document.getElementById('import-excel').addEventListener('click', function(e) {
            //     e.preventDefault();
            //     var modal = new bootstrap.Modal(document.getElementById('importExcelModal'));
            //     modal.show();
            // });

            let start = moment();
            let end = moment().add(1, 'month');

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
            });

            $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
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

            triggerFilter()
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
                                loadBankTransactions();
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
            const $button = $(this);
            const $menu = $button.siblings('.action-menu');

            $('.action-menu').not($menu).hide();
            $menu.toggle();

            if ($menu.is(':visible') && window.matchMedia('(max-width: 767.98px)').matches) {
                const rect = this.getBoundingClientRect();
                const menuWidth = $menu.outerWidth() || 150;
                const left = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, rect.right - menuWidth));

                $menu.css({
                    top: `${rect.bottom + 6}px`,
                    left: `${left}px`,
                    right: 'auto'
                });
            } else {
                $menu.css({
                    top: '',
                    left: '',
                    right: ''
                });
            }
        });

        function loadBankTransactions(filters = {}, page = 1) {
            $.ajax({
                url: "/admin/transactions/bank/ajax/list",
                type: "GET",
                data: {
                    ...filters,
                    page,
                },
                success: function(res) {
                    if (res.success) {
                        $('.bank-transactions-table tbody').html(res.html);
                        $('#bank-pagination-area').html(res.pagination || '');
                    }
                },
                error: function() {
                    Notifications('Tải danh sách phiếu thu chi thất bại', 'error');
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

        const debouncedFilter = debounce(triggerFilter, 500);

        $('#amountFilter').on('input', function() {
            debouncedFilter();
        });

        function triggerFilter() {

            let filters = {
                date_range: $('#dateFilter').val(),
            };

            loadBankTransactions(filters);
        }

        $(document).on('click', '#bank-pagination-area a', function(event) {
            event.preventDefault();
            const page = new URL(this.href).searchParams.get('page') || 1;
            loadBankTransactions({
                date_range: $('#dateFilter').val(),
            }, page);
        });

        $('#filterButton').on('click', function() {
            triggerFilter()
        })

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

        @media (max-width: 767.98px) {
            .bank-transaction-page {
                max-width: 100%;
                padding-left: 10px;
                padding-right: 10px;
            }

            .bank-transaction-page .card {
                width: 100%;
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .bank-transaction-page .card-body {
                min-width: 0;
                padding: 12px;
            }

            .bank-transaction-page .filter-section {
                padding: 12px;
                margin-bottom: 12px;
            }

            .bank-transaction-page .bank-toolbar {
                flex-wrap: nowrap;
                gap: 8px;
            }

            .bank-transaction-page .bank-date-filter {
                flex: 1 1 auto;
                min-width: 0;
            }

            .bank-transaction-page #dateFilter {
                width: 100%;
                min-width: 0;
                height: 40px;
            }

            .bank-transaction-page .bank-create-action {
                flex: 0 0 auto;
                margin-left: auto;
                margin-right: 0;
                --bs-gutter-x: 0;
            }

            .bank-transaction-page .bank-create-action .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                height: 40px;
                padding-left: 12px;
                padding-right: 12px;
                white-space: nowrap;
            }

            .bank-transaction-page .bank-table-scroll {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .bank-transaction-page .bank-transactions-table {
                min-width: 1360px;
                table-layout: auto;
            }

            .bank-transaction-page .bank-transactions-table th,
            .bank-transaction-page .bank-transactions-table td {
                overflow-wrap: normal;
                word-break: normal;
            }

            .bank-transaction-page .bank-transactions-table th {
                text-align: center;
                vertical-align: middle;
                white-space: nowrap;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(1),
            .bank-transaction-page .cash-col-check {
                min-width: 44px;
                width: 44px;
                text-align: center;
            }

            /* Cột ID */
            .bank-transaction-page .bank-transactions-table th:nth-child(2),
            .bank-transaction-page .cash-col-id {
                min-width: 120px;
                width: 120px;
            }

            /* Cột Ngày */
            .bank-transaction-page .bank-transactions-table th:nth-child(3),
            .bank-transaction-page .cash-col-date {
                min-width: 120px;
                width: 120px;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(4),
            .bank-transaction-page .cash-col-account {
                min-width: 145px;
                width: 145px;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(5),
            .bank-transaction-page .cash-col-contra {
                min-width: 185px;
                width: 185px;
                white-space: normal;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(6),
            .bank-transaction-page .cash-col-party {
                min-width: 220px;
                width: 220px;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(7),
            .bank-transaction-page .bank-transactions-table th:nth-child(8),
            .bank-transaction-page .cash-col-money {
                min-width: 135px;
                width: 135px;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(9),
            .bank-transaction-page .cash-col-creator {
                min-width: 145px;
                width: 145px;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(10),
            .bank-transaction-page .cash-col-file {
                min-width: 155px;
                width: 155px;
            }

            .bank-transaction-page .bank-transactions-table th:nth-child(11),
            .bank-transaction-page .cash-col-action {
                min-width: 76px;
                width: 76px;
            }

            .bank-transaction-page .cash-cell-line {
                display: block;
                white-space: nowrap;
                overflow-wrap: normal;
                word-break: normal;
            }

            .bank-transaction-page .cash-date-cell,
            .bank-transaction-page .cash-money-cell,
            .bank-transaction-page .cash-creator-cell,
            .bank-transaction-page .cash-file-link {
                white-space: nowrap;
            }

            .bank-transaction-page .cash-money-cell {
                text-align: right;
            }

            .bank-transaction-page .cash-total-label,
            .bank-transaction-page .cash-total-money {
                white-space: nowrap;
            }

            .bank-transaction-page .cash-empty-cell {
                text-align: center;
                vertical-align: middle;
            }

            .bank-transaction-page .action-toggle-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                padding: 0;
            }

            .bank-transaction-page .action-menu {
                position: fixed !important;
                right: auto;
                z-index: 2050 !important;
            }
        }

        @media (max-width: 360px) {
            .bank-transaction-page .bank-toolbar {
                flex-wrap: wrap;
            }

            .bank-transaction-page .bank-date-filter,
            .bank-transaction-page .bank-create-action,
            .bank-transaction-page .bank-create-action .btn {
                width: 100%;
            }

            .bank-transaction-page .bank-create-action {
                margin-left: 0;
            }
        }

        .bank-transaction-page {
            max-width: 100%;
            overflow-x: hidden;
        }

        .bank-transaction-page .bank-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .bank-transaction-page .bank-scroll-hint {
            color: #6c757d;
            font-size: 12px;
            margin: 0 0 8px;
        }

        .bank-transaction-page .bank-transactions-table {
            min-width: 1890px;
            width: max-content;
            table-layout: auto;
        }

        .bank-transaction-page .bank-transactions-table th,
        .bank-transaction-page .bank-transactions-table td {
            box-sizing: border-box;
        }

        .bank-transaction-page .cash-col-id {
            min-width: 70px !important;
            width: 70px !important;
            text-align: center;
            white-space: nowrap;
        }

        .bank-transaction-page .cash-col-date {
            min-width: 105px !important;
            width: 105px !important;
            white-space: nowrap;
        }

        .bank-transaction-page .cash-col-account {
            min-width: 110px !important;
            width: 110px !important;
            white-space: normal;
        }

        .bank-transaction-page .cash-col-operation {
            min-width: 180px !important;
            width: 180px !important;
            white-space: normal;
        }

        .bank-transaction-page .cash-col-contra {
            min-width: 165px !important;
            width: 165px !important;
            white-space: normal;
        }

        .bank-transaction-page .cash-col-party {
            min-width: 170px !important;
            width: 170px !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .bank-transaction-page .cash-col-document {
            min-width: 165px !important;
            width: 165px !important;
            white-space: normal;
        }

        .bank-transaction-page .cash-col-description {
            min-width: 250px !important;
            width: 250px !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .bank-transaction-page .cash-col-money {
            min-width: 120px !important;
            width: 120px !important;
            text-align: right;
            white-space: nowrap;
        }

        .bank-transaction-page .cash-col-status {
            min-width: 120px !important;
            width: 120px !important;
            white-space: nowrap;
        }

        .bank-transaction-page .cash-col-creator {
            min-width: 110px !important;
            width: 110px !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .bank-transaction-page .cash-col-file {
            min-width: 110px !important;
            width: 110px !important;
            white-space: nowrap;
        }

        .bank-transaction-page .cash-col-action {
            min-width: 80px !important;
            width: 80px !important;
            white-space: nowrap;
        }

        .bank-transaction-page .cash-cell-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            max-height: 2.7em;
            overflow: hidden;
            overflow-wrap: anywhere;
            line-height: 1.35;
            word-break: break-word;
        }

        .bank-transaction-page .bank-pagination-area {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
            padding: 12px 4px 0;
        }

        .bank-transaction-page .bank-pagination-area nav {
            margin: 0;
        }

        .bank-transaction-page .bank-pagination-area .pagination {
            margin: 0;
            gap: 4px;
        }

        .bank-transaction-page .bank-pagination-area .page-link {
            min-width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .375rem .65rem;
            line-height: 1;
        }

        @media (max-width: 767.98px) {
            .bank-transaction-page .bank-transactions-table th,
            .bank-transaction-page .bank-transactions-table td {
                overflow-wrap: normal;
                word-break: normal;
            }

            .bank-transaction-page .bank-transactions-table th {
                text-align: center;
                vertical-align: middle;
                white-space: nowrap;
            }
        }
    </style>
@endpush
