<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 3%"><input type="checkbox" id="check-all"></th>
            <th style="width: 12%"># | Ngày tạo</th>
            <th style="width: 18%">tên khách hàng</th>
            <th style="width: 12%">Số điện thoại</th>
            <th style="width: 20%">Email</th>
            <th>Địa chỉ</th>
            <th style="width: 10%" class="text-center">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($clients as $client)
            <tr>
                <td><input type="checkbox" class="checked-item" value="{{ $client->id }}"></td>
                <td>
                    {{ ($clients->currentPage() - 1) * $clients->perPage() + $loop->iteration }}
                    | {{ $client->created_at->format('d/m/Y') }}
                </td>
                <td>{{ $client->name }}</td>
                <td>{{ $client->phone }}</td>
                <td>{{ $client->email }}</td>
                <td>{{ $client->address ?? '-----' }}</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $client->id }}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="7">Không có khách hàng nào</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $clients->links('vendor.pagination.custom') }}
    </div>
</div>
