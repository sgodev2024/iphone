<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdministrator($request);

        $roles = Roles::query()
            ->select('roles.*')
            ->selectSub(function ($query) {
                $query->from('role_permission')
                    ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                    ->whereColumn('role_permission.role_id', 'roles.id')
                    ->where('role_permission.guard_name', 'web')
                    ->selectRaw('COUNT(DISTINCT permissions.id)');
            }, 'valid_permissions_count')
            ->withCount('users')
            ->whereKey(Roles::CANONICAL_IDS)
            ->orderBy('id')
            ->paginate(10);

        return view('admin.role.index', compact('roles'));
    }

    public function permissions(Request $request, Roles $role)
    {
        $this->ensureAdministrator($request);
        $this->ensurePermissionTarget($role);

        $permissions = Permission::orderBy('module')
            ->orderBy('permission_key')
            ->get()
            ->groupBy('module');

        $selectedPermissions = RolePermission::where('role_id', $role->id)
            ->where('guard_name', 'web')
            ->whereIn('permission_id', Permission::query()->select('id'))
            ->distinct()
            ->pluck('permission_id')
            ->toArray();

        return view(
            'admin.role.permissions',
            compact('role', 'permissions', 'selectedPermissions')
        );
    }

    public function savePermissions(Request $request, Roles $role)
    {
        $this->ensureAdministrator($request);
        $this->ensurePermissionTarget($role);

        // Browsers omit an unchecked checkbox array. The explicit form marker
        // distinguishes an intentional empty selection from a forged payload.
        if (! $request->exists('permissions')) {
            $request->validate([
                'permissions_submission' => ['required', 'accepted'],
            ]);

            $request->merge(['permissions' => []]);
        }

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('permissions', 'id'),
            ],
        ]);

        $permissionIds = collect($validated['permissions'])
            ->map(fn ($permissionId) => (int) $permissionId)
            ->values();

        DB::transaction(function () use ($role, $permissionIds): void {
            RolePermission::where('role_id', $role->getKey())->delete();

            if ($permissionIds->isEmpty()) {
                return;
            }

            $now = now();

            DB::table('role_permission')->insert(
                $permissionIds->map(fn (int $permissionId) => [
                    'guard_name' => 'web',
                    'role_id' => (int) $role->getKey(),
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        return redirect()
            ->route('admin.role.index')
            ->with('success', 'Cập nhật quyền thành công');
    }

    public function create(Request $request)
    {
        $this->ensureAdministrator($request);

        abort(403);
    }

    public function store(Request $request)
    {
        $this->ensureAdministrator($request);

        abort(403);
    }

    public function edit(Request $request, Roles $role)
    {
        $this->ensureAdministrator($request);

        abort(403);
    }

    public function update(Request $request, Roles $role)
    {
        $this->ensureAdministrator($request);

        abort(403);
    }

    public function destroy(Request $request, Roles $role)
    {
        $this->ensureAdministrator($request);

        // Reject before touching either the role or its permission pivots.
        abort(403);
    }

    private function ensureAdministrator(Request $request): void
    {
        abort_unless($request->user()?->isAdministrator(), 403);
    }

    private function ensurePermissionTarget(Roles $role): void
    {
        abort_unless(
            in_array((int) $role->getKey(), Roles::ASSIGNABLE_PERMISSION_ROLE_IDS, true),
            403
        );
    }
}
