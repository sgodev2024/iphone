@php($showBranchColumn = $showBranchColumn ?? auth()->user()->isAdministrator())
<div class="client-table-hint d-none mb-2 small text-muted">Vuốt ngang để xem đầy đủ bảng</div>

<div class="table-responsive client-table-scroll">
    <table class="table table-hover table-striped table-bordered align-middle mb-3 client-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Họ tên</th>
                <th>Số điện thoại</th>
                <th>Email</th>
                <th>Địa chỉ</th>
                @if ($showBranchColumn)
                    <th>Cửa hàng</th>
                @endif
                <th>Ngày tạo</th>
                <th class="text-center">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>{{ $clients->firstItem() + $loop->index }}</td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->email ?: '---' }}</td>
                    <td>{{ $client->address ?: '---' }}</td>
                    @if ($showBranchColumn)
                        <td>
                            @if ($client->branch)
                                <span class="badge bg-info text-dark">{{ $client->branch->name }}</span>
                            @else
                                <span class="badge bg-secondary">Chưa xác định</span>
                            @endif
                        </td>
                    @endif
                    <td>{{ $client->created_at?->format('d/m/Y H:i') ?? '---' }}</td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1 client-row-actions">
                            @can('client.view')
                                <a href="{{ route('admin.client.show', $client) }}" class="btn btn-info btn-sm"
                                    title="Xem khách hàng" aria-label="Xem khách hàng">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            @endcan
                            @can('client.update')
                                <a href="{{ route('admin.client.edit', $client) }}" class="btn btn-warning btn-sm"
                                    title="Sửa khách hàng" aria-label="Sửa khách hàng">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            @endcan
                            @can('client.delete')
                                <button type="button" class="btn btn-danger btn-sm btn-delete-client"
                                    data-url="{{ route('admin.client.destroy', $client) }}"
                                    title="Ngừng hoạt động khách hàng" aria-label="Ngừng hoạt động khách hàng">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center py-4" colspan="{{ $showBranchColumn ? 8 : 7 }}">
                        Không có khách hàng nào
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($clients->hasPages())
    <div class="d-flex justify-content-center" id="pagination">
        {{ $clients->onEachSide(1)->links('vendor.pagination.custom') }}
    </div>
@endif
