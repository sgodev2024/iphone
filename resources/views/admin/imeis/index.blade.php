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

        .imei-summary-line {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.4;
        }

        .imei-summary-label {
            color: #4b5563;
            font-weight: 500;
        }

        .imei-summary-value {
            color: #1f2937;
            font-weight: 700;
        }

        .imei-summary-separator {
            color: #9ca3af;
        }

        .imei-list-card {
            margin-bottom: 0;
        }

        .imei-card-toolbar {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #ebecec;
        }

        .imei-list-card .card-title {
            font-size: 18px;
            line-height: 1.25;
        }

        .imei-card-toolbar .alert {
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

            .imei-summary-line {
                gap: 5px;
                margin-bottom: 8px;
            }

            .imei-filter-actions {
                display: grid;
                width: 100%;
                grid-template-columns: 1fr;
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
    <div class="page-inner imei-page">
        <x-breadcrumb :items="[['label' => 'Sản phẩm', 'url' => route('admin.products.index')], ['label' => 'Quản lý IMEI']]" />

        {{-- <div class="imei-page-header">
            <div>
                <h3 class="fw-bold mb-1 imei-page-title">{{ $title }}</h3>
                <div class="text-muted imei-page-subtitle">Tra cứu và theo dõi toàn bộ thiết bị theo IMEI.</div>
            </div>
        </div> --}}
        <div class="imei-summary-line" aria-label="Thống kê IMEI">
            <span>
                <span class="imei-summary-label">Tổng thiết bị:</span>
                <span class="imei-summary-value">{{ number_format($statistics['total']) }}</span>
            </span>
            <span class="imei-summary-separator">/</span>
            <span>
                <span class="imei-summary-label">Đang tồn kho:</span>
                <span class="imei-summary-value">{{ number_format($statistics['in_stock']) }}</span>
            </span>
        </div>

        <div class="card imei-list-card">
            <div class="card-toolbar imei-card-toolbar">
                @if ($filterWarning)
                    <div class="alert alert-warning" role="alert">{{ $filterWarning }}</div>
                @endif

                <form method="GET" action="{{ route('admin.imeis.index') }}" class="imei-filter-form">
                    @foreach (request()->except(['page', 'imei', 'product']) as $name => $value)
                        @if (is_array($value))
                            @foreach ($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-6 col-lg-3">
                            <input id="imei" type="text" name="imei" class="form-control"
                                value="{{ $filters['imei'] }}" placeholder="Nhập IMEI">
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
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
                                    <i class="fa-solid fa-rotate me-1"></i>
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

            {{-- <div class="card-header">
                <h5 class="card-title mb-0">Danh sách IMEI</h5>
            </div> --}}
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
                                                aria-label="Xem IMEI theo sản phẩm">
                                                <i class="fa-solid fa-barcode"></i>
                                            </a>
                                        @endif

                                        @if ($importCoupon)
                                            <a href="{{ route('admin.importproduct.importCoupon.detail', $importCoupon->id) }}"
                                                class="btn btn-info btn-sm" title="Xem phiếu nhập"
                                                aria-label="Xem phiếu nhập">
                                                <i class="fa-solid fa-receipt"></i>
                                            </a>
                                        @endif

                                        @if ($isInStock)
                                            <button type="button" class="btn btn-danger btn-sm" title="Xóa IMEI"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteImeiModal{{ $productImei->id }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endif

                                        @if (!$product && !$importCoupon && !$isInStock)
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
                @foreach ($imeis as $productImei)
                    @if ($productImei->status === \App\Models\ProductImei::STATUS_IN_STOCK)
                        <div class="modal fade" id="deleteImeiModal{{ $productImei->id }}" tabindex="-1"
                            aria-labelledby="deleteImeiModalLabel{{ $productImei->id }}" aria-hidden="true">

                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.imeis.destroy', $productImei) }}">

                                        @csrf
                                        @method('DELETE')

                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteImeiModalLabel{{ $productImei->id }}">
                                                Xác nhận xóa IMEI
                                            </h5>

                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Đóng">
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="alert alert-warning">
                                                Bạn đang loại IMEI
                                                <strong>{{ $productImei->imei }}</strong>
                                                khỏi tồn kho.
                                            </div>

                                            <div class="mb-3">
                                                <label for="delete_reason_{{ $productImei->id }}" class="form-label">
                                                    Lý do xóa <span class="text-danger">*</span>
                                                </label>

                                                <textarea id="delete_reason_{{ $productImei->id }}" name="delete_reason" class="form-control" rows="3"
                                                    maxlength="500" required placeholder="Nhập lý do xóa IMEI"></textarea>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Hủy
                                            </button>

                                            <button type="submit" class="btn btn-danger">
                                                <i class="fa-solid fa-trash me-1"></i>
                                                Xác nhận xóa
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                {{ $imeis->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
@endsection
