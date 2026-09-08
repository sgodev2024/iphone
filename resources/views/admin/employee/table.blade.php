@php
    $employees = $employees ?? ($users ?? collect());
    $actor = $actor ?? Auth::user();
@endphp

<div class="table-responsive employee-table-scroll">
<table class="table table-hover table-striped table-bordered mt-3 employee-table">
    <thead>
        <tr>
            <th><input type="checkbox" id="check-all"></th>
            <th>ID</th>
            <th>Ngày tạo</th>
            <th>Tên</th>
            <th>Vai trò</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Nơi làm việc</th>
            <th>Trạng thái</th>
            <th class="text-center">Hành động</th>
        </tr>
    </thead>
    <tbody>

        @forelse ($employees as $employee)
            @php
                $isSelf = (int) optional($actor)->id === (int) $employee->id;
                $isAdministratorAccount = (int) $employee->role_id === \App\Models\Roles::ADMINISTRATOR_ID;
                $isAdminStoreAccount = (int) $employee->role_id === \App\Models\Roles::ADMIN_STORE_ID;
                $isStaffAccount = (int) $employee->role_id === \App\Models\Roles::STAFF_ID;
                $canActOnAccount = !$isSelf;
                $roleLabel = $isAdministratorAccount
                    ? 'Administrator'
                    : ($isAdminStoreAccount ? 'Admin Store' : 'Nhân viên');
                $roleBadgeClass = $isAdministratorAccount
                    ? 'bg-primary'
                    : ($isAdminStoreAccount ? 'bg-info text-dark' : 'bg-secondary');

                if ($isAdministratorAccount) {
                    $workplaceLabel = 'Toàn hệ thống';
                } elseif ($isAdminStoreAccount) {
                    $workplaceLabel = optional($employee->administeredBranch)->name
                        ?? optional($employee->branch)->name
                        ?? 'Chưa được gán chi nhánh';
                } else {
                    $workplaceLabel = optional($employee->storage)->name ?? 'Chưa gán kho';
                }

                $statusAction = match ($employee->status) {
                    'active' => [
                        'label' => 'Ngừng hoạt động',
                        'icon' => 'fa-user-slash',
                        'class' => 'btn-warning',
                        'target' => 'inactive',
                        'confirm' => 'Bạn có chắc muốn ngừng hoạt động tài khoản này?',
                    ],
                    'inactive' => [
                        'label' => 'Kích hoạt',
                        'icon' => 'fa-user-check',
                        'class' => 'btn-success',
                        'target' => 'active',
                        'confirm' => 'Bạn có chắc muốn kích hoạt lại tài khoản này?',
                    ],
                    'locked' => [
                        'label' => 'Mở khóa',
                        'icon' => 'fa-unlock',
                        'class' => 'btn-info',
                        'target' => 'active',
                        'confirm' => 'Bạn có chắc muốn mở khóa tài khoản này?',
                    ],
                    default => null,
                };
            @endphp
            <tr>
                <td>
                    @if ($canActOnAccount)
                        <input type="checkbox" class="checked-item" value="{{ $employee->id }}">
                    @endif
                </td>
                <td>{{ $employee->id }}</td>
                <td>{{ optional($employee->created_at)->format('d/m/Y') ?? '-' }}</td>
                <td>
                    {{-- <img src="{{ showImage($employee->img_url) }}" alt="avatar" class="rounded-circle me-2" width="32"
                        height="32"> --}}
                    {{ $employee->name }}
                </td>
                <td><span class="badge {{ $roleBadgeClass }}">{{ $roleLabel }}</span></td>
                <td>{{ $employee->email }}</td>
                <td>{{ $employee->phone }}</td>
                <td>
                    <div class="fw-semibold">{{ $workplaceLabel }}</div>
                    @if ($isStaffAccount && optional($employee->branch)->name)
                        <div class="small text-muted">{{ $employee->branch->name }}</div>
                    @endif
                </td>
                <td>
                    @switch($employee->status)
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
                    <div class="d-flex flex-nowrap justify-content-center gap-1">
                    <a href="/admin/{{ $mode }}/{{ $employee->id }}/edit" class="btn btn-primary btn-sm"
                        title="Sửa">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    @if ($canActOnAccount)
                        @if ($statusAction)
                            <button type="button" class="btn {{ $statusAction['class'] }} btn-sm btn-employee-status"
                                data-id="{{ $employee->id }}"
                                data-url="{{ route('admin.employees.status.update', ['id' => $employee->id]) }}"
                                data-target-status="{{ $statusAction['target'] }}"
                                data-confirm="{{ $statusAction['confirm'] }}" title="{{ $statusAction['label'] }}"
                                aria-label="{{ $statusAction['label'] }}">
                                <i class="fa-solid {{ $statusAction['icon'] }}"></i>
                                <span class="visually-hidden">{{ $statusAction['label'] }}</span>
                            </button>
                        @endif
                        <button type="button" class="btn btn-danger btn-sm btn-delete-employee"
                            data-id="{{ $employee->id }}"
                            data-url="{{ route('admin.employees.destroy', ['employee' => $employee->id]) }}"
                            title="Xóa tài khoản">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    @endif
                    </div>
                </td>
            </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Không có dữ liệu</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


    <div class="row">
        <div class="col-sm-12" id="pagination">
            {{ $employees->links('vendor.pagination.custom') }}
        </div>
    </div>
