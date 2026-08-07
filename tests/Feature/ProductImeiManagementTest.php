<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_quantity_tracked_product_imei_page_reports_not_applicable(): void
    {
        $product = $this->createProduct($this->admin, [
            'name' => 'Sac nhanh',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/products/{$product->id}/imeis")
            ->assertOk()
            ->assertSee('Sản phẩm này là sản phẩm thường và không có dữ liệu IMEI.')
            ->assertDontSee('Danh sách IMEI');
    }

    public function test_product_list_uses_tracking_specific_stock_and_imei_button(): void
    {
        $product = $this->createProduct($this->admin, ['quantity' => 99]);
        $quantityProduct = $this->createProduct($this->admin, [
            'name' => 'Sac nhanh',
            'quantity' => 50,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
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
        \App\Models\ProductStorage::create([
            'product_id' => $quantityProduct->id,
            'storage_id' => 1,
            'quantity' => 8,
        ]);
        \App\Models\ProductStorage::create([
            'product_id' => $quantityProduct->id,
            'storage_id' => 2,
            'quantity' => 12,
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/products');

        $html = $response->json('data.html');
        $this->assertStringContainsString("/admin/products/{$product->id}/imeis", $html);
        $this->assertStringNotContainsString("/admin/products/{$quantityProduct->id}/imeis", $html);
        $this->assertStringContainsString('IMEI', $html);
        $this->assertStringContainsString('Sản phẩm thường', $html);
        $this->assertMatchesRegularExpression('/<td>\s*1\s*<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td>\s*20\s*<\/td>/', $html);
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

    public function test_admin_can_browse_and_filter_all_imeis_with_historical_import_data(): void
    {
        $product = $this->createProduct($this->admin, [
            'code' => 'IP16',
            'name' => 'iPhone 16 Pro',
        ]);
        $company = Company::create([
            'user_id' => $this->admin->id,
            'name' => 'Apple Việt Nam',
        ]);
        $coupon = ImportCoupon::create([
            'user_id' => $this->admin->id,
            'companies_id' => $company->id,
            'total' => 2000000,
        ]);
        $coupon->forceFill(['created_at' => Carbon::parse('2026-07-10 09:30:00')])->save();
        $detail = ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 2000000,
            'old_price' => 1900000,
        ]);
        ProductImei::create([
            'product_id' => $product->id,
            'import_detail_id' => $detail->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012346',
            'status' => ProductImei::STATUS_SOLD,
        ]);
        ProductImei::create([
            'product_id' => $product->id,
            'imei' => '123456789012347',
            'status' => 'returned',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.imeis.index', [
            'imei' => '123456789012345',
            'product' => 'IP16',
            'status' => ProductImei::STATUS_IN_STOCK,
            'company_id' => $company->id,
            'coupon_code' => $coupon->coupon_code,
            'from_date' => '2026-07-10',
            'to_date' => '2026-07-10',
        ]));

        $response->assertOk()
            ->assertSee('Quản lý IMEI')
            ->assertSee('123456789012345')
            ->assertDontSee('123456789012346')
            ->assertSee('IP16')
            ->assertSee('iPhone 16 Pro')
            ->assertSee($coupon->coupon_code)
            ->assertSee('Apple Việt Nam')
            ->assertSee('2.000.000 đ')
            ->assertSee('10/07/2026')
            ->assertSee(route('admin.products.imeis.index', $product), false)
            ->assertSee(route('admin.importproduct.importCoupon.detail', $coupon->id), false)
            ->assertViewHas('statistics', fn(array $statistics) => $statistics === [
                'total' => 3,
                'in_stock' => 1,
                'sold' => 1,
                'other' => 1,
            ]);
    }

    public function test_global_imei_page_excludes_orphan_and_quantity_products_and_keeps_pagination_filters(): void
    {
        $product = $this->createProduct($this->admin, [
            'code' => 'IMEIPAGE',
            'name' => 'iPhone Pagination',
        ]);
        $quantityProduct = $this->createProduct($this->admin, [
            'name' => 'Sac nhanh',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);

        ProductImei::create([
            'product_id' => 999999,
            'imei' => '900000000000000',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        ProductImei::create([
            'product_id' => $quantityProduct->id,
            'imei' => '800000000000000',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        foreach (range(1, 10) as $number) {
            ProductImei::create([
                'product_id' => $product->id,
                'imei' => str_pad((string) $number, 15, '0', STR_PAD_LEFT),
                'status' => ProductImei::STATUS_IN_STOCK,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.imeis.index', [
            'status' => ProductImei::STATUS_IN_STOCK,
            'from_date' => '2026-07-11',
            'to_date' => '2026-07-10',
        ]));

        $response->assertOk()
            ->assertSee('Từ ngày phải nhỏ hơn hoặc bằng đến ngày.')
            ->assertSee('iPhone Pagination')
            ->assertDontSee('900000000000000')
            ->assertDontSee('800000000000000')
            ->assertDontSee('Sac nhanh')
            ->assertViewHas('imeis', function ($imeis) {
                return $imeis->perPage() === 10
                    && str_contains($imeis->url(2), 'status=in_stock');
            });
    }

    public function test_warehouse_role_cannot_access_global_imei_management(): void
    {
        $warehouseUser = $this->createAdmin('warehouse@example.com');
        $warehouseUser->update(['role_id' => 4]);

        $this->actingAs($warehouseUser)
            ->get(route('admin.imeis.index'))
            ->assertForbidden();
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
            $table->string('inventory_tracking', 20)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('import_coupon', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('companies_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->integer('total')->nullable();
            $table->integer('payment_ncc')->nullable();
            $table->string('status')->nullable();
            $table->string('coupon_code')->nullable()->unique();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->timestamps();
        });

        Schema::create('import_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->integer('price');
            $table->integer('old_price')->nullable();
            $table->timestamps();
        });

        Schema::create('product_imeis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('import_detail_id')->nullable();
            $table->string('imei', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('status', 30)->default(ProductImei::STATUS_IN_STOCK);
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('delete_reason', 500)->nullable();
            $table->softDeletes();
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
            'code' => 'SP' . fake()->unique()->numerify('######'),
            'name' => 'iPhone Test',
            'price' => 20000000,
            'price_buy' => 18000000,
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'status' => true,
        ], $attributes));
    }
}
