<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Branch;
use App\Models\Categories;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Storage as StorageModel;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminProductCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        Storage::fake('public');
    }

    public function test_admin_can_create_product_without_quantity(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $brand = Brand::create(['name' => 'Apple']);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'iPhone 15 Pro',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'brands_id' => $brand->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'description' => 'May moi',
            'status' => 'published',
            'thumbnail' => UploadedFile::fake()->image('iphone.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'iPhone 15 Pro')->first();

        $this->assertNotNull($product);
        $this->assertSame('0', (string) $product->quantity);
        $this->assertSame(Product::INVENTORY_TRACKING_IMEI, $product->inventory_tracking);
    }

    public function test_admin_store_sees_created_product_without_inventory_with_zero_stock(): void
    {
        $branch = $this->createBranch('Branch A');
        $adminStore = $this->createAdminStore($branch);
        $category = Categories::create(['name' => 'Dien thoai']);

        $this->actingAs($adminStore)->post('/admin/products', [
            'name' => 'Global product without inventory',
            'price' => 20000000,
            'price_buy' => 18000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => null,
            'status' => 'published',
        ], ['Accept' => 'application/json'])
            ->assertCreated();

        $product = Product::where('name', 'Global product without inventory')->firstOrFail();

        $this->assertFalse(
            ProductStorage::where('product_id', $product->id)->exists()
        );

        $row = $this->productRow(
            $this->ajaxProductHtml($adminStore),
            $product->name
        );

        $this->assertStringContainsString('<td>0</td>', $row);
    }

    public function test_administrator_sees_global_product_with_global_stock(): void
    {
        $branchA = $this->createBranch('Branch A');
        $branchB = $this->createBranch('Branch B');
        $adminStore = $this->createAdminStore($branchA);
        $administrator = $this->createAdmin();
        $storageA = $this->createStorage($branchA, 'Storage A');
        $storageB = $this->createStorage($branchB, 'Storage B');
        $product = $this->createProduct($adminStore, 'Administrator visible product');

        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageA->id,
            'quantity' => 2,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageB->id,
            'quantity' => 5,
        ]);

        $row = $this->productRow(
            $this->ajaxProductHtml($administrator),
            $product->name
        );

        $this->assertStringContainsString('<td>7</td>', $row);
    }

    public function test_admin_store_sees_catalog_product_but_not_stock_from_another_branch(): void
    {
        $branchA = $this->createBranch('Branch A');
        $branchB = $this->createBranch('Branch B');
        $adminStore = $this->createAdminStore($branchA);
        $storageB = $this->createStorage($branchB, 'Storage B');
        $product = $this->createProduct($adminStore, 'Cross branch stock product');

        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageB->id,
            'quantity' => 9,
        ]);

        $row = $this->productRow(
            $this->ajaxProductHtml($adminStore),
            $product->name
        );

        $this->assertStringContainsString('<td>0</td>', $row);
        $this->assertStringNotContainsString('<td>9</td>', $row);
    }

    public function test_admin_store_sees_only_own_branch_stock_for_global_product(): void
    {
        $branchA = $this->createBranch('Branch A');
        $branchB = $this->createBranch('Branch B');
        $adminStore = $this->createAdminStore($branchA);
        $storageA = $this->createStorage($branchA, 'Storage A');
        $storageB = $this->createStorage($branchB, 'Storage B');
        $product = $this->createProduct($adminStore, 'Own branch stock product');

        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageA->id,
            'quantity' => 3,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageB->id,
            'quantity' => 9,
        ]);

        $row = $this->productRow(
            $this->ajaxProductHtml($adminStore),
            $product->name
        );

        $this->assertStringContainsString('<td>3</td>', $row);
        $this->assertStringNotContainsString('<td>12</td>', $row);
    }

    public function test_admin_store_imei_stock_count_remains_branch_scoped(): void
    {
        $branchA = $this->createBranch('Branch A');
        $branchB = $this->createBranch('Branch B');
        $adminStore = $this->createAdminStore($branchA);
        $storageA = $this->createStorage($branchA, 'Storage A');
        $storageB = $this->createStorage($branchB, 'Storage B');
        $product = $this->createProduct(
            $adminStore,
            'Branch scoped IMEI product',
            Product::INVENTORY_TRACKING_IMEI
        );

        ProductImei::create([
            'product_id' => $product->id,
            'storage_id' => $storageA->id,
            'imei' => '111111111111111',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        ProductImei::create([
            'product_id' => $product->id,
            'storage_id' => $storageB->id,
            'imei' => '222222222222222',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $row = $this->productRow(
            $this->ajaxProductHtml($adminStore),
            $product->name
        );

        $this->assertStringContainsString('<td>1</td>', $row);
        $this->assertStringNotContainsString('<td>2</td>', $row);
    }

    public function test_admin_store_latest_import_remains_branch_scoped(): void
    {
        $branchA = $this->createBranch('Branch A');
        $branchB = $this->createBranch('Branch B');
        $adminStore = $this->createAdminStore($branchA);
        $storageA = $this->createStorage($branchA, 'Storage A');
        $storageB = $this->createStorage($branchB, 'Storage B');
        $product = $this->createProduct($adminStore, 'Branch scoped latest import');
        $importA = DB::table('import_coupon')->insertGetId([
            'storage_id' => $storageA->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $importB = DB::table('import_coupon')->insertGetId([
            'storage_id' => $storageB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('import_detail')->insert([
            [
                'import_id' => $importA,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 18000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'import_id' => $importB,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 18000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $row = $this->productRow(
            $this->ajaxProductHtml($adminStore),
            $product->name
        );

        $this->assertStringContainsString(
            route('admin.importproduct.importCoupon.detail', $importA, false),
            $row
        );
        $this->assertStringNotContainsString(
            route('admin.importproduct.importCoupon.detail', $importB, false),
            $row
        );
    }

    public function test_admin_store_export_includes_global_catalog_and_only_own_branch_stock(): void
    {
        $branchA = $this->createBranch('Branch A');
        $branchB = $this->createBranch('Branch B');
        $adminStore = $this->createAdminStore($branchA);
        $storageA = $this->createStorage($branchA, 'Storage A');
        $storageB = $this->createStorage($branchB, 'Storage B');
        $withoutInventory = $this->createProduct($adminStore, 'Export without inventory');
        $withStock = $this->createProduct($adminStore, 'Export scoped stock');

        ProductStorage::create([
            'product_id' => $withStock->id,
            'storage_id' => $storageA->id,
            'quantity' => 4,
        ]);
        ProductStorage::create([
            'product_id' => $withStock->id,
            'storage_id' => $storageB->id,
            'quantity' => 8,
        ]);

        $response = $this->actingAs($adminStore)->get('/admin/products/export');

        $response->assertOk();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'product-export-');
        $this->assertNotFalse($temporaryFile);

        try {
            file_put_contents($temporaryFile, $response->streamedContent());
            $rows = IOFactory::load($temporaryFile)
                ->getActiveSheet()
                ->toArray();
            $rowsByName = collect(array_slice($rows, 1))
                ->keyBy(fn (array $row) => $row[1]);

            $this->assertTrue($rowsByName->has($withoutInventory->name));
            $this->assertSame(0, (int) $rowsByName->get($withoutInventory->name)[2]);
            $this->assertSame(4, (int) $rowsByName->get($withStock->name)[2]);
        } finally {
            if (is_string($temporaryFile) && file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function test_admin_can_create_product_without_thumbnail(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'iPhone Without Thumbnail',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => 'May moi',
            'status' => 'published',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'iPhone Without Thumbnail')->first();

        $this->assertNotNull($product);
        $this->assertNull($product->thumbnail);
        $this->assertSame(asset(Product::DEFAULT_THUMBNAIL), $product->thumbnail_url);
    }

    public function test_inventory_tracking_is_required_when_creating_product(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);

        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'iPhone Missing Tracking',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'description' => 'May moi',
            'status' => 'published',
            'thumbnail' => UploadedFile::fake()->image('iphone.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['inventory_tracking']);
    }

    public function test_submitted_quantity_is_ignored_when_creating_product(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'iPhone 16',
            'price' => 22000000,
            'price_buy' => 25000000,
            'product_unit' => 'chiec',
            'quantity' => 99,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'category_id' => $category->id,
            'description' => 'May moi',
            'status' => 'published',
            'thumbnail' => UploadedFile::fake()->image('iphone-16.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'iPhone 16')->first();

        $this->assertNotNull($product);
        $this->assertSame('0', (string) $product->quantity);
        $this->assertSame(Product::INVENTORY_TRACKING_QUANTITY, $product->inventory_tracking);
    }

    public function test_admin_can_create_product_with_empty_description(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'iPhone Empty Description',
            'price' => 22000000,
            'price_buy' => 25000000,
            'product_unit' => 'chiec',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'category_id' => $category->id,
            'description' => '',
            'status' => 'published',
            'thumbnail' => UploadedFile::fake()->image('iphone-empty-description.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'iPhone Empty Description')->first();

        $this->assertNotNull($product);
        $this->assertNull($product->description);
        $this->assertSame(Product::INVENTORY_TRACKING_QUANTITY, $product->inventory_tracking);
    }

    public function test_admin_can_clear_product_description_on_update_without_changing_thumbnail(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $product = Product::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'code' => 'SPDESC001',
            'name' => 'iPhone Clear Description',
            'price' => 20000000,
            'price_buy' => 23000000,
            'thumbnail' => 'products/existing.webp',
            'product_unit' => 'chiec',
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => 'May moi',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'iPhone Clear Description',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => '',
            'status' => 'published',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $product->refresh();

        $this->assertNull($product->description);
        $this->assertSame('products/existing.webp', $product->thumbnail);
        $this->assertSame(Product::INVENTORY_TRACKING_QUANTITY, $product->inventory_tracking);
    }

    public function test_admin_can_update_product_with_description_without_affecting_inventory_or_imei(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $product = Product::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'code' => 'SPDESC002',
            'name' => 'iPhone Keep Description',
            'price' => 20000000,
            'price_buy' => 23000000,
            'thumbnail' => 'products/existing.webp',
            'product_unit' => 'chiec',
            'quantity' => 1,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'description' => 'Mo ta cu',
            'status' => 'published',
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => 1,
            'quantity' => 1,
        ]);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $response = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'iPhone Keep Description',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'description' => 'Mo ta moi',
            'status' => 'published',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $product->refresh();

        $this->assertSame('Mo ta moi', $product->description);
        $this->assertSame('products/existing.webp', $product->thumbnail);
        $this->assertSame(Product::INVENTORY_TRACKING_IMEI, $product->inventory_tracking);
        $this->assertSame(1, ProductStorage::where('product_id', $product->id)->count());
        $this->assertSame(1, ProductImei::where('product_id', $product->id)->count());
    }

    public function test_create_and_edit_product_forms_do_not_render_quantity_field(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $brand = Brand::create(['name' => 'Apple']);
        $product = Product::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'brands_id' => $brand->id,
            'code' => 'SPFORM001',
            'name' => 'iPhone Form Test',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'quantity' => 7,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'description' => 'May moi',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/admin/products/create')
            ->assertOk()
            ->assertSee('name="inventory_tracking"', false)
            ->assertSee('Quản lý theo IMEI')
            ->assertSee('Sản phẩm thường')
            ->assertDontSee('name="quantity"', false);

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('name="inventory_tracking"', false)
            ->assertDontSee('name="quantity"', false);
    }

    public function test_product_without_activity_can_change_inventory_tracking(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $product = Product::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'code' => 'SPCHANGE001',
            'name' => 'iPhone Change Tracking',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'description' => 'May moi',
            'status' => 'published',
        ]);

        $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'iPhone Change Tracking',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => 'May moi',
            'status' => 'published',
        ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame(Product::INVENTORY_TRACKING_QUANTITY, $product->fresh()->inventory_tracking);
    }

    public function test_product_with_inventory_activity_cannot_change_inventory_tracking(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $product = Product::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'code' => 'SPLOCK001',
            'name' => 'iPhone Locked Tracking',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'quantity' => 1,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'description' => 'May moi',
            'status' => 'published',
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => 1,
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Không thể thay đổi phương thức quản lý tồn kho vì sản phẩm đã phát sinh dữ liệu kho hoặc giao dịch.');

        $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'iPhone Locked Tracking',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'category_id' => $category->id,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => 'May moi',
            'status' => 'published',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['inventory_tracking']);

        $this->assertSame(Product::INVENTORY_TRACKING_IMEI, $product->fresh()->inventory_tracking);
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(1);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_store_user_id')->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
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

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('code')->nullable();
            $table->boolean('notification')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brands_id')->nullable();
            $table->string('code')->nullable()->unique();
            $table->string('name')->index();
            $table->decimal('price', 15, 2)->unsigned()->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('product_unit')->nullable();
            $table->string('quantity')->nullable()->default('0');
            $table->string('inventory_tracking', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('is_featured')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();
        });

        Schema::create('product_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('import_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->integer('price')->default(0);
            $table->timestamps();
        });

        Schema::create('import_coupon', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('product_imeis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('imei', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('status', 30)->default(ProductImei::STATUS_IN_STOCK);
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamps();

            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('delete_reason', 500)->nullable();
            $table->softDeletes();
        });
    }

    private function ajaxProductHtml(User $user): string
    {
        $response = $this->actingAs($user)->get('/admin/products', [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        return (string) $response->json('data.html');
    }

    private function productRow(string $html, string $productName): string
    {
        $matched = preg_match(
            '/<tr>.*?'.preg_quote($productName, '/').'.*?<\/tr>/s',
            $html,
            $matches
        );

        $this->assertSame(1, $matched, "Product row not found for {$productName}.");

        return $matches[0];
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'status' => true,
        ]);
    }

    private function createStorage(Branch $branch, string $name): StorageModel
    {
        return StorageModel::create([
            'branch_id' => $branch->id,
            'name' => $name,
        ]);
    }

    private function createAdminStore(Branch $branch): User
    {
        return User::create([
            'name' => "Admin Store {$branch->id}",
            'email' => "admin-store-{$branch->id}@example.com",
            'phone' => '09022222'.str_pad((string) $branch->id, 2, '0', STR_PAD_LEFT),
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
    }

    private function createProduct(
        User $creator,
        string $name,
        string $inventoryTracking = Product::INVENTORY_TRACKING_QUANTITY
    ): Product {
        $category = Categories::firstOrCreate(['name' => 'Dien thoai']);

        return Product::create([
            'user_id' => $creator->id,
            'category_id' => $category->id,
            'code' => 'SP'.str_pad((string) (Product::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => $name,
            'price' => 20000000,
            'price_buy' => 18000000,
            'product_unit' => 'chiec',
            'quantity' => 0,
            'inventory_tracking' => $inventoryTracking,
            'status' => 'published',
        ]);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '0901111111',
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
    }
}
