<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImei;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductImeiManagementTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->admin = $this->createAdmin('admin@example.com');
    }

    public function test_imei_page_is_read_only_and_links_to_the_existing_import_form(): void
    {
        $product = $this->createProduct($this->admin, ['name' => 'iPhone 16']);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/products/{$product->id}/imeis");

        $response->assertOk()
            ->assertSee('Quản lý IMEI – iPhone 16')
            ->assertSee('123456789012345')
            ->assertSee('Dữ liệu cũ')
            ->assertSee('Nhập thêm hàng')
            ->assertSee("/admin/importproduct/add?product_id={$product->id}", false)
            ->assertDontSee('Thêm IMEI cho')
            ->assertDontSee('Xóa IMEI');
    }

    public function test_imei_page_only_searches_within_the_selected_product(): void
    {
        $product = $this->createProduct($this->admin);
        $otherProduct = $this->createProduct($this->admin);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        ProductImei::create([
            'product_id' => $otherProduct->id,
            'imei' => '123000000000000',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/products/{$product->id}/imeis?search=456789")
            ->assertOk()
            ->assertSee('123456789012345')
            ->assertDontSee('123000000000000');
    }

    public function test_direct_store_and_destroy_imei_routes_no_longer_exist(): void
    {
        $product = $this->createProduct($this->admin);
        $imei = ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/products/{$product->id}/imeis", ['imei' => '999999999999999'])
            ->assertMethodNotAllowed();
        $this->actingAs($this->admin)
            ->delete("/admin/products/{$product->id}/imeis/{$imei->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('product_imeis', ['id' => $imei->id]);
    }

    public function test_another_users_product_cannot_be_viewed(): void
    {
        $otherAdmin = $this->createAdmin('other@example.com');
        $product = $this->createProduct($otherAdmin);

        $this->actingAs($this->admin)
            ->get("/admin/products/{$product->id}/imeis")
            ->assertNotFound();
    }

    public function test_product_list_uses_in_stock_imei_count_and_contains_management_button(): void
    {
        $product = $this->createProduct($this->admin, ['quantity' => 99]);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '999999999999999',
            'status' => ProductImei::STATUS_SOLD,
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/products');

        $html = $response->json('data.html');
        $this->assertStringContainsString("/admin/products/{$product->id}/imeis", $html);
        $this->assertMatchesRegularExpression('/<td>\s*1\s*<\/td>/', $html);
        $this->assertStringNotContainsString('<td>99</td>', $html);
    }

    public function test_product_with_imei_cannot_be_deleted(): void
    {
        $product = $this->createProduct($this->admin);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/admin/bulk/delete', [
                'ids' => [$product->id],
                'model' => 'Product',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
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

        Schema::create('config', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('quantity')->nullable()->default('0');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_imeis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('import_detail_id')->nullable();
            $table->string('imei', 15)->unique();
            $table->string('status', 30)->default(ProductImei::STATUS_IN_STOCK);
            $table->timestamps();
            $table->index(['product_id', 'status']);
        });
    }

    private function createAdmin(string $email): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'phone' => fake()->unique()->numerify('09########'),
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
    }

    private function createProduct(User $user, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $user->id,
            'code' => 'SP'.fake()->unique()->numerify('######'),
            'name' => 'iPhone Test',
            'price' => 20000000,
            'price_buy' => 18000000,
            'quantity' => 0,
            'status' => true,
        ], $attributes));
    }
}
