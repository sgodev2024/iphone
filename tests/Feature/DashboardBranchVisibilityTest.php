<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardBranchVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-05 12:00:00');
        $this->createDashboardSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_administrator_dashboard_is_global_while_admin_store_is_branch_scoped(): void
    {
        $now = now();
        $administratorId = DB::table('users')->insertGetId([
            'name' => 'Owner A - Clean POS',
            'email' => 'administrator@example.test',
            'phone' => '0900000001',
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $adminStoreAId = DB::table('users')->insertGetId([
            'name' => 'Admin Store A',
            'email' => 'store-a@example.test',
            'phone' => '0900000002',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 101,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $adminStoreBId = DB::table('users')->insertGetId([
            'name' => 'Admin Store B',
            'email' => 'store-b@example.test',
            'phone' => '0900000003',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 202,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $storageAId = DB::table('storages')->insertGetId([
            'user_id' => $adminStoreAId,
            'branch_id' => 101,
            'name' => 'Kho Branch A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $storageBId = DB::table('storages')->insertGetId([
            'user_id' => $adminStoreBId,
            'branch_id' => 202,
            'name' => 'Kho Branch B',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productAId = DB::table('products')->insertGetId([
            'user_id' => $adminStoreAId,
            'name' => 'Đức Việt Branch A',
            'price_buy' => 1000000,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $productBId = DB::table('products')->insertGetId([
            'user_id' => $adminStoreBId,
            'name' => 'Sản phẩm Branch B',
            'price_buy' => 500000,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_storage')->insert([
            [
                'product_id' => $productAId,
                'storage_id' => $storageAId,
                'quantity' => 24,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'product_id' => $productBId,
                'storage_id' => $storageBId,
                'quantity' => 11,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $clientAId = DB::table('clients')->insertGetId([
            'user_id' => $adminStoreAId,
            'branch_id' => 101,
            'name' => 'Khách Branch A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $clientBId = DB::table('clients')->insertGetId([
            'user_id' => $adminStoreBId,
            'branch_id' => 202,
            'name' => 'Khách Branch B',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderAId = DB::table('orders')->insertGetId([
            'user_id' => $adminStoreAId,
            'client_id' => $clientAId,
            'branch_id' => 101,
            'name' => 'Khách Branch A',
            'total_money' => 9000000,
            'status' => 1,
            'notification' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $orderBId = DB::table('orders')->insertGetId([
            'user_id' => $adminStoreBId,
            'client_id' => $clientBId,
            'branch_id' => 202,
            'name' => 'Khách Branch B',
            'total_money' => 4000000,
            'status' => 1,
            'notification' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_details')->insert([
            [
                'order_id' => $orderAId,
                'product_id' => $productAId,
                'quantity' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'order_id' => $orderBId,
                'product_id' => $productBId,
                'quantity' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $outsideRangeOrderId = DB::table('orders')->insertGetId([
            'user_id' => $adminStoreBId,
            'client_id' => null,
            'branch_id' => 202,
            'name' => 'Đơn ngoài date range',
            'total_money' => 50000000,
            'status' => 1,
            'notification' => 0,
            'created_at' => '2026-08-04 12:00:00',
            'updated_at' => '2026-08-04 12:00:00',
        ]);
        DB::table('order_details')->insert([
            'order_id' => $outsideRangeOrderId,
            'product_id' => $productBId,
            'quantity' => 100,
            'created_at' => '2026-08-04 12:00:00',
            'updated_at' => '2026-08-04 12:00:00',
        ]);

        DB::table('order_returns')->insert([
            'original_order_id' => $orderBId,
            'user_id' => $adminStoreBId,
            'branch_id' => 202,
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $administrator = User::query()->findOrFail($administratorId);
        $adminStoreA = User::query()->findOrFail($adminStoreAId);
        $storeData = $this->dashboardDataFor($adminStoreA);
        $globalData = $this->dashboardDataFor($administrator);

        $this->assertSame(9000000.0, $storeData['stats']['today_revenue']);
        $this->assertSame(1, $storeData['orderStats']['today_orders']);
        $this->assertSame(9000000.0, $storeData['totalRevenueStats']['total_revenue']);
        $this->assertSame(24, $storeData['inventoryStats']['total_stock']);
        $this->assertSame(1, $storeData['newCustomers']['total_new']);
        $this->assertSame(1, $storeData['newCustomers']['today_new']);
        $this->assertSame(['Đức Việt Branch A'], collect($storeData['topSellingProducts'])->pluck('name')->all());
        $this->assertSame(3, (int) $storeData['topSellingProducts'][0]->total_sold);
        $this->assertCount(1, $storeData['latestOrders']);
        $this->assertSame(0, $storeData['returnStats']['returned_orders']);

        $this->assertSame(13000000.0, $globalData['stats']['today_revenue']);
        $this->assertSame(2, $globalData['orderStats']['today_orders']);
        $this->assertSame(13000000.0, $globalData['totalRevenueStats']['total_revenue']);
        $this->assertSame(35, $globalData['inventoryStats']['total_stock']);
        $this->assertSame(2, $globalData['newCustomers']['total_new']);
        $this->assertSame(2, $globalData['newCustomers']['today_new']);
        $this->assertEqualsCanonicalizing(
            ['Đức Việt Branch A', 'Sản phẩm Branch B'],
            collect($globalData['topSellingProducts'])->pluck('name')->all()
        );
        $globalSold = collect($globalData['topSellingProducts'])->pluck('total_sold', 'name');
        $this->assertSame(3, (int) $globalSold['Đức Việt Branch A']);
        $this->assertSame(5, (int) $globalSold['Sản phẩm Branch B']);
        $this->assertCount(3, $globalData['latestOrders']);
        $this->assertSame(1, $globalData['returnStats']['returned_orders']);

        $this->assertGreaterThanOrEqual(
            $storeData['stats']['today_revenue'],
            $globalData['stats']['today_revenue']
        );
        $this->assertGreaterThanOrEqual(
            $storeData['orderStats']['today_orders'],
            $globalData['orderStats']['today_orders']
        );
        $this->assertGreaterThanOrEqual(
            $storeData['inventoryStats']['total_stock'],
            $globalData['inventoryStats']['total_stock']
        );
        $this->assertGreaterThanOrEqual(
            $storeData['newCustomers']['total_new'],
            $globalData['newCustomers']['total_new']
        );

        $notifications = app(OrderService::class);
        $this->assertSame(1, $notifications->getOrderNotification($adminStoreA)->count());
        $this->assertSame(2, $notifications->getOrderNotification($administrator)->count());
    }

    private function dashboardDataFor(User $actor): array
    {
        $this->actingAs($actor);
        $request = Request::create('/admin', 'GET', [
            'start_date' => '2026-08-05',
            'end_date' => '2026-09-05',
        ]);
        $request->setUserResolver(fn () => $actor);
        $this->app->instance('request', $request);

        return app(DashboardController::class)->index($request)->getData();
    }

    private function createDashboardSchema(): void
    {
        foreach ([
            'order_returns',
            'order_details',
            'orders',
            'product_storage',
            'product_imeis',
            'import_detail',
            'products',
            'storages',
            'clients',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('price_buy')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_storage', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('total_money')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_imei_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('product_imeis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('import_detail_id')->nullable();
        });

        Schema::create('import_detail', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price')->default(0);
        });

        Schema::create('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('original_order_id');
            $table->unsignedBigInteger('exchange_order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('return_amount')->default(0);
            $table->unsignedBigInteger('exchange_amount')->default(0);
            $table->unsignedBigInteger('fee_amount')->default(0);
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->unsignedBigInteger('additional_payment')->default(0);
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }
}
