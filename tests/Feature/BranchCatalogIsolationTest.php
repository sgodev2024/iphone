<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Categories;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BranchCatalogIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_brand_visibility_same_name_crud_and_create_are_branch_isolated(): void
    {
        [$administrator, $storeA, $storeB, $branchA, $branchB] = $this->actors();
        $brandA = $this->brand($branchA, 'Lux', 'Brand A only');
        $brandB = $this->brand($branchB, 'Lux', 'Brand B only');

        $storeAHtml = $this->actingAs($storeA)->get('/admin/brand', [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json('html');
        $this->assertStringContainsString('Brand A only', $storeAHtml);
        $this->assertStringNotContainsString('Brand B only', $storeAHtml);

        $storeBHtml = $this->actingAs($storeB)->get('/admin/brand', [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json('html');
        $this->assertStringContainsString('Brand B only', $storeBHtml);
        $this->assertStringNotContainsString('Brand A only', $storeBHtml);

        $adminHtml = $this->actingAs($administrator)->get('/admin/brand', [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json('html');
        $this->assertStringContainsString('Brand A only', $adminHtml);
        $this->assertStringContainsString('Brand B only', $adminHtml);
        $this->assertNotSame($brandA->id, $brandB->id);

        $filteredAdminHtml = $this->actingAs($administrator)->get('/admin/brand?branch_id='.$branchA->id, [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json('html');
        $this->assertStringContainsString('Brand A only', $filteredAdminHtml);
        $this->assertStringNotContainsString('Brand B only', $filteredAdminHtml);

        $this->actingAs($administrator)->postJson('/admin/brand', [
            'name' => 'Lux',
            'status' => 1,
            'branch_id' => $branchA->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $this->actingAs($storeA)->get('/admin/brand/create')
            ->assertOk()
            ->assertDontSee('name="branch_id"', false);
        $this->actingAs($administrator)->get('/admin/brand/create')
            ->assertOk()
            ->assertSee('name="branch_id"', false);

        $this->actingAs($storeA)->get("/admin/brand/{$brandB->id}/edit")->assertNotFound();
        $this->actingAs($storeA)->putJson("/admin/brand/{$brandB->id}", [
            'name' => 'Blocked update',
            'status' => 1,
        ])->assertNotFound();
        $this->actingAs($storeA)->deleteJson("/admin/brand/{$brandB->id}")->assertNotFound();

        $this->actingAs($storeA)->putJson("/admin/brand/{$brandA->id}", [
            'name' => 'Lux A updated',
            'description' => 'Updated only in A',
            'status' => 1,
            'branch_id' => $branchB->id,
        ])->assertOk();
        $brandA->refresh();
        $this->assertSame($branchA->id, $brandA->branch_id);
        $this->assertSame('Lux A updated', $brandA->name);
        $this->assertSame('Lux', $brandB->fresh()->name);

        try {
            $brandA->update(['branch_id' => $branchB->id]);
            $this->fail('A Brand was moved to another Branch after creation.');
        } catch (ValidationException) {
            $this->assertSame($branchA->id, $brandA->fresh()->branch_id);
        }

        $this->actingAs($storeA)->postJson('/admin/brand', [
            'name' => 'Store-created',
            'status' => 1,
            'branch_id' => $branchB->id,
        ])->assertCreated();
        $storeCreated = Brand::query()->where('name', 'Store-created')->firstOrFail();
        $this->assertSame($branchA->id, $storeCreated->branch_id);

        $this->actingAs($administrator)->postJson('/admin/brand', [
            'name' => 'Administrator-created',
            'status' => 1,
            'branch_id' => $branchB->id,
        ])->assertCreated();
        $this->assertDatabaseHas('brands', [
            'name' => 'Administrator-created',
            'branch_id' => $branchB->id,
        ]);

        $this->actingAs($storeA)->deleteJson("/admin/brand/{$storeCreated->id}")->assertOk();
        $this->assertDatabaseMissing('brands', ['id' => $storeCreated->id]);
    }

    public function test_brand_bulk_actions_are_branch_scoped_and_atomic(): void
    {
        [, $storeA, , $branchA, $branchB] = $this->actors();
        $brandA = $this->brand($branchA, 'A');
        $brandB = $this->brand($branchB, 'B');

        $this->actingAs($storeA)->postJson('/admin/bulk/status', [
            'model' => 'Brand',
            'ids' => [$brandA->id, $brandB->id],
        ])->assertNotFound();
        $this->assertTrue((bool) $brandA->fresh()->status);
        $this->assertTrue((bool) $brandB->fresh()->status);

        $this->actingAs($storeA)->postJson('/admin/bulk/delete', [
            'model' => 'Brand',
            'ids' => [$brandB->id],
        ])->assertNotFound();
        $this->assertDatabaseHas('brands', ['id' => $brandB->id]);
    }

    public function test_product_brand_invariant_and_form_options_follow_the_product_branch(): void
    {
        [$administrator, $storeA, , $branchA, $branchB] = $this->actors();
        $categoryA = $this->category($branchA, 'Category A');
        $brandA = $this->brand($branchA, 'Brand A option');
        $brandB = $this->brand($branchB, 'Brand B option');
        $productA = $this->product($storeA, $branchA, $categoryA, 'Product A', brand: $brandA);

        try {
            $productA->update(['brands_id' => $brandB->id]);
            $this->fail('A cross-branch Product-Brand link was accepted.');
        } catch (ValidationException) {
            $this->assertSame($brandA->id, $productA->fresh()->brands_id);
        }

        $payload = [
            'name' => 'Spoofed brand product',
            'price' => 100,
            'price_buy' => 150,
            'product_unit' => 'Piece',
            'category_id' => $categoryA->id,
            'brands_id' => $brandB->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'status' => 'published',
        ];
        $this->actingAs($storeA)->postJson('/admin/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brands_id']);
        $this->actingAs($storeA)->putJson("/admin/products/{$productA->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brands_id']);

        $this->actingAs($storeA)->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Brand A option')
            ->assertDontSee('Brand B option');
        $this->actingAs($administrator)->get('/admin/products/create?branch_id='.$branchA->id)
            ->assertOk()
            ->assertSee('Brand A option')
            ->assertDontSee('Brand B option');
        $this->actingAs($administrator)->get('/admin/products/create?branch_id='.$branchB->id)
            ->assertOk()
            ->assertSee('Brand B option')
            ->assertDontSee('Brand A option');
    }

    public function test_product_supplier_invariant_and_form_options_follow_the_product_branch(): void
    {
        [$administrator, $storeA, , $branchA, $branchB] = $this->actors();
        $categoryA = $this->category($branchA, 'Supplier Category A');
        $supplierA = $this->company($storeA, $branchA, 'Supplier A option');
        $supplierB = $this->company($administrator, $branchB, 'Supplier B option');

        $this->actingAs($storeA)->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Supplier A option')
            ->assertDontSee('Supplier B option');
        $this->actingAs($administrator)->get('/admin/products/create?branch_id='.$branchB->id)
            ->assertOk()
            ->assertSee('Supplier B option')
            ->assertDontSee('Supplier A option');

        $payload = [
            'name' => 'Supplier-scoped product',
            'price' => 100,
            'price_buy' => 150,
            'product_unit' => 'Piece',
            'category_id' => $categoryA->id,
            'company_id' => $supplierB->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'status' => 'published',
        ];
        $this->actingAs($storeA)->postJson('/admin/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
        $this->assertDatabaseMissing('products', ['name' => 'Supplier-scoped product']);

        $payload['company_id'] = $supplierA->id;
        $this->actingAs($storeA)->postJson('/admin/products', $payload)->assertCreated();
        $product = Product::query()->where('name', 'Supplier-scoped product')->firstOrFail();
        $this->assertDatabaseHas('company_product', [
            'product_id' => $product->id,
            'company_id' => $supplierA->id,
        ]);

        $this->actingAs($storeA)->get('/admin/products/'.$product->id.'/edit')
            ->assertOk()
            ->assertSee('Supplier A option')
            ->assertDontSee('Supplier B option');

        try {
            CompanyProduct::create([
                'product_id' => $product->id,
                'company_id' => $supplierB->id,
            ]);
            $this->fail('A cross-Branch Product-Supplier link was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('company_product', [
                'product_id' => $product->id,
                'company_id' => $supplierB->id,
            ]);
        }
    }

    public function test_branch_delete_is_blocked_while_a_brand_exists(): void
    {
        [$administrator, , , $branchA] = $this->actors();
        $this->brand($branchA, 'Deletion blocker');

        $this->actingAs($administrator)
            ->deleteJson("/admin/branches/{$branchA->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['branch']);

        $this->assertDatabaseHas('branches', ['id' => $branchA->id]);
    }

    public function test_admin_store_catalog_reads_and_mutations_are_branch_scoped_while_administrator_is_global(): void
    {
        [$administrator, $storeA, $storeB, $branchA, $branchB] = $this->actors();
        $categoryA = $this->category($branchA, 'Điện thoại');
        $categoryB = $this->category($branchB, 'Điện thoại');
        $productA = $this->product($storeA, $branchA, $categoryA, 'Sản phẩm A');
        $productB = $this->product($storeB, $branchB, $categoryB, 'Sản phẩm B');

        $storeHtml = $this->actingAs($storeA)->get('/admin/products', [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json('data.html');
        $this->assertStringContainsString('Sản phẩm A', $storeHtml);
        $this->assertStringNotContainsString('Sản phẩm B', $storeHtml);

        $adminHtml = $this->actingAs($administrator)->get('/admin/products', [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json('data.html');
        $this->assertStringContainsString('Sản phẩm A', $adminHtml);
        $this->assertStringContainsString('Sản phẩm B', $adminHtml);

        $this->actingAs($storeA)->get("/admin/products/{$productB->id}/edit")->assertNotFound();
        $this->actingAs($storeA)->putJson("/admin/products/{$productB->id}", [
            'name' => 'Không được sửa',
            'price' => 100,
            'price_buy' => 150,
            'product_unit' => 'Chiếc',
            'category_id' => $categoryB->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'status' => 'published',
        ])->assertNotFound();
        $this->actingAs($storeA)->getJson("/admin/category/{$categoryB->id}")->assertNotFound();
        $this->actingAs($storeA)->putJson("/admin/category/{$categoryB->id}", [
            'name' => 'Không được sửa',
            'status' => 1,
        ])->assertNotFound();
        $this->actingAs($storeA)->deleteJson("/admin/category/delete/{$categoryB->id}")
            ->assertNotFound();

        $this->actingAs($storeA)->postJson('/admin/bulk/status', [
            'model' => 'Product',
            'ids' => [$productA->id, $productB->id],
        ])->assertNotFound();
        $this->assertSame('published', $productA->fresh()->status);
        $this->assertSame('published', $productB->fresh()->status);
    }

    public function test_names_codes_and_barcodes_are_unique_per_branch(): void
    {
        [, $storeA, $storeB, $branchA, $branchB] = $this->actors();
        $categoryA = $this->category($branchA, 'Điện thoại');
        $categoryB = $this->category($branchB, 'Điện thoại');

        $productA = $this->product($storeA, $branchA, $categoryA, 'iPhone 15', 'SP-001', '8930000000001');
        $productB = $this->product($storeB, $branchB, $categoryB, 'iPhone 15', 'SP-001', '8930000000001');

        $productA->update(['name' => 'iPhone 15 - Branch A']);
        $this->assertSame('iPhone 15 - Branch A', $productA->fresh()->name);
        $this->assertSame('iPhone 15', $productB->fresh()->name);

        foreach ([
            fn () => $this->product($storeA, $branchA, $categoryA, 'Trùng code', 'SP-001', '8930000000099'),
            fn () => $this->product($storeA, $branchA, $categoryA, 'Trùng barcode', 'SP-099', '8930000000001'),
            fn () => Categories::create([
                'branch_id' => $branchA->id,
                'name' => 'Điện thoại',
                'status' => true,
            ]),
        ] as $duplicateWrite) {
            try {
                $duplicateWrite();
                $this->fail('A same-branch duplicate was accepted.');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_cross_branch_catalog_inventory_import_and_imei_links_are_rejected(): void
    {
        [, $storeA, $storeB, $branchA, $branchB] = $this->actors();
        $categoryA = $this->category($branchA, 'A');
        $categoryB = $this->category($branchB, 'B');
        $productA = $this->product($storeA, $branchA, $categoryA, 'Sản phẩm A');
        $storageB = Storage::create([
            'user_id' => $storeB->id,
            'branch_id' => $branchB->id,
            'name' => 'Kho B',
        ]);

        try {
            $productA->update(['category_id' => $categoryB->id]);
            $this->fail('Cross-branch category link was accepted.');
        } catch (ValidationException) {
            $this->assertSame($categoryA->id, $productA->fresh()->category_id);
        }

        foreach ([
            fn () => ProductStorage::create([
                'product_id' => $productA->id,
                'storage_id' => $storageB->id,
                'quantity' => 1,
            ]),
            fn () => ProductImei::create([
                'product_id' => $productA->id,
                'storage_id' => $storageB->id,
                'imei' => '356000000000001',
                'barcode' => '2900000000001',
                'status' => ProductImei::STATUS_IN_STOCK,
            ]),
        ] as $write) {
            try {
                $write();
                $this->fail('Cross-branch inventory link was accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $couponId = DB::table('import_coupon')->insertGetId([
            'user_id' => $storeB->id,
            'storage_id' => $storageB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        ImportDetail::create([
            'import_id' => $couponId,
            'product_id' => $productA->id,
            'quantity' => 1,
            'price' => 100,
        ]);
    }

    private function actors(): array
    {
        $administrator = $this->user(1, 'administrator@example.test');
        $storeA = $this->user(2, 'a@example.test');
        $storeB = $this->user(2, 'b@example.test');
        $branchA = Branch::create(['user_id' => $administrator->id, 'name' => 'A', 'address' => 'A']);
        $branchB = Branch::create(['user_id' => $administrator->id, 'name' => 'B', 'address' => 'B']);
        $storeA->update(['branch_id' => $branchA->id]);
        $storeB->update(['branch_id' => $branchB->id]);
        $branchA->update(['admin_store_user_id' => $storeA->id]);
        $branchB->update(['admin_store_user_id' => $storeB->id]);

        return [$administrator, $storeA, $storeB, $branchA, $branchB];
    }

    private function user(int $roleId, string $email): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'phone' => substr(md5($email), 0, 10),
            'password' => 'password',
            'role_id' => $roleId,
            'status' => 'active',
        ]);
    }

    private function category(Branch $branch, string $name): Categories
    {
        return Categories::create([
            'branch_id' => $branch->id,
            'name' => $name,
            'status' => true,
        ]);
    }

    private function brand(Branch $branch, string $name, ?string $description = null): Brand
    {
        return Brand::create([
            'branch_id' => $branch->id,
            'name' => $name,
            'description' => $description,
            'status' => true,
        ]);
    }

    private function company(User $owner, Branch $branch, string $name): Company
    {
        return Company::create([
            'user_id' => $owner->id,
            'branch_id' => $branch->id,
            'name' => $name,
            'phone' => '09'.str_pad((string) (Company::count() + 1), 8, '0', STR_PAD_LEFT),
            'address' => $branch->name,
            'status' => true,
        ]);
    }

    private function product(
        User $creator,
        Branch $branch,
        Categories $category,
        string $name,
        ?string $code = null,
        ?string $barcode = null,
        ?Brand $brand = null
    ): Product {
        return Product::create([
            'branch_id' => $branch->id,
            'user_id' => $creator->id,
            'category_id' => $category->id,
            'brands_id' => $brand?->id,
            'code' => $code ?? 'SP-'.$branch->id,
            'barcode' => $barcode ?? '893000000000'.$branch->id,
            'name' => $name,
            'price' => 100,
            'price_buy' => 150,
            'product_unit' => 'Chiếc',
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => '',
            'status' => 'published',
        ]);
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('user_info', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('img_url')->nullable();
            $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_store_user_id')->nullable();
            $table->string('name');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('address');
            $table->string('email')->nullable()->unique();
            $table->string('tax_number')->nullable()->unique();
            $table->string('bank_account')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->text('note')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brands_id')->nullable();
            $table->string('code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->decimal('price_buy', 15, 2);
            $table->string('thumbnail')->nullable();
            $table->string('product_unit')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('inventory_tracking');
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status');
            $table->timestamps();
            $table->unique(['branch_id', 'code']);
            $table->unique(['branch_id', 'barcode']);
        });
        Schema::create('company_product', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
            $table->unique(['product_id', 'company_id']);
        });
        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });
        Schema::create('product_storage', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity');
            $table->timestamps();
        });
        Schema::create('import_coupon', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('storage_id');
            $table->string('coupon_code')->nullable();
            $table->timestamps();
        });
        Schema::create('import_detail', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->integer('price');
            $table->integer('old_price')->nullable();
            $table->timestamps();
        });
        Schema::create('product_imeis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->unsignedBigInteger('import_detail_id')->nullable();
            $table->string('imei')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('status');
            $table->timestamp('printed_at')->nullable();
            $table->integer('print_count')->default(0);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('delete_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });
    }
}
