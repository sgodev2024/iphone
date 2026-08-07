<?php

namespace Tests\Feature;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportBarcodePrintTest extends TestCase
{
    private User $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->warehouse = User::create([
            'name' => 'Warehouse',
            'email' => 'warehouse@example.com',
            'phone' => '0900000000',
            'password' => 'password',
            'role_id' => 4,
            'status' => 'active',
        ]);
    }

    public function test_imei_only_import_can_select_and_print_device_labels_without_inventory_changes(): void
    {
        $coupon = $this->createCoupon();
        $product = $this->createProduct([
            'code' => 'IP16',
            'name' => 'iPhone 16',
            'quantity' => 2,
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
        ]);
        $detail = $this->createDetail($coupon, $product, quantity: 2);
        $firstImei = $this->createImei($detail, $product, '123456789012345');
        $secondImei = $this->createImei($detail, $product, '223456789012345');

        $this->actingAs($this->warehouse)
            ->get(route('admin.importproduct.barcodes.index', $coupon->id))
            ->assertOk()
            ->assertViewHas('items', function ($items): bool {
                return $items->count() === 2
                    && $items->every(fn(array $item): bool => $item['type'] === 'imei')
                    && $items->pluck('type_label')->unique()->sole() === 'Tem thiết bị IMEI';
            });

        $this->actingAs($this->warehouse)
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'print_all' => 1,
            ])
            ->assertOk()
            ->assertViewHas('labels', function ($labels) use ($firstImei, $secondImei): bool {
                return $labels->count() === 2
                    && $labels->pluck('barcode')->all() === [
                        $firstImei->barcode,
                        $secondImei->barcode,
                    ];
            });

        $this->assertSame(ProductImei::STATUS_IN_STOCK, $firstImei->fresh()->status);
        $this->assertSame(ProductImei::STATUS_IN_STOCK, $secondImei->fresh()->status);
        $this->assertSame(1, (int) $firstImei->fresh()->print_count);
        $this->assertSame('2', (string) $product->fresh()->quantity);
    }

    public function test_quantity_only_import_uses_product_barcode_and_prints_default_import_quantity(): void
    {
        $coupon = $this->createCoupon();
        $product = $this->createProduct([
            'code' => 'CABLE-001',
            'name' => 'Cap sac USB-C',
            'quantity' => 5,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $detail = $this->createDetail($coupon, $product, quantity: 5);

        $this->actingAs($this->warehouse)
            ->get(route('admin.importproduct.barcodes.index', $coupon->id))
            ->assertOk()
            ->assertViewHas('items', function ($items) use ($detail): bool {
                return $items->count() === 1
                    && $items->first()['type'] === 'product'
                    && $items->first()['type_label'] === 'Tem sản phẩm'
                    && $items->first()['id'] === $detail->id
                    && $items->first()['barcode'] === 'CABLE-001'
                    && $items->first()['default_label_quantity'] === 5;
            });

        $this->actingAs($this->warehouse)
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'print_all' => 1,
            ])
            ->assertOk()
            ->assertViewHas('labels', function ($labels): bool {
                return $labels->count() === 5
                    && $labels->every(fn(array $label): bool => $label['type'] === 'product')
                    && $labels->pluck('barcode')->unique()->sole() === 'CABLE-001';
            });

        $this->assertDatabaseCount('product_imeis', 0);
        $this->assertSame('CABLE-001', $product->fresh()->barcode);
    }

    public function test_mixed_import_lists_and_prints_imei_and_product_labels(): void
    {
        $coupon = $this->createCoupon();
        $imeiProduct = $this->createProduct([
            'code' => 'IP16PM',
            'name' => 'iPhone 16 Pro Max',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
        ]);
        $regularProduct = $this->createProduct([
            'code' => 'CASE-001',
            'name' => 'Op lung',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $imeiDetail = $this->createDetail($coupon, $imeiProduct, quantity: 2);
        $this->createImei($imeiDetail, $imeiProduct, '123456789012345');
        $this->createImei($imeiDetail, $imeiProduct, '223456789012345');
        $this->createDetail($coupon, $regularProduct, quantity: 3);

        $this->actingAs($this->warehouse)
            ->get(route('admin.importproduct.barcodes.index', $coupon->id))
            ->assertOk()
            ->assertViewHas('items', function ($items): bool {
                return $items->count() === 3
                    && $items->where('type', 'imei')->count() === 2
                    && $items->where('type', 'product')->count() === 1;
            });

        $this->actingAs($this->warehouse)
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'print_all' => 1,
            ])
            ->assertOk()
            ->assertViewHas('labels', function ($labels): bool {
                return $labels->count() === 5
                    && $labels->where('type', 'imei')->count() === 2
                    && $labels->where('type', 'product')->count() === 3;
            });
    }

    public function test_can_print_one_regular_product_label(): void
    {
        $coupon = $this->createCoupon();
        $product = $this->createProduct([
            'code' => 'CHARGER-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $detail = $this->createDetail($coupon, $product, quantity: 4);

        $this->actingAs($this->warehouse)
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'single_product_detail_id' => $detail->id,
                'product_label_quantities' => [
                    $detail->id => 1,
                ],
            ])
            ->assertOk()
            ->assertViewHas('labels', function ($labels): bool {
                return $labels->count() === 1
                    && $labels->first()['type'] === 'product'
                    && $labels->first()['copy_total'] === 1;
            });
    }

    public function test_can_print_selected_regular_product_label_quantity(): void
    {
        $coupon = $this->createCoupon();
        $product = $this->createProduct([
            'code' => 'ADAPTER-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $detail = $this->createDetail($coupon, $product, quantity: 6);

        $this->actingAs($this->warehouse)
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'product_detail_ids' => [$detail->id],
                'product_label_quantities' => [
                    $detail->id => 3,
                ],
            ])
            ->assertOk()
            ->assertViewHas('labels', function ($labels): bool {
                return $labels->count() === 3
                    && $labels->pluck('copy_number')->all() === [1, 2, 3]
                    && $labels->pluck('copy_total')->unique()->sole() === 3;
            });
    }

    public function test_regular_product_label_quantity_cannot_exceed_import_quantity_or_limit(): void
    {
        $coupon = $this->createCoupon();
        $product = $this->createProduct([
            'code' => 'LIMIT-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $detail = $this->createDetail($coupon, $product, quantity: 5);

        $this->actingAs($this->warehouse)
            ->from(route('admin.importproduct.barcodes.index', $coupon->id))
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'product_detail_ids' => [$detail->id],
                'product_label_quantities' => [
                    $detail->id => 6,
                ],
            ])
            ->assertRedirect(route('admin.importproduct.barcodes.index', $coupon->id))
            ->assertSessionHasErrors("product_label_quantities.{$detail->id}");

        $largeDetail = $this->createDetail($coupon, $product, quantity: 1200);

        $this->actingAs($this->warehouse)
            ->from(route('admin.importproduct.barcodes.index', $coupon->id))
            ->post(route('admin.importproduct.barcodes.print', $coupon->id), [
                'product_detail_ids' => [$largeDetail->id],
                'product_label_quantities' => [
                    $largeDetail->id => 1001,
                ],
            ])
            ->assertRedirect(route('admin.importproduct.barcodes.index', $coupon->id))
            ->assertSessionHasErrors("product_label_quantities.{$largeDetail->id}");
    }

    public function test_cannot_print_barcode_items_from_another_import_coupon(): void
    {
        $firstCoupon = $this->createCoupon();
        $secondCoupon = $this->createCoupon();
        $firstProduct = $this->createProduct([
            'code' => 'FIRST-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $secondProduct = $this->createProduct([
            'code' => 'SECOND-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
        ]);
        $foreignDetail = $this->createDetail($secondCoupon, $firstProduct, quantity: 2);
        $foreignImeiDetail = $this->createDetail($secondCoupon, $secondProduct, quantity: 1);
        $foreignImei = $this->createImei($foreignImeiDetail, $secondProduct, '323456789012345');

        $this->actingAs($this->warehouse)
            ->from(route('admin.importproduct.barcodes.index', $firstCoupon->id))
            ->post(route('admin.importproduct.barcodes.print', $firstCoupon->id), [
                'single_product_detail_id' => $foreignDetail->id,
            ])
            ->assertRedirect(route('admin.importproduct.barcodes.index', $firstCoupon->id))
            ->assertSessionHasErrors('labels');

        $this->actingAs($this->warehouse)
            ->from(route('admin.importproduct.barcodes.index', $firstCoupon->id))
            ->post(route('admin.importproduct.barcodes.print', $firstCoupon->id), [
                'single_imei_id' => $foreignImei->id,
            ])
            ->assertRedirect(route('admin.importproduct.barcodes.index', $firstCoupon->id))
            ->assertSessionHasErrors('labels');
    }

    public function test_regular_product_with_duplicate_code_gets_generated_product_barcode_without_changing_code(): void
    {
        $coupon = $this->createCoupon();
        $product = $this->createProduct([
            'code' => 'DUPLICATE',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $this->createProduct([
            'code' => 'DUPLICATE',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        $this->createDetail($coupon, $product, quantity: 2);

        $this->actingAs($this->warehouse)
            ->get(route('admin.importproduct.barcodes.index', $coupon->id))
            ->assertOk()
            ->assertViewHas('items', function ($items) use ($product): bool {
                return $items->first()['barcode'] === sprintf('SP-%08d', $product->id);
            });

        $product->refresh();

        $this->assertSame('DUPLICATE', $product->code);
        $this->assertSame(sprintf('SP-%08d', $product->id), $product->barcode);
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('img_url')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(4);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->nullable();
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('quantity')->nullable()->default('0');
            $table->string('inventory_tracking', 20)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('import_coupon', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('companies_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->integer('total')->nullable();
            $table->integer('payment_ncc')->nullable();
            $table->string('status')->nullable();
            $table->string('coupon_code')->nullable()->unique();
            $table->unsignedBigInteger('storage_id')->nullable();
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
    }

    private function createCoupon(): ImportCoupon
    {
        return ImportCoupon::create([
            'user_id' => $this->warehouse->id,
            'total' => 0,
            'payment_ncc' => 0,
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $this->warehouse->id,
            'code' => 'SP' . fake()->unique()->numerify('######'),
            'name' => 'San pham test',
            'price' => 100000,
            'price_buy' => 80000,
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'status' => true,
        ], $overrides));
    }

    private function createDetail(
        ImportCoupon $coupon,
        Product $product,
        int $quantity
    ): ImportDetail {
        return ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => (int) $product->price,
            'old_price' => (int) $product->price_buy,
        ]);
    }

    private function createImei(
        ImportDetail $detail,
        Product $product,
        string $imei
    ): ProductImei {
        $productImei = ProductImei::create([
            'product_id' => $product->id,
            'import_detail_id' => $detail->id,
            'imei' => $imei,
            'status' => ProductImei::STATUS_IN_STOCK,
        ]);

        $productImei->forceFill([
            'barcode' => sprintf('TEL-%08d', $productImei->id),
        ])->save();

        return $productImei->fresh();
    }
}
