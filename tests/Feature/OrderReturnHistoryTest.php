<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Roles;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderReturnHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_return_list_and_detail_are_scoped_for_administrator_admin_store_and_staff(): void
    {
        $admin = $this->actor(Roles::ADMINISTRATOR_ID, null, 'Administrator');
        $managerA = $this->actor(Roles::ADMIN_STORE_ID, 1, 'Manager A');
        $staffA = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $staffSameBranch = $this->actor(Roles::STAFF_ID, 1, 'Staff A2', 1);
        $staffB = $this->actor(Roles::STAFF_ID, 2, 'Staff B', 2);

        $returnA = $this->returnFixture($staffA, 1, 'ODR-A-001', 'RTN-A-001');
        $returnSameBranch = $this->returnFixture($staffSameBranch, 1, 'ODR-A-002', 'RTN-A-002');
        $returnB = $this->returnFixture($staffB, 2, 'ODR-B-001', 'RTN-B-001');

        $this->actingAs($admin)
            ->get(route('staff.returns.index'))
            ->assertOk()
            ->assertSee('RTN-A-001')
            ->assertSee('RTN-A-002')
            ->assertSee('RTN-B-001');

        $this->actingAs($managerA)
            ->get(route('staff.returns.index'))
            ->assertOk()
            ->assertSee('RTN-A-001')
            ->assertSee('RTN-A-002')
            ->assertDontSee('RTN-B-001');

        $this->actingAs($managerA)
            ->get(route('staff.returns.show', $returnA))
            ->assertOk();

        $this->actingAs($staffA)
            ->get(route('staff.returns.index'))
            ->assertOk()
            ->assertSee('RTN-A-001')
            ->assertDontSee('RTN-A-002')
            ->assertDontSee('RTN-B-001');

        $this->actingAs($admin)
            ->get(route('staff.returns.show', $returnB))
            ->assertOk();

        $this->actingAs($managerA)
            ->get(route('staff.returns.show', $returnB))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->get(route('staff.returns.show', $returnSameBranch))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->get(route('staff.returns.show', $returnA))
            ->assertOk();
    }

    public function test_return_index_searches_codes_and_renders_original_order_and_amounts(): void
    {
        $admin = $this->actor(Roles::ADMINISTRATOR_ID, null, 'Administrator');
        $staff = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $this->returnFixture($staff, 1, 'ODR-SEARCH-001', 'RTN-SEARCH-001', [
            'return_amount' => 120000,
            'exchange_amount' => 90000,
            'fee_amount' => 5000,
            'refund_amount' => 25000,
        ]);
        $this->returnFixture($staff, 1, 'ODR-OTHER-001', 'RTN-OTHER-001');

        $this->actingAs($admin)
            ->get(route('staff.returns.index', ['s' => 'ODR-SEARCH']))
            ->assertOk()
            ->assertSee('RTN-SEARCH-001')
            ->assertSee('ODR-SEARCH-001')
            ->assertSee('120.000')
            ->assertSee('90.000')
            ->assertSee('25.000')
            ->assertDontSee('RTN-OTHER-001');

        $this->actingAs($admin)
            ->get(route('staff.returns.index', ['s' => 'Khách hàng ODR-SEARCH-001']))
            ->assertOk()
            ->assertSee('RTN-SEARCH-001')
            ->assertDontSee('RTN-OTHER-001');
    }

    public function test_return_detail_renders_imei_items_settlement_and_print_action(): void
    {
        $admin = $this->actor(Roles::ADMINISTRATOR_ID, null, 'Administrator');
        $staff = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $return = $this->returnFixture($staff, 1, 'ODR-IMEI-001', 'RTN-IMEI-001', [
            'return_amount' => 150000,
            'exchange_amount' => 100000,
            'fee_amount' => 10000,
            'refund_amount' => 40000,
            'note' => 'Khách đổi máy khác',
        ], true);

        $this->actingAs($admin)
            ->get(route('staff.returns.show', $return))
            ->assertOk()
            ->assertSee('PHIẾU ĐỔI / TRẢ HÀNG')
            ->assertSee('RTN-IMEI-001')
            ->assertSee('IMEI-RETURN-HISTORY')
            ->assertSee('150.000')
            ->assertSee('100.000')
            ->assertSee('40.000')
            ->assertSee('Khách đổi máy khác')
            ->assertSee('In phiếu');
    }

    public function test_multiple_returns_for_one_order_are_all_visible(): void
    {
        $staff = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $first = $this->returnFixture($staff, 1, 'ODR-MULTI-001', 'RTN-MULTI-001');

        $second = OrderReturn::create([
            'code' => 'RTN-MULTI-002',
            'original_order_id' => $first->original_order_id,
            'user_id' => $staff->id,
            'branch_id' => 1,
            'created_by' => $staff->id,
            'return_amount' => 50000,
            'refund_amount' => 50000,
            'status' => 'completed',
        ]);
        $detail = OrderDetail::query()->where('order_id', $first->original_order_id)->firstOrFail();
        $second->details()->create([
            'order_detail_id' => $detail->id,
            'product_id' => $detail->product_id,
            'storage_id' => $detail->storage_id,
            'quantity' => 1,
            'original_unit_price' => 50000,
            'gross_amount' => 50000,
            'discount_amount' => 0,
            'return_amount' => 50000,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.returns.index'))
            ->assertOk()
            ->assertSee('RTN-MULTI-001')
            ->assertSee('RTN-MULTI-002');

        $orderHistory = $this->actingAs($staff)
            ->get(route('staff.order'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $historyHtml = (string) $orderHistory->json('html');
        $this->assertStringContainsString('2 phiếu trả', $historyHtml);
        $this->assertStringContainsString('RTN-MULTI-001', $historyHtml);
        $this->assertStringContainsString('RTN-MULTI-002', $historyHtml);
    }

    public function test_staff_cannot_create_or_store_return_for_another_staff_order_in_same_branch(): void
    {
        $staffA = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $staffB = $this->actor(Roles::STAFF_ID, 1, 'Staff B', 1);
        $return = $this->returnFixture($staffB, 1, 'ODR-OWNER-001', 'RTN-OWNER-001');
        $order = $return->originalOrder;
        $order->update(['user_id' => $staffA->id]);
        $detail = $order->orderDetails()->firstOrFail();

        $this->actingAs($staffA)
            ->get(route('staff.orders.returns.create', $order))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->postJson(route('staff.orders.returns.store', $order), [
                'return_items' => [[
                    'order_detail_id' => $detail->id,
                    'quantity' => 1,
                ]],
                'fee_amount' => 0,
            ])
            ->assertNotFound();

        $this->actingAs($staffA)
            ->postJson(route('staff.orders.returns.store', $order), [])
            ->assertNotFound();
    }

    public function test_store_response_points_to_the_new_return_detail(): void
    {
        $staff = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $product = Product::create([
            'code' => 'SP-STORE-001',
            'name' => 'iPhone Store Test',
            'price' => 200000,
            'quantity' => 0,
            'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
            'status' => true,
        ]);
        ProductStorage::create([
            'product_id' => $product->id,
            'storage_id' => 1,
            'quantity' => 0,
        ]);
        $order = $this->orderFixture($staff, 1, 'ODR-STORE-001', $product, 1, 2);
        $detail = $order->orderDetails()->firstOrFail();

        $response = $this->actingAs($staff)
            ->postJson(route('staff.orders.returns.store', $order), [
                'return_items' => [[
                    'order_detail_id' => $detail->id,
                    'quantity' => 1,
                ]],
                'fee_amount' => 10000,
                'note' => 'Return created from endpoint',
            ])
            ->assertCreated()
            ->assertJsonPath('order_return.original_order.code', 'ODR-STORE-001');

        $returnId = (int) $response->json('order_return.id');
        $showUrl = route('staff.returns.show', $returnId);

        $response->assertJsonPath('order_return.show_url', $showUrl);
        $this->actingAs($staff)
            ->get($showUrl)
            ->assertOk()
            ->assertSee($response->json('order_return.code'));
    }

    public function test_missing_return_permissions_are_forbidden(): void
    {
        $staff = $this->actor(Roles::STAFF_ID, 1, 'Staff A', 1);
        $return = $this->returnFixture($staff, 1, 'ODR-PERM-001', 'RTN-PERM-001');
        $order = $return->originalOrder;

        $this->removePermission(Roles::STAFF_ID, 'order_return.view');
        $this->actingAs($staff)
            ->get(route('staff.returns.index'))
            ->assertForbidden();

        $this->removePermission(Roles::STAFF_ID, 'order_return.detail');
        $this->actingAs($staff)
            ->get(route('staff.returns.show', $return))
            ->assertForbidden();

        $this->removePermission(Roles::STAFF_ID, 'order_return.create');
        $this->actingAs($staff)
            ->get(route('staff.orders.returns.create', $order))
            ->assertForbidden();
        $this->actingAs($staff)
            ->postJson(route('staff.orders.returns.store', $order), [])
            ->assertForbidden();
    }

    private function actor(int $roleId, ?int $branchId, string $name, ?int $storageId = null): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).uniqid('', true).'@example.test',
            'phone' => uniqid('09'),
            'password' => 'password',
            'status' => 'active',
            'role_id' => $roleId,
            'branch_id' => $branchId,
            'storage_id' => $storageId,
        ]);
    }

    private function returnFixture(
        User $seller,
        int $branchId,
        string $orderCode,
        string $returnCode,
        array $amounts = [],
        bool $withImei = false
    ): OrderReturn {
        $product = Product::create([
            'code' => 'SP-'.$returnCode,
            'name' => 'iPhone Return Test',
            'price' => 150000,
            'quantity' => 0,
            'inventory_tracking' => $withImei
                ? Product::INVENTORY_TRACKING_IMEI
                : Product::INVENTORY_TRACKING_QUANTITY,
            'status' => true,
        ]);
        $imei = null;

        if ($withImei) {
            $imei = ProductImei::create([
                'product_id' => $product->id,
                'storage_id' => $branchId,
                'imei' => 'IMEI-RETURN-HISTORY',
                'status' => ProductImei::STATUS_IN_STOCK,
            ]);
        }

        $order = $this->orderFixture($seller, $branchId, $orderCode, $product, $branchId, 2, $imei);
        $detail = $order->orderDetails()->firstOrFail();
        $orderReturn = OrderReturn::create(array_merge([
            'code' => $returnCode,
            'original_order_id' => $order->id,
            'user_id' => $seller->id,
            'branch_id' => $branchId,
            'created_by' => $seller->id,
            'return_amount' => 100000,
            'exchange_amount' => 0,
            'fee_amount' => 0,
            'refund_amount' => 100000,
            'additional_payment' => 0,
            'status' => 'completed',
            'note' => null,
        ], $amounts));
        $orderReturn->details()->create([
            'order_detail_id' => $detail->id,
            'product_id' => $product->id,
            'product_imei_id' => $imei?->id,
            'storage_id' => $branchId,
            'quantity' => 1,
            'original_unit_price' => 150000,
            'gross_amount' => 150000,
            'discount_amount' => 0,
            'return_amount' => (int) $orderReturn->return_amount,
        ]);

        return $orderReturn->fresh('originalOrder');
    }

    private function orderFixture(
        User $seller,
        int $branchId,
        string $code,
        Product $product,
        int $storageId,
        int $quantity,
        ?ProductImei $imei = null
    ): Order {
        $order = Order::create([
            'user_id' => $seller->id,
            'branch_id' => $branchId,
            'code' => $code,
            'name' => 'Khách hàng '.$code,
            'phone' => '0900000000',
            'total_money' => (int) $product->price * $quantity,
            'discount_value' => 0,
            'discount_type' => 'amount',
            'payment_method' => Order::PAYMENT_METHOD_CASH,
            'paid_amount' => (int) $product->price * $quantity,
            'debt_amount' => 0,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'status' => true,
            'created_by' => $seller->id,
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'storage_id' => $storageId,
            'product_id' => $product->id,
            'product_imei_id' => $imei?->id,
            'price' => $product->price,
            'quantity' => $quantity,
        ]);

        return $order->fresh();
    }

    private function removePermission(int $roleId, string $key): void
    {
        $permissionId = DB::table('permissions')
            ->where('permission_key', $key)
            ->value('id');

        DB::table('role_permission')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->delete();

        User::query()->get()->each(fn (User $user) => $user->unsetRelation('role'));
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'Branch A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Branch B', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('logo')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('receiver')->nullable();
            $table->string('qr')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->string('location')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        DB::table('storages')->insert([
            ['id' => 1, 'branch_id' => 1, 'name' => 'Kho A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'branch_id' => 2, 'name' => 'Kho B', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('quantity')->default(0);
            $table->string('inventory_tracking', 20)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_imeis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('imei', 50)->unique();
            $table->string('status', 30);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_storage', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('receive_address')->nullable();
            $table->unsignedBigInteger('total_money')->default(0);
            $table->unsignedBigInteger('discount_value')->default(0);
            $table->string('discount_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_status')->nullable();
            $table->boolean('status')->default(true);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_imei_id')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('original_order_id');
            $table->unsignedBigInteger('exchange_order_id')->nullable()->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('return_amount')->default(0);
            $table->unsignedBigInteger('exchange_amount')->default(0);
            $table->unsignedBigInteger('fee_amount')->default(0);
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->unsignedBigInteger('additional_payment')->default(0);
            $table->string('status', 20)->default('completed');
            $table->string('note', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('order_return_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_return_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_imei_id')->nullable();
            $table->unsignedBigInteger('storage_id');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('original_unit_price');
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('return_amount');
            $table->timestamps();
        });
    }
}
