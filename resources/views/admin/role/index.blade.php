@extends('admin.layout.index')

@section('title', 'Quản lý vai trò')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Quản lý vai trò hệ thống</h3>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th width="70">STT</th>
                        <th>Vai trò</th>
                        <th>Mô tả</th>
                        <th width="140">Số quyền</th>
                        <th width="150">Người dùng</th>
                        <th width="170">Ngày tạo</th>
                        <th width="180">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $index => $role)
                        <tr>
                            <td>{{ $roles->firstItem() + $index }}</td>
                            <td><strong>{{ $role->name }}</strong></td>
                            <td>{{ $role->description ?: '-' }}</td>
                            <td>
                                @if((int) $role->id === \App\Models\Roles::ADMINISTRATOR_ID)
                                    <span class="badge bg-primary">Toàn quyền</span>
                                @else
                                    <span class="badge bg-primary">{{ (int) $role->valid_permissions_count }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-success">{{ (int) $role->users_count }}</span></td>
                            <td>{{ $role->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                @if(in_array((int) $role->id, \App\Models\Roles::ASSIGNABLE_PERMISSION_ROLE_IDS, true))
                                    <a href="{{ route('admin.role.permissions', $role->id) }}" class="btn btn-success btn-sm">
                                        🔑 Phân quyền
                                    </a>
                                @else
                                    <span class="text-muted">Vai trò hệ thống</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Không có dữ liệu</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $roles->links() }}
        </div>
    </div>
</div>
@endsection
