<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            id="back-to-storages">
            <i class="fa-solid fa-arrow-left me-1"></i>
            Quay lại
        </button>

        <div>
            <span class="fw-bold">Sản phẩm tồn kho:</span>
            <span>{{ $storage->name }}</span>
        </div>
    </div>

    <div>
        Tổng tồn:
        <strong>
            {{ number_format((int) ($storage->total_quantity ?? 0)) }}
        </strong>
    </div>
</div>

<table class="table table-hover table-striped table-bordered mt-3">
    <thead>
        <tr>
            <th style="width: 7%">STT</th>
            <th style="width: 13%">Mã sản phẩm</th>
            <th>Tên sản phẩm</th>
            <th style="width: 15%">Danh mục</th>
            <th style="width: 12%">Loại sản phẩm</th>
            <th class="text-center" style="width: 12%">
                Số lượng tồn
            </th>
        </tr>
    </thead>

    <tbody>
    @forelse ($products as $product)
        @php
            $isImeiTracked = $product->isImeiTracked();

            $stockQuantity = $isImeiTracked
                ? (int) ($product->storage_imei_stock_count ?? 0)
                : (int) ($product->pivot?->quantity ?? 0);
        @endphp

        <tr>
            <td>  {{ (($products->currentPage() - 1) * $products->perPage()) + $loop->iteration }}</td>

            <td>{{ $product->code ?? '-' }}</td>

            <td>{{ $product->name }}</td>

            <td>
                {{ $product->category?->name ?? '-' }}
            </td>

            <td class="text-center align-middle">
                @if ($isImeiTracked)
                        <span class="badge bg-info">
                            IMEI
                        </span>
                    </button>
                @else
                    <span class="badge bg-secondary">
                        Sản phẩm thường
                    </span>
                @endif
            </td>

            <td class="text-center">
            @if ($isImeiTracked)
                    <button
                        type="button"
                        class="btn btn-link text-decoration-none btn-view-storage-imeis"
                        data-url="{{ route('admin.storage.products.imeis', [
                            'storage' => $storage->id,
                            'product' => $product->id,
                        ]) }}"
                        title="Xem danh sách IMEI"
                    >
                
                @endif
                {{ number_format($stockQuantity) }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center">
                Kho chưa có sản phẩm.
            </td>
        </tr>
    @endforelse
</tbody>
</table>

@if ($products->hasPages())
<div class="row">
    <div class="col-sm-12">
        {{ $products->links('vendor.pagination.custom') }}
    </div>
</div>
@endif