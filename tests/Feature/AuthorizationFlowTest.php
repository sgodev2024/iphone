<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Roles;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthorizationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAuthorizationSchema();

        Route::middleware(['auth', 'role:administrator,admin_store', 'permission:dashboard.view'])
            ->get('/authorization/admin-dashboard', fn () => response('dashboard'))
            ->name('authorization.admin_dashboard');

        Route::middleware(['auth', 'role:staff', 'permission:product.view'])
            ->get('/authorization/staff-product', fn () => response('product'))
            ->name('authorization.staff_product');

        Route::get('/authorization/permission-only', fn () => response('ok'))
            ->middleware('permission:dashboard.view')
            ->name('authorization.permission_only');
    }

    public function test_admin_store_login_redirects_to_admin_and_reaches_dashboard(): void
    {
        $adminStore = $this->createUser('admin_store', 'admin@example.test');
        $permission = Permission::create([
            'module' => 'Dashboard',
            'permission_key' => 'dashboard.view',
        ]);

        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => $adminStore->role_id,
            'permission_id' => $permission->id,
        ]);

        $this->postJson(route('auth.authenticate'), [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data', route('admin.dashboard', absolute: false));

        $this->get('/authorization/admin-dashboard')
            ->assertOk()
            ->assertSee('dashboard');
    }

    public function test_authenticated_roles_are_redirected_from_login_by_semantic_role(): void
    {
        $administrator = $this->createUser('administrator', 'redirect-owner@example.test');
        $adminStore = $this->createUser('admin_store', 'redirect-admin@example.test');
        $staff = $this->createUser('staff', 'redirect-staff@example.test');

        $this->actingAs($administrator)
            ->get(route('auth.login'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($adminStore)
            ->get(route('auth.login'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($staff)
            ->get(route('auth.login'))
            ->assertRedirect(route('staff.index'));
    }

    public function test_permission_seeder_grants_capabilities_to_admin_store_without_full_access(): void
    {
        $this->seed(PermissionSeeder::class);

        $adminStore = $this->createUser('admin_store', 'seeded-admin@example.test');

        $this->assertFalse($adminStore->hasFullAccess());
        $this->assertGreaterThan(
            0,
            DB::table('role_permission')->where('role_id', $adminStore->role_id)->count()
        );
        $this->assertTrue(Gate::forUser($adminStore)->allows('dashboard.view'));
    }

    public function test_store_owner_has_full_access_without_permission_rows(): void
    {
        $owner = $this->createUser('administrator', 'owner@example.test');

        $this->actingAs($owner)
            ->get('/authorization/admin-dashboard')
            ->assertOk();
    }

    public function test_only_administrator_has_full_access_and_admin_store_uses_permissions(): void
    {
        $administrator = $this->createUser('administrator', 'gate-owner@example.test');
        $adminStore = $this->createUser('admin_store', 'gate-admin@example.test');
        $staff = $this->createUser('staff', 'gate-staff@example.test');

        $this->assertTrue($administrator->isAdministrator());
        $this->assertTrue($administrator->hasFullAccess());
        $this->assertTrue($adminStore->isAdminStore());
        $this->assertFalse($adminStore->hasFullAccess());
        $this->assertTrue($staff->isStaff());
        $this->assertFalse(Gate::forUser($adminStore)->allows('permission.that.is.not.seeded'));
        $this->assertFalse(Gate::forUser($staff)->allows('permission.that.is.not.seeded'));

        $permission = Permission::create(['module' => 'Dashboard', 'permission_key' => 'dashboard.allowed']);
        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => $adminStore->role_id,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue(Gate::forUser($adminStore)->allows('dashboard.allowed'));
    }

    public function test_staff_with_permission_is_allowed_and_without_it_is_forbidden(): void
    {
        $staff = $this->createUser('staff', 'staff@example.test');
        $permission = Permission::create([
            'module' => 'Product',
            'permission_key' => 'product.view',
        ]);

        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => $staff->role_id,
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($staff)
            ->get('/authorization/staff-product')
            ->assertOk();

        $this->actingAs($staff)
            ->getJson('/authorization/admin-dashboard')
            ->assertForbidden();
    }

    public function test_unauthenticated_permission_route_redirects_to_login(): void
    {
        $this->get('/authorization/permission-only')
            ->assertRedirect(route('auth.login'));
    }

    public function test_intended_url_is_preserved_only_when_role_can_access_it(): void
    {
        $staff = $this->createUser('staff', 'staff-intended@example.test');
        $permission = Permission::create([
            'module' => 'Product',
            'permission_key' => 'product.view',
        ]);

        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => $staff->role_id,
            'permission_id' => $permission->id,
        ]);

        $this->withSession(['url.intended' => url('/authorization/staff-product')])
            ->postJson(route('auth.authenticate'), [
                'email' => 'staff-intended@example.test',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('data', url('/authorization/staff-product'));

        Auth::logout();

        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->postJson(route('auth.authenticate'), [
                'email' => 'staff-intended@example.test',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('data', route('staff.index', absolute: false));
    }

    public function test_user_with_missing_role_is_denied_without_null_error(): void
    {
        $user = User::create([
            'name' => 'Orphan',
            'email' => 'orphan@example.test',
            'phone' => '0900000099',
            'password' => Hash::make('password'),
            'role_id' => 999,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->getJson('/authorization/permission-only')
            ->assertForbidden();
    }

    private function createUser(string $roleName, string $email): User
    {
        $role = Roles::where('name', $roleName)->firstOrFail();

        return User::create([
            'name' => ucfirst($roleName),
            'email' => $email,
            'phone' => '09000000' . random_int(10, 99),
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function createAuthorizationSchema(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Roles::insert([
            ['name' => 'administrator', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin_store', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'staff', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('module');
            $table->string('permission_key')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table): void {
            $table->id();
            $table->string('guard_name');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });
    }
}
