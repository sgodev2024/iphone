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

        <!-- Today's Orders Section -->
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
                    <h4 class="card-title" style="text-align: center; color:rgb(15, 0, 0)">Danh sách đơn nhập hàng hôm
                        nay</h4>
                </div>

                <div class="card-body">
                    <div class="">
                        <!-- Table for Orders -->
                        <div class="table-responsive px-3">
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
                            <div class="d-flex justify-content-center mt-3">
                                {{ $imports->appends(request()->except('import_page'))->links('vendor.pagination.custom') }}
                            </div>
                        @endif

                        <!-- End Table -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Products Section -->
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
        </div>

        <!-- Export Button -->
        <div class="text-center mt-4">
            <button type="button" id="exportimports" class="btn btn-primary">Xuất báo cáo hàng ngày</button>
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
        .report-import-table th:first-child,
        .report-import-table td:first-child {
            padding-left: 20px !important;
            white-space: nowrap;
        }
    </style>
@endpush
