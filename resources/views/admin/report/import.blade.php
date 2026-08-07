@extends('admin.layout.index')

@section('content')
    <div class="page-inner daily-import-report-page">
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

        <!-- Today's Orders Section -->
        <div class="col-md-12 daily-import-report-col">
            <div class="card daily-import-card">
                <div class="card-header d-flex justify-content-between align-items-center daily-import-filter-bar">
                    <div class="search-container daily-import-date-filter">
                        <input type="text" style="width: 350px" class="form-control search-input daily-import-date-input"
                            placeholder="Chọn khoảng ngày">
                    </div>

                    <div class="d-flex justify-content-end align-items-center daily-import-actions">
                        <input type="search" name="search" class="form-control me-2 daily-import-search-input" style="width: 300px;"
                            placeholder="Tìm kiếm...">

                        <button type="button" class="btn daily-import-reset-btn" id="btn-reset"> <i class="fa-solid fa-rotate"></i></button>
                    </div>
                </div>
                <div class="card-header daily-import-title-header">
                    <h4 class="card-title daily-import-title" style="text-align: center; color:rgb(15, 0, 0)">Danh sách đơn nhập hàng hôm nay
                    </h4>
                </div>

                <div class="card-body daily-import-card-body">
                    <div class="daily-import-table-section">
                        <!-- Table for Orders -->
                        <div class="daily-import-table-hint">Vuốt ngang để xem đầy đủ bảng</div>

                        <div class="table-responsive px-3 daily-import-table-scroll">
                            <table class="table table-striped table-hover report-import-table align-middle">
                                <thead>
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Nhân viên</th>
                                        <th>Ngày tạo</th>
                                        <th>Nhà cung cấp</th>
                                        <th>Phương thức thanh toán</th>
                                        <th>Trạng thái</th>
                                        <th>Tổng tiền</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($imports as $import)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.importproduct.importCoupon.detail', ['id' => $import->id]) }}"
                                                    class="text-dark fw-bold">
                                                    {{ $import->coupon_code ?? 'PN-' . $import->id }}
                                                </a>
                                            </td>

                                            <td>
                                                {{ $import->user?->name ?? 'Không xác định' }}
                                            </td>

                                            <td>
                                                {{ $import->created_at?->format('d/m/Y') ?? '---' }}
                                            </td>

                                            <td>
                                                {{ $import->company?->name ?? 'Nhà cung cấp không tồn tại' }}
                                            </td>

                                            <td>
                                                {{ $import->payment_method_label }}
                                            </td>

                                            <td>
                                                @if ($import->resolved_payment_status === \App\Models\ImportCoupon::PAYMENT_STATUS_PAID)
                                                    <span class="badge bg-success">
                                                        {{ $import->payment_status_label }}
                                                    </span>
                                                @elseif ($import->resolved_payment_status === \App\Models\ImportCoupon::PAYMENT_STATUS_PARTIAL)
                                                    <span class="badge bg-warning text-dark">
                                                        {{ $import->payment_status_label }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        {{ $import->payment_status_label }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                {{ number_format($import->total ?? 0) }} VND
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                Không có đơn nhập hàng nào
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($imports->hasPages())
                            <div class="d-flex justify-content-center mt-3 daily-import-pagination">
                                {{ $imports->appends(request()->except('import_page'))->links('vendor.pagination.custom') }}
                            </div>
                        @endif

                        <!-- End Table -->
                    </div>
                </div>
            </div>
        </div>

        {{-- <!-- Today's Products Section -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="search-container">
                        <input type="text" style="width: 350px" class="form-control search-input"
                            placeholder="Chọn khoảng ngày">
                    </div>

                    <div class="d-flex justify-content-end align-items-center">
                        <input type="search" name="search" class="form-control me-2" style="width: 300px;"
                            placeholder="Tìm kiếm...">

                        <button type="button" class="btn" id="btn-reset"> <i class="fa-solid fa-rotate"></i></button>
                    </div>
                </div>
                <div class="card-header">
                    <h4 class="card-title" style="text-align: center; color:rgb(15, 0, 0)">Danh sách sản phẩm nhập hôm
                        nay
                    </h4>
                </div>

                <div class="card-body">
                    <div class="">
                        <!-- Table for Product Sales -->
                        <div id="products-sales-table_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                            <div class="table-responsive px-3">
                                <table class="table table-striped table-hover align-middle report-product-table">
                                    <thead>
                                        <tr>
                                            <th>Tên sản phẩm</th>
                                            <th>Số lượng</th>
                                            <th>Giá nhập cũ</th>
                                            <th>Giá nhập mới</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($productImports as $productId => $importData)
                                            @php
                                                $product = $products->get($productId);
                                            @endphp

                                            @if ($product)
                                                <tr>
                                                    <td>{{ $product->name }}</td>
                                                    <td>{{ $importData['quantity'] ?? 0 }}</td>

                                                    <td>
                                                        {{ number_format($importData['old_price'] ?? 0) }} VND
                                                    </td>

                                                    <td>
                                                        {{ number_format($importData['price'] ?? 0) }} VND
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4">
                                                    Không có sản phẩm nhập nào
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($productImports->hasPages())
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $productImports->appends(request()->except('products_page'))->links('vendor.pagination.custom') }}
                                </div>
                            @endif
                        </div>
                        <!-- End Table -->
                    </div>
                </div>
            </div>
        </div> --}}

        <!-- Export Button -->
        <div class="text-center mt-4 daily-import-export-wrap">
            <button type="button" id="exportimports" class="btn btn-primary daily-import-export-btn">
                Xuất báo cáo hàng ngày
            </button>
        </div>
    </div>

    <!-- Include SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-notify/0.2.0/js/bootstrap-notify.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#exportimports').on('click', function() {
                // Fetch data from the server for the daily report
                const exportUrl = '{{ route('admin.report.imports.getDailyImportData') }}';

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
@push('style')
    <style>
        .daily-import-report-page,
        .daily-import-report-page .daily-import-card,
        .daily-import-report-page .daily-import-table-section {
            max-width: 100%;
            min-width: 0;
        }

        .daily-import-report-page .daily-import-table-hint {
            display: none;
        }

        .daily-import-report-page .daily-import-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .daily-import-report-page .daily-import-pagination .client-pagination-mobile-label,
        .daily-import-report-page .daily-import-pagination .pagination-arrow-mobile {
            display: none;
        }

        .daily-import-report-page .report-import-table th:first-child,
        .daily-import-report-page .report-import-table td:first-child {
            padding-left: 20px !important;
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .daily-import-report-page {
                padding: 0 10px 24px !important;
                overflow-x: visible;
            }

            .daily-import-report-page .page-header {
                margin: 0;
                padding: 0;
                min-height: auto;
            }

            .daily-import-report-page .page-header .breadcrumb {
                margin: 0 0 8px;
                padding-left: 0;
                padding-right: 0;
            }

            .daily-import-report-page .daily-import-report-col {
                padding-left: 0 !important;
                padding-right: 0 !important;
                max-width: 100%;
            }

            .daily-import-report-page .daily-import-card {
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .daily-import-report-page .daily-import-filter-bar {
                display: flex !important;
                flex-direction: column;
                align-items: stretch !important;
                justify-content: flex-start !important;
                gap: 8px;
                padding: 10px 12px;
            }

            .daily-import-report-page .daily-import-date-filter,
            .daily-import-report-page .daily-import-actions {
                width: 100%;
                max-width: 100%;
            }

            .daily-import-report-page .daily-import-date-input {
                width: 100% !important;
                max-width: 100%;
                height: 40px;
            }

            .daily-import-report-page .daily-import-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 8px;
            }

            .daily-import-report-page .daily-import-search-input {
                flex: 1 1 auto;
                width: auto !important;
                min-width: 0;
                max-width: 100%;
                height: 40px;
                margin-right: 0 !important;
            }

            .daily-import-report-page .daily-import-reset-btn {
                flex: 0 0 42px;
                width: 42px;
                min-width: 42px;
                height: 40px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .daily-import-report-page .daily-import-title-header {
                padding: 10px 12px;
            }

            .daily-import-report-page .daily-import-title {
                margin: 0;
                font-size: 16px;
                line-height: 1.35;
                text-align: center;
            }

            .daily-import-report-page .daily-import-card-body {
                padding: 10px 12px 12px;
            }

            .daily-import-report-page .daily-import-table-hint {
                display: block;
                margin-bottom: 8px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.35;
            }

            .daily-import-report-page .daily-import-table-scroll {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .daily-import-report-page .report-import-table {
                min-width: 1080px;
                margin-bottom: 0;
            }

            .daily-import-report-page .report-import-table th,
            .daily-import-report-page .report-import-table td {
                vertical-align: middle;
            }

            .daily-import-report-page .report-import-table th:nth-child(1),
            .daily-import-report-page .report-import-table th:nth-child(2),
            .daily-import-report-page .report-import-table th:nth-child(3),
            .daily-import-report-page .report-import-table th:nth-child(5),
            .daily-import-report-page .report-import-table th:nth-child(6),
            .daily-import-report-page .report-import-table th:nth-child(7),
            .daily-import-report-page .report-import-table td:nth-child(1),
            .daily-import-report-page .report-import-table td:nth-child(2),
            .daily-import-report-page .report-import-table td:nth-child(3),
            .daily-import-report-page .report-import-table td:nth-child(5),
            .daily-import-report-page .report-import-table td:nth-child(6),
            .daily-import-report-page .report-import-table td:nth-child(7) {
                white-space: nowrap;
            }

            .daily-import-report-page .report-import-table th:nth-child(4),
            .daily-import-report-page .report-import-table td:nth-child(4) {
                min-width: 190px;
                word-break: normal;
                overflow-wrap: normal;
            }

            .daily-import-report-page .daily-import-pagination {
                max-width: 100%;
                margin-top: 12px;
                overflow-x: hidden;
            }

            .daily-import-report-page .daily-import-pagination .pagination {
                flex-wrap: nowrap;
                align-items: center;
                gap: 8px !important;
                margin-bottom: 0;
            }

            .daily-import-report-page .daily-import-pagination .client-pagination-page,
            .daily-import-report-page .daily-import-pagination .client-pagination-ellipsis {
                display: none;
            }

            .daily-import-report-page .daily-import-pagination .client-pagination-mobile-label {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 36px;
                padding: 0 6px;
                color: #495057;
                font-size: 13px;
                white-space: nowrap;
            }

            .daily-import-report-page .daily-import-pagination .pagination-arrow-desktop {
                display: none;
            }

            .daily-import-report-page .daily-import-pagination .pagination-arrow-mobile {
                display: inline;
            }

            .daily-import-report-page .daily-import-pagination .page-link {
                min-width: 36px;
                height: 36px;
                padding: 0 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .daily-import-report-page .daily-import-export-wrap {
                margin-top: 18px !important;
                display: flex;
                justify-content: center;
            }

            .daily-import-report-page .daily-import-export-btn {
                width: auto;
                max-width: min(240px, 100%);
                white-space: normal;
                text-align: center;
            }
        }
    </style>
@endpush
