<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 3%" class="text-center"><input type="checkbox" id="check-all"></th>
            <th style="width: 12%"># | ngày tạo</th>
            <th style="width: 20%">Tên sản phẩm</th>
            <th style="width: 10%">Mã SP</th>
            <th style="width: 12%">Danh mục</th>
            <th style="width: 10%">Giá bán</th>
            <th style="width: 8%">Tồn kho</th>
            <th style="width: 12%">Trạng thái</th>
            <th style="width: 12%" class="text-center">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $product)
            <tr>
                <td><input type="checkbox" class="checked-item" value="{{ $product->id }}"></td>
                <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                    | {{ $product->created_at->format('d/m/Y') }}
                </td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->code }}</td>
                <td>{{ $product->category?->name }}</td>
                <td>{{ number_format($product->price_buy, 0, ',', '.') }}</td>
                <td>{{ $product->quantity }}</td>
                <td>
                    {!! $product->status
                        ? '<span class="badge bg-success">Kích hoạt</span>'
                        : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                </td>
                <td class="text-center">
                    <a href="/admin/products/{{ $product->id }}/edit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $product->id }}">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
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
