@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[
                ['label' => 'Nhập hàng', 'url' => route('admin.importproduct.index')],
                [
                    'label' => 'Chi tiết phiếu nhập',
                    'url' => route('admin.importproduct.importCoupon.detail', [
                        'id' => $importCouponId,
                    ]),
                ],
                ['label' => 'In tem barcode'],
            ]" />
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h4 class="text-center mb-0">Danh sách tem barcode</h4>
                    </div>

                    <div class="card-body">
                        <form method="POST"
                            action="{{ route('admin.importproduct.barcodes.print', [
                                'id' => $importCouponId,
                            ]) }}"
                            target="_blank">
                            @csrf

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <strong>Phiếu nhập #{{ $importCouponId }}</strong>
                                    <div class="text-muted">Chọn tem barcode cần in.</div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-print"></i>
                                        In tem đã chọn
                                    </button>

                                    <button type="submit" name="print_all" value="1" class="btn btn-success">
                                        <i class="fas fa-barcode"></i>
                                        In tất cả tem
                                    </button>
                                </div>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th width="50" class="text-center">
                                                <input type="checkbox" id="select-all-barcodes">
                                            </th>
                                            <th>Loại tem</th>
                                            <th>Sản phẩm</th>
                                            <th>IMEI</th>
                                            <th>Barcode</th>
                                            <th width="190">Số lượng</th>
                                            <th>Số lần in</th>
                                            <th width="120">Thao tác</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($items as $item)
                                            <tr>
                                                <td class="text-center">
                                                    @if ($item['type'] === 'imei')
                                                        <input type="checkbox" class="barcode-checkbox" name="imei_ids[]"
                                                            value="{{ $item['id'] }}">
                                                    @else
                                                        <input type="checkbox" class="barcode-checkbox"
                                                            name="product_detail_ids[]" value="{{ $item['id'] }}">
                                                    @endif
                                                </td>

                                                <td>
                                                    <span
                                                        class="badge {{ $item['type'] === 'imei' ? 'bg-info' : 'bg-secondary' }}">
                                                        {{ $item['type_label'] }}
                                                    </span>
                                                </td>

                                                <td>{{ $item['product_name'] }}</td>

                                                <td>
                                                    @if ($item['imei'])
                                                        <span class="font-monospace">{{ $item['imei'] }}</span>
                                                    @else
                                                        <span class="text-muted">Sản phẩm thường</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <strong>{{ $item['barcode'] }}</strong>
                                                </td>

                                                <td>
                                                    @if ($item['type'] === 'imei')
                                                        <span class="text-muted">1 tem</span>
                                                    @else
                                                        <div class="small text-muted">
                                                            Nhập: {{ $item['import_quantity'] }}
                                                        </div>
                                                        <input type="number" class="form-control form-control-sm"
                                                            name="product_label_quantities[{{ $item['id'] }}]"
                                                            value="{{ $item['default_label_quantity'] }}" min="1"
                                                            max="{{ $item['max_label_quantity'] }}">
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($item['type'] === 'imei')
                                                        {{ $item['print_count'] }}

                                                        @if ($item['printed_at'])
                                                            <div class="small text-muted">
                                                                {{ \Carbon\Carbon::parse($item['printed_at'])->format('d/m/Y H:i') }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">In theo nhu cầu</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($item['type'] === 'imei')
                                                        <button type="submit" name="single_imei_id"
                                                            value="{{ $item['id'] }}"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-print"></i>
                                                            In tem
                                                        </button>
                                                    @else
                                                        <button type="submit" name="single_product_detail_id"
                                                            value="{{ $item['id'] }}"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-print"></i>
                                                            In tem
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    Phiếu nhập này không có sản phẩm đủ điều kiện in tem.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <a href="{{ route('admin.importproduct.importCoupon.detail', [
                                'id' => $importCouponId,
                            ]) }}"
                                class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Quay lại chi tiết phiếu nhập
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const selectAllBarcode = document.getElementById('select-all-barcodes');

        if (selectAllBarcode) {
            selectAllBarcode.addEventListener('change', function(event) {
                document
                    .querySelectorAll('.barcode-checkbox')
                    .forEach(function(checkbox) {
                        checkbox.checked = event.target.checked;
                    });
            });
        }
    </script>
@endsection
