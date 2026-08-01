<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Client;
use App\Models\ClientDebt;
use App\Models\Config;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Receipts;
use App\Models\Storage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffPosSaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pos.default_storage_id' => null,
            'pos.default_storage_name' => 'Kho A',
        ]);

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

    public function test_admin_uses_default_kho_a_and_storage_picker_is_hidden(): void
    {
        $this->seedAccounts();
        $owner = $this->createManager(1);
        $branch = $this->createManager(2, $owner->id);
        $storageA = Storage::create([
            'user_id' => $branch->id,
            'name' => 'Kho A',
        ]);
        $storageB = Storage::create([
            'user_id' => $branch->id,
            'name' => 'Kho B',
        ]);
        $product = $this->createProduct([
            'user_id' => $owner->id,
            'price_buy' => 100000,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageA->id,
            'quantity' => 4,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storageB->id,
            'quantity' => 9,
        ]);

        $this->actingAs($owner)
            ->get('/ban-hang')
            ->assertOk()
            ->assertDontSee('Kho đang bán hàng', false)
            ->assertDontSee('Chọn kho bán hàng', false)
            ->assertDontSee('id="saleStorageSelect"', false)
            ->assertDontSee('disabled placeholder="Tìm sản phẩm"', false)
            ->assertDontSee('disabled placeholder="Nhập hoặc quét barcode"', false);

        $this->actingAs($owner)
            ->getJson('/ban-hang/product')
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $product->id,
                'available_quantity' => 4,
                'storage_id' => $storageA->id,
            ]);

        $this->actingAs($owner)
            ->postJson('/ban-hang/order', $this->orderPayload([
                ['id' => $product->id, 'qty' => 2],
            ], 200000))
            ->assertCreated();

        $this->assertDatabaseHas('order_details', [
            'product_id' => $product->id,
            'storage_id' => $storageA->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storageA->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $product->id,
            'storage_id' => $storageB->id,
            'quantity' => 9,
        ]);
    }

    public function test_manager_without_managed_storage_gets_manager_specific_message(): void
    {
        $manager = $this->createManager();

        $this->actingAs($manager)
            ->get('/ban-hang')
            ->assertOk()
            ->assertSee('Chưa có kho bán hàng. Vui lòng tạo hoặc phân quyền kho.', false)
            ->assertDontSee('Nhân viên chưa được gán kho bán hàng.', false)
            ->assertSee('id="productSearch"', false)
            ->assertSee('disabled placeholder="Tìm sản phẩm"', false)
            ->assertSee('id="barcodeInput"', false)
            ->assertSee('disabled placeholder="Nhập hoặc quét barcode"', false);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product')
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Chưa có kho bán hàng. Vui lòng tạo hoặc phân quyền kho.',
            ]);
    }

    public function test_manager_with_multiple_storages_uses_kho_a_for_search_barcode_and_checkout(): void
    {
        $this->seedAccounts();
        $manager = $this->createManager();
        $storageA = Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho A',
        ]);
        $storageB = Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho B',
        ]);
        $productInA = $this->createProduct([
            'user_id' => $manager->id,
            'barcode' => 'BAR-KHO-A',
            'name' => 'Sản phẩm Kho A',
            'price_buy' => 100000,
        ]);
        $productInB = $this->createProduct([
            'user_id' => $manager->id,
            'barcode' => 'BAR-KHO-B',
            'name' => 'Sản phẩm Kho B',
            'price_buy' => 100000,
        ]);
        $imeiProduct = $this->createProduct([
            'user_id' => $manager->id,
            'barcode' => 'IMEI-PRODUCT-B',
            'name' => 'IMEI Kho B',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'price_buy' => 12000000,
        ]);
        ProductStorage::create([
            'product_id' => $productInA->id,
            'storage_id' => $storageA->id,
            'quantity' => 5,
        ]);
        ProductStorage::create([
            'product_id' => $productInB->id,
            'storage_id' => $storageB->id,
            'quantity' => 9,
        ]);
        ProductStorage::create([
            'product_id' => $imeiProduct->id,
            'storage_id' => $storageB->id,
            'quantity' => 1,
        ]);
        $imeiInB = $this->createImeiInStorage($imeiProduct, $storageB, 'IMEI-B-001', [
            'barcode' => 'IMEI-B-BARCODE',
        ]);

        $this->actingAs($manager)
            ->get('/ban-hang')
            ->assertOk()
            ->assertDontSee('Kho đang bán hàng', false)
            ->assertDontSee('Chọn kho bán hàng', false)
            ->assertDontSee('id="saleStorageSelect"', false)
            ->assertSee('id="productSearch"', false)
            ->assertDontSee('disabled placeholder="Tìm sản phẩm"', false)
            ->assertSee('id="barcodeInput"', false)
            ->assertDontSee('disabled placeholder="Nhập hoặc quét barcode"', false);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product?search=BAR-KHO')
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $productInA->id,
                'available_quantity' => 5,
                'storage_id' => $storageA->id,
            ])
            ->assertJsonMissing(['product_id' => $productInB->id]);

        $this->actingAs($manager)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => 'BAR-KHO-A',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $productInA->id,
                'storage_id' => $storageA->id,
                'available_quantity' => 5,
            ]);

        $this->actingAs($manager)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => 'BAR-KHO-B',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Sản phẩm thường đã hết hàng.']);

        $this->actingAs($manager)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => $imeiInB->barcode,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Thiết bị không thuộc kho hiện tại.']);

        $this->actingAs($manager)
            ->postJson('/ban-hang/order', $this->orderPayload([
                ['id' => $productInA->id, 'qty' => 3],
            ], 300000))
            ->assertCreated();

        $this->assertDatabaseHas('order_details', [
            'product_id' => $productInA->id,
            'storage_id' => $storageA->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $productInA->id,
            'storage_id' => $storageA->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('product_storage', [
            'product_id' => $productInB->id,
            'storage_id' => $storageB->id,
            'quantity' => 9,
        ]);
    }

    public function test_manager_cannot_select_storage_outside_management_scope(): void
    {
        $manager = $this->createManager();
        $otherManager = $this->createManager();
        $managedStorage = Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho A',
        ]);
        Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho hợp lệ thứ hai',
        ]);
        $outsideStorage = Storage::create([
            'user_id' => $otherManager->id,
            'name' => 'Kho ngoài phạm vi',
        ]);
        $manager->update(['storage_id' => $outsideStorage->id]);

        $this->actingAs($manager)
            ->postJson('/ban-hang/storage/select', [
                'storage_id' => $managedStorage->id,
            ])
            ->assertOk();

        $this->actingAs($manager)
            ->postJson('/ban-hang/storage/select', [
                'storage_id' => $outsideStorage->id,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Kho bán hàng đã chọn không thuộc quyền quản lý của tài khoản.',
            ]);

        $product = $this->createProduct(['user_id' => $manager->id]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $managedStorage->id,
            'quantity' => 2,
        ]);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product')
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $product->id,
                'storage_id' => $managedStorage->id,
                'available_quantity' => 2,
            ]);
    }

    public function test_configured_default_storage_id_must_exist_and_be_in_management_scope(): void
    {
        $manager = $this->createManager();
        $otherManager = $this->createManager();
        Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho A',
        ]);
        $outsideStorage = Storage::create([
            'user_id' => $otherManager->id,
            'name' => 'Kho A',
        ]);

        config(['pos.default_storage_id' => 999999]);

        $this->actingAs($manager)
            ->get('/ban-hang')
            ->assertOk()
            ->assertSee('Kho bán hàng mặc định không tồn tại.', false)
            ->assertSee('disabled placeholder="Tìm sản phẩm"', false);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product')
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Kho bán hàng mặc định không tồn tại.']);

        config(['pos.default_storage_id' => $outsideStorage->id]);

        $this->actingAs($manager)
            ->get('/ban-hang')
            ->assertOk()
            ->assertSee('Kho bán hàng mặc định không thuộc quyền quản lý của tài khoản.', false)
            ->assertSee('disabled placeholder="Tìm sản phẩm"', false);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product')
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Kho bán hàng mặc định không thuộc quyền quản lý của tài khoản.']);
    }

    public function test_duplicate_kho_a_names_are_not_selected_ambiguously(): void
    {
        $manager = $this->createManager();
        Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho A',
        ]);
        Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho A',
        ]);

        $this->actingAs($manager)
            ->get('/ban-hang')
            ->assertOk()
            ->assertSee('Có nhiều kho tên Kho A trong phạm vi quản lý. Vui lòng cấu hình POS_DEFAULT_STORAGE_ID.', false)
            ->assertSee('disabled placeholder="Tìm sản phẩm"', false);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product')
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Có nhiều kho tên Kho A trong phạm vi quản lý. Vui lòng cấu hình POS_DEFAULT_STORAGE_ID.',
            ]);
    }

    public function test_manager_with_storages_but_no_kho_a_gets_default_storage_message(): void
    {
        $manager = $this->createManager();
        Storage::create([
            'user_id' => $manager->id,
            'name' => 'Kho B',
        ]);

        $this->actingAs($manager)
            ->get('/ban-hang')
            ->assertOk()
            ->assertSee('Chưa cấu hình kho bán hàng mặc định.', false)
            ->assertSee('disabled placeholder="Tìm sản phẩm"', false)
            ->assertDontSee('id="saleStorageSelect"', false);

        $this->actingAs($manager)
            ->getJson('/ban-hang/product')
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Chưa cấu hình kho bán hàng mặc định.']);
    }

    public function test_staff_cannot_change_assigned_sale_storage(): void
    {
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct();
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 2,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $otherStorage->id,
            'quantity' => 8,
        ]);

        $this->actingAs($staff)
            ->get('/ban-hang')
            ->assertOk()
            ->assertDontSee('id="saleStorageSelect"', false);

        $this->actingAs($staff)
            ->postJson('/ban-hang/storage/select', [
                'storage_id' => $otherStorage->id,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Nhân viên không được tự thay đổi kho bán hàng.',
            ]);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product')
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $product->id,
                'storage_id' => $storage->id,
                'available_quantity' => 2,
            ]);
    }

    public function test_bank_transfer_order_appears_in_bank_transactions_for_manager(): void
    {
        $accounts = $this->seedAccounts();
        [$storage, , $staff, $manager] = $this->createStaffContext();
        $client = Client::create([
            'user_id' => $manager->id,
            'name' => 'Nguyen Van A',
            'phone' => '0912345678',
            'address' => 'Ha Noi',
        ]);
        $product = $this->createProduct(['quantity' => 12, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                ['id' => $product->id, 'qty' => 3],
            ], 300000, 'bank_transfer', $client->id))
            ->assertCreated();

        $order = Order::firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $manager->id,
            'created_by' => $staff->id,
            'payment_method' => 'bank_transfer',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $manager->id,
            'created_by' => $staff->id,
            'description' => "Thu tiền đơn hàng #{$order->id}",
            'reference_number' => (string) $order->id,
            'type' => 'credit_notice',
            'document_type' => 'order',
        ]);

        $transaction = Transaction::firstOrFail();
        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $accounts['bank']->id,
            'debit_amount' => 300000,
            'credit_amount' => 0,
            'note' => 'Chuyển khoản',
        ]);
        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $accounts['receivable']->id,
            'debit_amount' => 0,
            'credit_amount' => 300000,
            'tableable_type' => Client::class,
            'tableable_id' => $client->id,
            'note' => 'Chuyển khoản',
        ]);

        $response = $this->actingAs($manager)
            ->getJson('/admin/transactions/bank/ajax/list');

        $response->assertOk()->assertJson(['success' => true]);
        $html = $response->json('html');

        $this->assertStringContainsString($accounts['bank']->code, $html);
        $this->assertStringContainsString($client->name, $html);
        $this->assertStringContainsString('300.000', $html);
    }

    public function test_cash_order_does_not_appear_in_bank_transactions(): void
    {
        $accounts = $this->seedAccounts();
        [$storage, , $staff, $manager] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 12, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                ['id' => $product->id, 'qty' => 3],
            ], 300000, 'cash'))
            ->assertCreated();

        $this->assertDatabaseHas('transaction_entries', [
            'account_id' => $accounts['cashParent']->id,
            'debit_amount' => 300000,
            'credit_amount' => 0,
            'note' => 'Tiền mặt',
        ]);

        $response = $this->actingAs($manager)
            ->getJson('/admin/transactions/bank/ajax/list');

        $response->assertOk()->assertJson(['success' => true]);
        $html = $response->json('html');

        $this->assertStringNotContainsString($accounts['cashParent']->code, $html);
        $this->assertStringNotContainsString('300.000', $html);
    }

    public function test_accounting_entries_are_not_duplicated_for_same_order(): void
    {
        $this->seedAccounts();
        [$storage, , $staff, $manager] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 12, 'price_buy' => 100000]);
        ProductStorage::create(['product_id' => $product->id, 'storage_id' => $storage->id, 'quantity' => 10]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                ['id' => $product->id, 'qty' => 3],
            ], 300000, 'bank_transfer'))
            ->assertCreated();

        $order = Order::firstOrFail();
        $service = app(SaleService::class);
        $method = new \ReflectionMethod(SaleService::class, 'createAccountingEntries');
        $method->setAccessible(true);
        $method->invoke($service, $order, 'bank_transfer', 300000, $staff, $manager->id);

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
            'logo' => 'logo/17841017266a573b5e296c9.webp',
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

        $response->assertInternalServerError();
        $this->assertStringContainsString(
            'Không tìm thấy tài khoản tiền mặt (111).',
            $response->json('message')
        );

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

    public function test_product_endpoint_uses_assigned_storage_not_product_owner_for_staff_search(): void
    {
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'user_id' => ((int) $staff->manager_id) + 100,
            'name' => 'Phu kien',
            'code' => 'PK-001',
            'barcode' => 'PK-BAR-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'price_buy' => 1500000,
        ]);
        $otherStorageProduct = $this->createProduct([
            'user_id' => ((int) $staff->manager_id) + 100,
            'name' => 'Phu kien kho khac',
            'code' => 'PK-OTHER',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
        ]);

        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 3,
        ]);
        ProductStorage::create([
            'product_id' => $otherStorageProduct->id,
            'storage_id' => $otherStorage->id,
            'quantity' => 8,
        ]);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=Phu%20kien')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'product_id' => $product->id,
                'quantity' => 3,
                'available_quantity' => 3,
                'storage_id' => $storage->id,
                'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
            ])
            ->assertJsonMissing([
                'product_id' => $otherStorageProduct->id,
            ]);
    }

    public function test_product_endpoint_returns_imei_devices_for_imei_or_device_barcode_search(): void
    {
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'name' => 'iPhone 15 IMEI',
            'code' => 'IMEI-15',
            'barcode' => 'PROD-IMEI-15',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
            'quantity' => 99,
            'price_buy' => 12000000,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 2,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $otherStorage->id,
            'quantity' => 1,
        ]);

        $firstImei = $this->createImeiInStorage($product, $storage, '123456789012340', [
            'barcode' => 'TEL-FIRST-IMEI',
        ]);
        $secondImei = $this->createImeiInStorage($product, $storage, '123456789012341', [
            'barcode' => 'TEL-SECOND-IMEI',
        ]);
        $this->createImeiInStorage($product, $storage, '123456789012342', [
            'status' => ProductImei::STATUS_SOLD,
        ]);
        $this->createImeiInStorage($product, $otherStorage, '123456789012343');

        $response = $this->actingAs($staff)->getJson('/ban-hang/product?search=12345678901234');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'product_imei_id' => $firstImei->id,
                'product_id' => $product->id,
                'code' => 'IMEI-15',
                'barcode' => 'TEL-FIRST-IMEI',
                'imei' => '123456789012340',
                'quantity' => 1,
                'available_quantity' => 1,
                'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                'result_type' => 'imei_device',
            ])
            ->assertJsonFragment([
                'product_imei_id' => $secondImei->id,
                'barcode' => 'TEL-SECOND-IMEI',
                'imei' => '123456789012341',
            ])
            ->assertJsonMissing(['imei' => '123456789012342'])
            ->assertJsonMissing(['imei' => '123456789012343']);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=123456789012341')
            ->assertOk()
            ->assertJsonPath('0.product_imei_id', $secondImei->id);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=TEL-FIRST-IMEI')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'product_imei_id' => $firstImei->id,
                'barcode' => 'TEL-FIRST-IMEI',
            ]);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=123')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_product_endpoint_accepts_search_aliases_and_matches_code_or_barcode(): void
    {
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'name' => 'Cap sac nhanh',
            'code' => 'SKU-CABLE-001',
            'barcode' => 'BAR-CABLE-001',
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'price_buy' => 250000,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 5,
        ]);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=Cap%20sac')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'product_id' => $product->id,
                'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
            ]);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=SKU-CABLE')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'product_id' => $product->id,
                'quantity' => 5,
                'available_quantity' => 5,
                'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
            ]);

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?searchText=BAR-CABLE')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'product_id' => $product->id,
                'barcode' => 'BAR-CABLE-001',
            ]);
    }

    public function test_product_endpoint_excludes_imei_products_without_in_stock_device_in_staff_storage(): void
    {
        [$storage, $otherStorage, $staff] = $this->createStaffContext();
        $product = $this->createProduct([
            'name' => 'iPhone empty IMEI',
            'code' => 'IMEI-EMPTY',
            'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
        ]);

        $this->createImeiInStorage($product, $storage, '123456789012344', [
            'status' => ProductImei::STATUS_SOLD,
        ]);
        $this->createImeiInStorage($product, $otherStorage, '123456789012345');

        $this->actingAs($staff)
            ->getJson('/ban-hang/product?search=IMEI-EMPTY')
            ->assertOk()
            ->assertJsonCount(0);
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
                'result_type' => 'imei_device',
            ]);

        $this->actingAs($staff)
            ->postJson('/ban-hang/barcode/resolve', [
                'barcode' => $imei->imei,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'type' => Product::INVENTORY_TRACKING_IMEI,
                'product_id' => $product->id,
                'product_imei_id' => $imei->id,
                'imei' => '123456789012345',
                'barcode' => $imei->barcode,
                'quantity' => 1,
                'result_type' => 'imei_device',
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

        $response = $this->actingAs($staff)
            ->postJson('/ban-hang/order', $this->orderPayload([
                [
                    'product_id' => $product->id,
                    'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                    'product_imei_id' => $imei->id,
                    'qty' => 1,
                ],
            ], 12000000))
            ->assertInternalServerError();
        $this->assertStringContainsString(
            'Không tìm thấy tài khoản tiền mặt (111).',
            $response->json('message')
        );

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

    public function test_customer_snapshot_survives_rename_and_deactivation_without_touching_sale_data(): void
    {
        $this->seedAccounts();
        [$storage, , $staff, $manager] = $this->createStaffContext();
        $client = Client::create([
            'user_id' => $manager->id,
            'name' => 'Tên tại thời điểm bán',
            'phone' => '0911222333',
            'email' => 'snapshot@example.com',
            'address' => 'Địa chỉ lúc bán',
        ]);
        $product = $this->createProduct(['quantity' => 5, 'price_buy' => 100000]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 5,
        ]);

        $payload = $this->orderPayload([
            ['id' => $product->id, 'qty' => 1],
        ], 100000, 'cash', $client->id);
        $payload['customer']['name'] = 'Tên bị sửa từ trình duyệt';

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $payload)
            ->assertCreated();

        $order = Order::firstOrFail();
        $this->assertSame('Tên tại thời điểm bán', $order->name);
        $this->assertSame('0911222333', $order->phone);
        $this->assertSame('snapshot@example.com', $order->email);
        $this->assertSame('Địa chỉ lúc bán', $order->receive_address);

        $client->update(['name' => 'Tên mới']);
        $this->assertSame('Tên tại thời điểm bán', $order->fresh()->customer_display_name);

        $clientDebt = ClientDebt::create([
            'client_id' => $client->id,
            'amount' => 0,
            'description' => 'Đã thanh toán hết',
        ]);
        DB::table('customer_debts_detail')->insert([
            'customer_debts_id' => $clientDebt->id,
            'content' => 'Lịch sử công nợ',
            'amount' => 0,
        ]);
        DB::table('receipts')->insert([
            'client_id' => $client->id,
            'content' => 'Lịch sử thu',
            'amount_spent' => 100000,
            'date_spent' => now()->toDateString(),
            'receipt_code' => 'PT000001',
        ]);

        $countsBeforeDeactivate = [
            'orders' => DB::table('orders')->count(),
            'order_details' => DB::table('order_details')->count(),
            'transactions' => DB::table('transactions')->count(),
            'transaction_entries' => DB::table('transaction_entries')->count(),
            'customer_debts' => DB::table('customer_debts')->count(),
            'customer_debts_detail' => DB::table('customer_debts_detail')->count(),
            'receipts' => DB::table('receipts')->count(),
            'product_storage' => DB::table('product_storage')->count(),
            'product_imeis' => DB::table('product_imeis')->count(),
        ];

        $this->actingAs($staff)
            ->postJson('/admin/bulk/delete', [
                'ids' => [$client->id],
                'model' => 'Client',
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Ngừng hoạt động khách hàng thành công!',
            ]);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertSame('Tên tại thời điểm bán', $order->fresh()->customer_display_name);
        $this->assertSame('Tên mới', $order->fresh()->client->name);
        $this->assertSame(
            $countsBeforeDeactivate,
            [
                'orders' => DB::table('orders')->count(),
                'order_details' => DB::table('order_details')->count(),
                'transactions' => DB::table('transactions')->count(),
                'transaction_entries' => DB::table('transaction_entries')->count(),
                'customer_debts' => DB::table('customer_debts')->count(),
                'customer_debts_detail' => DB::table('customer_debts_detail')->count(),
                'receipts' => DB::table('receipts')->count(),
                'product_storage' => DB::table('product_storage')->count(),
                'product_imeis' => DB::table('product_imeis')->count(),
            ]
        );
        $this->assertSame('Tên mới', ClientDebt::firstOrFail()->client->name);
        $this->assertSame('Tên mới', Receipts::firstOrFail()->client->name);

        $this->actingAs($staff)
            ->getJson('/ban-hang/get-clients?searchText=Tên')
            ->assertOk()
            ->assertJsonMissing(['id' => $client->id]);
    }

    public function test_walk_in_customer_can_have_nullable_link_and_snapshots(): void
    {
        $this->seedAccounts();
        [$storage, , $staff] = $this->createStaffContext();
        $product = $this->createProduct(['quantity' => 2, 'price_buy' => 100000]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => $storage->id,
            'quantity' => 2,
        ]);

        $payload = $this->orderPayload([
            ['id' => $product->id, 'qty' => 1],
        ], 100000);
        $payload['customer'] = [
            'id' => null,
            'name' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'payment' => 'cash',
            'note' => null,
        ];

        $this->actingAs($staff)
            ->postJson('/ban-hang/order', $payload)
            ->assertCreated();

        $order = Order::firstOrFail();
        $this->assertNull($order->client_id);
        $this->assertNull($order->name);
        $this->assertNull($order->phone);
        $this->assertSame('Khách lẻ', $order->customer_display_name);
    }

    public function test_customer_with_outstanding_debt_cannot_be_deactivated(): void
    {
        [, , $staff, $manager] = $this->createStaffContext();
        $client = Client::create([
            'user_id' => $manager->id,
            'name' => 'Khách còn nợ',
            'phone' => '0999888777',
        ]);
        ClientDebt::create([
            'client_id' => $client->id,
            'amount' => 500000,
            'description' => 'Còn nợ',
        ]);

        $this->actingAs($staff)
            ->postJson('/admin/bulk/delete', [
                'ids' => [$client->id],
                'model' => 'Client',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Không thể ngừng hoạt động khách hàng đang có công nợ: Khách còn nợ',
            ]);

        $this->assertNotNull(Client::find($client->id));
        $this->assertDatabaseHas('customer_debts', [
            'client_id' => $client->id,
            'amount' => 500000,
        ]);
    }

    public function test_snapshot_backfill_is_idempotent_and_never_overwrites_existing_values(): void
    {
        [, , , $manager] = $this->createStaffContext();
        $client = Client::create([
            'user_id' => $manager->id,
            'name' => 'Tên để backfill',
            'phone' => '0901234567',
            'email' => 'backfill@example.com',
            'address' => 'Địa chỉ backfill',
        ]);
        $order = Order::create([
            'user_id' => $manager->id,
            'client_id' => $client->id,
            'name' => null,
            'phone' => 'Số đã chụp',
            'email' => null,
            'receive_address' => null,
            'total_money' => 0,
        ]);

        $this->artisan('orders:backfill-customer-snapshots')->assertSuccessful();

        $order->refresh();
        $this->assertSame('Tên để backfill', $order->name);
        $this->assertSame('Số đã chụp', $order->phone);
        $this->assertSame('backfill@example.com', $order->email);
        $this->assertSame('Địa chỉ backfill', $order->receive_address);

        $client->update([
            'name' => 'Tên thay đổi sau backfill',
            'phone' => '0000000000',
        ]);

        $this->artisan('orders:backfill-customer-snapshots')->assertSuccessful();

        $order->refresh();
        $this->assertSame('Tên để backfill', $order->name);
        $this->assertSame('Số đã chụp', $order->phone);
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
            $table->unsignedBigInteger('user_id')->nullable();
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
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
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
            $table->string('receive_address')->nullable();
            $table->decimal('total_money', 15, 2)->default(0);
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('debt_amount', 15, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->boolean('status')->default(true);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_debts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_debts_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_debts_id');
            $table->string('content');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('content')->nullable();
            $table->decimal('amount_spent', 15, 2)->default(0);
            $table->date('date_spent')->nullable();
            $table->string('receipt_code')->nullable();
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
            'email' => uniqid('staff', true).'@example.com',
            'phone' => uniqid('09'),
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $managerId,
            'storage_id' => $storageId,
            'status' => 'active',
        ]);
    }

    private function createManager(int $roleId = 2, ?int $managerId = null): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => uniqid('manager', true).'@example.com',
            'phone' => uniqid('08'),
            'password' => 'password',
            'role_id' => $roleId,
            'manager_id' => $managerId,
            'storage_id' => null,
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

    private function seedAccounts(): array
    {
        $cashParent = Account::create([
            'code' => '111',
            'name' => 'Tiền mặt',
            'level' => 1,
            'status' => true,
            'is_default' => true,
        ]);
        $bankParent = Account::create([
            'code' => '112',
            'name' => 'Tiền gửi ngân hàng',
            'level' => 1,
            'status' => true,
            'is_default' => true,
        ]);

        $cash = Account::create([
            'code' => '111CH',
            'name' => 'Tiền mặt cửa hàng',
            'parent_id' => $cashParent->id,
            'level' => 2,
            'status' => true,
            'is_default' => false,
        ]);
        $bank = Account::create([
            'code' => '112BANK',
            'name' => 'Tài khoản ngân hàng',
            'parent_id' => $bankParent->id,
            'level' => 2,
            'status' => true,
            'is_default' => false,
        ]);
        $receivable = Account::create([
            'code' => '131',
            'name' => 'Phải thu khách hàng',
            'level' => 1,
            'status' => true,
            'is_default' => true,
        ]);
        $revenue = Account::create([
            'code' => '5111',
            'name' => 'Doanh thu bán hàng',
            'level' => 1,
            'status' => true,
            'is_default' => true,
        ]);

        return compact('cashParent', 'bankParent', 'cash', 'bank', 'receivable', 'revenue');
    }

    private function orderPayload(array $items, float $grand, string $payment = 'cash', ?int $clientId = null): array
    {
        return [
            'items' => $items,
            'subtotal' => $grand,
            'discountType' => 'amount',
            'discountInput' => 0,
            'grand' => $grand,
            'customer' => [
                'id' => $clientId,
                'name' => 'Nguyen Van A',
                'email' => 'customer@example.com',
                'phone' => '0912345678',
                'address' => 'Ha Noi',
                'payment' => $payment,
                'note' => null,
            ],
        ];
    }
}
