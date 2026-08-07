<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportProductBulkDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_bulk_delete_rolls_back_inventory_and_removes_duplicate_ids(): void
    {
        $user = $this->createUser(roleId: 4);
        $storage = Storage::create(['user_id' => $user->id, 'name' => 'Kho nhập']);
        $product = $this->createProduct(['quantity' => 10]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);
        $coupon = $this->createImportCoupon($user, $storage, ['total' => 0, 'payment_ncc' => 0]);
        ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 1000,
            'old_price' => 900,
        ]);

        $response = $this->actingAs($user)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => [$coupon->id, $coupon->id],
        ]);

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Đã xóa thành công 1 phiếu nhập.']);

        $this->assertDatabaseCount('import_coupon', 0);
        $this->assertDatabaseCount('import_detail', 0);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 7,
        ]);
        $this->assertSame('7', (string) $product->fresh()->quantity);
    }

    public function test_bulk_delete_blocks_supplier_debt_and_keeps_inventory_unchanged(): void
    {
        $user = $this->createUser(roleId: 4);
        $storage = Storage::create(['user_id' => $user->id, 'name' => 'Kho nhập']);
        $product = $this->createProduct(['quantity' => 10]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);
        $coupon = $this->createImportCoupon($user, $storage, ['total' => 5000, 'payment_ncc' => 0]);
        ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => [$coupon->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => "Phiếu nhập {$coupon->coupon_code} đã phát sinh công nợ nhà cung cấp, không thể xóa.",
            ]);

        $this->assertDatabaseCount('import_coupon', 1);
        $this->assertDatabaseCount('import_detail', 1);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 10,
        ]);
        $this->assertSame('10', (string) $product->fresh()->quantity);
    }

    public function test_bulk_delete_blocks_when_current_stock_is_not_enough_to_rollback(): void
    {
        $user = $this->createUser(roleId: 4);
        $storage = Storage::create(['user_id' => $user->id, 'name' => 'Kho nhập']);
        $product = $this->createProduct(['quantity' => 2]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 2]);
        $coupon = $this->createImportCoupon($user, $storage, ['total' => 0, 'payment_ncc' => 0]);
        ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => [$coupon->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => "Sản phẩm #{$product->id} trong kho #{$storage->id} không đủ tồn kho để hoàn tác phiếu nhập.",
            ]);

        $this->assertDatabaseCount('import_coupon', 1);
        $this->assertDatabaseCount('import_detail', 1);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 2,
        ]);
        $this->assertSame('2', (string) $product->fresh()->quantity);
    }

    public function test_bulk_delete_rolls_back_transaction_when_one_selected_coupon_cannot_be_deleted(): void
    {
        $user = $this->createUser(roleId: 4);
        $storage = Storage::create(['user_id' => $user->id, 'name' => 'Kho nhập']);
        $firstProduct = $this->createProduct(['quantity' => 5]);
        $secondProduct = $this->createProduct(['name' => 'iPhone 16', 'quantity' => 2]);
        ProductStorage::create(['product_id' => $firstProduct->id, 'storage_id' => $storage->id, 'quantity' => 5]);
        ProductStorage::create(['product_id' => $secondProduct->id, 'storage_id' => $storage->id, 'quantity' => 2]);
        $firstCoupon = $this->createImportCoupon($user, $storage, ['total' => 0, 'payment_ncc' => 0]);
        $secondCoupon = $this->createImportCoupon($user, $storage, ['total' => 0, 'payment_ncc' => 0]);

        ImportDetail::create([
            'import_id' => $firstCoupon->id,
            'product_id' => $firstProduct->id,
            'quantity' => 3,
            'price' => 1000,
        ]);
        ImportDetail::create([
            'import_id' => $secondCoupon->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
            'price' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => [$firstCoupon->id, $secondCoupon->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => "Sản phẩm #{$secondProduct->id} trong kho #{$storage->id} không đủ tồn kho để hoàn tác phiếu nhập.",
            ]);

        $this->assertDatabaseCount('import_coupon', 2);
        $this->assertDatabaseCount('import_detail', 2);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $firstProduct->id,
            'storage_id' => $storage->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $secondProduct->id,
            'storage_id' => $storage->id,
            'quantity' => 2,
        ]);
        $this->assertSame('5', (string) $firstProduct->fresh()->quantity);
        $this->assertSame('2', (string) $secondProduct->fresh()->quantity);
    }

    public function test_bulk_delete_validates_ids_as_array(): void
    {
        $user = $this->createUser(roleId: 4);

        $response = $this->actingAs($user)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => 1,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    }

    public function test_warehouse_user_cannot_delete_another_users_import_coupon(): void
    {
        $owner = $this->createUser(roleId: 4, email: 'owner@example.com');
        $otherUser = $this->createUser(roleId: 4, email: 'other@example.com');
        $storage = Storage::create(['user_id' => $owner->id, 'name' => 'Kho nhập']);
        $coupon = $this->createImportCoupon($owner, $storage, ['total' => 0, 'payment_ncc' => 0]);

        $response = $this->actingAs($otherUser)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => [$coupon->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Không tìm thấy phiếu nhập phù hợp hoặc bạn không có quyền xóa.',
            ]);

        $this->assertDatabaseCount('import_coupon', 1);
    }

    public function test_non_warehouse_role_cannot_bulk_delete_import_coupon(): void
    {
        $user = $this->createUser(roleId: 3);
        $storage = Storage::create(['user_id' => $user->id, 'name' => 'Kho nhập']);
        $coupon = $this->createImportCoupon($user, $storage, ['total' => 0, 'payment_ncc' => 0]);

        $response = $this->actingAs($user)->postJson('/admin/importproduct/bulk-delete', [
            'ids' => [$coupon->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('import_coupon', 1);
    }

    public function test_import_product_index_uses_bulk_action_and_distinct_checkbox_selectors(): void
    {
        $view = file_get_contents(resource_path('views/admin/Importproduct/index.blade.php'));

        $this->assertStringContainsString('id="bulk-delete"', $view);
        $this->assertStringContainsString('Xóa đã chọn', $view);
        $this->assertStringContainsString('id="select-all"', $view);
        $this->assertStringContainsString('class="row-checkbox"', $view);
        $this->assertStringContainsString('name="ids[]"', $view);
        $this->assertStringContainsString('Vui lòng chọn ít nhất một phiếu nhập cần xóa.', $view);
        $this->assertStringContainsString('Bạn có chắc chắn muốn xóa các phiếu nhập đã chọn không?', $view);
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(4);
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('img_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('bank_account')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->text('note')->nullable();
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

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('product_unit')->nullable();
            $table->string('quantity')->nullable();
            $table->string('inventory_tracking', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'storage_id']);
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
            $table->string('status', 30)->default('in_stock');
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('delete_reason', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    private function createUser(int $roleId, string $email = 'warehouse@example.com'): User
    {
        return User::create([
            'name' => 'Warehouse User',
            'email' => $email,
            'phone' => uniqid('09'),
            'password' => 'password',
            'role_id' => $roleId,
            'status' => 'active',
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => 1,
            'code' => uniqid('IP'),
            'name' => 'iPhone 15',
            'price' => 1000,
            'price_buy' => 900,
            'thumbnail' => null,
            'product_unit' => 'cái',
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => 'Test product',
            'status' => true,
        ], $overrides));
    }

    private function createImportCoupon(User $user, Storage $storage, array $overrides = []): ImportCoupon
    {
        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Nhà cung cấp',
        ]);

        return ImportCoupon::create(array_merge([
            'user_id' => $user->id,
            'companies_id' => $company->id,
            'supplier_id' => null,
            'total' => 0,
            'payment_ncc' => 0,
            'status' => null,
            'storage_id' => $storage->id,
        ], $overrides));
    }
}
