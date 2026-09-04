<?php

namespace Tests\Feature;

use App\Exports\ClientsExport;
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
        $adminStore = $this->createUser('branch-admin-store@example.com', '0902000099', 2);

        $this->actingAs($admin)->get('/admin/branchs/create')->assertOk();

        $response = $this->actingAs($admin)->postJson('/admin/branchs', [
            'name' => 'Chi nhanh Ha Noi',
            'admin_store_user_id' => $adminStore->id,
            'address' => 'Ha Noi',
            'phone' => '0901234567',
            'email' => 'hn@example.com',
            'status' => '1',
        ]);

        $response->assertCreated();
        $branch = Branch::where('name', 'Chi nhanh Ha Noi')->first();

        $this->actingAs($admin)->putJson("/admin/branchs/{$branch->id}", [
            'name' => 'Chi nhanh Sai Gon',
            'admin_store_user_id' => $adminStore->id,
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

    public function test_branch_can_assign_one_admin_store_and_rejects_staff_or_duplicate_assignment(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $adminStore = $this->createUser('admin-store@example.com', '0902000010', 2);
        $staff = $this->createUser('staff-branch@example.com', '0902000011', 3);

        $this->actingAs($administrator)->postJson('/admin/branchs', [
            'name' => 'Store Ha Noi',
            'admin_store_user_id' => $adminStore->id,
            'address' => 'Ha Noi',
            'status' => '1',
        ])->assertCreated();

        $branch = Branch::query()->where('name', 'Store Ha Noi')->firstOrFail();
        $adminStore->refresh();

        $this->assertSame($adminStore->id, (int) $branch->admin_store_user_id);
        $this->assertSame($branch->id, (int) $adminStore->branch_id);
        $this->assertTrue($branch->adminStore->is($adminStore));
        $this->assertTrue($adminStore->branch->is($branch));
        $this->assertDatabaseHas('storages', ['branch_id' => $branch->id, 'user_id' => $adminStore->id, 'name' => 'Kho Store Ha Noi']);
        $this->assertSame(1, Storage::query()->where('branch_id', $branch->id)->count());
        $this->assertNull($adminStore->storage_id);

        $this->actingAs($administrator)->postJson('/admin/branchs', [
            'name' => 'Invalid Staff Branch',
            'admin_store_user_id' => $staff->id,
            'address' => 'Da Nang',
            'status' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('admin_store_user_id');

        $this->actingAs($administrator)->postJson('/admin/branchs', [
            'name' => 'Duplicate Admin Store',
            'admin_store_user_id' => $adminStore->id,
            'address' => 'Sai Gon',
            'status' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('admin_store_user_id');
    }

    public function test_admin_store_cannot_assign_branch_manager_through_direct_request(): void
    {
        $adminStore = $this->createUser('admin-store-owner@example.com', '0902000012', 2);
        $otherAdminStore = $this->createUser('other-admin-store@example.com', '0902000013', 2);


        $this->actingAs($adminStore)->postJson('/admin/branchs', [
            'name' => 'Forbidden Assignment',
            'admin_store_user_id' => $otherAdminStore->id,
            'address' => 'Ha Noi',
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

    public function test_storage_index_uses_inventory_storage_scope_with_search_and_pagination(): void
    {
        $manager = $this->createUser('manager-storage@example.com', '0903100001', 1);
        $warehouseUser = $this->createUser('warehouse-storage@example.com', '0903100002', 4);

        $warehouseUser->update(['manager_id' => $manager->id]);

        $storageA = Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho A',
            'location' => 'Ha Noi',
        ]);

        $storageB = Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho B',
            'location' => 'Sai Gon',
        ]);

        $assignedStorage = Storage::create([
            'user_id' => null,
            'name' => 'Kho duoc gan',
            'location' => 'Da Nang',
        ]);

        Storage::create([
            'user_id' => $manager->id + 999,
            'name' => 'Kho ngoai pham vi',
            'location' => 'Can Tho',
        ]);

        for ($i = 1; $i <= 9; $i++) {
            Storage::create([
                'user_id' => $manager->id,
                'name' => sprintf('Kho phu %02d', $i),
                'location' => 'Ha Noi',
            ]);
        }

        $warehouseUser->update(['storage_id' => $assignedStorage->id]);

        $response = $this->actingAs($warehouseUser)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/storage?s=Kho');

        $response->assertOk();

        $html = $response->json('html');

        $this->assertStringContainsString((string) $storageA->id, $html);
        $this->assertStringContainsString('Kho A', $html);
        $this->assertStringContainsString('Kho B', $html);
        $this->assertStringContainsString('page=2', $html);
        $this->assertStringContainsString('s=Kho', $html);
        $this->assertStringNotContainsString('Kho ngoai pham vi', $html);

        $searchResponse = $this->actingAs($warehouseUser)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/storage?s=Kho%20B');

        $searchHtml = $searchResponse->assertOk()->json('html');

        $this->assertStringContainsString((string) $storageB->id, $searchHtml);
        $this->assertStringContainsString('Kho B', $searchHtml);
        $this->assertStringNotContainsString('Kho A', $searchHtml);

        $emptyResponse = $this->actingAs($warehouseUser)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/storage?s=Khong%20ton%20tai');

        $this->assertStringContainsString('Không có kho hàng', $emptyResponse->assertOk()->json('html'));

        $this->actingAs($warehouseUser)
            ->getJson("/admin/storage/{$storageA->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $storageA->id);
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

    public function test_customer_creation_list_crud_export_and_pos_lookup_are_branch_scoped(): void
    {
        $administrator = $this->createUser();
        $storeA = $this->createUser('customer-store-a@example.com', '0908100001', 2, 101, $administrator->id);
        $staffA = $this->createUser('customer-staff-a@example.com', '0908100002', 3, 101, $storeA->id);
        $groupId = $this->createClientGroup();
        $clientA = $this->createClient($storeA, $groupId, 'client-a@example.com', '0908100011', 101);
        $clientB = $this->createClient($administrator, $groupId, 'client-b@example.com', '0908100012', 202);
        $legacy = $this->createClient($administrator, $groupId, 'legacy-client@example.com', '0908100013');

        $this->actingAs($storeA)->postJson('/ban-hang/clients/add', [
            'name' => 'Created by Store A',
            'phone' => '0908100014',
            'email' => 'created-store-a@example.com',
        ])->assertCreated()->assertJsonPath('data.branch_id', 101);

        $this->actingAs($staffA)->postJson('/ban-hang/clients/add', [
            'name' => 'Created by Staff A',
            'phone' => '0908100015',
            'email' => 'created-staff-a@example.com',
        ])->assertCreated()->assertJsonPath('data.branch_id', 101);

        $html = $this->actingAs($storeA)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/client?s=client')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString($clientA->email, $html);
        $this->assertStringNotContainsString($clientB->email, $html);
        $this->assertStringNotContainsString($legacy->email, $html);

        $this->actingAs($storeA)->get("/admin/client/detail/{$clientB->id}")->assertNotFound();
        $this->actingAs($storeA)->put("/admin/client/update/{$clientB->id}", [
            'name' => 'Tampered',
            'phone' => $clientB->phone,
            'email' => $clientB->email,
        ])->assertNotFound();
        $this->actingAs($storeA)->delete("/admin/client/delete/{$clientB->id}")->assertNotFound();

        $posClients = $this->actingAs($staffA)
            ->getJson('/ban-hang/get-clients?searchText=Khach')
            ->assertOk()
            ->json();
        $posIds = collect($posClients)->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($posIds->contains($clientA->id));
        $this->assertFalse($posIds->contains($clientB->id));
        $this->assertFalse($posIds->contains($legacy->id));

        $exportIds = (new ClientsExport('', 101))->collection()->pluck('id');
        $this->assertTrue($exportIds->contains($clientA->id));
        $this->assertFalse($exportIds->contains($clientB->id));
        $this->assertFalse($exportIds->contains($legacy->id));

        $globalHtml = $this->actingAs($administrator)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/client?s=client')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString($clientA->email, $globalHtml);
        $this->assertStringContainsString($clientB->email, $globalHtml);
        $this->assertStringContainsString($legacy->email, $globalHtml);
    }

    public function test_company_and_supplier_crud_and_import_selection_are_branch_scoped(): void
    {
        $administrator = $this->createUser();
        $storeA = $this->createUser('supplier-store-a@example.com', '0908200001', 2, 301, $administrator->id);
        $bankId = $this->createBank();

        $this->actingAs($storeA)->postJson('/admin/company', [
            'name' => 'NCC Branch A',
            'phone' => '0908200011',
            'email' => 'ncc-a@example.com',
            'address' => 'A',
            'tax_number' => 'TAX-A',
            'bank_account' => '111',
            'bank_id' => $bankId,
            'status' => '1',
        ])->assertCreated();
        $companyA = Company::where('email', 'ncc-a@example.com')->firstOrFail();
        $this->assertSame(301, (int) $companyA->branch_id);

        $companyB = Company::create([
            'user_id' => $administrator->id,
            'branch_id' => 302,
            'name' => 'NCC Branch B',
            'phone' => '0908200012',
            'email' => 'ncc-b@example.com',
            'address' => 'B',
            'tax_number' => 'TAX-B',
            'bank_account' => '222',
            'bank_id' => $bankId,
            'status' => true,
        ]);
        $legacy = Company::create([
            'user_id' => $administrator->id,
            'branch_id' => null,
            'name' => 'NCC Legacy',
            'phone' => '0908200013',
            'email' => 'ncc-legacy@example.com',
            'address' => 'Legacy',
            'tax_number' => 'TAX-LEGACY',
            'bank_account' => '333',
            'bank_id' => $bankId,
            'status' => true,
        ]);

        $html = $this->actingAs($storeA)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/company?s=NCC')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString($companyA->name, $html);
        $this->assertStringNotContainsString($companyB->name, $html);
        $this->assertStringNotContainsString($legacy->name, $html);

        $this->actingAs($storeA)->get("/admin/company/{$companyB->id}/edit")->assertNotFound();
        $this->actingAs($storeA)->postJson('/admin/bulk/delete', [
            'ids' => [$companyB->id],
            'model' => 'Company',
        ])->assertNotFound();

        $this->actingAs($storeA)->post('/admin/supplier/store', [
            'company_id' => $companyA->id,
            'name' => 'Supplier A',
            'email' => 'supplier-a@example.com',
            'phone' => '0908200021',
        ])->assertRedirect("/admin/supplier/{$companyA->id}");
        $supplierA = Supplier::where('email', 'supplier-a@example.com')->firstOrFail();
        $supplierB = Supplier::create([
            'company_id' => $companyB->id,
            'name' => 'Supplier B',
            'email' => 'supplier-b@example.com',
            'phone' => '0908200022',
        ]);

        $this->actingAs($storeA)
            ->getJson('/admin/supplier/findByPhone?phone=0908200021')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->actingAs($storeA)
            ->getJson('/admin/supplier/findByPhone?phone=0908200022')
            ->assertNotFound();

        $this->actingAs($storeA)->get("/admin/supplier/{$companyA->id}")
            ->assertOk()->assertSee('Supplier A')->assertDontSee('Supplier B');
        $this->actingAs($storeA)->get("/admin/supplier/{$companyB->id}")->assertNotFound();
        $this->actingAs($storeA)->get("/admin/supplier/detail/{$supplierB->id}")->assertNotFound();
        $this->actingAs($storeA)->delete("/admin/supplier/delete/{$supplierB->id}")->assertNotFound();
        $this->actingAs($storeA)->post('/admin/supplier/store', [
            'company_id' => $companyB->id,
            'name' => 'Bypass',
            'email' => 'supplier-bypass@example.com',
        ])->assertNotFound();

        $storageA = Storage::create([
            'user_id' => $administrator->id,
            'branch_id' => 301,
            'name' => 'Kho A',
        ]);
        $this->actingAs($storeA)
            ->postJson('/admin/importproduct/importCoupon', [
                'supplier' => $companyB->id,
                'storage' => $storageA->id,
                'payment_method' => 'cash',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('supplier');

        $globalHtml = $this->actingAs($administrator)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/company?s=NCC')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString($companyA->name, $globalHtml);
        $this->assertStringContainsString($companyB->name, $globalHtml);
        $this->assertStringContainsString($legacy->name, $globalHtml);
        $this->actingAs($administrator)->get("/admin/supplier/{$companyB->id}")
            ->assertOk()->assertSee($supplierB->name);
        $this->actingAs($administrator)->get("/admin/supplier/detail/{$supplierA->id}")
            ->assertOk();
    }

    public function test_branch_creation_rolls_back_when_default_storage_fails(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $adminStore = $this->createUser('rollback-store@example.com', '0902000081', 2);
        $dispatcher = Storage::getEventDispatcher();
        Storage::creating(static function (): void { throw new \RuntimeException('storage failure'); });
        try {
            $this->actingAs($administrator)->postJson('/admin/branchs', [
                'name' => 'Rollback Branch', 'admin_store_user_id' => $adminStore->id,
                'address' => 'Ha Noi', 'status' => '1',
            ])->assertStatus(500);
        } finally {
            Storage::setEventDispatcher($dispatcher);
        }
        $this->assertDatabaseMissing('branches', ['name' => 'Rollback Branch']);
        $this->assertDatabaseMissing('storages', ['name' => 'Kho Rollback Branch']);
        $this->assertNull($adminStore->fresh()->branch_id);
    }

    public function test_administrator_can_replace_admin_store_without_creating_a_second_storage(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $storeA = $this->createUser('store-a@example.com', '0902000082', 2);
        $storeB = $this->createUser('store-b@example.com', '0902000083', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', ['name' => 'Branch A', 'admin_store_user_id' => $storeA->id, 'address' => 'Ha Noi', 'status' => '1'])->assertCreated();
        $branch = Branch::where('name', 'Branch A')->firstOrFail();
        $storageId = Storage::where('branch_id', $branch->id)->value('id');
        $this->actingAs($administrator)->putJson("/admin/branchs/{$branch->id}", ['name' => 'Branch A renamed', 'admin_store_user_id' => $storeB->id, 'address' => 'Ha Noi', 'status' => '1'])->assertOk();
        $this->assertSame($storeB->id, (int) $branch->fresh()->admin_store_user_id);
        $this->assertNull($storeA->fresh()->branch_id);
        $this->assertSame($branch->id, (int) $storeB->fresh()->branch_id);
        $this->assertSame($storageId, Storage::where('branch_id', $branch->id)->value('id'));

        $other = $this->createUser('store-other@example.com', '0902000084', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', ['name' => 'Branch B', 'admin_store_user_id' => $other->id, 'address' => 'Da Nang', 'status' => '1'])->assertCreated();
        $this->actingAs($administrator)->putJson("/admin/branchs/{$branch->id}", ['name' => 'Branch A renamed', 'admin_store_user_id' => $other->id, 'address' => 'Ha Noi', 'status' => '1'])->assertUnprocessable();
        $this->assertSame($storeB->id, (int) $branch->fresh()->admin_store_user_id);
    }

    public function test_duplicate_admin_store_assignment_leaves_no_partial_branch_or_storage(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $adminStore = $this->createUser('duplicate-store@example.com', '0902000085', 2);
        $payload = ['admin_store_user_id' => $adminStore->id, 'address' => 'Ha Noi', 'status' => '1'];
        $this->actingAs($administrator)->postJson('/admin/branchs', $payload + ['name' => 'Concurrent A'])->assertCreated();
        $this->actingAs($administrator)->postJson('/admin/branchs', $payload + ['name' => 'Concurrent B'])->assertUnprocessable()->assertJsonValidationErrors('admin_store_user_id');
        $this->assertSame(1, Branch::where('admin_store_user_id', $adminStore->id)->count());
        $this->assertDatabaseMissing('branches', ['name' => 'Concurrent B']);
        $this->assertDatabaseMissing('storages', ['name' => 'Kho Concurrent B']);
    }
    public function test_branch_form_keeps_current_admin_store_for_edit_and_disables_empty_create_state(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $current = $this->createUser('current-store@example.com', '0902000086', 2);
        $available = $this->createUser('available-store@example.com', '0902000087', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', ['name' => 'Editable Branch', 'admin_store_user_id' => $current->id, 'address' => 'Ha Noi', 'status' => '1'])->assertCreated();
        $branch = Branch::where('name', 'Editable Branch')->firstOrFail();
        $this->actingAs($administrator)->getJson("/admin/branchs/{$branch->id}/show")->assertOk()->assertJsonPath('data.admin_store.id', $current->id);
        $this->actingAs($administrator)->get('/admin/branchs/create')->assertOk()->assertSee('available-store@example.com')->assertDontSee('current-store@example.com');
        $this->actingAs($administrator)->postJson('/admin/branchs', ['name' => 'Available Branch', 'admin_store_user_id' => $available->id, 'address' => 'Da Nang', 'status' => '1'])->assertCreated();
        $this->actingAs($administrator)->get('/admin/branchs/create')->assertOk()->assertSee('Không còn tài khoản Admin Store chưa được gán cửa hàng.');
    }
    public function test_empty_default_storage_can_be_deleted_but_inventory_blocks_branch_delete(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $emptyStore = $this->createUser('empty-delete-store@example.com', '0902000088', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', ['name' => 'Empty Delete Branch', 'admin_store_user_id' => $emptyStore->id, 'address' => 'Ha Noi', 'status' => '1'])->assertCreated();
        $emptyBranch = Branch::where('name', 'Empty Delete Branch')->firstOrFail();
        $this->actingAs($administrator)->deleteJson('/admin/branchs', ['ids' => [$emptyBranch->id]])->assertOk();
        $this->assertDatabaseMissing('branches', ['id' => $emptyBranch->id]);
        $this->assertDatabaseMissing('storages', ['branch_id' => $emptyBranch->id]);
        $this->assertNull($emptyStore->fresh()->branch_id);

        $usedStore = $this->createUser('used-delete-store@example.com', '0902000089', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', ['name' => 'Used Delete Branch', 'admin_store_user_id' => $usedStore->id, 'address' => 'Da Nang', 'status' => '1'])->assertCreated();
        $usedBranch = Branch::where('name', 'Used Delete Branch')->firstOrFail();
        $storage = Storage::where('branch_id', $usedBranch->id)->firstOrFail();
        DB::table('product_storage')->insert(['product_id' => 1, 'storage_id' => $storage->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($administrator)->deleteJson('/admin/branchs', ['ids' => [$usedBranch->id]])->assertUnprocessable();
        $this->assertDatabaseHas('branches', ['id' => $usedBranch->id]);
        $this->assertDatabaseHas('storages', ['id' => $storage->id]);
    }

    public function test_branch_delete_rejects_staff_order_customer_and_company_data(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $adminStore = $this->createUser('reject-delete-store@example.com', '0902000091', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', [
            'name' => 'Reject Delete Branch',
            'admin_store_user_id' => $adminStore->id,
            'address' => 'Ha Noi',
            'status' => '1',
        ])->assertCreated();
        $branch = Branch::where('name', 'Reject Delete Branch')->firstOrFail();
        $staff = $this->createUser('reject-delete-staff@example.com', '0902000092', 3);
        $staff->update(['branch_id' => $branch->id]);
        DB::table('orders')->insert(['branch_id' => $branch->id, 'total_money' => 100, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('clients')->insert([
            'branch_id' => $branch->id, 'code' => 'REJECT-CLIENT', 'name' => 'Customer',
            'phone' => '0902000093', 'email' => 'reject-client@example.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('companies')->insert([
            'branch_id' => $branch->id, 'name' => 'Company', 'phone' => '0902000094',
            'address' => 'Ha Noi', 'email' => 'reject-company@example.com',
            'tax_number' => 'REJECT-COMPANY', 'bank_account' => '123456', 'bank_id' => 1,
            'status' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $storage = Storage::where('branch_id', $branch->id)->firstOrFail();

        $this->actingAs($administrator)->deleteJson('/admin/branchs', ['ids' => [$branch->id]])->assertUnprocessable();
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('storages', ['id' => $storage->id]);
        $this->assertSame($branch->id, (int) $adminStore->fresh()->branch_id);
    }

    public function test_branch_delete_rolls_back_storage_and_admin_store_when_branch_delete_fails(): void
    {
        $administrator = $this->createUser(roleId: 1);
        $adminStore = $this->createUser('rollback-delete-store@example.com', '0902000095', 2);
        $this->actingAs($administrator)->postJson('/admin/branchs', [
            'name' => 'Rollback Delete Branch',
            'admin_store_user_id' => $adminStore->id,
            'address' => 'Ha Noi',
            'status' => '1',
        ])->assertCreated();
        $branch = Branch::where('name', 'Rollback Delete Branch')->firstOrFail();
        $storage = Storage::where('branch_id', $branch->id)->firstOrFail();
        $dispatcher = Branch::getEventDispatcher();
        Branch::deleting(static function (): void { throw new \RuntimeException('branch delete failure'); });

        try {
            $this->actingAs($administrator)->deleteJson('/admin/branchs', ['ids' => [$branch->id]])->assertStatus(500);
        } finally {
            Branch::setEventDispatcher($dispatcher);
        }

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('storages', ['id' => $storage->id, 'branch_id' => $branch->id]);
        $this->assertSame($branch->id, (int) $adminStore->fresh()->branch_id);
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();

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
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('user_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('img_url')->nullable();
            $table->timestamps();
        });


        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_store_user_id')->nullable()->unique();
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
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('product_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('inventory_tracking')->nullable();
            $table->timestamps();
        });

        Schema::create('import', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
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
            $table->unsignedBigInteger('branch_id')->nullable();
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
            $table->unsignedBigInteger('branch_id')->nullable();
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
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('zip_code')->nullable();
            $table->string('address')->nullable();
            $table->string('email');
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->unsignedBigInteger('clientgroup_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    private function createUser(
        string $email = 'admin@example.com',
        string $phone = '0900000001',
        int $roleId = 1,
        ?int $branchId = null,
        ?int $managerId = null,
    ): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'branch_id' => $branchId,
            'manager_id' => $managerId,
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

    private function createCompany(User $admin, ?int $branchId = null): Company
    {
        $bankId = $this->createBank();

        return Company::create([
            'user_id' => $admin->id,
            'branch_id' => $branchId,
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
        string $phone = '0906000001',
        ?int $branchId = null,
    ): Client {
        return Client::create([
            'user_id' => $admin->id,
            'branch_id' => $branchId,
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
