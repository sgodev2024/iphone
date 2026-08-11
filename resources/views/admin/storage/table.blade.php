@php
    $storagePage = is_object($storages ?? null) && method_exists($storages, 'currentPage')
        ? $storages->currentPage()
        : 1;
    $storagePerPage = is_object($storages ?? null) && method_exists($storages, 'perPage')
        ? $storages->perPage()
        : 0;
@endphp

<div class="storage-table-scroll">
    <table class="table table-hover table-striped table-bordered mt-3 storage-table" role="grid">
        <thead>
            <tr>
                <th style="width: 3%" class="text-center storage-col-check">
                    <input type="checkbox" id="check-all">
                </th>
                <th style="width: 7%" class="storage-col-id">STT</th>
                <th style="width: 8%" class="storage-col-id">ID</th>
                <th style="width: 15%" class="storage-col-date">Ngày tạo</th>
                <th class="storage-col-name">TÊN KHO</th>
                <th class="storage-col-location">Địa chỉ</th>
                <th class="storage-col-quantity text-center">Tồn kho</th>
                <th class="text-center storage-col-action" style="width: 13%">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($storages ?? []) as $storage)
                @php
                    $storageId = data_get($storage, 'id');
                    $storageQuantity = (int) data_get($storage, 'total_quantity', 0);
                @endphp
                <tr>
                    <td class="storage-col-check">
                        <input type="checkbox" class="checked-item" value="{{ $storageId }}">
                    </td>
                    <td class="storage-col-id">
                        {{ $storageId ? (($storagePage - 1) * $storagePerPage) + $loop->iteration : '-' }}
                    </td>
                    <td class="storage-col-id">{{ $storageId ?? '-' }}</td>
                    <td class="storage-col-date">
                        {{ optional(data_get($storage, 'created_at'))->format('d/m/Y') ?? '-' }}
                    </td>
                    <td class="storage-col-name">{{ data_get($storage, 'name', '-') }}</td>
                    <td class="storage-col-location">{{ data_get($storage, 'location') ?? '-' }}</td>
                    <td class="storage-col-quantity text-center">
                        @can('storage.products')
                            @if ($storageId)
                                <a href="#"
                                    class="btn-storage-inventory"
                                    data-storage-id="{{ $storageId }}"
                                    data-storage-name="{{ data_get($storage, 'name', '') }}">
                                    {{ number_format($storageQuantity) }}
                                </a>
                            @else
                                {{ number_format($storageQuantity) }}
                            @endif
                        @else
                            {{ number_format($storageQuantity) }}
                        @endcan
                    </td>
                    <td class="storage-col-action">
                        <div class="d-flex gap-2 justify-content-center storage-actions">
                            @can('storage.update')
                                @if ($storageId)
                                    <button type="button"
                                        class="btn btn-primary btn-sm btn-show"
                                        data-id="{{ $storageId }}"
                                        title="Sửa">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @endif
                            @endcan

                            @can('bulk.action')
                                @if ($storageId)
                                    <button type="button"
                                        class="btn btn-danger btn-sm btn-delete"
                                        data-id="{{ $storageId }}"
                                        title="Xóa">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="8">Không có kho hàng</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (is_object($storages ?? null) && method_exists($storages, 'links'))
    <div class="row">
        <div class="col-sm-12" id="pagination">
            {{ $storages->links('vendor.pagination.custom') }}
        </div>
    </div>
@endif
