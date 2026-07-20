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
