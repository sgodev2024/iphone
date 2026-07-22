<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Import as ImportItem;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Storage;
use App\Models\User;
use App\Services\CompanyProductService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ImportImeiWorkflowTest extends TestCase
{
    private User $admin;

    private Company $company;

    private Storage $storage;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '0900000000',
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
        $this->company = Company::create([
            'user_id' => $this->admin->id,
            'name' => 'Nhà cung cấp Apple',
        ]);
        $this->storage = Storage::create([
            'user_id' => $this->admin->id,
            'name' => 'Kho chính',
        ]);
        $this->product = Product::create([
            'user_id' => $this->admin->id,
            'code' => 'IP16',
            'name' => 'iPhone 16',
            'price' => 0,
            'price_buy' => 0,
            'quantity' => 0,
            'status' => true,
        ]);
    }

    public function test_valid_product_query_preselects_product_with_quantity_one(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/importproduct/add?product_id={$this->product->id}");

        $response->assertOk()
            ->assertSee('Danh sách IMEI')
            ->assertSee('Đã nhập 0/')
            ->assertSee('name="imeis[${item.id}][]"', false);

        $this->assertDatabaseHas('import', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
    }

    public function test_missing_or_invalid_product_query_opens_normal_form_without_preselection(): void
    {
        $otherAdmin = User::create([
            'name' => 'Other Admin',
            'email' => 'other@example.com',
            'phone' => '0911111111',
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
        $otherProduct = Product::create([
            'user_id' => $otherAdmin->id,
            'code' => 'OTHER',
            'name' => 'Other Product',
            'price' => 0,
            'price_buy' => 0,
            'quantity' => 0,
            'status' => true,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/importproduct/add?product_id=999999')
            ->assertOk();
        $this->actingAs($this->admin)
            ->get("/admin/importproduct/add?product_id={$otherProduct->id}")
            ->assertOk();
        $this->actingAs($this->admin)
            ->get('/admin/importproduct/add')
            ->assertOk();

        $this->assertDatabaseCount('import', 0);
    }

    public function test_confirming_import_creates_exact_imeis_linked_to_detail_and_increases_stock(): void
    {
        $item = $this->createImportItem(quantity: 3);

        $response = $this->actingAs($this->admin)
            ->post('/admin/importproduct/importCoupon', $this->validPayload($item, [
                ' 012345678901234 ',
                '123456789012345',
                '223456789012345',
            ]));

        $response->assertRedirect('/admin/importproduct')
            ->assertSessionHas('success', 'Nhập hàng và ghi nhận IMEI thành công.');

        $coupon = ImportCoupon::first();
        $detail = ImportDetail::first();

        $this->assertNotNull($coupon);
        $this->assertNotNull($detail);
        $this->assertSame($coupon->id, $detail->import_id);
        $this->assertDatabaseHas('product_imeis', [
            'product_id' => $this->product->id,
            'import_detail_id' => $detail->id,
            'imei' => '012345678901234',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        $this->assertDatabaseHas('product_imeis', [
            'product_id' => $this->product->id,
            'import_detail_id' => $detail->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        $this->assertDatabaseHas('product_imeis', [
            'product_id' => $this->product->id,
            'import_detail_id' => $detail->id,
            'imei' => '223456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $this->product->id,
            'storage_id' => $this->storage->id,
            'quantity' => 3,
        ]);
        $this->assertSame('3', (string) $this->product->fresh()->quantity);
        $this->assertDatabaseCount('import', 0);
    }

    public function test_confirmed_request_cannot_be_processed_twice(): void
    {
        $item = $this->createImportItem(quantity: 1);
        $payload = $this->validPayload($item, ['123456789012345']);

        $this->actingAs($this->admin)
            ->post('/admin/importproduct/importCoupon', $payload)
            ->assertRedirect('/admin/importproduct');

        $this->actingAs($this->admin)
            ->from('/admin/importproduct/add')
            ->post('/admin/importproduct/importCoupon', $payload)
            ->assertRedirect('/admin/importproduct/add')
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('import_coupon', 1);
        $this->assertDatabaseCount('import_detail', 1);
        $this->assertDatabaseCount('product_imeis', 1);
        $this->assertSame('1', (string) $this->product->fresh()->quantity);
    }

    public function test_database_unique_index_rejects_duplicate_imei(): void
    {
        ProductImei::create([
            'product_id' => $this->product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        ProductImei::create([
            'product_id' => $this->product->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
    }

    /**
     * @dataProvider invalidPayloadProvider
     */
    public function test_invalid_imei_payload_does_not_create_receipt_or_increase_stock(string $case): void
    {
        $item = $this->createImportItem(quantity: 2);
        ProductImei::create([
            'product_id' => $this->product->id,
            'imei' => '888888888888888',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $imeis = match ($case) {
            'missing' => ['123456789012345'],
            'excess' => ['123456789012345', '223456789012345', '323456789012345'],
            'invalid' => ['12345678901234A', '223456789012345'],
            'duplicate_in_receipt' => ['123456789012345', '123456789012345'],
            'duplicate_in_database' => ['888888888888888', '223456789012345'],
        };

        $response = $this->actingAs($this->admin)
            ->from('/admin/importproduct/add')
            ->post('/admin/importproduct/importCoupon', $this->validPayload($item, $imeis));

        $response->assertRedirect('/admin/importproduct/add')
            ->assertSessionHasErrors();
        $this->assertDatabaseCount('import_coupon', 0);
        $this->assertDatabaseCount('import_detail', 0);
        $this->assertDatabaseCount('product_imeis', 1);
        $this->assertDatabaseCount('product_storage', 0);
        $this->assertSame('0', (string) $this->product->fresh()->quantity);
        $this->assertDatabaseHas('import', ['id' => $item->id]);
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            'missing IMEI' => ['missing'],
            'excess IMEI' => ['excess'],
            'invalid IMEI' => ['invalid'],
            'duplicate in receipt' => ['duplicate_in_receipt'],
            'duplicate in database' => ['duplicate_in_database'],
        ];
    }

    public function test_late_failure_rolls_back_receipt_details_imeis_and_inventory(): void
    {
        $item = $this->createImportItem(quantity: 1);
        $companyProductService = Mockery::mock(CompanyProductService::class);
        $companyProductService->shouldReceive('updateCompanyProduct')
            ->once()
            ->andThrow(new RuntimeException('Late failure'));
        $this->app->instance(CompanyProductService::class, $companyProductService);

        $response = $this->actingAs($this->admin)
            ->from('/admin/importproduct/add')
            ->post('/admin/importproduct/importCoupon', $this->validPayload($item, [
                '123456789012345',
            ]));

        $response->assertRedirect('/admin/importproduct/add')
            ->assertSessionHas('error');
        $this->assertDatabaseCount('import_coupon', 0);
        $this->assertDatabaseCount('import_detail', 0);
        $this->assertDatabaseCount('product_imeis', 0);
        $this->assertDatabaseCount('product_storage', 0);
        $this->assertSame('0', (string) $this->product->fresh()->quantity);
        $this->assertDatabaseHas('import', ['id' => $item->id]);
    }

    public function test_confirmed_import_with_imei_cannot_be_hard_deleted(): void
    {
        $coupon = ImportCoupon::create([
            'user_id' => $this->admin->id,
            'companies_id' => $this->company->id,
            'total' => 0,
            'payment_ncc' => 0,
            'storage_id' => $this->storage->id,
        ]);
        $detail = ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 0,
            'old_price' => 0,
        ]);
        ProductImei::create([
            'product_id' => $this->product->id,
            'import_detail_id' => $detail->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/admin/importproduct/bulk-delete', ['ids' => [$coupon->id]])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => "Không thể xóa phiếu nhập {$coupon->coupon_code} vì dữ liệu IMEI đã được ghi nhận vào kho.",
            ]);

        $this->assertDatabaseHas('import_coupon', ['id' => $coupon->id]);
        $this->assertDatabaseHas('product_imeis', ['import_detail_id' => $detail->id]);
    }

    private function createImportItem(int $quantity): ImportItem
    {
        return ImportItem::create([
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'price' => 0,
            'total' => 0,
        ]);
    }

    private function validPayload(ImportItem $item, array $imeis): array
    {
        return [
            'supplier' => $this->company->id,
            'storage' => $this->storage->id,
            'total' => 0,
            'totalncc' => 0,
            'imeis' => [
                $item->id => $imeis,
            ],
        ];
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
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

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
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

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('quantity')->nullable()->default('0');
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

        Schema::create('import', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
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
            $table->string('imei', 15)->unique();
            $table->string('status', 30)->default(ProductImei::STATUS_IN_STOCK);
            $table->timestamps();
        });

        Schema::create('company_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
            $table->unique(['product_id', 'company_id']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
}
