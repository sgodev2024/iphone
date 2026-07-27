<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Config;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffPosSaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_staff_can_sell_from_assigned_storage(): void
    {
        $this->seedAccounts();
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 12, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $otherStorage->id, 'quantity' => 2]);

        $response = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 3],
        ], 300000));

        $response->assertCreated()
            ->assertJson(['message' => 'Tạo đơn hàng thành công!']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 1);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 7,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $otherStorage->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_details', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 3,
            'price' => 100000,
        ]);
        $this->assertSame('9', (string) $product->fresh()->quantity);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_entries', 2);
    }

    public function test_staff_index_renders_bank_transfer_info_from_config(): void
    {
        [, , $staff, $manager] = $this->createStaffContext();
        $bank = Bank::create([
            'name' => 'MBBank Test',
            'code' => 'MB',
            'bin' => '970422',
            'shortName' => 'MBBank',
        ]);

        Config::create([
            'user_id' => $manager->id,
            'bank_id' => $bank->id,
            'bank_account' => '0000000000',
            'receiver' => 'Nguoi nhan mau',
            'logo' => 'assets/img/default-image.jpg',
            'qr' => 'https://img.vietqr.io/image/MB-0000000000-compact.jpg',
        ]);

        $response = $this->actingAs($staff)->get('/ban-hang');

        $response->assertOk()
            ->assertSee('MBBank Test', false)
            ->assertSee('0000000000', false)
            ->assertSee('Nguoi nhan mau', false);
    }

    public function test_staff_index_renders_placeholder_when_config_is_missing(): void
    {
        [, , $staff] = $this->createStaffContext();

        $response = $this->actingAs($staff)->get('/ban-hang');

        $response->assertOk()
            ->assertSee('Chưa cấu hình ngân hàng', false);
    }

    public function test_sale_fails_when_assigned_storage_does_not_have_enough_stock(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 2, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 2]);

        $response = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 3],
        ], 300000));

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Sản phẩm iPhone 15 chỉ còn 2 sản phẩm trong kho, không đủ bán 3 sản phẩm.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 2,
        ]);
        $this->assertSame('2', (string) $product->fresh()->quantity);
    }

    public function test_sale_with_multiple_products_rolls_back_when_one_product_is_short(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $firstProduct = $this->createProduct(['name' => 'iPhone 15', 'quantity' => 10, 'price_buy' => 100000]);
        $secondProduct = $this->createProduct(['name' => 'iPhone 16', 'quantity' => 1, 'price_buy' => 200000]);
        ProductStorage::create(['product_id' => $firstProduct->id, 'storage_id' => $storage->id, 'quantity' => 10]);
        ProductStorage::create(['product_id' => $secondProduct->id, 'storage_id' => $storage->id, 'quantity' => 1]);

        $response = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $firstProduct->id, 'qty' => 2],
            ['id' => $secondProduct->id, 'qty' => 2],
        ], 600000));

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Sản phẩm iPhone 16 chỉ còn 1 sản phẩm trong kho, không đủ bán 2 sản phẩm.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $firstProduct->id,
            'storage_id' => $storage->id,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $secondProduct->id,
            'storage_id' => $storage->id,
            'quantity' => 1,
        ]);
    }

    public function test_product_stock_in_another_storage_cannot_be_sold(): void
    {
        $this->seedAccounts();
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 5, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $otherStorage->id, 'quantity' => 5]);

        $response = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 1],
        ], 100000));

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Sản phẩm iPhone 15 không có trong kho đang bán.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $otherStorage->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseMissing('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
        ]);
    }

    public function test_staff_without_assigned_storage_cannot_create_order(): void
    {
        $this->seedAccounts();
        $staff = $this->createStaff(null);
        $product = $this->createProduct(['quantity' => 5, 'price_buy' => 100000]);

        $response = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 1],
        ], 100000));

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Nhân viên chưa được gán kho bán hàng.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
    }

    public function test_two_sell_requests_cannot_oversell_the_same_stock(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 5, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 5]);

        $first = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 4],
        ], 400000));

        $second = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 4],
        ], 400000));

        $first->assertCreated();
        $second->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Sản phẩm iPhone 15 chỉ còn 1 sản phẩm trong kho, không đủ bán 4 sản phẩm.']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 1,
        ]);
        $this->assertSame('1', (string) $product->fresh()->quantity);
    }

    public function test_order_stock_and_accounting_roll_back_when_late_error_occurs(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        Account::create(['code' => '131', 'name' => 'Receivable']);
        $product = $this->createProduct(['quantity' => 5, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 5]);

        $response = $this->actingAs($staff)->postJson('/ban-hang/order', $this->orderPayload([
            ['id' => $product->id, 'qty' => 2],
        ], 200000));

        $response->assertInternalServerError()
            ->assertJsonFragment(['message' => 'Không tìm thấy tài khoản TMCH']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 5,
        ]);
        $this->assertSame('5', (string) $product->fresh()->quantity);
    }

    public function test_product_endpoint_returns_only_assigned_storage_availability(): void
    {
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $sameProduct = $this->createProduct(['quantity' => 12, 'price_buy' => 100000]);
        $otherStorageProduct = $this->createProduct(['name' => 'iPhone only other storage', 'quantity' => 8, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $sameProduct->id, 'storage_id' => $storage->id, 'quantity' => 2]);
        ProductStorage::create(['product_id' => $sameProduct->id, 'storage_id' => $otherStorage->id, 'quantity' => 10]);
        ProductStorage::create(['product_id' => $otherStorageProduct->id, 'storage_id' => $otherStorage->id, 'quantity' => 8]);

        $response = $this->actingAs($staff)->getJson('/ban-hang/product');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'product_id' => $sameProduct->id,
                'quantity' => 2,
                'available_quantity' => 2,
                'storage_id' => $storage->id,
            ])
            ->assertJsonMissing(['product_id' => $otherStorageProduct->id]);
    }

    public function test_barcode_resolve_returns_valid_imei_device_from_staff_storage(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'name' => 'iPhone IMEI',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
            'price_buy' => 12000000,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $storage, '123456789012345');

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => $imei->barcode,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'type' => Product::INVENTORY_TRACKING_IMEI,
                'product_id' => $product->id,
                'product_imei_id' => $imei->id,
                'imei' => '123456789012345',
                'barcode' => $imei->barcode,
                'quantity' => 1,
            ]);
    }

    public function test_barcode_resolve_returns_valid_quantity_product(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'name' => 'Cap sac',
            'barcode' => 'CABLE-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'price_buy' => 250000,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => 'CABLE-001',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'type' => Product::INVENTORY_TRACKING_QUANTITY,
                'product_id' => $product->id,
                'barcode' => 'CABLE-001',
                'available_quantity' => 10,
                'quantity' => 1,
            ]);
    }

    public function test_barcode_resolve_reports_missing_barcode(): void
    {
        [, , $staff] = $this->createStaffContext();

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => 'UNKNOWN-BARCODE',
            ])
            ->assertNotFound()
            ->assertJsonFragment([
                'message' => 'Không tìm thấy barcode.',
            ]);
    }

    public function test_barcode_resolve_rejects_sold_imei(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $storage, '123456789012346', [
            'status' => ProductImei::STATUS_SOLD,
        ]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => $imei->barcode,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Thiết bị đã bán.',
            ]);
    }

    public function test_barcode_resolve_rejects_imei_from_another_storage(): void
    {
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $otherStorage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $otherStorage, '123456789012347');

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => $imei->barcode,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Thiết bị không thuộc kho hiện tại.',
            ]);
    }

    public function test_barcode_resolve_rejects_duplicate_imei_already_in_cart(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $storage, '123456789012348');

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => $imei->barcode,
                'cart_imei_ids' => [$imei->id],
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Thiết bị đã có trong giỏ.',
            ]);
    }

    public function test_quantity_barcode_can_be_scanned_repeatedly_until_stock_limit(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'barcode' => 'CASE-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 2]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => 'CASE-001',
                'cart_product_quantities' => [
                    $product->id => 1,
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => 'CASE-001',
                'cart_product_quantities' => [
                    $product->id => 2,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Số lượng yêu cầu vượt tồn kho.',
            ]);
    }

    public function test_staff_can_checkout_imei_only_order_and_marks_device_sold(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'name' => 'iPhone IMEI',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
            'price_buy' => 12000000,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $storage, '123456789012349');

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                [
                    'product_id' => $product->id,
                    'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                    'product_imei_id' => $imei->id,
                    'qty' => 1,
                ],
            ], 12000000))
            ->assertCreated();

        $this->assertDatabaseHas('order_details', [
            'product_id' => $product->id,
            'product_imei_id' => $imei->id,
            'storage_id' => $storage->id,
            'quantity' => 1,
            'price' => 12000000,
        ]);
        $this->assertSame(ProductImei::STATUS_SOLD, $imei->fresh()->status);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 0,
        ]);
        $this->assertSame('0', (string) $product->fresh()->quantity);
    }

    public function test_staff_can_checkout_mixed_quantity_and_imei_order(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $imeiProduct = $this->createProduct([
            'name' => 'iPhone IMEI',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
            'price_buy' => 12000000,
        ]);
        $quantityProduct = $this->createProduct([
            'name' => 'Cap sac',
            'barcode' => 'CABLE-MIX',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'quantity' => 5,
            'price_buy' => 250000,
        ]);
        ProductStorage::create(['product_id' => $imeiProduct->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        ProductStorage::create(['product_id' => $quantityProduct->id, 'storage_id' => $storage->id, 'quantity' => 5]);
        $imei = $this->createImeiInStorage($imeiProduct, $storage, '123456789012350');

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                [
                    'product_id' => $imeiProduct->id,
                    'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                    'product_imei_id' => $imei->id,
                    'qty' => 1,
                ],
                [
                    'product_id' => $quantityProduct->id,
                    'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
                    'qty' => 2,
                ],
            ], 12500000))
            ->assertCreated();

        $this->assertDatabaseCount('order_details', 2);
        $this->assertDatabaseHas('order_details', [
            'product_id' => $imeiProduct->id,
            'product_imei_id' => $imei->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('order_details', [
            'product_id' => $quantityProduct->id,
            'product_imei_id' => null,
            'quantity' => 2,
        ]);
        $this->assertSame(ProductImei::STATUS_SOLD, $imei->fresh()->status);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $imeiProduct->id,
            'storage_id' => $storage->id,
            'quantity' => 0,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $quantityProduct->id,
            'storage_id' => $storage->id,
            'quantity' => 3,
        ]);
    }

    public function test_checkout_late_failure_rolls_back_imei_order_stock_and_accounting(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        Account::create(['code' => '131', 'name' => 'Receivable']);
        $product = $this->createProduct([
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
            'price_buy' => 12000000,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $storage, '123456789012351');

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                [
                    'product_id' => $product->id,
                    'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                    'product_imei_id' => $imei->id,
                    'qty' => 1,
                ],
            ], 12000000))
            ->assertInternalServerError()
            ->assertJsonFragment([
                'message' => 'Không tìm thấy tài khoản TMCH',
            ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
        $this->assertSame(ProductImei::STATUS_IN_STOCK, $imei->fresh()->status);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 1,
        ]);
    }

    public function test_same_imei_cannot_be_sold_twice(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 1,
            'price_buy' => 12000000,
        ]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 1]);
        $imei = $this->createImeiInStorage($product, $storage, '123456789012352');
        $payload = $this->orderPayload([
            [
                'product_id' => $product->id,
                'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                'product_imei_id' => $imei->id,
                'qty' => 1,
            ],
        ], 12000000);

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $payload)
            ->assertCreated();

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Thiết bị đã bán.',
            ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 1);
        $this->assertSame(ProductImei::STATUS_SOLD, $imei->fresh()->status);
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
            $table->string('address')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(3);
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('bin');
            $table->string('shortName');
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table) {
            $table->id();
            $table->string('logo');
            $table->string('bank_account')->nullable();
            $table->string('qr')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('receiver');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('client_group', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->nullable();
            $table->string('barcode', 50)->nullable()->unique();
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

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('image_path')->nullable();
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

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('amount')->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('level')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('total_money', 15, 2)->default(0);
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('status')->default(true);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_imei_id')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('transaction_date')->nullable();
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
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    private function createStaffContext(): array
    {
        $storage = Storage::create(['name' => 'Kho A', 'location' => 'A']);
        $otherStorage = Storage::create(['name' => 'Kho B', 'location' => 'B']);
        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'phone' => '0900000000',
            'password' => 'password',
            'role_id' => 2,
            'storage_id' => $otherStorage->id,
            'status' => 'active',
        ]);
        $staff = $this->createStaff($storage->id, $manager->id);

        return [$storage, $otherStorage, $staff, $manager];
    }

    private function createStaff(?int $storageId, ?int $managerId = null): User
    {
        return User::create([
            'name' => 'Staff',
            'email' => uniqid('staff', true) . '@example.com',
            'phone' => uniqid('09'),
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $managerId,
            'storage_id' => $storageId,
            'status' => 'active',
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => 1,
            'code' => uniqid('IP'),
            'barcode' => null,
            'name' => 'iPhone 15',
            'price' => 90000,
            'price_buy' => 100000,
            'thumbnail' => null,
            'product_unit' => 'cái',
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'description' => 'Test product',
            'status' => true,
        ], $overrides));
    }

    private function createImeiInStorage(
        Product $product,
        Storage $storage,
        string $imei,
        array $overrides = []
    ): ProductImei {
        $coupon = ImportCoupon::create([
            'user_id' => 1,
            'total' => 0,
            'payment_ncc' => 0,
            'storage_id' => $storage->id,
        ]);
        $detail = ImportDetail::create([
            'import_id' => $coupon->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => (int) $product->price_buy,
            'old_price' => (int) $product->price_buy,
        ]);
        $productImei = ProductImei::create(array_merge([
            'product_id' => $product->id,
            'import_detail_id' => $detail->id,
            'imei' => $imei,
            'status' => ProductImei::STATUS_IN_STOCK,
        ], $overrides));

        if (! $productImei->barcode) {
            $productImei->forceFill([
                'barcode' => sprintf('TEL-%08d', $productImei->id),
            ])->save();
        }

        return $productImei->fresh();
    }

    private function seedAccounts(): void
    {
        foreach (['TMCH', 'tech', '131', '5111'] as $code) {
            Account::create(['code' => $code, 'name' => $code]);
        }
    }

    private function orderPayload(array $items, float $grand): array
    {
        return [
            'items' => $items,
            'subtotal' => $grand,
            'discountType' => 'amount',
            'discountInput' => 0,
            'grand' => $grand,
            'customer' => [
                'id' => null,
                'name' => 'Nguyen Van A',
                'email' => 'customer@example.com',
                'phone' => '0912345678',
                'address' => 'Ha Noi',
                'payment' => 'cash',
                'note' => null,
            ],
        ];
    }
}
