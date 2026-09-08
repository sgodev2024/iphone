<?php

namespace Tests\Feature;

use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountStatusSessionEnforcementTest extends TestCase
{
    private const PROTECTED_PATH = '/_test/account-status/protected';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user_info');
        Schema::dropIfExists('config');
        Schema::dropIfExists('storages');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('address')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_store_user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->timestamps();
        });

        Route::middleware(['web', 'auth'])->get(
            self::PROTECTED_PATH,
            fn (Request $request) => response('active:'.$request->user()->getAuthIdentifier())
        );

        Route::middleware('web')->get(
            '/_test/account-status/guest',
            fn () => response('guest-ok')
        );
    }

    public function test_active_user_can_login_and_access_an_authenticated_request(): void
    {
        $user = $this->createUser(1, 'active', 'active-login@example.test', '0909000001');

        $this->postJson(route('auth.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->get(self::PROTECTED_PATH)
            ->assertOk()
            ->assertSee('active:'.$user->id);
    }

    public function test_existing_session_is_revoked_on_next_html_request_after_becoming_inactive(): void
    {
        $user = $this->createUser(1, 'active', 'session-inactive@example.test', '0909000002');
        $message = 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.';

        $this->actingAs($user)->get(self::PROTECTED_PATH)->assertOk();
        DB::table('users')->where('id', $user->id)->update(['status' => 'inactive']);

        $this->get(self::PROTECTED_PATH)
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas('error', $message);
        $this->assertGuest();
        $this->get(route('auth.login'))->assertOk()->assertSee($message);
    }

    public function test_existing_session_is_revoked_on_next_html_request_after_becoming_locked(): void
    {
        $user = $this->createUser(1, 'active', 'session-locked@example.test', '0909000003');
        $message = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';

        $this->actingAs($user)->get(self::PROTECTED_PATH)->assertOk();
        DB::table('users')->where('id', $user->id)->update(['status' => 'locked']);

        $this->get(self::PROTECTED_PATH)
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas('error', $message);
        $this->assertGuest();
        $this->get(route('auth.login'))->assertOk()->assertSee($message);
    }
    public function test_inactive_user_is_rejected_during_new_login(): void
    {
        $user = $this->createUser(1, 'inactive', 'inactive-login@example.test', '0909000004');

        $this->postJson(route('auth.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.'
            );

        $this->assertGuest();
    }

    public function test_locked_user_is_rejected_during_new_login(): void
    {
        $user = $this->createUser(1, 'locked', 'locked-login@example.test', '0909000005');

        $this->postJson(route('auth.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            );

        $this->assertGuest();
    }

    public function test_locked_ajax_session_gets_json_403_instead_of_login_html(): void
    {
        $user = $this->createUser(1, 'active', 'locked-json@example.test', '0909000006');

        $this->actingAs($user)->get(self::PROTECTED_PATH)->assertOk();
        DB::table('users')->where('id', $user->id)->update(['status' => 'locked']);

        $this->getJson(self::PROTECTED_PATH)
            ->assertForbidden()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath(
                'message',
                'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            );

        $this->assertGuest();
    }

    public function test_guest_and_login_routes_are_not_redirected_in_a_loop(): void
    {
        $this->assertGuest();
        $this->get('/_test/account-status/guest')
            ->assertOk()
            ->assertSee('guest-ok');
        $this->get(route('auth.login'))->assertOk();
    }

    public function test_administrator_locking_logged_in_admin_store_revokes_its_next_request_without_releasing_branch(): void
    {
        $administrator = $this->createUser(
            1,
            'active',
            'session-administrator@example.test',
            '0909000007'
        );
        $adminStore = $this->createUser(
            2,
            'active',
            'logged-admin-store@example.test',
            '0909000008',
            91
        );
        DB::table('branches')->insert([
            'id' => 91,
            'user_id' => $administrator->id,
            'admin_store_user_id' => $adminStore->id,
            'name' => 'Branch 91',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($adminStore)->get(self::PROTECTED_PATH)->assertOk();

        $this->actingAs($administrator)
            ->putJson('/admin/employees/'.$adminStore->id, [
                'name' => $adminStore->name,
                'email' => $adminStore->email,
                'phone' => $adminStore->phone,
                'status' => 'locked',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $adminStore->id,
            'status' => 'locked',
            'branch_id' => 91,
        ]);
        $this->assertDatabaseHas('branches', [
            'id' => 91,
            'admin_store_user_id' => $adminStore->id,
        ]);

        $this->actingAs($adminStore)
            ->get(self::PROTECTED_PATH)
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas(
                'error',
                'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            );
        $this->assertGuest();
    }

    public function test_admin_store_locking_logged_in_same_branch_staff_revokes_its_next_request_without_releasing_assignment(): void
    {
        $adminStore = $this->createUser(
            2,
            'active',
            'staff-session-manager@example.test',
            '0909000009',
            92
        );
        DB::table('branches')->insert([
            'id' => 92,
            'admin_store_user_id' => $adminStore->id,
            'name' => 'Branch 92',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $storage = Storage::create([
            'user_id' => $adminStore->id,
            'branch_id' => 92,
            'name' => 'Storage 92',
        ]);
        $staff = $this->createUser(
            3,
            'active',
            'logged-staff@example.test',
            '0909000010',
            92,
            $storage->id,
            $adminStore->id,
        );

        $this->actingAs($staff)->get(self::PROTECTED_PATH)->assertOk();

        $this->actingAs($adminStore)
            ->putJson('/admin/employees/'.$staff->id, [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'storage_id' => $storage->id,
                'status' => 'locked',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'locked',
            'manager_id' => $adminStore->id,
            'branch_id' => 92,
            'storage_id' => $storage->id,
        ]);

        $this->actingAs($staff)
            ->get(self::PROTECTED_PATH)
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas(
                'error',
                'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            );
        $this->assertGuest();
    }

    private function createUser(
        int $roleId,
        string $status,
        string $email,
        string $phone,
        ?int $branchId = null,
        ?int $storageId = null,
        ?int $managerId = null,
    ): User {
        return User::create([
            'name' => 'Account '.$roleId,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'manager_id' => $managerId,
            'branch_id' => $branchId,
            'storage_id' => $storageId,
            'status' => $status,
        ]);
    }
}
