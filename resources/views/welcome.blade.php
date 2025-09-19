@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <div class="dashboard-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="search-container">
                            {{-- <input type="text" class="form-control search-input"
                                placeholder="Tìm kiếm đơn hàng, khách hàng..."> --}}
                            <input type="text" id="dateFilter" class="form-control search-input"
                                placeholder="Chọn khoảng ngày">
                        </div>
                    </div>
                    <div class="col-md-6">

                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <!-- Main Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon revenue">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="metric-title">Doanh thu (hôm nay)</div>

                        {{-- Giá trị hôm nay --}}
                        <div class="metric-value">
                            {{ number_format($stats['today_revenue'], 0, ',', '.') }} ₫
                        </div>

                        {{-- So sánh hôm qua --}}
                        @if ($stats['percent_change'] !== null)
                            @php
                                $isPositive = $stats['percent_change'] >= 0;
                            @endphp
                            <div class="metric-change {{ $isPositive ? 'positive' : 'negative' }}">
                                <i class="fas {{ $isPositive ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                {{ $stats['percent_change'] }}% so với hôm qua
                            </div>
                        @else
                            <div class="metric-change">
                                Không có dữ liệu hôm qua
                            </div>
                        @endif
                    </div>

                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="metric-title">Đơn hàng (hôm nay)</div>

                        {{-- Tổng số đơn hôm nay --}}
                        <div class="metric-value">
                            {{ $orderStats['today_orders'] }}
                        </div>

                        {{-- So sánh hôm qua --}}
                        @if (!is_null($orderStats['percent_change']))
                            @php
                                $isPositive = $orderStats['percent_change'] >= 0;
                            @endphp
                            <div class="metric-change {{ $isPositive ? 'positive' : 'negative' }}">
                                <i class="fas {{ $isPositive ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                {{ $orderStats['percent_change'] }}% so với hôm qua
                            </div>
                        @else
                            <div class="metric-change">
                                Không có dữ liệu hôm qua
                            </div>
                        @endif
                    </div>
                </div>


                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon profit">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="metric-title">Tổng doanh thu</div>

                        {{-- Doanh thu --}}
                        <div class="metric-value">
                            {{ number_format($totalRevenueStats['total_revenue'], 0, ',', '.') }} ₫
                        </div>

                        {{-- Biên lợi nhuận gộp --}}
                        @if (isset($totalRevenueStats['gross_margin']))
                            <div
                                class="metric-change {{ $totalRevenueStats['gross_margin'] >= 0 ? 'positive' : 'negative' }}">
                                Biên LN gộp ~{{ $totalRevenueStats['gross_margin'] }}%
                            </div>
                        @endif
                    </div>
                </div>


                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon inventory">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="metric-title">Tồn kho</div>

                        {{-- Tổng tồn kho --}}
                        <div class="metric-value">
                            {{ $inventoryStats['total_stock'] }} sp
                        </div>

                        {{-- Sản phẩm sắp hết --}}
                        <div class="metric-change {{ $inventoryStats['low_stock_count'] > 0 ? 'negative' : 'positive' }}">
                            @if ($inventoryStats['low_stock_count'] > 0)
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ $inventoryStats['low_stock_count'] }} sản phẩm sắp hết
                            @else
                                <i class="fas fa-check-circle"></i>
                                Không có sản phẩm sắp hết
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- Additional Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon customers">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-title">Khách hàng mới</div>
                        <div class="metric-value">{{ $newCustomers['today_new'] }}</div>
                        <div class="metric-change {{ $newCustomers['percent_change'] >= 0 ? 'positive' : 'negative' }}">
                            <i
                                class="fas {{ $newCustomers['percent_change'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                            {{ abs($newCustomers['percent_change'] ?? 0) }}% so với hôm qua
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon conversion">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="metric-title">Tỷ lệ chuyển đổi</div>
                        <div class="metric-value">3.2%</div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up"></i>
                            0.5% so với tháng trước
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon orders">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div class="metric-title">Đơn hàng hoàn trả</div>
                        <div class="metric-value">8</div>
                        <div class="metric-change negative">
                            <i class="fas fa-arrow-down"></i>
                            2.1% tổng đơn hàng
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="metric-card">
                        <div class="metric-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="metric-title">Giá trị đơn hàng TB</div>

                        {{-- AOV --}}
                        <div class="metric-value">
                            {{ number_format($aovStats['current_aov'], 0, ',', '.') }} ₫
                        </div>

                        {{-- So sánh tháng trước --}}
                        @if (!is_null($aovStats['percent_change']))
                            @php
                                $isPositive = $aovStats['percent_change'] >= 0;
                            @endphp
                            <div class="metric-change {{ $isPositive ? 'positive' : 'negative' }}">
                                <i class="fas {{ $isPositive ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                {{ $aovStats['percent_change'] }}% so với tháng trước
                            </div>
                        @else
                            <div class="metric-change">
                                Không có dữ liệu tháng trước
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Charts and Tables -->
            <div class="row g-4 mt-2">
                <div class="col-xl-6">
                    <div class="chart-card">
                        <div class="chart-title">Doanh thu theo thời gian</div>
                        <div
                            style="height: 300px; background: #f8fafc; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <div class="text-center">
                                <i class="fas fa-chart-area fa-3x mb-3"></i>
                                <div>Biểu đồ doanh thu sẽ được hiển thị tại đây</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="table-card">
                        <div class="table-header">
                            <h5 class="table-title">Sản phẩm bán chạy</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table custom-table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đã bán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topSellingProducts as $product)
                                        <tr>
                                            <td>{{ $product->name }}</td>
                                            <td><strong>{{ $product->total_sold }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2">Không có dữ liệu</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-xl-6">
                    <div class="table-card">
                        <div class="table-header">
                            <h5 class="table-title">Đơn hàng gần đây</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table custom-table">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Giá trị</th>
                                        <th>Phương thức thanh toán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($latestOrders as $order)
                                        <tr>
                                            <td>{{ $order->order_code }}</td>
                                            <td>{{ $order->customer_name }}</td>
                                            <td>{{ number_format($order->total_money) }} đ</td>
                                            <td>
                                                @if ($order->payment_method === 'cash')
                                                    Tiền mặt
                                                @elseif($order->payment_method === 'bank_transfer')
                                                    Chuyển khoản
                                                @elseif($order->payment_method === 'debt')
                                                    Công nợ
                                                @else
                                                    {{ ucfirst($order->payment_method) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="table-card">
                        <div class="table-header">
                            <h5 class="table-title">Sản phẩm sắp hết hàng</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table custom-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50%">Sản phẩm</th>
                                        <th>Tồn kho</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lowStockProducts as $product)
                                        <tr>
                                            <td>{{ $product->name }}</td>
                                            <td><strong>{{ $product->quantity }}</strong></td>
                                            <td><span
                                                    class="{{ $product->status_class }}">{{ $product->status_label }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">Không có dữ liệu</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {

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

            $('#day').click(function(e) {
                $.ajax({
                    url: '{{ route('admin.dashboard.day') }}',
                    method: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}',
                    },
                    success: function(response) {
                        let today = new Date();
                        let formattedDate = today.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        $('#day_month_year').text(formattedDate);
                        $('#daily').text('Thu nhập ngày');
                        $('#dropdownMenuButton').text('Theo ngày');
                        $('#income').text(response.daily['income']);
                        $('#amount').text(response.daily['amount'] + ' Đơn');
                        $('#interest').text('Lợi nhuận : ' + response.daily['interest']);
                        $('#moneyinterest').text(response.daily['moneyinterest']);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            });

            $('#month').click(function(e) {
                $.ajax({
                    url: '{{ route('admin.dashboard.month') }}',
                    method: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}',
                    },
                    success: function(response) {
                        let today = new Date();
                        let month = today.getMonth() + 1;
                        let year = today.getFullYear();
                        let formattedMonthYear = `${month}/${year}`;
                        $('#day_month_year').text(formattedMonthYear);
                        $('#daily').text('Thu nhập tháng');
                        $('#dropdownMenuButton').text('Theo tháng');
                        $('#income').text(response.daily['income']);
                        $('#amount').text(response.daily['amount'] + ' Đơn');
                        $('#interest').text('Lãi xuất : ' + response.daily['interest']);
                        $('#moneyinterest').text(response.daily['moneyinterest']);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            })

            $('#year').click(function(e) {
                $.ajax({
                    url: '{{ route('admin.dashboard.year') }}',
                    method: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}',
                    },
                    success: function(response) {
                        let today = new Date();
                        let year = today.getFullYear();
                        let formattedYear = `${year}`;
                        $('#day_month_year').text(formattedYear);
                        $('#daily').text('Thu nhập năm');
                        $('#dropdownMenuButton').text('Theo năm');
                        $('#income').text(response.daily['income']);
                        $('#amount').text(response.daily['amount'] + ' Đơn');
                        $('#interest').text('Lợi nhuận : ' + response.daily['interest']);
                        $('#moneyinterest').text(response.daily['moneyinterest']);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            })
        });

        function formatCurrency(number) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(number).replace('₫', 'VND');
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const slider = document.querySelector(".slider");
            const slides = document.querySelectorAll(".slide");
            const prevBtn = document.querySelector(".prev-btn");
            const nextBtn = document.querySelector(".next-btn");

            let slideIndex = 0;
            const totalSlides = slides.length;
            const autoSlideInterval = 3000;

            showSlide(slideIndex);

            prevBtn.addEventListener("click", function() {
                slideIndex = (slideIndex - 1 + totalSlides) % totalSlides;
                showSlide(slideIndex);
                resetAutoSlide();
            });


            nextBtn.addEventListener("click", function() {
                slideIndex = (slideIndex + 1) % totalSlides;
                showSlide(slideIndex);
                resetAutoSlide();
            });

            let autoSlideTimer = setInterval(function() {
                slideIndex = (slideIndex + 1) % totalSlides;
                showSlide(slideIndex);
            }, autoSlideInterval);

            function resetAutoSlide() {
                clearInterval(autoSlideTimer);
                autoSlideTimer = setInterval(function() {
                    slideIndex = (slideIndex + 1) % totalSlides;
                    showSlide(slideIndex);
                }, autoSlideInterval);
            }

            function showSlide(index) {
                slider.style.transform = `translateX(${-index * 100}%)`;
            }
        });
    </script>
@endpush


@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <style>
        .dashboard-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 0.75rem 1rem 0.75rem 1.5rem;
            font-size: 0.875rem;
            width: 100%;
            max-width: 400px;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .filter-controls {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .time-filter {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            background: white;
            font-size: 0.875rem;
        }

        .refresh-btn {
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .refresh-btn:hover {
            background: #5855eb;
        }

        .metric-card {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            height: 100%;
            transition: all 0.2s ease;
        }

        .metric-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .metric-icon.revenue {
            background: #fef3c7;
            color: #d97706;
        }

        .metric-icon.orders {
            background: #dbeafe;
            color: #2563eb;
        }

        .metric-icon.profit {
            background: #dcfce7;
            color: #16a34a;
        }

        .metric-icon.inventory {
            background: #fce7f3;
            color: #db2777;
        }

        .metric-icon.customers {
            background: #e0e7ff;
            color: #6366f1;
        }

        .metric-icon.conversion {
            background: #fed7d7;
            color: #e53e3e;
        }

        .metric-title {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .metric-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .metric-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .metric-change.positive {
            color: #16a34a;
        }

        .metric-change.negative {
            color: #dc2626;
        }

        .chart-card {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .table-card {
            background: white;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .table-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .custom-table {
            margin: 0;
        }

        .custom-table th {
            background: #f8fafc;
            border: none;
            color: #64748b;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 1rem 1.5rem;
        }

        .custom-table td {
            border: none;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .low-stock {
            color: #dc2626;
            font-weight: 500;
        }

        .in-stock {
            color: #16a34a;
            font-weight: 500;
        }
    </style>
@endpush
