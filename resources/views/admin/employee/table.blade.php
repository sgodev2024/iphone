<table class="table table-hover table-striped table-bordered mt-3">
    <thead>
        <tr>
            <th style="width: 3%"><input type="checkbox" id="check-all"></th>
            <th style="width: 14%"># | Ngày tạo</th>
            <th style="width: 25%"> Tên</th>
            <th style="width: 20%">Email</th>
            <th style="width: 12%">Điện thoại</th>
            <th style="width: 12%">Trạng thái</th>
            <th style="width: 12%" class="text-center">Hành động</th>
        </tr>
    </thead>
    <tbody>

        @forelse ($users as $index => $user)
            <tr>
                <td>
                    <input type="checkbox" class="checked-item" value="{{ $user->id }}">
                </td>
                <td>
                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                    | {{ $user->created_at->format('d/m/Y') }}
                </td>
                <td>
                    {{-- <img src="{{ showImage($user->img_url) }}" alt="avatar" class="rounded-circle me-2" width="32"
                        height="32"> --}}
                    {{ $user->name }}
                </td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone }}</td>
                <td>
                    @switch($user->status)
                        @case('active')
                            <span class="badge bg-success">Kích hoạt</span>
                        @break

                        @case('inactive')
                            <span class="badge bg-secondary">Không kích hoạt</span>
                        @break

                        @case('locked')
                            <span class="badge bg-danger">Bị khóa</span>
                        @break

                        @default
                            <span class="badge bg-light text-dark">Không xác định</span>
                    @endswitch
                </td>
                <td class="text-center">
                    <a href="/admin/{{ $mode }}/{{ $user->id }}/edit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $user->id }}">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Không có dữ liệu</td>
                </tr>
            @endforelse
        </tbody>
    </table>


    <div class="row">
        <div class="col-sm-12" id="pagination">
            {{ $users->links('vendor.pagination.custom') }}
        </div>
    </div>
