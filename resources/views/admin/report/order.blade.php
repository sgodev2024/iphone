@extends('admin.layout.index')

@section('content')
    <div class="page-inner daily-order-report-page">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'BÁO CÁO'], ['label' => 'HÔM NAY']]" />
            {{-- <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">BÁO CÁO</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">HÔM NAY</a>
                </li>
            </ul> --}}
        </div>

        <div class="row daily-report-row">
            <!-- Today's Orders Section -->
            <div class="col-md-12 daily-report-col">
                <div class="card daily-order-card">
                    <div class="card-header d-flex justify-content-between align-items-center daily-report-filter-bar">
                        <div class="search-container daily-report-date-filter">
                            <input type="text" id="dateFilter" style="width: 350px" class="form-control search-input daily-report-date-input"
                                placeholder="Chọn khoảng ngày">
                        </div>

                        <div class="d-flex justify-content-end align-items-center daily-report-actions">
                            <input type="search" name="search" class="form-control me-2 daily-report-search-input" style="width: 300px;"
                                placeholder="Tìm kiếm...">

                            <button type="button" class="btn daily-report-reset-btn" id="btn-reset"> <i
                                    class="fa-solid fa-rotate"></i></button>
                        </div>
                    </div>
                    <div class="card-header daily-report-title-header">
                        <h4 class="card-title daily-report-title" style="text-align: center; color:rgb(17, 1, 1)">Danh sách đơn hàng hôm nay
                        </h4>
                    </div>

                    <div class="card-body daily-report-card-body">

                        <!-- Table for Orders -->
                        <div class="daily-report-table-section">
                            <div class="row daily-report-table-row">
                                <div class="col-sm-12 daily-report-table-col">
                                    <div class="daily-report-table-hint">Vuốt ngang để xem đầy đủ bảng</div>

                                    <div class="daily-report-table-scroll">
                                        <table class="table table-striped table-hover daily-report-orders-table">
                                        <thead>
                                            <tr role="row">
                                                <th>Mã đơn hàng</th>
                                                <th>Nhân viên</th>
                                                <th>Ngày tạo</th>
                                                <th>Khách hàng</th>
                                                <th>Trạng thái</th>
                                                <th>Tổng tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($orders as $order)
                                                <tr>
                                                    <td>
                                                        <a style="color: black; font-weight:bold"
                                                            href="{{ route('admin.order.show', $order->id) }}">{{ $order->id }}</a>
                                                    </td>
                                                    <td>
                                                        {{ optional($order->user)->name ?? '' }}
                                                    </td>
                                                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                                    <td>
                                                        @if ($order->client && !$order->client->trashed())
                                                            <a style="color:black"
                                                                href="{{ route('admin.client.detail', ['id' => $order->client->id]) }}">
                                                                {{ $order->customer_display_name }}
                                                            </a>
                                                        @else
                                                            {{ $order->customer_display_name }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($order->status == 1)
                                                            <span class="badge badge-success">Đã thanh toán</span>
                                                        @else
                                                            <span class="badge badge-danger">Công nợ</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ number_format($order->total_money) }} VND</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td class="text-center" colspan="6">Không có đơn hàng nào</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination for Orders -->
                                    <div class="daily-report-pagination">
                                        {{ $orders->appends(request()->except('orders_page'))->links('vendor.pagination.custom') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Table -->

                    </div>
                </div>
            </div>

        </div>

        <!-- Export Button -->
        <div class="text-center mt-4 daily-report-export-wrap">
            <button type="button" id="exportorders" class="btn btn-primary daily-report-export-btn">
                Xuất báo cáo hàng ngày
            </button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#exportorders').on('click', function() {
                // Fetch data from the server for the daily report
                const exportUrl = '{{ route('admin.report.orders.getDailyOrderData') }}';

                $.ajax({
                    url: exportUrl,
                    method: 'GET',
                    xhrFields: {
                        responseType: 'blob' // To receive data as a blob
                    },
                    success: function(data) {
                        // Create a URL for the blob
                        const url = window.URL.createObjectURL(new Blob([data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.setAttribute('download', 'daily_report.xlsx');
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                        alert('Có lỗi xảy ra khi xuất báo cáo.');
                    }
                });
            });
        });
    </script>
@endsection
@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
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

            });

            $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

            });

            $('#btn-reset').click(function() {
                $('input[name="search"]').val('');
            })

        })
    </script>
@endpush
@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .daily-order-report-page,
        .daily-order-report-page .daily-order-card,
        .daily-order-report-page .daily-report-table-section {
            max-width: 100%;
            min-width: 0;
        }

        .daily-order-report-page .daily-report-table-hint {
            display: none;
        }

        .daily-order-report-page .daily-report-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .daily-order-report-page {
                padding: 0 10px 24px !important;
            }

            .daily-order-report-page .page-header {
                margin: 0;
                padding: 0;
                min-height: auto;
            }

            .daily-order-report-page .page-header .breadcrumb {
                margin: 0 0 8px;
                padding-left: 0;
                padding-right: 0;
            }

            .daily-order-report-page .daily-report-row,
            .daily-order-report-page .daily-report-table-row {
                margin-left: 0;
                margin-right: 0;
            }

            .daily-order-report-page .daily-report-col,
            .daily-order-report-page .daily-report-table-col {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .daily-order-report-page .daily-order-card {
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .daily-order-report-page .daily-report-filter-bar {
                display: flex !important;
                flex-direction: column;
                align-items: stretch !important;
                justify-content: flex-start !important;
                gap: 8px;
                padding: 10px 12px;
            }

            .daily-order-report-page .daily-report-date-filter,
            .daily-order-report-page .daily-report-actions {
                width: 100%;
                max-width: 100%;
            }

            .daily-order-report-page .daily-report-date-input {
                width: 100% !important;
                max-width: 100%;
                height: 40px;
            }

            .daily-order-report-page .daily-report-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 8px;
            }

            .daily-order-report-page .daily-report-search-input {
                flex: 1 1 auto;
                width: auto !important;
                min-width: 0;
                max-width: 100%;
                height: 40px;
                margin-right: 0 !important;
            }

            .daily-order-report-page .daily-report-reset-btn {
                flex: 0 0 42px;
                width: 42px;
                min-width: 42px;
                height: 40px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .daily-order-report-page .daily-report-title-header {
                padding: 10px 12px;
            }

            .daily-order-report-page .daily-report-title {
                margin: 0;
                font-size: 16px;
                line-height: 1.35;
                text-align: center;
            }

            .daily-order-report-page .daily-report-card-body {
                padding: 10px 12px 12px;
            }

            .daily-order-report-page .daily-report-table-hint {
                display: block;
                margin-bottom: 8px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.35;
            }

            .daily-order-report-page .daily-report-orders-table {
                min-width: 920px;
                margin-bottom: 0;
            }

            .daily-order-report-page .daily-report-orders-table th:nth-child(1),
            .daily-order-report-page .daily-report-orders-table th:nth-child(2),
            .daily-order-report-page .daily-report-orders-table th:nth-child(3),
            .daily-order-report-page .daily-report-orders-table th:nth-child(5),
            .daily-order-report-page .daily-report-orders-table th:nth-child(6),
            .daily-order-report-page .daily-report-orders-table td:nth-child(1),
            .daily-order-report-page .daily-report-orders-table td:nth-child(2),
            .daily-order-report-page .daily-report-orders-table td:nth-child(3),
            .daily-order-report-page .daily-report-orders-table td:nth-child(5),
            .daily-order-report-page .daily-report-orders-table td:nth-child(6) {
                white-space: nowrap;
            }

            .daily-order-report-page .daily-report-orders-table th:nth-child(4),
            .daily-order-report-page .daily-report-orders-table td:nth-child(4) {
                min-width: 180px;
                word-break: normal;
                overflow-wrap: normal;
            }

            .daily-order-report-page .daily-report-orders-table th,
            .daily-order-report-page .daily-report-orders-table td {
                vertical-align: middle;
            }

            .daily-order-report-page .daily-report-pagination {
                max-width: 100%;
                margin-top: 12px;
                overflow-x: hidden;
            }

            .daily-order-report-page .daily-report-pagination .pagination {
                flex-wrap: nowrap;
                align-items: center;
                gap: 8px !important;
                margin-bottom: 0;
            }

            .daily-order-report-page .daily-report-pagination .client-pagination-page,
            .daily-order-report-page .daily-report-pagination .client-pagination-ellipsis {
                display: none;
            }

            .daily-order-report-page .daily-report-pagination .client-pagination-mobile-label {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 36px;
                padding: 0 6px;
                color: #495057;
                font-size: 13px;
                white-space: nowrap;
            }

            .daily-order-report-page .daily-report-pagination .pagination-arrow-desktop {
                display: none;
            }

            .daily-order-report-page .daily-report-pagination .pagination-arrow-mobile {
                display: inline;
            }

            .daily-order-report-page .daily-report-pagination .page-link {
                min-width: 36px;
                height: 36px;
                padding: 0 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .daily-order-report-page .daily-report-export-wrap {
                margin-top: 18px !important;
                display: flex;
                justify-content: center;
            }

            .daily-order-report-page .daily-report-export-btn {
                width: auto;
                max-width: min(240px, 100%);
                white-space: normal;
                text-align: center;
            }
        }
    </style>
@endpush
