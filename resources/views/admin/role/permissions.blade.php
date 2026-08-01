@extends('admin.layout.index')

@section('content')

<div class="container">

    <div class="card">

        <div class="card-header">

            <h3>
                Phân quyền
            </h3>

            <strong>
                Chức vụ :
                {{ $role->name }}
            </strong>

        </div>

        <div class="card-body">

            <div class="row mb-4">

                <div class="col-md-5">

                    <input
                        type="text"
                        id="searchPermission"
                        class="form-control"
                        placeholder="🔍 Tìm kiếm quyền...">

                </div>

                <div class="col-md-7 text-end">

                    <div class="form-check form-check-inline">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="checkAll" hidden>

                        

                    </div>

                    <button
                        type="button"
                        id="btnSelectAll"
                        class="btn btn-success btn-sm">

                        Chọn tất cả

                    </button>

                    <button
                        type="button"
                        id="btnUnSelectAll"
                        class="btn btn-secondary btn-sm">

                        Bỏ chọn

                    </button>

                </div>

            </div>

            <form
                action="#"
                method="POST">

                @csrf

                <div class="row" id="permissionList">

                    @foreach($permissions->flatten() as $permission)

                    <div
                        class="col-lg-4 col-md-6 mb-2 permission-item">

                        <div class="form-check">

                            <input
                                class="form-check-input permission-checkbox"
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->id }}"
                                id="permission{{ $permission->id }}"
                                {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}>

                            <label
                                class="form-check-label"
                                for="permission{{ $permission->id }}">

                                {{ $permission->description }}

                            </label>

                        </div>

                    </div>

                    @endforeach

                </div>

                <div class="text-end mt-4">

                    <button
                        class="btn btn-primary">

                        Lưu phân quyền

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const checkAll = document.getElementById('checkAll');

    const checkboxes = document.querySelectorAll('.permission-checkbox');

    const search = document.getElementById('searchPermission');

    const rows = document.querySelectorAll('.permission-item');

    // =========================
    // Check All
    // =========================

    checkAll.addEventListener('change', function () {

        checkboxes.forEach(item => {

            item.checked = this.checked;

        });

    });

    // =========================
    // Button Select All
    // =========================

    document.getElementById('btnSelectAll').addEventListener('click', function () {

        checkboxes.forEach(item => item.checked = true);

        checkAll.checked = true;

    });

    // =========================
    // Button UnSelect
    // =========================

    document.getElementById('btnUnSelectAll').addEventListener('click', function () {

        checkboxes.forEach(item => item.checked = false);

        checkAll.checked = false;

    });

    // =========================
    // Đồng bộ checkbox
    // =========================

    checkboxes.forEach(item => {

        item.addEventListener('change', function () {

            checkAll.checked =
                [...checkboxes].every(cb => cb.checked);

        });

    });

    // =========================
    // Search
    // =========================

    search.addEventListener('keyup', function () {

        const keyword = this.value.toLowerCase();

        rows.forEach(function (row) {

            const text = row.innerText.toLowerCase();

            row.style.display = text.includes(keyword)
                ? ''
                : 'none';

        });

    });

});

</script>

@endsection