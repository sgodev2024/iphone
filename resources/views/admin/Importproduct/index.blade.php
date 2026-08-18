@extends('admin.layout.index')

@push('style')
    <style>
        .import-product-page .import-product-table-hint,
        .import-product-page .import-product-pagination-mobile {
            display: none;
        }

        .import-product-page .import-product-company-filter {
            width: 220px;
            min-width: 180px;
            height: 40px;
        }

        @media (max-width: 767.98px) {
            .main-panel > .container:has(.import-product-page) {
                overflow-x: clip;
            }

            .import-product-page {
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px !important;
                padding-left: 10px !important;
                overflow-x: visible;
            }

            .import-product-page .page-header {
                align-items: flex-start;
                margin-bottom: 10px;
            }

            .import-product-page .page-header .breadcrumb,
            .import-product-page .page-header .breadcrumbs {
                max-width: 100%;
                margin-left: 0;
            }

            .import-product-page > .row {
                --bs-gutter-x: 0;
                margin-right: 0;
                margin-left: 0;
            }

            .import-product-page > .row > [class*="col-"] {
                min-width: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .import-product-page .card {
                width: 100%;
                max-width: 100%;
                margin-right: auto;
                margin-left: auto;
                overflow: visible;
            }

            .import-product-page .import-product-toolbar {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 8px;
                align-items: center;
                padding: 10px;
            }

            .import-product-page .import-product-toolbar-left {
                display: contents !important;
            }

            .import-product-page .import-product-search {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 40px;
                grid-row: 1;
                grid-column: 1 / -1;
                gap: 8px;
                width: 100%;
                min-width: 0;
            }

            .import-product-page .import-product-search-input {
                grid-row: 1;
                grid-column: 1;
                width: auto !important;
                min-width: 0;
                height: 40px;
                margin-right: 0 !important;
                font-size: 14px;
            }

            .import-product-page .import-product-company-filter {
                grid-row: 2;
                grid-column: 1 / -1;
                width: 100%;
                min-width: 0;
                margin-right: 0 !important;
            }

            .import-product-page .import-product-refresh {
                display: inline-flex;
                grid-row: 1;
                grid-column: 2;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                padding: 0;
                border: 1px solid #d7dde7;
                border-radius: 4px;
                background: #fff;
                color: #495057;
            }

            .import-product-page .import-product-action {
                grid-row: 2;
                grid-column: 1;
                min-width: 0;
            }

            .import-product-page .import-product-action > .btn {
                display: inline-flex;
                align-items: center;
                height: 40px;
                padding: 0 12px;
                white-space: nowrap;
            }

            .import-product-page .import-product-add-button {
                display: inline-flex;
                grid-row: 2;
                grid-column: 2;
                align-items: center;
                justify-content: center;
                height: 40px;
                padding: 0 12px;
                gap: 6px;
                white-space: nowrap;
            }

            .import-product-page .import-product-add-button i {
                margin-right: 0;
            }

            .import-product-page .card-body {
                padding: 10px;
            }

            .import-product-page .import-product-table-hint {
                display: block;
                margin: 0 0 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.4;
            }

            .import-product-page .import-product-table-wrapper {
                width: 100%;
                max-width: 100%;
                overflow-x: auto !important;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .import-product-page .import-product-table {
                width: 100%;
                min-width: 1200px;
            }

            .import-product-page .import-product-table th,
            .import-product-page .import-product-table td {
                vertical-align: middle;
                font-size: 13px;
            }

            .import-product-page .import-product-table th {
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(1),
            .import-product-page .import-product-table td:nth-child(1) {
                width: 44px;
                min-width: 44px;
                text-align: center;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(2),
            .import-product-page .import-product-table td:nth-child(2) {
                width: 60px;
                min-width: 60px;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(3),
            .import-product-page .import-product-table td:nth-child(3) {
                min-width: 150px;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(4),
            .import-product-page .import-product-table td:nth-child(4) {
                min-width: 140px;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(5),
            .import-product-page .import-product-table td:nth-child(5) {
                min-width: 140px;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(6),
            .import-product-page .import-product-table td:nth-child(6) {
                min-width: 190px;
            }

            .import-product-page .import-product-table th:nth-child(7),
            .import-product-page .import-product-table td:nth-child(7),
            .import-product-page .import-product-table th:nth-child(8),
            .import-product-page .import-product-table td:nth-child(8) {
                min-width: 130px;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(9),
            .import-product-page .import-product-table td:nth-child(9) {
                min-width: 180px;
                white-space: nowrap;
            }

            .import-product-page .import-product-table th:nth-child(10),
            .import-product-page .import-product-table td:nth-child(10) {
                width: 86px;
                min-width: 86px;
                text-align: center;
                white-space: nowrap;
            }

            .import-product-page .import-product-table .badge {
                white-space: nowrap;
                font-size: 12px;
            }

            .import-product-page .import-product-detail-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                padding: 0;
            }

            .import-product-page .import-product-pagination-desktop {
                display: none !important;
            }

            .import-product-page .import-product-pagination-mobile {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin-top: 12px;
            }

            .import-product-page .import-product-pagination-mobile .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                padding: 0;
                font-size: 18px;
                line-height: 1;
            }

            .import-product-page .import-product-pagination-status {
                color: #495057;
                font-size: 13px;
                white-space: nowrap;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-inner import-product-page">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'NHẬP HÀNG'], ['label' => 'DANH SÁCH PHIẾU NHẬP']]" />
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    {{-- Header --}}
                    <div class="card-header d-flex justify-content-between align-items-center import-product-toolbar">
                        <div class="d-flex justify-content-between align-items-center gap-2 import-product-toolbar-left">
                            <div class="btn-group import-product-action">
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

                            {{-- Form tìm kiếm --}}
                            <form method="GET" action="{{ route('admin.importproduct.index') }}"
                                class="d-flex align-items-center gap-2 import-product-search">
                                <!-- Ô tìm kiếm -->
                                <input type="search" name="search" value="{{ request('search') }}"
                                    class="form-control import-product-search-input" style="width: 300px;" placeholder="Tìm kiếm...">
                                <select name="company_id" class="form-select import-product-company-filter"
                                    aria-label="Nhà cung cấp">
                                    <option value="">Tất cả nhà cung cấp</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}"
                                            @selected($companyId === (int) $company->id)>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <!-- Nút reset -->
                                <button type="button" class="btn import-product-refresh" id="btn-reset">
                                    <i class="fa-solid fa-rotate"></i>
                                </button>
                            </form>
                        </div>
                        {{-- Nút nhập hàng --}}
                        <a class="btn btn-success import-product-add-button" href="{{ route('admin.importproduct.add') }}">
                            <i class="fa-solid fa-plus"></i> Nhập hàng
                        </a>
                    </div>
                    {{-- Body --}}
                    <div class="card-body">
                        <p class="import-product-table-hint">Vuốt ngang để xem đầy đủ bảng</p>
                        <div class="table-responsive import-product-table-wrapper">
                            <table id="basic-datatables" class="display table table-striped table-hover import-product-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>STT</th>
                                        <th>Mã đơn hàng</th>
                                        <th>Nhân viên</th>
                                        <th>Ngày tạo</th>
                                        <th>Nhà cung cấp</th>
                                        <th>Tổng tiền</th>
                                        <th>Đã trả</th>
                                        <th>Trạng thái thanh toán</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($import as $key => $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="row-checkbox" name="ids[]"
                                                    value="{{ $item->id }}">
                                            </td>
                                            <td>{{ $import->firstItem() + $key }}</td>
                                            <td>
                                                <a style="font-weight: 900; color: black"
                                                    href="{{ route('admin.importproduct.importCoupon.detail', ['id' => $item->id]) }}">
                                                    {{ $item->coupon_code }}
                                                </a>
                                            </td>
                                            <td>{{ $item->getRelation('user')?->name ?? '—' }}</td>
                                            <td>{{ $item->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td>{{ $item->getRelation('companyRelation')?->name ?? '—' }}</td>
                                            <td>{{ number_format($item->total, 0, ',', '.') }} đ</td>
                                            <td>{{ number_format($item->resolved_paid_amount, 0, ',', '.') }} đ</td>
                                            <td>
                                                <span class="badge {{ $item->payment_status_badge_class }}">
                                                    {{ $item->payment_status_label }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a class="btn btn-sm btn-outline-primary import-product-detail-button"
                                                    href="{{ route('admin.importproduct.importCoupon.detail', ['id' => $item->id]) }}"
                                                    title="Xem chi tiết"
                                                    aria-label="Xem chi tiết phiếu nhập {{ $item->coupon_code ?: '#' . $item->id }}">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">Không có dữ liệu</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @php
                            $importPaginator = $import->withQueryString();
                        @endphp
                        <div class="d-flex justify-content-end import-product-pagination-desktop">
                            {{ $importPaginator->links('pagination::bootstrap-4') }}
                        </div>
                        @if ($importPaginator->hasPages())
                            <div class="import-product-pagination-mobile" aria-label="Phân trang phiếu nhập">
                                <a class="btn btn-outline-secondary {{ $importPaginator->onFirstPage() ? 'disabled' : '' }}"
                                    href="{{ $importPaginator->previousPageUrl() ?: '#' }}"
                                    aria-disabled="{{ $importPaginator->onFirstPage() ? 'true' : 'false' }}"
                                    aria-label="Trang trước">‹</a>
                                <span class="import-product-pagination-status">
                                    Trang {{ $importPaginator->currentPage() }} / {{ $importPaginator->lastPage() }}
                                </span>
                                <a class="btn btn-outline-secondary {{ $importPaginator->hasMorePages() ? '' : 'disabled' }}"
                                    href="{{ $importPaginator->nextPageUrl() ?: '#' }}"
                                    aria-disabled="{{ $importPaginator->hasMorePages() ? 'false' : 'true' }}"
                                    aria-label="Trang sau">›</a>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(function() {
            const $selectAll = $('#select-all');
            const bulkDeleteUrl = @json(route('admin.importproduct.bulk-delete'));
            const indexUrl = @json(route('admin.importproduct.index'));

            const getRowCheckboxes = () => $('.row-checkbox');

            const updateSelectAllState = () => {
                const $rows = getRowCheckboxes();
                const total = $rows.length;
                const checked = $rows.filter(':checked').length;

                $selectAll.prop('checked', total > 0 && checked === total);
                $selectAll.prop('indeterminate', false);
            };

            $selectAll.on('change', function() {
                getRowCheckboxes().prop('checked', $(this).prop('checked'));
                updateSelectAllState();
            });

            $(document).on('change', '.row-checkbox', updateSelectAllState);

            $('#bulk-delete').on('click', function(e) {
                e.preventDefault();

                const ids = getRowCheckboxes()
                    .filter(':checked')
                    .map((i, el) => $(el).val())
                    .get();

                if (ids.length <= 0) {
                    return datgin.warning('Vui lòng chọn ít nhất một phiếu nhập cần xóa.');
                }

                Swal.fire({
                    title: 'Xác nhận xóa?',
                    text: 'Bạn có chắc chắn muốn xóa các phiếu nhập đã chọn không?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Vâng, xóa ngay!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: bulkDeleteUrl,
                        method: 'POST',
                        data: {
                            ids
                        },
                        success: (res) => {
                            datgin.success(res.message);
                            getRowCheckboxes().prop('checked', false);
                            updateSelectAllState();
                            window.location.reload();
                        },
                        error: (xhr) => {
                            datgin.error(xhr.responseJSON?.message ||
                                'Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                        }
                    });
                });
            });

            $('#btn-reset').on('click', function() {
                window.location.href = indexUrl;
            });

            $('.import-product-company-filter').on('change', function() {
                this.form.submit();
            });

            updateSelectAllState();
        });
    </script>
@endpush
