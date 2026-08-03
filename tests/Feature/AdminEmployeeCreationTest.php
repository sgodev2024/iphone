<?php

namespace Tests\Feature;

use App\Mail\SendMailInfo;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminEmployeeCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pos.default_storage_id' => null,
            'pos.default_storage_name' => 'Kho A',
        ]);

        $this->createSchema();
    }

    public function test_employee_is_created_even_when_account_email_fails(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho bán hàng',
            'location' => 'Hà Nội',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('staff@example.com')
            ->andReturn(new class {
                public function send(SendMailInfo $mail): void
                {
                    throw new RuntimeException('SMTP is down');
                }
            });

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send employee account email.', Mockery::on(function (array $context) {
                return ($context['email'] ?? null) === 'staff@example.com'
                    && ($context['error'] ?? null) === 'SMTP is down';
            }));

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => 'Nhân viên bán hàng',
            'email' => 'staff@example.com',
            'phone' => '0901234567',
            'password' => 'secret123',
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', '/admin/employees');

        $employee = User::where('email', 'staff@example.com')->first();

        $this->assertNotNull($employee);
        $this->assertSame(3, (int) $employee->role_id);
        $this->assertSame($admin->id, (int) $employee->manager_id);
        $this->assertSame($storage->id, (int) $employee->storage_id);
        $this->assertSame('active', $employee->status);
        $this->assertTrue(Hash::check('secret123', $employee->password));
    }

    public function test_employee_validation_returns_field_errors(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho bán hàng',
        ]);
        User::create([
            'name' => 'Existing',
            'email' => 'taken@example.com',
            'phone' => '0909999999',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => '',
            'email' => 'taken@example.com',
            'phone' => '0909999999',
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_employee_credentials_login_to_staff_area(): void
    {
        User::create([
            'name' => 'Nhân viên bán hàng',
            'email' => 'staff-login@example.com',
            'phone' => '0901234567',
            'password' => 'secret123',
            'role_id' => 3,
            'status' => 'active',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'staff-login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', '/ban-hang');
    }

    public function test_employee_storage_must_belong_to_current_admin(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0902222222');
        $otherStorage = Storage::create([
            'user_id' => $otherAdmin->id,
            'name' => 'Kho của admin khác',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => 'Nhân viên bán hàng',
            'email' => 'staff@example.com',
            'phone' => '0901234567',
            'password' => 'secret123',
            'storage_id' => $otherStorage->id,
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['storage_id']);

        $this->assertDatabaseMissing('users', [
            'email' => 'staff@example.com',
        ]);
    }

    public function test_employee_storage_is_required_for_staff_accounts(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => 'Nhân viên thiếu kho',
            'email' => 'missing-storage@example.com',
            'phone' => '0901234568',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['storage_id']);

        $this->assertDatabaseMissing('users', [
            'email' => 'missing-storage@example.com',
        ]);
    }

    public function test_current_admin_appears_first_without_checkbox_or_delete_button(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('hidden-admin@example.com', '0902222222');
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);
        $employee = User::create([
            'name' => 'Nhân viên thuộc quyền',
            'email' => 'visible-staff@example.com',
            'phone' => '0903333333',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Chi nhánh không hiển thị',
            'email' => 'branch-not-listed@example.com',
            'phone' => '0904444444',
            'password' => 'password',
            'role_id' => 2,
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Admin hệ thống', $html);
        $this->assertStringContainsString('Toàn hệ thống', $html);
        $this->assertStringContainsString('Kho bán: Kho A', $html);
        $this->assertStringNotContainsString('Toàn hệ thống · Kho bán mặc định: Kho A', $html);
        $this->assertStringContainsString($admin->email, $html);
        $this->assertStringContainsString($employee->email, $html);
        $this->assertStringNotContainsString($otherAdmin->email, $html);
        $this->assertStringNotContainsString('branch-not-listed@example.com', $html);
        $this->assertStringNotContainsString('value="' . $admin->id . '"', $html);
        $this->assertStringNotContainsString('data-id="' . $admin->id . '"', $html);
        $this->assertLessThan(strpos($html, $employee->email), strpos($html, $admin->email));

        $adminSearchHtml = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees?s=' . urlencode($admin->email))
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString($admin->email, $adminSearchHtml);
        $this->assertStringNotContainsString($otherAdmin->email, $adminSearchHtml);
    }

    public function test_admin_edit_form_hides_storage_and_status_inputs(): void
    {
        $admin = $this->createAdmin();
        Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/employees/' . $admin->id . '/edit')
            ->assertOk();

        $response->assertSee('Admin', false);
        $response->assertSee('Toàn hệ thống · Kho bán mặc định: Kho A', false);
        $response->assertSee('Kích hoạt', false);
        $response->assertDontSee('name="storage_id"', false);
        $response->assertDontSee('name="status"', false);
        $response->assertDontSee('name="role_id"', false);
    }

    public function test_admin_can_update_only_own_allowed_fields_including_avatar(): void
    {
        $admin = $this->createAdmin();
        Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $admin->id, [
            '_method' => 'PUT',
            'name' => 'Admin Updated',
            'email' => 'admin-updated@example.com',
            'phone' => '0905555555',
            'password' => 'newsecret',
            'img_url' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $admin->refresh();

        $this->assertSame('Admin Updated', $admin->name);
        $this->assertSame('admin-updated@example.com', $admin->email);
        $this->assertSame('0905555555', $admin->phone);
        $this->assertTrue(Hash::check('newsecret', $admin->password));
        $this->assertSame(1, (int) $admin->role_id);
        $this->assertNull($admin->manager_id);
        $this->assertNull($admin->storage_id);
        $this->assertSame('active', $admin->status);
        $this->assertNotNull($admin->getRawOriginal('img_url'));
        $this->assertStringStartsWith('avatar/', $admin->getRawOriginal('img_url'));
        StorageFacade::disk('public')->assertExists($admin->getRawOriginal('img_url'));
        StorageFacade::disk('public')->delete($admin->getRawOriginal('img_url'));
    }

    public function test_admin_update_rejects_forged_role_manager_storage_and_status_fields(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);

        $response = $this->actingAs($admin)->putJson('/admin/employees/' . $admin->id, [
            'name' => 'Admin Forged',
            'email' => 'admin-forged@example.com',
            'phone' => '0906666666',
            'role_id' => 3,
            'manager_id' => 999,
            'storage_id' => $storage->id,
            'status' => 'inactive',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id', 'manager_id', 'storage_id', 'status']);

        $admin->refresh();

        $this->assertSame('Admin', $admin->name);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertSame('0901111111', $admin->phone);
        $this->assertSame(1, (int) $admin->role_id);
        $this->assertNull($admin->manager_id);
        $this->assertNull($admin->storage_id);
        $this->assertSame('active', $admin->status);
    }

    public function test_admin_cannot_edit_another_admin(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0907777777');

        $this->actingAs($admin)
            ->get('/admin/employees/' . $otherAdmin->id . '/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->putJson('/admin/employees/' . $otherAdmin->id, [
                'name' => 'Other Admin Edited',
                'email' => 'other-admin-edited@example.com',
                'phone' => '0908888888',
            ])
            ->assertNotFound();

        $this->assertSame('other-admin@example.com', $otherAdmin->fresh()->email);
    }

    public function test_bulk_delete_cannot_deactivate_any_admin(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0907777777');

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$admin->id],
            'model' => 'User',
        ])->assertUnprocessable();

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$otherAdmin->id],
            'model' => 'User',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role_id' => 1,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'role_id' => 1,
            'status' => 'active',
        ]);
    }

    public function test_bulk_delete_deactivates_managed_staff_without_losing_history(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);
        $employee = User::create([
            'name' => 'Nhân viên có lịch sử',
            'email' => 'history-staff@example.com',
            'phone' => '0909999999',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        DB::table('orders')->insert([
            'id' => 1,
            'user_id' => $employee->id,
            'notification' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('import_coupon')->insert([
            'id' => 1,
            'user_id' => $employee->id,
            'status' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transactions')->insert([
            'id' => 1,
            'user_id' => $employee->id,
            'created_by' => $employee->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$employee->id],
            'model' => 'User',
        ])->assertOk()
            ->assertJsonPath('message', 'Ngừng hoạt động nhân viên thành công!');

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => 1,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('import_coupon', [
            'id' => 1,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => 1,
            'user_id' => $employee->id,
            'created_by' => $employee->id,
        ]);
    }

    public function test_bulk_delete_rejects_staff_outside_management_scope(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0907777777');
        $otherStorage = Storage::create([
            'user_id' => $otherAdmin->id,
            'name' => 'Kho A',
        ]);
        $outsideEmployee = User::create([
            'name' => 'Nhân viên ngoài phạm vi',
            'email' => 'outside-scope@example.com',
            'phone' => '0910000000',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $otherAdmin->id,
            'storage_id' => $otherStorage->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$outsideEmployee->id],
            'model' => 'User',
        ])->assertForbidden();

        $this->assertSame('active', $outsideEmployee->fresh()->status);
    }

    public function test_non_admin_cannot_bulk_deactivate_users(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);
        $staff = User::create([
            'name' => 'Staff actor',
            'email' => 'staff-actor@example.com',
            'phone' => '0911000001',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);
        $target = User::create([
            'name' => 'Staff target',
            'email' => 'staff-target@example.com',
            'phone' => '0911000002',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $this->actingAs($staff)->postJson('/admin/bulk/delete', [
            'ids' => [$target->id],
            'model' => 'User',
        ])->assertForbidden();

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_bulk_action_rejects_unlisted_model_names(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$admin->id],
            'model' => 'SuperAdmin',
        ])->assertBadRequest();
    }

    public function test_employee_index_uses_management_scope_with_search_and_pagination(): void
    {
        $admin = $this->createAdmin();
        $branch = User::create([
            'name' => 'Branch Account',
            'email' => 'branch@example.com',
            'phone' => '0903333300',
            'password' => 'password',
            'role_id' => 2,
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);
        $otherAdmin = $this->createAdmin('other-scope-admin@example.com', '0904444400');

        $adminStorage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Admin Storage',
        ]);
        $branchStorage = Storage::create([
            'user_id' => $branch->id,
            'name' => 'Branch Storage',
        ]);
        $otherStorage = Storage::create([
            'user_id' => $otherAdmin->id,
            'name' => 'Other Storage',
        ]);

        for ($i = 1; $i <= 8; $i++) {
            User::create([
                'name' => sprintf('Pager Staff %02d', $i),
                'email' => sprintf('pager%02d@example.com', $i),
                'phone' => sprintf('09100000%02d', $i),
                'password' => 'password',
                'role_id' => 3,
                'manager_id' => $admin->id,
                'storage_id' => $adminStorage->id,
                'status' => 'active',
            ]);
        }

        $directEmployee = User::create([
            'name' => 'Direct Staff',
            'email' => 'direct-staff@example.com',
            'phone' => '0911000001',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $adminStorage->id,
            'status' => 'active',
        ]);
        $branchEmployee = User::create([
            'name' => 'Branch Staff Inactive',
            'email' => 'branch-staff@example.com',
            'phone' => '0911000002',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $branch->id,
            'storage_id' => $branchStorage->id,
            'status' => 'inactive',
        ]);
        $storageEmployee = User::create([
            'name' => 'Storage Staff Locked',
            'email' => 'storage-staff@example.com',
            'phone' => '0911000003',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $otherAdmin->id,
            'storage_id' => $branchStorage->id,
            'status' => 'locked',
        ]);

        User::create([
            'name' => 'Outside Staff',
            'email' => 'outside-staff@example.com',
            'phone' => '0911000004',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $otherAdmin->id,
            'storage_id' => $otherStorage->id,
            'status' => 'active',
        ]);
        User::create([
            'name' => 'Admin Staff Name',
            'email' => 'admin-staff-name@example.com',
            'phone' => '0911000005',
            'password' => 'password',
            'role_id' => 1,
            'manager_id' => $admin->id,
            'storage_id' => $adminStorage->id,
            'status' => 'active',
        ]);
        User::create([
            'name' => 'Branch Account Staff Name',
            'email' => 'branch-account-staff@example.com',
            'phone' => '0911000006',
            'password' => 'password',
            'role_id' => 2,
            'manager_id' => $admin->id,
            'storage_id' => $branchStorage->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees?s=Staff');

        $html = $response->assertOk()->json('html');

        $this->assertStringContainsString('page=2', $html);
        $this->assertStringContainsString('s=Staff', $html);

        foreach ([$directEmployee, $branchEmployee, $storageEmployee] as $employee) {
            $employeeHtml = $this->actingAs($admin)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->get('/admin/employees?s=' . urlencode($employee->email))
                ->assertOk()
                ->json('html');

            $this->assertStringContainsString((string) $employee->id, $employeeHtml);
            $this->assertStringContainsString($employee->name, $employeeHtml);
            $this->assertStringContainsString($employee->email, $employeeHtml);
            $this->assertStringContainsString($employee->phone, $employeeHtml);
        }

        foreach (['outside-staff@example.com', 'admin-staff-name@example.com', 'branch-account-staff@example.com'] as $search) {
            $emptyHtml = $this->actingAs($admin)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->get('/admin/employees?s=' . urlencode($search))
                ->assertOk()
                ->json('html');

            $this->assertStringContainsString('Không có dữ liệu', $emptyHtml);
        }

        $this->actingAs($admin)
            ->get("/admin/employees/{$branchEmployee->id}/edit")
            ->assertOk()
            ->assertSee('Branch Staff Inactive')
            ->assertSee('Branch Storage');

        $this->actingAs($admin)
            ->get("/admin/employees/{$storageEmployee->id}/edit")
            ->assertOk()
            ->assertSee('Storage Staff Locked')
            ->assertSee('Branch Storage');
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('img_url')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(3);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('receiver')->nullable();
            $table->string('logo')->nullable();
            $table->string('qr')->nullable();
            $table->timestamps();
        });

        Schema::create('user_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('img_url')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });

        Schema::create('import_coupon', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function createAdmin(string $email = 'admin@example.com', string $phone = '0901111111'): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
    }
}
