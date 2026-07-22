@extends('admin.layout.index')

@section('title', $title)

@push('style')
    <style>
        .product-imei-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap !important;
        }

        .product-imei-search-form {
            display: flex;
            flex: 0 1 auto;
            min-width: 0;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .product-imei-search-form .form-control {
            width: 460px;
            max-width: 46vw;
            flex: 0 1 460px;
            min-width: 0;
        }

        .product-imei-toolbar-actions {
            display: flex;
            flex: 0 0 auto;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .product-imei-toolbar .btn {
            flex-shrink: 0;
            padding-right: 12px;
            padding-left: 12px;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .product-imei-toolbar {
                align-items: stretch !important;
                flex-wrap: wrap !important;
            }

            .product-imei-search-form {
                width: 100%;
                align-items: stretch;
                flex-direction: column;
                flex-wrap: wrap;
            }

            .product-imei-search-form .form-control {
                flex-basis: 100%;
                width: 100%;
                max-width: none;
            }

            .product-imei-search-form .btn {
                width: 100%;
                flex: 0 0 auto;
            }

            .product-imei-toolbar-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Sản phẩm', 'url' => route('admin.products.index')], ['label' => 'Quản lý IMEI']]" />

        <div class="mb-3">
            <div>
                <h3 class="fw-bold mb-1">{{ $title }}</h3>
                <div class="text-muted">
                    Mã sản phẩm: <strong>{{ $product->code ?: '—' }}</strong>
                    <span class="mx-2">|</span>
                    IMEI đang tồn kho: <strong>{{ $product->imei_stock_count }}</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header product-imei-toolbar">
                {{-- <h4 class="card-title mb-0">Danh sách IMEI</h4> --}}
                <form method="GET" action="{{ route('admin.products.imeis.index', $product) }}"
                    class="product-imei-search-form">
                    <input type="text" name="search" class="form-control" value="{{ $search }}"
                        placeholder="Tìm kiếm IMEI" aria-label="Tìm kiếm IMEI">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm kiếm
                    </button>
                    <a href="{{ route('admin.products.imeis.index', $product) }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-rotate me-1"></i> Làm mới
                    </a>
                </form>
                <div class="product-imei-toolbar-actions">
                    <a href="{{ route('admin.importproduct.add', ['product_id' => $product->id]) }}"
                        class="btn btn-primary">
                        <i class="fa-solid fa-truck-ramp-box me-1"></i> Nhập thêm hàng
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>IMEI</th>
                                <th>Mã phiếu nhập</th>
                                <th>Nhà cung cấp</th>
                                <th>Giá nhập</th>
                                <th>Ngày nhập</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($imeis as $productImei)
                                @php
                                    $importDetail = $productImei->importDetail;
                                    $importCoupon = $importDetail?->import;
                                @endphp
                                <tr>
                                    <td>{{ ($imeis->currentPage() - 1) * $imeis->perPage() + $loop->iteration }}</td>
                                    <td class="font-monospace">{{ $productImei->imei }}</td>
                                    <td>
                                        @if ($importCoupon)
                                            <a
                                                href="{{ route('admin.importproduct.importCoupon.detail', $importCoupon->id) }}">
                                                {{ $importCoupon->coupon_code ?: '#' . $importCoupon->id }}
                                            </a>
                                        @else
                                            <span class="text-muted">Dữ liệu cũ</span>
                                        @endif
                                    </td>
                                    <td>{{ $importCoupon?->companyRelation?->name ?: '—' }}</td>
                                    <td>{{ $importDetail ? number_format($importDetail->price, 0, ',', '.') . ' đ' : '—' }}
                                    </td>
                                    <td>{{ ($importCoupon?->created_at ?? $productImei->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td>
                                        @if ($productImei->status === \App\Models\ProductImei::STATUS_IN_STOCK)
                                            <span class="badge bg-success">{{ $productImei->status_label }}</span>
                                        @elseif ($productImei->status === \App\Models\ProductImei::STATUS_SOLD)
                                            <span class="badge bg-secondary">{{ $productImei->status_label }}</span>
                                        @else
                                            <span
                                                class="badge bg-warning text-dark">{{ $productImei->status_label }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($importCoupon)
                                            <a href="{{ route('admin.importproduct.importCoupon.detail', $importCoupon->id) }}"
                                                class="btn btn-info btn-sm" title="Xem phiếu nhập">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        {{ $search !== '' ? 'Không tìm thấy IMEI phù hợp.' : 'Sản phẩm này chưa có IMEI nào.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $imeis->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
@endsection
