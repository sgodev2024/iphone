<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Categories;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Quản lý theo số lượng')
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(1);
            $table->rememberToken();
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
            $table->string('imei', 15)->unique();
            $table->string('status', 30)->default(ProductImei::STATUS_IN_STOCK);
            $table->timestamps();
        });
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
