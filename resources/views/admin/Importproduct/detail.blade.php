@extends('admin.layout.index')

@section('content')
    @php
        $supplier = $importdetail->relationLoaded('companyRelation')
            ? $importdetail->getRelation('companyRelation')
            : null;
        $employee = $importdetail->relationLoaded('user') ? $importdetail->getRelation('user') : null;
        $storage = $importdetail->relationLoaded('storage') ? $importdetail->getRelation('storage') : null;
    @endphp

    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[
                ['label' => 'Nhập hàng', 'url' => route('admin.importproduct.index')],
                ['label' => 'Chi tiết phiếu nhập'],
            ]" />
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h4 class="text-center mb-sm-0 font-size-18">
                            Phiếu nhập {{ $importdetail->coupon_code ?: '#' . $importdetail->id }}
                        </h4>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin nhà cung cấp</b></h5>
                                <table class="table table-bordered table-hover detail_import">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-building"></i> Nhà cung cấp</th>
                                            <td>
                                                <div class="nowrap">{{ $supplier?->name ?? '—' }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-phone"></i> Số điện thoại</th>
                                            <td>
                                                <div class="nowrap">{{ $supplier?->phone ?? '—' }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-envelope"></i> Email</th>
                                            <td>
                                                <div class="nowrap">{{ $supplier?->email ?? '—' }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-map-marker-alt"></i> Địa chỉ</th>
                                            <td>
                                                <div class="nowrap">{{ $supplier?->address ?? '—' }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin phiếu nhập</b></h5>
                                <table class="table table-bordered table-hover detail_import">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-receipt"></i> Mã phiếu nhập</th>
                                            <td>{{ $importdetail->coupon_code ?: '#' . $importdetail->id }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-calendar-alt"></i> Ngày nhập</th>
                                            <td>{{ $importdetail->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-user-tie"></i> Nhân viên</th>
                                            <td>{{ $employee?->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-warehouse"></i> Kho nhập</th>
                                            <td>{{ $storage?->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-wallet"></i> Phương thức thanh toán</th>
                                            <td>{{ $importdetail->payment_method_label }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-check-circle"></i> Trạng thái thanh toán
                                            </th>
                                            <td>
                                                <span class="badge {{ $importdetail->payment_status_badge_class }}">
                                                    {{ $importdetail->payment_status_label }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-coins"></i> Tổng tiền</th>
                                            <td>{{ number_format($importdetail->total, 0, ',', '.') }} đ</td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-money-bill-wave"></i> Đã trả</th>
                                            <td>{{ number_format($importdetail->resolved_paid_amount, 0, ',', '.') }} đ
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-file-invoice-dollar"></i> Còn nợ</th>
                                            <td>{{ number_format($importdetail->resolved_debt_amount, 0, ',', '.') }} đ
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5 class="text-primary"><b>Danh sách sản phẩm</b></h5>
                                <div class="table-responsive">
                                    <table id="basic-datatables"
                                        class="display table table-striped table-hover dataTable w-100" style="width: 100%"
                                        role="grid" aria-describedby="basic-datatables_info">
                                        <thead>
                                            <tr role="row">
                                                <th>Mã hàng hóa</th>
                                                <th>Tên hàng hóa</th>
                                                {{-- <th>Quản lý kho</th> --}}
                                                <th>Số lượng</th>
                                                <th>Giá cũ</th>
                                                <th>Giá nhập</th>
                                                <th>IMEI / Barcode</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($importdetail->details as $detail)
                                                @php
                                                    $product = $detail->product;
                                                    $hasImeiData = $detail->imeis->isNotEmpty();
                                                    $isImeiTracked = $product?->isImeiTracked() || $hasImeiData;
                                                @endphp
                                                <tr>
                                                    <td>{{ $product?->code ?? '—' }}</td>
                                                    <td>{{ $product?->name ?? 'Sản phẩm không còn tồn tại' }}</td>
                                                    {{-- <td>{{ $product?->inventory_tracking_label ?? 'Không xác định' }}</td> --}}
                                                    <td>{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($detail->old_price, 0, ',', '.') }} đ</td>
                                                    <td>{{ number_format($detail->price, 0, ',', '.') }} đ</td>
                                                    <td>
                                                        @if ($isImeiTracked)
                                                            @forelse ($detail->imeis as $imei)
                                                                <div class="mb-2">
                                                                    <div class="font-monospace">
                                                                        <strong>IMEI:</strong> {{ $imei->imei ?: '—' }}
                                                                    </div>
                                                                    <div class="font-monospace text-muted">
                                                                        <strong>Barcode:</strong>
                                                                        {{ $imei->barcode ?: '—' }}
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <span class="text-muted">Dữ liệu IMEI cũ không còn</span>
                                                            @endforelse
                                                        @else
                                                            <span class="text-muted">Sản phẩm quản lý theo số lượng</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ number_format($detail->price * $detail->quantity, 0, ',', '.') }}
                                                        đ
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        Phiếu nhập chưa có sản phẩm
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="6" class="text-end">Tổng cộng</th>
                                                <th>{{ number_format($importdetail->total, 0, ',', '.') }} đ</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{{ route('admin.importproduct.index') }}" class="btn btn-primary w-md">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                            <a href="{{ route('admin.importproduct.barcodes.index', ['id' => $importdetail->id]) }}"
                                class="btn btn-success">
                                <i class="fas fa-barcode"></i> In tem barcode
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
