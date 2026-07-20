<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
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
            'p_quantity' => 3,
            'p_price' => 100000,
        ]);
        $this->assertSame('9', (string) $product->fresh()->quantity);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_entries', 2);
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
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('price_buy', 15, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('product_unit')->nullable();
            $table->string('quantity')->nullable();
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
            $table->string('p_name');
            $table->decimal('p_price', 12, 2)->default(0);
            $table->integer('p_quantity')->default(0);
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
            'name' => 'iPhone 15',
            'price' => 90000,
            'price_buy' => 100000,
            'thumbnail' => null,
            'product_unit' => 'cái',
            'quantity' => 0,
            'description' => 'Test product',
            'status' => true,
        ], $overrides));
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
