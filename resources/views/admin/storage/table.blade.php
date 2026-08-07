<div class="storage-table-scroll">
    <table class="table table-hover table-striped table-bordered mt-3 storage-table" role="grid">
        <thead>
            <tr>
                <th style="width: 3%" class="text-center storage-col-check"><input type="checkbox" id="check-all"></th>
                <th style="width: 8%" class="storage-col-id">ID</th>
                <th style="width: 15%" class="storage-col-date">Ngày tạo</th>
                <th class="storage-col-name">TÊN KHO</th>
                <th class="storage-col-location">Địa chỉ</th>
                <th class="text-center storage-col-action" style="width: 13%">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($storages as $storage)
                <tr>
                    <td class="storage-col-check"><input type="checkbox" class="checked-item" value="{{ $storage->id }}"></td>
                    <td class="storage-col-id">{{ $storage->id }}</td>
                    <td class="storage-col-date">{{ optional($storage->created_at)->format('d/m/Y') ?? '-' }}</td>
                    <td class="storage-col-name">{{ $storage->name }}</td>
                    <td class="storage-col-location">{{ $storage->location ?? '-' }}</td>
                    <td class="storage-col-action">
                        <div class="d-flex gap-2 justify-content-center storage-actions">
                            <button class="btn btn-primary btn-sm btn-show" data-id="{{ $storage->id }}" title="Sửa">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $storage->id }}" title="Xóa">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="6">Không có kho hàng</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $storages->links('vendor.pagination.custom') }}
    </div>
</div>