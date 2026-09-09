<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Roles;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class RolePermissionSecurityTest extends TestCase
{
    private int $userSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRoleSecuritySchema();
    }

    public function test_administrator_can_open_role_index(): void
    {
        $this->actingAs($this->createUser(Roles::ADMINISTRATOR_ID))
            ->get('/admin/roles')
            ->assertOk();
    }

    public function test_admin_store_cannot_open_role_index(): void
    {
        $this->actingAs($this->createUser(Roles::ADMIN_STORE_ID))
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_staff_cannot_open_role_index(): void
    {
        $this->actingAs($this->createUser(Roles::STAFF_ID))
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_admin_store_cannot_open_administrator_permissions(): void
    {
        $this->assertAdminStoreCannotOpenPermissions(Roles::ADMINISTRATOR_ID);
    }

    public function test_admin_store_cannot_open_admin_store_permissions(): void
    {
        $this->assertAdminStoreCannotOpenPermissions(Roles::ADMIN_STORE_ID);
    }

    public function test_admin_store_cannot_open_staff_permissions(): void
    {
        $this->assertAdminStoreCannotOpenPermissions(Roles::STAFF_ID);
    }

    public function test_administrator_cannot_rename_role_one(): void
    {
        $this->assertCanonicalRoleCannotBeRenamed(Roles::ADMINISTRATOR_ID);
    }

    public function test_administrator_cannot_rename_role_two(): void
    {
        $this->assertCanonicalRoleCannotBeRenamed(Roles::ADMIN_STORE_ID);
    }

    public function test_administrator_cannot_rename_role_three(): void
    {
        $this->assertCanonicalRoleCannotBeRenamed(Roles::STAFF_ID);
    }

    public function test_canonical_roles_cannot_be_deleted_and_pivots_do_not_change(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $permissionId = $this->permission('security.delete-proof')->id;

        foreach (Roles::CANONICAL_IDS as $roleId) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            $this->insertPivot($roleId, $permissionId);
            $before = $this->pivotPermissionIds($roleId);

            $this->actingAs($administrator)
                ->delete("/admin/roles/{$roleId}")
                ->assertForbidden();

            $this->assertDatabaseHas('roles', ['id' => $roleId]);
            $this->assertSame($before, $this->pivotPermissionIds($roleId));
        }
    }

    public function test_role_two_cannot_be_renamed_to_store_alias(): void
    {
        $this->assertCanonicalRoleCannotBeRenamed(Roles::ADMIN_STORE_ID, 'store');
    }

    public function test_role_three_cannot_be_renamed_to_store_alias(): void
    {
        $this->assertCanonicalRoleCannotBeRenamed(Roles::STAFF_ID, 'store');
    }

    public function test_full_access_is_true_only_for_role_id_one(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $adminStore = $this->createUser(Roles::ADMIN_STORE_ID);
        $staff = $this->createUser(Roles::STAFF_ID);
        $roleFour = $this->createUser(4);

        $this->assertTrue($administrator->hasFullAccess());
        $this->assertFalse($adminStore->hasFullAccess());
        $this->assertFalse($staff->hasFullAccess());
        $this->assertFalse($roleFour->hasFullAccess());
    }

    public function test_role_two_named_store_still_has_no_full_access(): void
    {
        DB::table('roles')->where('id', Roles::ADMIN_STORE_ID)->update(['name' => 'store']);
        $adminStore = $this->createUser(Roles::ADMIN_STORE_ID);

        $this->assertFalse($adminStore->hasFullAccess());
        $this->assertFalse($adminStore->isAdministrator());
        $this->assertTrue($adminStore->isAdminStore());
        $this->assertFalse(Roles::findOrFail(Roles::ADMIN_STORE_ID)->isAdministrator());
    }

    public function test_invalid_permission_id_is_rejected_before_existing_pivots_are_deleted(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $before = $this->pivotPermissionIds(Roles::ADMIN_STORE_ID);

        $this->actingAs($administrator)
            ->postJson('/admin/roles/2/permissions', ['permissions' => [999999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0');

        $this->assertSame($before, $this->pivotPermissionIds(Roles::ADMIN_STORE_ID));
    }

    public function test_duplicate_permission_ids_are_rejected_before_mutation(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $permissionId = $this->permission('dashboard.view')->id;
        $before = $this->pivotPermissionIds(Roles::ADMIN_STORE_ID);

        $this->actingAs($administrator)
            ->postJson('/admin/roles/2/permissions', [
                'permissions' => [$permissionId, (string) $permissionId],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.1');

        $this->assertSame($before, $this->pivotPermissionIds(Roles::ADMIN_STORE_ID));
    }

    public function test_nested_and_string_permission_payloads_are_rejected(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $permissionId = $this->permission('dashboard.view')->id;
        $before = $this->pivotPermissionIds(Roles::STAFF_ID);

        $this->actingAs($administrator)
            ->postJson('/admin/roles/3/permissions', ['permissions' => [[$permissionId]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0');

        $this->actingAs($administrator)
            ->postJson('/admin/roles/3/permissions', ['permissions' => '1 OR 1=1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions');

        $this->assertSame($before, $this->pivotPermissionIds(Roles::STAFF_ID));
    }

    public function test_successful_permission_sync_replaces_the_set_atomically(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $first = $this->permission('security.sync.first');
        $second = $this->permission('security.sync.second');

        $this->actingAs($administrator)
            ->post('/admin/roles/3/permissions', [
                'permissions' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('admin.role.index'));

        $this->assertSame(
            [$first->id, $second->id],
            $this->pivotPermissionIds(Roles::STAFF_ID)
        );
        $this->assertSame(
            ['web'],
            DB::table('role_permission')
                ->where('role_id', Roles::STAFF_ID)
                ->distinct()
                ->pluck('guard_name')
                ->all()
        );
    }

    public function test_insert_failure_rolls_back_deleted_permissions(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $permissionId = $this->permission('security.rollback')->id;
        $before = $this->pivotPermissionIds(Roles::ADMIN_STORE_ID);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER fail_role_permission_insert
BEFORE INSERT ON role_permission
WHEN NEW.role_id = 2
BEGIN
    SELECT RAISE(ABORT, 'forced role permission failure');
END;
SQL);

        $thrown = false;
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($administrator)
                ->post('/admin/roles/2/permissions', ['permissions' => [$permissionId]]);
        } catch (Throwable) {
            $thrown = true;
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS fail_role_permission_insert');
        }

        $this->assertTrue($thrown, 'The test trigger should force the insert to fail.');
        $this->assertSame($before, $this->pivotPermissionIds(Roles::ADMIN_STORE_ID));
    }

    public function test_role_index_exposes_actual_user_counts(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $this->createUser(Roles::ADMINISTRATOR_ID);

        for ($i = 0; $i < 3; $i++) {
            $this->createUser(Roles::ADMIN_STORE_ID);
        }

        for ($i = 0; $i < 8; $i++) {
            $this->createUser(Roles::STAFF_ID);
        }

        $response = $this->actingAs($administrator)->get('/admin/roles')->assertOk();
        $roles = $response->viewData('roles')->keyBy('id');

        $this->assertSame(2, (int) $roles[1]->users_count);
        $this->assertSame(3, (int) $roles[2]->users_count);
        $this->assertSame(8, (int) $roles[3]->users_count);
    }

    public function test_administrator_permission_count_is_displayed_as_full_access(): void
    {
        $this->actingAs($this->createUser(Roles::ADMINISTRATOR_ID))
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('Toàn quyền');
    }

    public function test_role_two_and_three_counts_include_only_distinct_valid_web_permissions(): void
    {
        DB::table('role_permission')->whereIn('role_id', [2, 3])->delete();
        $first = $this->permission('security.count.first');
        $second = $this->permission('security.count.second');

        $this->insertPivot(2, $first->id);
        $this->insertPivot(2, $second->id);
        $this->insertPivot(3, $first->id);
        DB::table('role_permission')->insert([
            'guard_name' => 'api',
            'role_id' => 3,
            'permission_id' => $second->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => 3,
            'permission_id' => 999999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->createUser(1))->get('/admin/roles')->assertOk();
        $roles = $response->viewData('roles')->keyBy('id');

        $this->assertSame(2, (int) $roles[2]->valid_permissions_count);
        $this->assertSame(1, (int) $roles[3]->valid_permissions_count);
    }

    public function test_role_index_has_no_custom_role_create_edit_or_delete_controls(): void
    {
        $this->actingAs($this->createUser(Roles::ADMINISTRATOR_ID))
            ->get('/admin/roles')
            ->assertOk()
            ->assertDontSee('Thêm chức vụ')
            ->assertDontSee('>Sửa<', false)
            ->assertDontSee('>Xóa<', false);
    }

    public function test_role_one_permission_page_and_sync_are_forbidden_even_for_administrator(): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $before = $this->pivotPermissionIds(Roles::ADMINISTRATOR_ID);

        $this->actingAs($administrator)
            ->get('/admin/roles/1/permissions')
            ->assertForbidden();

        $this->actingAs($administrator)
            ->post('/admin/roles/1/permissions', ['permissions' => []])
            ->assertForbidden();

        $this->assertSame($before, $this->pivotPermissionIds(Roles::ADMINISTRATOR_ID));
    }

    public function test_admin_store_mutation_routes_are_forbidden(): void
    {
        $adminStore = $this->createUser(Roles::ADMIN_STORE_ID);
        $permissionId = $this->permission('dashboard.view')->id;

        $this->actingAs($adminStore)->post('/admin/roles', ['name' => 'custom'])->assertForbidden();
        $this->actingAs($adminStore)->put('/admin/roles/2', ['name' => 'store'])->assertForbidden();
        $this->actingAs($adminStore)->delete('/admin/roles/2')->assertForbidden();
        $this->actingAs($adminStore)
            ->post('/admin/roles/2/permissions', ['permissions' => [$permissionId]])
            ->assertForbidden();
    }

    public function test_unknown_role_id_is_not_treated_as_warehouse_or_a_named_runtime_role(): void
    {
        $roleFour = $this->createUser(4);

        $this->assertFalse($roleFour->matchesRoleRequirement('warehouse'));
        $this->assertFalse($roleFour->isAdministrator());
        $this->assertFalse($roleFour->isAdminStore());
        $this->assertFalse($roleFour->isStaff());
    }

    public function test_non_web_guard_permission_does_not_grant_runtime_access(): void
    {
        DB::table('role_permission')->where('role_id', Roles::ADMIN_STORE_ID)->delete();
        $permission = $this->permission('security.guard');
        DB::table('role_permission')->insert([
            'guard_name' => 'api',
            'role_id' => Roles::ADMIN_STORE_ID,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(
            $this->createUser(Roles::ADMIN_STORE_ID)->hasPermission('security.guard')
        );
    }

    public function test_permission_seeder_uses_admin_store_business_matrix_only(): void
    {
        $stalePermission = $this->permission('security.stale-permission');
        $this->insertPivot(Roles::ADMINISTRATOR_ID, $stalePermission->id);
        $this->insertPivot(Roles::ADMIN_STORE_ID, $stalePermission->id);
        $this->insertPivot(Roles::STAFF_ID, $stalePermission->id);

        $staffBefore = $this->pivotPermissionIds(Roles::STAFF_ID);
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $allowlist = (new \ReflectionClass(PermissionSeeder::class))
            ->getReflectionConstant('ADMIN_STORE_PERMISSION_KEYS');

        $this->assertNotFalse($allowlist);
        $expectedAdminStoreKeys = collect($allowlist->getValue())
            ->sort()
            ->values()
            ->all();

        $this->seed(PermissionSeeder::class);

        $firstAdminStoreKeys = $this->pivotPermissionKeys(Roles::ADMIN_STORE_ID);

        $this->assertSame([], $this->pivotPermissionIds(Roles::ADMINISTRATOR_ID));
        $this->assertCount(115, $firstAdminStoreKeys);
        $this->assertSame($expectedAdminStoreKeys, $firstAdminStoreKeys);
        $this->assertSame($staffBefore, $this->pivotPermissionIds(Roles::STAFF_ID));
        $this->assertTrue($administrator->hasFullAccess());
        $this->assertTrue($administrator->hasPermission('permission.not.in.pivot'));

        $this->seed(PermissionSeeder::class);

        $this->assertSame([], $this->pivotPermissionIds(Roles::ADMINISTRATOR_ID));
        $this->assertSame($firstAdminStoreKeys, $this->pivotPermissionKeys(Roles::ADMIN_STORE_ID));
        $this->assertSame($staffBefore, $this->pivotPermissionIds(Roles::STAFF_ID));
        $this->assertTrue($administrator->hasFullAccess());
    }

    public function test_role_created_at_null_is_rendered_as_dash(): void
    {
        DB::table('roles')->where('id', Roles::STAFF_ID)->update(['created_at' => null]);

        $this->actingAs($this->createUser(Roles::ADMINISTRATOR_ID))
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('staff')
            ->assertSee('>-<', false);
    }

    private function assertAdminStoreCannotOpenPermissions(int $roleId): void
    {
        $this->actingAs($this->createUser(Roles::ADMIN_STORE_ID))
            ->get("/admin/roles/{$roleId}/permissions")
            ->assertForbidden();
    }

    private function assertCanonicalRoleCannotBeRenamed(int $roleId, string $name = 'renamed'): void
    {
        $administrator = $this->createUser(Roles::ADMINISTRATOR_ID);
        $before = Roles::findOrFail($roleId)->name;

        $this->actingAs($administrator)
            ->put("/admin/roles/{$roleId}", ['name' => $name])
            ->assertForbidden();

        $this->assertSame($before, Roles::findOrFail($roleId)->name);
    }

    private function permission(string $key): Permission
    {
        return Permission::firstOrCreate(
            ['permission_key' => $key],
            ['module' => 'Security', 'description' => $key]
        );
    }

    private function insertPivot(int $roleId, int $permissionId): void
    {
        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function pivotPermissionIds(int $roleId): array
    {
        return DB::table('role_permission')
            ->where('role_id', $roleId)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->map(fn ($permissionId) => (int) $permissionId)
            ->all();
    }

    private function pivotPermissionKeys(int $roleId): array
    {
        return DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('role_permission.role_id', $roleId)
            ->orderBy('permissions.permission_key')
            ->pluck('permissions.permission_key')
            ->all();
    }

    private function createUser(int $roleId): User
    {
        $this->userSequence++;

        return User::create([
            'name' => "Security User {$this->userSequence}",
            'email' => "role-security-{$this->userSequence}@example.test",
            'phone' => sprintf('09%08d', $this->userSequence),
            'password' => Hash::make('password'),
            'status' => 'active',
            'role_id' => $roleId,
        ]);
    }

    private function createRoleSecuritySchema(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('config');
        Schema::dropIfExists('user_info');
        Schema::dropIfExists('users');

        Schema::table('roles', function (Blueprint $table): void {
            $table->text('description')->nullable();
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->text('description')->nullable();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('user_info', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('img_url')->nullable();
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });
    }
}
