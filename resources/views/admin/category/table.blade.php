<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 5%"><input type="checkbox" id="check-all"></th>
            <th style="width: 15%"># | ngày tạo</th>
            <th>Tên danh mục</th>
            <th>Mô tả</th>
            <th style="width: 13%">Trạng thái</th>
            <th class="text-center" style="width: 13%">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($categories as $index => $category)
            <tr>
                <td><input type="checkbox" class="checked-item" value="{{ $category->id }}"></td>
                <td>
                    {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                    | {{ $category->created_at->format('d/m/Y') }}
                </td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
                <td>
                    {!! $category->status
                        ? '<span class="badge bg-success">Kích hoạt</span>'
                        : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                </td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-primary btn-sm btn-show" data-id="{{ $category->id }}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $category->id }}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="6">Không có danh mục</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $categories->links('vendor.pagination.custom') }}
    </div>
</div>
