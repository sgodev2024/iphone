@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
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

        <div class="row">
            <!-- Today's Orders Section -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="search-container">
                            <input type="text" id="dateFilter" style="width: 350px" class="form-control search-input"
                                placeholder="Chọn khoảng ngày">
                        </div>

                        <div class="d-flex justify-content-end align-items-center">
                            <input type="search" name="search" class="form-control me-2" style="width: 300px;"
                                placeholder="Tìm kiếm...">

                            <button type="button" class="btn" id="btn-reset"> <i
                                    class="fa-solid fa-rotate"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:rgb(17, 1, 1)">Danh sách đơn hàng hôm nay
                        </h4>
                    </div>

                    <div class="card-body">

                        <!-- Table for Orders -->
                        <div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <table class="table table-striped table-hover">
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

                                    <!-- Pagination for Orders -->
                                    {{ $orders->appends(request()->except('orders_page'))->links('vendor.pagination.custom') }}
                                </div>
                            </div>
                        </div>
                        <!-- End Table -->

                    </div>
                </div>
            </div>

        </div>

        <!-- Export Button -->
        <div class="text-center mt-4">
            <button type="button" id="exportorders" class="btn btn-primary">
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
@endpush
