<?php

namespace Tests\Feature;

use App\Mail\SendMailInfo;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminEmployeeCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('notification')->default(false);
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
