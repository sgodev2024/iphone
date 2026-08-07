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
use App\Services\InternalBarcodeService;
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

    private Product $quantityProduct;

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
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'status' => true,
        ]);
        $this->quantityProduct = Product::create([
            'user_id' => $this->admin->id,
            'code' => 'CHARGER',
            'name' => 'Sac nhanh',
            'price' => 0,
            'price_buy' => 0,
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
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

    public function test_product_query_resets_stale_staging_and_initial_ajax_contains_product_details(): void
    {
        $staleProduct = Product::create([
            'user_id' => $this->admin->id,
            'code' => 'OLD',
            'name' => 'Old import row',
            'price' => 100000,
            'price_buy' => 0,
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'status' => true,
        ]);
        ImportItem::create([
            'product_id' => $staleProduct->id,
            'quantity' => 2,
            'price' => 100000,
            'total' => 200000,
        ]);

        $this->product->update(['price' => 2000000]);

        $this->actingAs($this->admin)
            ->get("/admin/importproduct/add?product_id={$this->product->id}")
            ->assertOk();

        $this->assertDatabaseCount('import', 1);
        $this->assertDatabaseHas('import', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('import', ['product_id' => $staleProduct->id]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/importproduct/import');

        $response->assertOk()
            ->assertJsonCount(1, 'import')
            ->assertJsonPath('import.0.product_id', $this->product->id)
            ->assertJsonPath('import.0.product.code', 'IP16')
            ->assertJsonPath('import.0.product.name', 'iPhone 16');
        $this->assertSame(2000000, $response->json('total'));
    }

    public function test_adding_same_product_again_does_not_create_duplicate_staging_row(): void
    {
        $this->createImportItem(quantity: 1);

        $response = $this->actingAs($this->admin)
            ->post('/admin/importproduct/import/add', [
                'product' => $this->product->id,
            ]);

        $response->assertOk()
            ->assertJsonCount(1, 'import')
            ->assertJsonPath('import.0.product_id', $this->product->id);
        $this->assertDatabaseCount('import', 1);
    }

    public function test_import_quantity_update_accepts_one_and_thirty_five_but_rejects_larger_values(): void
    {
        $item = $this->createImportItem(quantity: 1);
        $item->update([
            'price' => 2000,
            'total' => 2000,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/admin/importproduct/import/update', [
                'dataId' => $item->id,
                'value' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('import.0.quantity', 1)
            ->assertJsonPath('total', 2000);

        $this->actingAs($this->admin)
            ->postJson('/admin/importproduct/import/update', [
                'dataId' => $item->id,
                'value' => ProductImei::MAX_IMPORT_QUANTITY,
            ])
            ->assertOk()
            ->assertJsonPath('import.0.quantity', ProductImei::MAX_IMPORT_QUANTITY)
            ->assertJsonPath('total', 70000);

        foreach ([36, 73] as $quantity) {
            $response = $this->actingAs($this->admin)
                ->postJson('/admin/importproduct/import/update', [
                    'dataId' => $item->id,
                    'value' => $quantity,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['value']);

            $this->assertSame(
                'Mỗi lần chỉ được nhập tối đa 35 sản phẩm',
                $response->json('errors.value.0')
            );

            $this->assertSame(
                (string) ProductImei::MAX_IMPORT_QUANTITY,
                (string) $item->fresh()->quantity
            );
        }
    }

    public function test_quantity_tracked_product_can_update_quantity_above_imei_limit(): void
    {
        $item = $this->createImportItem(quantity: 1, product: $this->quantityProduct);

        $this->actingAs($this->admin)
            ->postJson('/admin/importproduct/import/update', [
                'dataId' => $item->id,
                'value' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('import.0.quantity', 50);

        $this->assertSame('50', (string) $item->fresh()->quantity);
    }

    public function test_import_add_page_exposes_quantity_limit_to_frontend(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/importproduct/add')
            ->assertOk()
            ->assertSee('const MAX_IMPORT_QUANTITY = 35;', false)
            ->assertSee('max="${MAX_IMPORT_QUANTITY}"', false)
            ->assertSee('Mỗi lần chỉ được nhập tối đa 35 sản phẩm');
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
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
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

    public function test_duplicate_product_rows_are_rejected_before_confirmation(): void
    {
        $firstItem = $this->createImportItem(quantity: 1);
        $secondItem = ImportItem::create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 0,
            'total' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->from('/admin/importproduct/add')
            ->post('/admin/importproduct/importCoupon', [
                'supplier' => $this->company->id,
                'storage' => $this->storage->id,
                'total' => 0,
                'totalncc' => 0,
                'imeis' => [
                    $firstItem->id => ['123456789012345'],
                    $secondItem->id => ['223456789012345'],
                ],
            ]);

        $response->assertRedirect('/admin/importproduct/add')
            ->assertSessionHasErrors('items');
        $this->assertDatabaseCount('import_coupon', 0);
        $this->assertDatabaseCount('import_detail', 0);
        $this->assertDatabaseCount('product_imeis', 0);
    }

    public function test_confirmation_rejects_staged_quantity_above_thirty_five(): void
    {
        $item = $this->createImportItem(quantity: 36);

        $response = $this->actingAs($this->admin)
            ->from('/admin/importproduct/add')
            ->post('/admin/importproduct/importCoupon', $this->validPayload(
                $item,
                $this->makeImeis(36)
            ));

        $response->assertRedirect('/admin/importproduct/add')
            ->assertSessionHasErrors([
                "imeis.{$item->id}" => 'Mỗi lần chỉ được nhập tối đa 35 sản phẩm',
            ]);
        $this->assertDatabaseCount('import_coupon', 0);
        $this->assertDatabaseCount('import_detail', 0);
        $this->assertDatabaseCount('product_imeis', 0);
        $this->assertDatabaseHas('import', ['id' => $item->id]);
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

        $coupon = ImportCoupon::first();
        $detail = ImportDetail::first();

        $this->assertNotNull($coupon);
        $this->assertNotNull($detail);
        $response->assertRedirect(route('admin.importproduct.importCoupon.detail', ['id' => $coupon->id]))
            ->assertSessionHas('success', 'Nhập hàng thành công. Barcode nội bộ đã được tạo.');

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
        ProductImei::query()
            ->orderBy('id')
            ->get()
            ->each(function (ProductImei $productImei): void {
                $this->assertSame(
                    '29' . str_pad((string) $productImei->id, 11, '0', STR_PAD_LEFT),
                    $productImei->barcode
                );
            });
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $this->product->id,
            'storage_id' => $this->storage->id,
            'quantity' => 3,
        ]);
        $this->assertSame('3', (string) $this->product->fresh()->quantity);
        $this->assertDatabaseCount('import', 0);
    }

    public function test_mixed_import_creates_imeis_only_for_imei_tracked_products(): void
    {
        $imeiItem = $this->createImportItem(quantity: 2);
        $this->createImportItem(quantity: 20, product: $this->quantityProduct);

        $response = $this->actingAs($this->admin)
            ->post('/admin/importproduct/importCoupon', [
                'supplier' => $this->company->id,
                'storage' => $this->storage->id,
                'total' => 0,
                'totalncc' => 0,
                'imeis' => [
                    $imeiItem->id => [
                        '123456789012345',
                        '223456789012345',
                    ],
                ],
            ]);

        $imeiDetail = ImportDetail::where('product_id', $this->product->id)->first();
        $quantityDetail = ImportDetail::where('product_id', $this->quantityProduct->id)->first();
        $coupon = ImportCoupon::first();

        $this->assertNotNull($coupon);
        $this->assertNotNull($imeiDetail);
        $this->assertNotNull($quantityDetail);
        $response->assertRedirect(route('admin.importproduct.importCoupon.detail', ['id' => $coupon->id]))
            ->assertSessionHas('success', 'Nhập hàng thành công. Barcode nội bộ đã được tạo.');

        $this->assertSame(2, (int) $imeiDetail->quantity);
        $this->assertSame(20, (int) $quantityDetail->quantity);
        $this->assertDatabaseCount('product_imeis', 2);
        $this->assertDatabaseHas('product_imeis', [
            'product_id' => $this->product->id,
            'import_detail_id' => $imeiDetail->id,
            'imei' => '123456789012345',
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);
        $this->assertDatabaseMissing('product_imeis', [
            'product_id' => $this->quantityProduct->id,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $this->product->id,
            'storage_id' => $this->storage->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $this->quantityProduct->id,
            'storage_id' => $this->storage->id,
            'quantity' => 20,
        ]);
        $this->assertSame('2', (string) $this->product->fresh()->quantity);
        $this->assertSame('20', (string) $this->quantityProduct->fresh()->quantity);
        $this->assertDatabaseCount('import', 0);
    }

    public function test_confirmed_request_cannot_be_processed_twice(): void
    {
        $item = $this->createImportItem(quantity: 1);
        $payload = $this->validPayload($item, ['123456789012345']);

        $response = $this->actingAs($this->admin)
            ->post('/admin/importproduct/importCoupon', $payload);

        $coupon = ImportCoupon::first();

        $this->assertNotNull($coupon);
        $response->assertRedirect(route('admin.importproduct.importCoupon.detail', ['id' => $coupon->id]));

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

    public function test_quantity_tracked_product_rejects_submitted_imeis(): void
    {
        $item = $this->createImportItem(quantity: 2, product: $this->quantityProduct);

        $this->actingAs($this->admin)
            ->from('/admin/importproduct/add')
            ->post('/admin/importproduct/importCoupon', [
                'supplier' => $this->company->id,
                'storage' => $this->storage->id,
                'total' => 0,
                'totalncc' => 0,
                'imeis' => [
                    $item->id => ['123456789012345', '223456789012345'],
                ],
            ])
            ->assertRedirect('/admin/importproduct/add')
            ->assertSessionHasErrors("imeis.{$item->id}");

        $this->assertDatabaseCount('import_coupon', 0);
        $this->assertDatabaseCount('import_detail', 0);
        $this->assertDatabaseCount('product_imeis', 0);
        $this->assertDatabaseCount('product_storage', 0);
        $this->assertSame('0', (string) $this->quantityProduct->fresh()->quantity);
    }

    public function test_barcode_generation_failure_rolls_back_receipt_details_imeis_and_inventory(): void
    {
        $item = $this->createImportItem(quantity: 1);
        $barcodeService = Mockery::mock(InternalBarcodeService::class);
        $barcodeService->shouldReceive('generate')
            ->once()
            ->andThrow(new RuntimeException('Barcode failure'));
        $this->app->instance(InternalBarcodeService::class, $barcodeService);

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

    public function test_delete_staging_import_item_removes_regular_product(): void
    {
        $item = ImportItem::create([
            'product_id' => $this->quantityProduct->id,
            'quantity' => 2,
            'price' => 50000,
            'total' => 100000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/importproduct/import/delete?id={$item->id}");

        $response->assertOk()
            ->assertJson([
                'import' => [],
                'total' => 0,
            ]);

        $this->assertDatabaseMissing('import', ['id' => $item->id]);
    }

    public function test_delete_staging_import_item_removes_imei_product_and_updates_totals(): void
    {
        $imeiItem = ImportItem::create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 200000,
            'total' => 200000,
        ]);
        $regularItem = ImportItem::create([
            'product_id' => $this->quantityProduct->id,
            'quantity' => 1,
            'price' => 50000,
            'total' => 50000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/importproduct/import/delete?id={$imeiItem->id}");

        $response->assertOk()
            ->assertJson([
                'total' => 50000,
            ]);

        $this->assertDatabaseMissing('import', ['id' => $imeiItem->id]);
        $this->assertDatabaseHas('import', ['id' => $regularItem->id]);
    }

    private function createImportItem(int $quantity, ?Product $product = null): ImportItem
    {
        return ImportItem::create([
            'product_id' => ($product ?? $this->product)->id,
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

    private function makeImeis(int $quantity): array
    {
        return array_map(
            fn(int $number) => '9' . str_pad((string) $number, 14, '0', STR_PAD_LEFT),
            range(1, $quantity)
        );
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
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('user_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('img_url')->nullable();
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
            $table->string('payment_method', 30)->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_status', 20)->nullable();
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
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('status', 30)->default(ProductImei::STATUS_IN_STOCK);
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('delete_reason', 500)->nullable();
            $table->softDeletes();
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

        \Illuminate\Support\Facades\DB::table('accounts')->insert([
            ['code' => '111', 'name' => 'Tiền mặt', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '112', 'name' => 'Tiền gửi ngân hàng', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '156', 'name' => 'Hàng hóa', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '331', 'name' => 'Phải trả nhà cung cấp', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
            $table->string('document_type')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 15, 2)->nullable()->default(0);
            $table->decimal('credit_amount', 15, 2)->nullable()->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }
}
