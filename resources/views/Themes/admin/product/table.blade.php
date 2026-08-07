<!-- resources/views/admin/product/table.blade.php -->
<table id="basic-datatables" class="display table table-striped table-hover dataTable" role="grid"
    aria-describedby="basic-datatables_info">
    <thead>
        <tr role="row">
            <th>STT</th>
            <th>Tên sản phẩm</th>
            <th>Thương hiệu</th>
            <th>Nhà cung cấp</th>
            <th>Số lượng(đơn vị)</th>
            <th>Giá nhập</th>
            <th style="text-align: center">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($product as $key => $value)
            <tr id="product-{{ $value->id }}">
                <td>{{ ($product->currentPage() - 1) * $product->perPage() + $loop->index + 1 }}</td>
                <td>{{ $value->name ?? '' }}</td>
                <td>{{ $value->brands->name ?? '' }}</td>
                <td>
                    @if ($value->company && $value->company->isNotEmpty())
                        <button class="accordion-button">
                            Xem nhà cung cấp
                        </button>
                        <div class="accordion-content">
                            <ul>
                                @foreach ($value->company as $company)
                                    <li>
                                        {{ $company->name }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        Chưa có nhà cung cấp
                    @endif
                </td>
                <td>{{ $value->quantity ?? '' }} {{ $value->product_unit ?? '' }}</td>
                <td>{{ number_format($value->price) ?? '' }} đ</td>
                <td align="center">
                    <a class="btn btn-warning" href="{{ route('admin.product.edit', ['id' => $value->id]) }}"><i
                            class="fa-solid fa-pen"></i></a>
                    <button class="btn btn-danger btn-delete" data-id="{{ $value->id }}"><i
                            class="fa-solid fa-regular fa-trash"></i></button>
                </td>
            </tr>
        @endforeach
    </tbody>

</table>
{{-- <div id="pagination">
    {{ $product->links('vendor.pagination.custom') }}
</div> --}}
