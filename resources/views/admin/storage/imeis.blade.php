<div class="mb-3">
    <div>
        <strong>Sản phẩm:</strong>
        {{ $product->name }}
    </div>

    <div>
        <strong>Kho:</strong>
        {{ $storage->name }}
    </div>

    <div>
        <strong>Tổng IMEI:</strong>
        {{ number_format($imeis->count()) }}
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th style="width: 7%">STT</th>
                <th>IMEI</th>
                <th>Barcode</th>
                <th style="width: 18%">Trạng thái</th>
                <th style="width: 18%">Ngày nhập</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($imeis as $imei)
                <tr>
                    <td>{{ $loop->iteration }}</td>

                    <td>
                        <strong>{{ $imei->imei }}</strong>
                    </td>

                    <td>{{ $imei->barcode ?? '-' }}</td>

                    <td>
                        @if ($imei->status === \App\Models\ProductImei::STATUS_IN_STOCK)
                            <span class="badge bg-success">
                                {{ $imei->status_label }}
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                {{ $imei->status_label }}
                            </span>
                        @endif
                    </td>

                    <td>
                        {{ $imei->created_at?->format('d/m/Y H:i') ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        Không có IMEI nào tại kho này.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>