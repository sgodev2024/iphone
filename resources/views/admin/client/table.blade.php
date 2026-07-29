<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered align-middle mt-3">
        <thead>
            <tr>
                <th style="width: 3%">
                    <input type="checkbox" id="check-all">
                </th>

                <th style="width: 12%"># | Ngày tạo</th>
                <th style="width: 18%">Tên khách hàng</th>
                <th style="width: 12%">Số điện thoại</th>
                <th style="width: 20%">Email</th>
                <th>Địa chỉ</th>
                <th style="width: 10%" class="text-center">
                    Hành động
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>
                        <input type="checkbox" class="checked-item" value="{{ $client->id }}">
                    </td>

                    <td>
                        {{ $clients->firstItem() + $loop->index }}
                        |
                        {{ $client->created_at?->format('d/m/Y') ?? '---' }}
                    </td>

                    <td>
                        {{ $client->name ?? 'Chưa có tên' }}
                    </td>

                    <td>
                        {{ $client->phone ?? '---' }}
                    </td>

                    <td>
                        {{ $client->email ?? '---' }}
                    </td>

                    <td>
                        {{ $client->address ?? '-----' }}
                    </td>

                    <td>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route('admin.client.detail', ['id' => $client->id]) }}"
                                class="btn btn-warning btn-sm" title="Sửa khách hàng">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <button type="button" class="btn btn-danger btn-sm btn-delete"
                                data-id="{{ $client->id }}" title="Xóa khách hàng">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center py-4" colspan="7">
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
