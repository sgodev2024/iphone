<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Categories;
use App\Models\Product;
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
            'brand_id' => $brand->id,
            'description' => 'May moi',
            'status' => 1,
            'thumbnail' => UploadedFile::fake()->image('iphone.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'iPhone 15 Pro')->first();

        $this->assertNotNull($product);
        $this->assertSame('0', (string) $product->quantity);
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
            'category_id' => $category->id,
            'description' => 'May moi',
            'status' => 1,
            'thumbnail' => UploadedFile::fake()->image('iphone-16.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'iPhone 16')->first();

        $this->assertNotNull($product);
        $this->assertSame('0', (string) $product->quantity);
    }

    public function test_create_and_edit_product_forms_do_not_render_quantity_field(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create(['name' => 'Dien thoai']);
        $brand = Brand::create(['name' => 'Apple']);
        $product = Product::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'code' => 'SPFORM001',
            'name' => 'iPhone Form Test',
            'price' => 20000000,
            'price_buy' => 23000000,
            'product_unit' => 'chiec',
            'quantity' => 7,
            'description' => 'May moi',
            'status' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/products/create')
            ->assertOk()
            ->assertDontSee('name="quantity"', false);

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertDontSee('name="quantity"', false);
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
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('code')->nullable()->unique();
            $table->string('name')->index();
            $table->decimal('price', 15, 2)->unsigned()->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('product_unit')->nullable();
            $table->string('quantity')->nullable()->default('0');
            $table->text('description')->nullable();
            $table->string('is_featured')->nullable();
            $table->boolean('status')->default(1);
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
