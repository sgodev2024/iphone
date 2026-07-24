<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\Storage;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCrudAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_branch_account_create_update_validation_and_authorization(): void
    {
        Mail::fake();
        $admin = $this->createUser(roleId: 1);

        $this->actingAs($admin)->get('/admin/users/create')->assertOk();

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Chi nhanh SGO',
            'email' => 'branch@example.com',
            'phone' => '0901000001',
            'password' => 'secret123',
            'address' => 'Ha Noi',
            'status' => 'active',
            'role_id' => 1,
            'manager_id' => 999,
        ]);

        $response->assertCreated()->assertJsonPath('data.redirect', '/admin/users');

        $branchUser = User::where('email', 'branch@example.com')->first();
        $this->assertSame(2, (int) $branchUser->role_id);
        $this->assertSame($admin->id, (int) $branchUser->manager_id);
        $this->assertTrue(Hash::check('secret123', $branchUser->password));

        $this->actingAs($admin)->putJson("/admin/users/{$branchUser->id}", [
            'name' => 'Chi nhanh SGO Update',
            'email' => 'branch@example.com',
            'phone' => '0901000001',
            'password' => '',
            'address' => 'Sai Gon',
            'status' => 'inactive',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $branchUser->id,
            'name' => 'Chi nhanh SGO Update',
            'status' => 'inactive',
            'role_id' => 2,
            'manager_id' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => '',
            'email' => 'branch@example.com',
            'phone' => '0901000001',
            'password' => '',
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);

        $staff = $this->createUser('staff@example.com', '0901000002', 3);
        $this->actingAs($staff)->postJson('/admin/users', [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'phone' => '0901000003',
            'password' => 'secret123',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_branch_crud_and_validation_errors(): void
    {
        $admin = $this->createUser(roleId: 1);

        $this->actingAs($admin)->get('/admin/branchs/create')->assertOk();

        $response = $this->actingAs($admin)->postJson('/admin/branchs', [
            'name' => 'Chi nhanh Ha Noi',
            'manager_name' => 'Quan ly',
            'address' => 'Ha Noi',
            'phone' => '0901234567',
            'email' => 'hn@example.com',
            'status' => '1',
        ]);

        $response->assertCreated();
        $branch = Branch::where('name', 'Chi nhanh Ha Noi')->first();

        $this->actingAs($admin)->putJson("/admin/branchs/{$branch->id}", [
            'name' => 'Chi nhanh Sai Gon',
            'manager_name' => 'Quan ly moi',
            'address' => 'Sai Gon',
            'phone' => '0907654321',
            'email' => 'sg@example.com',
            'status' => '0',
        ])->assertOk();

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Chi nhanh Sai Gon',
            'status' => 0,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson('/admin/branchs', [
            'name' => '',
            'address' => '',
            'status' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address']);

        $this->actingAs($admin)->postJson('/admin/branchs', [
            'name' => 'Chi nhanh Sai Gon',
            'address' => 'Duplicate',
            'status' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $staff = $this->createUser('staff2@example.com', '0902000002', 3);
        $this->actingAs($staff)->postJson('/admin/branchs', [
            'name' => 'Blocked',
            'address' => 'Blocked',
            'status' => '1',
        ])->assertForbidden();
    }

    public function test_storage_crud_validation_and_authorization(): void
    {
        $warehouseUser = $this->createUser(roleId: 4);

        $this->actingAs($warehouseUser)->get('/admin/storage')->assertOk();

        $this->actingAs($warehouseUser)->postJson('/admin/storage', [
            'name' => 'Kho chinh',
            'location' => 'Ha Noi',
        ])->assertCreated();

        $storage = Storage::where('name', 'Kho chinh')->first();

        $this->actingAs($warehouseUser)->putJson("/admin/storage/{$storage->id}", [
            'name' => 'Kho phu',
            'location' => 'Sai Gon',
        ])->assertOk();

        $this->assertDatabaseHas('storages', [
            'id' => $storage->id,
            'name' => 'Kho phu',
            'user_id' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)->postJson('/admin/storage', [
            'name' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->actingAs($warehouseUser)->postJson('/admin/storage', [
            'name' => 'Kho phu',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $staff = $this->createUser('staff3@example.com', '0903000002', 3);
        $this->actingAs($staff)->postJson('/admin/storage', [
            'name' => 'Blocked',
        ])->assertForbidden();
    }

    public function test_supplier_create_update_validation_and_authorization(): void
    {
        $admin = $this->createUser(roleId: 1);
        $company = $this->createCompany($admin);

        $this->actingAs($admin)->get("/admin/supplier/add/{$company->id}")->assertOk();

        $this->actingAs($admin)
            ->from("/admin/supplier/add/{$company->id}")
            ->post('/admin/supplier/store', [
                'company_id' => $company->id,
                'name' => 'Dai dien NCC',
                'email' => 'rep@example.com',
                'phone' => '0904000001',
            ])
            ->assertRedirect("/admin/supplier/{$company->id}");

        $supplier = Supplier::where('email', 'rep@example.com')->first();

        $this->actingAs($admin)
            ->post("/admin/supplier/update/{$supplier->id}", [
                'company_id' => $company->id,
                'name' => 'Dai dien NCC Update',
                'email' => 'rep@example.com',
                'phone' => '0904000002',
            ])
            ->assertRedirect("/admin/supplier/{$company->id}");

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Dai dien NCC Update',
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->from("/admin/supplier/add/{$company->id}")
            ->post('/admin/supplier/store', [
                'company_id' => $company->id,
                'name' => '',
                'email' => 'rep@example.com',
            ])
            ->assertSessionHasErrors(['name', 'email']);

        $this->actingAs($admin)
            ->from("/admin/supplier/add/{$company->id}")
            ->post('/admin/supplier/store', [
                'company_id' => 999,
                'name' => 'Invalid FK',
                'email' => 'invalid-fk@example.com',
            ])
            ->assertSessionHasErrors(['company_id']);

        $staff = $this->createUser('staff4@example.com', '0904000004', 3);
        $this->actingAs($staff)->post('/admin/supplier/store', [
            'company_id' => $company->id,
            'name' => 'Blocked',
            'email' => 'blocked-supplier@example.com',
        ])->assertForbidden();
    }

    public function test_company_create_update_validation_and_authorization(): void
    {
        $admin = $this->createUser(roleId: 1);
        $bankId = $this->createBank();

        $this->actingAs($admin)->get('/admin/company/create')->assertOk();

        $this->actingAs($admin)->postJson('/admin/company', [
            'name' => 'NCC Apple',
            'phone' => '0905000001',
            'email' => 'company@example.com',
            'address' => 'Ha Noi',
            'tax_number' => 'TAX001',
            'bank_account' => '123456',
            'bank_id' => $bankId,
            'city_id' => null,
            'status' => '1',
        ])->assertCreated();

        $company = Company::where('email', 'company@example.com')->first();
        $this->assertSame($admin->id, (int) $company->user_id);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 1,
        ]);

        $this->actingAs($admin)->putJson("/admin/company/{$company->id}", [
            'name' => 'NCC Apple Update',
            'phone' => '0905000001',
            'email' => 'company@example.com',
            'address' => 'Sai Gon',
            'tax_number' => 'TAX001',
            'bank_account' => '123456',
            'bank_id' => $bankId,
            'city_id' => null,
            'status' => '0',
        ])->assertOk();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'NCC Apple Update',
            'user_id' => $admin->id,
            'status' => 0,
        ]);

        $this->actingAs($admin)->putJson("/admin/company/{$company->id}", [
            'name' => 'NCC Apple Update',
            'phone' => '0905000001',
            'email' => 'company@example.com',
            'address' => 'Sai Gon',
            'tax_number' => 'TAX001',
            'bank_account' => '123456',
            'bank_id' => $bankId,
            'city_id' => null,
            'status' => '1',
        ])->assertOk();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 1,
        ]);

        $this->actingAs($admin)->postJson('/admin/company', [
            'name' => '',
            'phone' => '123',
            'email' => 'company@example.com',
            'tax_number' => 'TAX001',
            'bank_id' => 999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'email', 'address', 'bank_account', 'bank_id', 'tax_number']);

        $staff = $this->createUser('staff5@example.com', '0905000005', 3);
        $this->actingAs($staff)->postJson('/admin/company', [
            'name' => 'Blocked',
        ])->assertForbidden();
    }

    public function test_client_update_validation_and_authorization(): void
    {
        $admin = $this->createUser(roleId: 1);
        $groupId = $this->createClientGroup();
        $client = $this->createClient($admin, $groupId);
        $otherClient = $this->createClient($admin, $groupId, 'other@example.com', '0906000002');

        $this->actingAs($admin)->get("/admin/client/detail/{$client->id}")->assertOk();

        $this->actingAs($admin)
            ->from("/admin/client/detail/{$client->id}")
            ->put("/admin/client/update/{$client->id}", [
                'name' => 'Khach hang Update',
                'phone' => '0906000001',
                'email' => 'client@example.com',
                'gender' => 'Female',
                'dob' => '1990-01-01',
                'address' => 'Sai Gon',
                'zip_code' => '70000',
                'clientgroup_id' => $groupId,
            ])
            ->assertRedirect('/admin/client');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Khach hang Update',
            'gender' => 'Female',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from("/admin/client/detail/{$client->id}")
            ->put("/admin/client/update/{$client->id}", [
                'name' => '',
                'phone' => $otherClient->phone,
                'email' => $otherClient->email,
                'gender' => 'Other',
                'dob' => 'not-a-date',
                'clientgroup_id' => 999,
            ])
            ->assertSessionHasErrors(['name', 'phone', 'email', 'gender', 'dob', 'clientgroup_id']);

        $staff = $this->createUser('staff6@example.com', '0906000006', 3);
        $this->actingAs($staff)->put("/admin/client/update/{$client->id}", [
            'name' => 'Blocked',
        ])->assertForbidden();
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('address')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('manager_name')->nullable();
            $table->string('address', 500);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('total_money')->nullable();
            $table->boolean('notification')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('city', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('email')->unique();
            $table->string('tax_number')->unique();
            $table->string('bank_account');
            $table->unsignedBigInteger('bank_id');
            $table->text('note')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('client_group', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('zip_code')->nullable();
            $table->string('address')->nullable();
            $table->string('email');
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->unsignedBigInteger('clientgroup_id')->nullable();
            $table->timestamps();
        });
    }

    private function createUser(string $email = 'admin@example.com', string $phone = '0900000001', int $roleId = 1): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'status' => 'active',
        ]);
    }

    private function createBank(): int
    {
        return DB::table('banks')->insertGetId([
            'name' => 'Test Bank',
            'code' => 'TB',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCompany(User $admin): Company
    {
        $bankId = $this->createBank();

        return Company::create([
            'user_id' => $admin->id,
            'name' => 'NCC Test',
            'phone' => '0904000000',
            'email' => 'ncc@example.com',
            'address' => 'Ha Noi',
            'tax_number' => 'TAXSUP',
            'bank_account' => '123456',
            'bank_id' => $bankId,
        ]);
    }

    private function createClientGroup(): int
    {
        return DB::table('client_group')->insertGetId([
            'code' => 'GROUP1',
            'name' => 'Khach le',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createClient(
        User $admin,
        int $groupId,
        string $email = 'client@example.com',
        string $phone = '0906000001'
    ): Client {
        return Client::create([
            'user_id' => $admin->id,
            'code' => 'KH' . random_int(1000, 9999),
            'name' => 'Khach hang',
            'phone' => $phone,
            'email' => $email,
            'gender' => 'Male',
            'dob' => '1991-01-01',
            'address' => 'Ha Noi',
            'zip_code' => '10000',
            'clientgroup_id' => $groupId,
        ]);
    }
}
