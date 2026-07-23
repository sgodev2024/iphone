<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 3%" class="text-center"><input type="checkbox" id="check-all"></th>
            <th style="width: 12%"># | ngày tạo</th>
            <th style="width: 20%">Tên sản phẩm</th>
            <th style="width: 10%">Giá nhập</th>
            {{-- <th style="width: 10%">Mã SP</th>
            <th style="width: 12%">Danh mục</th> --}}
            <th style="width: 10%">Giá bán</th>
            <th style="width: 8%">Tồn kho</th>
            <th style="width: 12%">Trạng thái</th>
            <th style="width: 12%" class="text-center product-actions-column">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $product)
            @php
                $isImeiTracked = $product->inventory_tracking === \App\Models\Product::INVENTORY_TRACKING_IMEI;
                $stockQuantity = $isImeiTracked
                    ? (int) $product->imei_stock_count
                    : (int) ($product->storage_stock_quantity ?? $product->quantity);
            @endphp
            <tr>
                <td><input type="checkbox" class="checked-item" value="{{ $product->id }}"></td>
                <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                    | {{ $product->created_at->format('d/m/Y') }}
                </td>
                <td>
                    {{ $product->name }}
                    <span class="badge {{ $isImeiTracked ? 'bg-info' : 'bg-secondary' }} ms-1">
                        {{ $isImeiTracked ? 'IMEI' : 'Theo số lượng' }}
                    </span>
                </td>
                <td>{{ number_format($product->price, 0, ',', '.') }}</td>
                {{-- <td>{{ $product->code }}</td> mã sp
                <td>{{ $product->category?->name }}</td> --}}
                <td>{{ number_format($product->price_buy, 0, ',', '.') }}</td>
                <td>{{ $stockQuantity }}</td>
                <td>
                    {!! $product->status
                        ? '<span class="badge bg-success">Kích hoạt</span>'
                        : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                </td>
                <td class="text-center product-actions-column">
                    <div class="product-actions">
                        @if ($isImeiTracked)
                            <a href="{{ route('admin.products.imeis.index', $product) }}"
                                class="btn btn-info btn-sm product-action-btn" title="Quản lý IMEI"
                                aria-label="Quản lý IMEI">
                                <i class="fa-solid fa-barcode"></i>
                            </a>
                        @endif
                        <a href="/admin/products/{{ $product->id }}/edit"
                            class="btn btn-primary btn-sm product-action-btn" title="Chỉnh sửa sản phẩm"
                            aria-label="Chỉnh sửa sản phẩm">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-danger btn-sm btn-delete product-action-btn" data-id="{{ $product->id }}"
                            title="Xóa sản phẩm" aria-label="Xóa sản phẩm">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="10">Không có sản phẩm nào</td>
            </tr>
        @endforelse
    </tbody>
</table>


<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $products->links('vendor.pagination.custom') }}
    </div>
</div>
