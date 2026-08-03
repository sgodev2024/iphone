@extends('admin.layout.index')



@section('title','Quản lý Role')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between mb-3">

        <h3>Quản lý chức vụ</h3>

        <a href="{{ route('admin.role.create') }}"
            class="btn btn-primary">

            Thêm chức vụ

        </a>

    </div>

    <div class="card">

        <div class="card-body">

            <table class="table table-bordered table-hover">

                <thead>

                    <tr>

                        <th width="70">
                            STT
                        </th>

                        <th>
                            Chức vụ
                        </th>

                        <th>
                            Mô tả
                        </th>

                        <th width="120">
                            Số quyền
                        </th>

                        <th width="150">
                            Người dùng
                        </th>

                        <th width="170">
                            Ngày tạo
                        </th>

                        <th width="260">
                            Thao tác
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($roles as $index=>$role)

                    <tr>

                        <td>

                            {{ $roles->firstItem()+$index }}

                        </td>

                        <td>

                            <strong>

                                {{ $role->name }}

                            </strong>

                        </td>

                        <td>

                            {{ $role->description }}

                        </td>

                        <td>

                            <span class="badge bg-primary">

                                {{ $role->role_permissions_count }}

                            </span>

                        </td>

                        <td>

                            @isset($role->users_count)

                            <span class="badge bg-success">

                                {{ $role->users_count }}

                            </span>

                            @else

                            -

                            @endisset

                        </td>

                        <td>

                            #

                        </td>

                        <td>

                            <a
                                href="{{ route('admin.role.permissions',$role->id) }}"
                                class="btn btn-success btn-sm">

                                🔑 Phân quyền

                            </a>

                            <a
                                href="{{ route('admin.role.edit',$role->id) }}"
                                class="btn btn-warning btn-sm">

                                Sửa

                            </a>

                            <form
                                action="{{ route('admin.role.destroy', $role->id) }}"
                                method="POST"
                                class="d-inline">

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bạn có chắc muốn xóa vai trò này?')">

                                    Xóa

                                </button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="7"
                            class="text-center">

                            Không có dữ liệu

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

            {{ $roles->links() }}

        </div>

    </div>

</div>

@endsection