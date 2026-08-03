<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Roles;
use App\Models\RolePermission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Roles::withCount('rolePermissions')
            ->paginate(10);

        return view('admin.role.index', compact('roles'));
    }

    public function permissions(Roles $role)
    {
        $permissions = Permission::orderBy('module')
            ->orderBy('permission_key')
            ->get()
            ->groupBy('module');

        $selectedPermissions = RolePermission::where('role_id', $role->id)
            ->pluck('permission_id')
            ->toArray();

        return view(
            'admin.role.permissions',
            compact(
                'role',
                'permissions',
                'selectedPermissions'
            )
        );
    }

    public function savePermissions(Request $request, Roles $role)
    {
        RolePermission::where('role_id', $role->id)
            ->delete();

        if ($request->has('permissions')) {
            foreach ($request->permissions as $permission) {
                RolePermission::create([

                    'role_id' => $role->id,

                    'permission_id' => $permission,

                    'guard_name' => 'web'

                ]);
            }
        }

        return redirect()
            ->route('admin.role.index')
            ->with('success', 'Cập nhật quyền thành công');
    }

    public function create()
    {
        return view('admin.role.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.unique' => 'Tên vai trò đã tồn tại.',
        ]);

        Roles::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('admin.role.index')
            ->with('success', 'Thêm vai trò thành công.');
    }

    public function edit(Roles $role)
    {
        return view('admin.role.edit', compact('role'));
    }

    public function update(Request $request, Roles $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.unique' => 'Tên vai trò đã tồn tại.',
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('admin.role.index')
            ->with('success', 'Cập nhật vai trò thành công.');
    }

    public function destroy(Roles $role)
    {
        RolePermission::where('role_id', $role->id)->delete();

        $role->delete();

        return redirect()
            ->route('admin.role.index')
            ->with('success', 'Xóa vai trò thành công.');
    }
}
