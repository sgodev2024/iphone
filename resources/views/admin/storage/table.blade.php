<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 3%" class="text-center"><input type="checkbox" id="check-all"></th>
            <th style="width: 15%"># | ngày tạo</th>
            <th>Tên danh mục</th>
            <th>Địa chỉ</th>
            <th class="text-center" style="width: 13%">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($storages as $storage)
            <tr>
                <td><input type="checkbox" class="checked-item" value="{{ $storage->id }}"></td>
                <td>
                    {{ ($storages->currentPage() - 1) * $storages->perPage() + $loop->iteration }}
                    | {{ $storage->created_at->format('d/m/Y') }}
                </td>
                <td>{{ $storage->name }}</td>
                <td>{{ $storage->location }}</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-primary btn-sm btn-show" data-id="{{ $storage->id }}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $storage->id }}">
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
        {{ $storages->links('vendor.pagination.custom') }}
    </div>
</div>
