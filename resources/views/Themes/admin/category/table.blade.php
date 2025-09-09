<table id="basic-datatables" class="display table table-striped table-hover" role="grid">
    <thead>
        <tr>
            <th>Mã danh mục</th>
            <th>Tên danh mục</th>
            <th>Mô tả</th>
            <th style="text-align: center">Hành động</th>
        </tr>
    </thead>
    @if ($category)
        <tbody>
            @foreach ($category as $key => $value)
                <tr id="category-{{ $value->id }}">
                    <td>{{ $value->id }}</td>
                    <td>{{ $value->name ?? '' }}</td>
                    <td>{!! $value->description !!}</td>
                    <td style="text-align:center">
                        <a class="btn btn-warning"
                            href="{{ route('admin.category.detail', ['id' => $value->id]) }}"><i class="fa-solid fa-pen"></i></a>
                        <button class="btn btn-danger btn-delete" data-id="{{ $value->id }}"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    @else
        <tr>
            <td class="text-center" colspan="6">Không có danh mục</td>
        </tr>
    @endif
</table>

