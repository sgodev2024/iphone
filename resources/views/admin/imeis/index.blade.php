@extends('admin.layout.index')

@section('title', $title)

@push('style')
    <style>
        .imei-page {
            padding-top: 12px;
        }

        .imei-page-header {
            margin-bottom: 12px;
        }

        .imei-page-title {
            font-size: 22px;
            line-height: 1.2;
        }

        .imei-page-subtitle {
            font-size: 13px;
            line-height: 1.35;
        }

        .imei-stat-row {
            margin-bottom: 14px;
        }

        .imei-stat-card {
            height: 100%;
            margin-bottom: 0;
            border-radius: 8px;
        }

        .imei-stat-card .card-body {
            padding: 8px 12px !important;
        }

        .imei-stat-content {
            min-height: 40px;
            justify-content: center;
            gap: 8px;
        }

        .imei-stat-card .icon-big.imei-stat-icon {
            flex: 0 0 40px;
            width: 40px;
            height: 40px;
            min-height: 40px;
            border-radius: 8px;
            font-size: 17px;
        }

        .imei-stat-card .imei-stat-icon i {
            line-height: 1;
        }

        .imei-stat-copy {
            min-width: 0;
        }

        .imei-stat-card .imei-stat-label {
            margin-bottom: 2px;
            color: #6c757d;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.25;
            white-space: nowrap;
        }

        .imei-stat-card .imei-stat-value {
            margin-bottom: 0;
            color: #1f2937;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.15;
        }

        .imei-filter-card,
        .imei-list-card {
            margin-bottom: 14px;
            border-radius: 8px;
        }

        .imei-filter-card .card-header,
        .imei-list-card .card-header {
            padding: 11px 16px;
        }

        .imei-filter-card .card-title,
        .imei-list-card .card-title {
            font-size: 16px;
            line-height: 1.25;
        }

        .imei-filter-card .card-body,
        .imei-list-card .card-body {
            padding: 14px 16px;
        }

        .imei-filter-card .alert {
            margin-bottom: 12px;
            padding: 8px 12px;
        }

        .imei-filter-form .form-label {
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .imei-filter-actions {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .imei-filter-actions .btn {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .imei-page {
                padding-top: 8px;
            }

            .imei-page-title {
                font-size: 20px;
            }

            .imei-stat-card .card-body {
                padding: 8px 10px !important;
            }

            .imei-stat-content {
                gap: 8px;
            }

            .imei-stat-card .icon-big.imei-stat-icon {
                flex-basis: 38px;
                width: 38px;
                height: 38px;
                min-height: 38px;
                font-size: 16px;
            }

            .imei-stat-card .imei-stat-label {
                font-size: 12px;
            }

            .imei-stat-card .imei-stat-value {
                font-size: 18px;
            }

            .imei-filter-actions {
                display: grid;
                width: 100%;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .imei-filter-actions .btn {
                width: 100%;
                padding-right: 8px;
                padding-left: 8px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $filterQuery = request()->except(['page', 'status']);
    @endphp

    <div class="page-inner imei-page">
        <x-breadcrumb :items="[['label' => 'Sản phẩm', 'url' => route('admin.products.index')], ['label' => 'Quản lý IMEI']]" />

        <div class="imei-page-header">
            <div>
                <h3 class="fw-bold mb-1 imei-page-title">{{ $title }}</h3>
                <div class="text-muted imei-page-subtitle">Tra cứu và theo dõi toàn bộ thiết bị theo IMEI.</div>
            </div>
        </div>

        <div class="row g-2 imei-stat-row">
            <div class="col-12 col-md-6">
                <div class="card card-stats card-round imei-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center imei-stat-content">
                            <div class="icon-big text-center icon-primary bubble-shadow-small imei-stat-icon">
                                <i class="fa-solid fa-barcode"></i>
                            </div>
                            <div class="imei-stat-copy">
                                <div class="numbers">
                                    <p class="card-category imei-stat-label">Tổng thiết bị</p>
                                    <h4 class="card-title imei-stat-value">{{ number_format($statistics['total']) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <a class="text-decoration-none"
                    href="{{ route('admin.imeis.index', array_merge($filterQuery, ['status' => \App\Models\ProductImei::STATUS_IN_STOCK])) }}">
                    <div class="card card-stats card-round imei-stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center imei-stat-content">
                                <div class="icon-big text-center icon-success bubble-shadow-small imei-stat-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>
                                <div class="imei-stat-copy">
                                    <div class="numbers">
                                        <p class="card-category imei-stat-label">Đang tồn kho</p>
                                        <h4 class="card-title imei-stat-value">{{ number_format($statistics['in_stock']) }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="card imei-filter-card">
            <div class="card-header">
                <h4 class="card-title mb-0">Tra cứu IMEI</h4>
            </div>
            <div class="card-body">
                @if ($filterWarning)
                    <div class="alert alert-warning" role="alert">{{ $filterWarning }}</div>
                @endif

                <form method="GET" action="{{ route('admin.imeis.index') }}" class="imei-filter-form">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="imei" class="form-label">IMEI</label>
                            <input id="imei" type="text" name="imei" class="form-control"
                                value="{{ $filters['imei'] }}" placeholder="Nhập IMEI">
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <label for="product" class="form-label">Mã hoặc tên sản phẩm</label>
                            <input id="product" type="text" name="product" class="form-control"
                                value="{{ $filters['product'] }}" placeholder="Mã hoặc tên sản phẩm">
                        </div>
                        {{-- <div class="col-md-2">
                            <label for="status" class="form-label">Trạng thái</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Tất cả</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div> --}}
                        <div class="col-12 col-lg-auto">
                            <div class="imei-filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm kiếm
                                </button>
                                <a href="{{ route('admin.imeis.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-rotate me-1"></i> Làm mới
                                </a>
                            </div>
                        </div>

                        {{-- <div class="col-md-3">
                            <label for="company_id" class="form-label">Nhà cung cấp</label>
                            <select id="company_id" name="company_id" class="form-select">
                                <option value="">Tất cả</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected($filters['company_id'] === (string) $company->id)>{{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div> --}}
                        {{-- <div class="col-md-3">
                            <label for="coupon_code" class="form-label">Mã phiếu nhập</label>
                            <input id="coupon_code" type="text" name="coupon_code" class="form-control"
                                value="{{ $filters['coupon_code'] }}" placeholder="Nhập mã phiếu">
                        </div> --}}
                        {{-- <div class="col-md-2">
                            <label for="from_date" class="form-label">Từ ngày</label>
                            <input id="from_date" type="date" name="from_date" class="form-control"
                                value="{{ $filters['from_date'] }}">
                        </div>
                        <div class="col-md-2">
                            <label for="to_date" class="form-label">Đến ngày</label>
                            <input id="to_date" type="date" name="to_date" class="form-control"
                                value="{{ $filters['to_date'] }}">
                        </div> --}}
                    </div>
                </form>
            </div>
        </div>

        <div class="card imei-list-card">
            <div class="card-header">
                <h4 class="card-title mb-0">Danh sách IMEI</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>IMEI</th>
                                <th>Mã sản phẩm</th>
                                <th>Sản phẩm</th>
                                <th>Phiếu nhập</th>
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
                                    $product = $productImei->product;
                                    $importDetail = $productImei->importDetail;
                                    $importCoupon = $importDetail?->import;
                                    $isInStock = $productImei->status === \App\Models\ProductImei::STATUS_IN_STOCK;
                                    $isSold = $productImei->status === \App\Models\ProductImei::STATUS_SOLD;
                                @endphp
                                <tr>
                                    <td>{{ $imeis->firstItem() + $loop->index }}</td>
                                    <td class="font-monospace text-nowrap">{{ $productImei->imei }}</td>
                                    <td>{{ $product?->code ?: '—' }}</td>
                                    <td>{{ $product?->name ?: 'Sản phẩm không tồn tại' }}</td>
                                    <td>
                                        @if ($importCoupon)
                                            <a
                                                href="{{ route('admin.importproduct.importCoupon.detail', $importCoupon->id) }}">{{ $importCoupon->coupon_code ?: '#' . $importCoupon->id }}</a>
                                        @else
                                            <span class="text-muted">Chưa xác định</span>
                                        @endif
                                    </td>
                                    <td>{{ $importCoupon?->companyRelation?->name ?: '—' }}</td>
                                    <td>{{ $importDetail ? number_format($importDetail->price, 0, ',', '.') . ' đ' : '—' }}
                                    </td>
                                    <td>{{ $importCoupon?->created_at?->format('d/m/Y') ?: '—' }}</td>
                                    <td>
                                        @if ($isInStock)
                                            <span class="badge bg-success">{{ $productImei->status_label }}</span>
                                        @elseif ($isSold)
                                            <span class="badge bg-secondary">{{ $productImei->status_label }}</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Trạng thái khác</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        @if ($product)
                                            <a href="{{ route('admin.products.imeis.index', $product) }}"
                                                class="btn btn-outline-primary btn-sm" title="Xem IMEI theo sản phẩm"
                                                aria-label="Xem IMEI theo sản phẩm"><i
                                                    class="fa-solid fa-barcode"></i></a>
                                        @endif
                                        @if ($importCoupon)
                                            <a href="{{ route('admin.importproduct.importCoupon.detail', $importCoupon->id) }}"
                                                class="btn btn-info btn-sm" title="Xem phiếu nhập"
                                                aria-label="Xem phiếu nhập"><i class="fa-solid fa-receipt"></i></a>
                                        @endif
                                        @if (!$product && !$importCoupon)
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">Không tìm thấy IMEI phù hợp.</td>
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
