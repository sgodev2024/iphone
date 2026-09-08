@extends('admin.layout.index')

@section('title', 'Phân quyền vai trò')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h3>Phân quyền</h3>
            <strong>Vai trò: {{ $role->name }}</strong>
        </div>

        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                Thay đổi quyền sẽ áp dụng cho tất cả tài khoản thuộc vai trò này.
            </div>

            <div class="row mb-4">
                <div class="col-md-5">
                    <input type="text" id="searchPermission" class="form-control" placeholder="🔍 Tìm kiếm quyền...">
                </div>
                <div class="col-md-7 text-end">
                    <button type="button" id="btnSelectAll" class="btn btn-success btn-sm">Chọn tất cả</button>
                    <button type="button" id="btnUnSelectAll" class="btn btn-secondary btn-sm">Bỏ chọn</button>
                </div>
            </div>

            <form action="{{ route('admin.role.permissions.save', $role->id) }}" method="POST">
                @csrf
                <input type="hidden" name="permissions_submission" value="1">

                <div class="row" id="permissionList">
                    @foreach($permissions->flatten() as $permission)
                        <div class="col-lg-4 col-md-6 mb-2 permission-item">
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox" type="checkbox"
                                    name="permissions[]" value="{{ $permission->id }}"
                                    id="permission{{ $permission->id }}"
                                    {{ in_array($permission->id, $selectedPermissions, true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="permission{{ $permission->id }}">
                                    {{ $permission->description ?: $permission->permission_key }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-end mt-4">
                    <a href="{{ route('admin.role.index') }}" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu phân quyền</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    const permissionItems = document.querySelectorAll('.permission-item');
    const search = document.getElementById('searchPermission');

    document.getElementById('btnSelectAll').addEventListener('click', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = true;
        });
    });

    document.getElementById('btnUnSelectAll').addEventListener('click', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = false;
        });
    });

    search.addEventListener('keyup', function () {
        const keyword = this.value.toLowerCase();

        permissionItems.forEach(function (item) {
            item.style.display = item.innerText.toLowerCase().includes(keyword) ? '' : 'none';
        });
    });
});
</script>
@endsection
