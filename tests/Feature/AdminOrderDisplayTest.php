<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminOrderDisplayTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('qr')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('receiver')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('code');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('total_money')->default(0);
            $table->unsignedBigInteger('discount_value')->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_status');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_imei_id')->nullable();
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('price')->default(0);
            $table->timestamps();
        });

        $this->admin = User::create([
            'name' => 'Admin order test',
            'email' => 'admin-order@example.test',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 10,
        ]);
    }

    public function test_list_uses_canonical_payment_badges_and_summed_product_quantity(): void
    {
        $paid = $this->createOrder('PAID-ORDER', Order::PAYMENT_STATUS_PAID, false, 1000, 0);
        $partial = $this->createOrder('PARTIAL-ORDER', Order::PAYMENT_STATUS_PARTIAL, true, 400, 600);
        $debt = $this->createOrder('DEBT-ORDER', Order::PAYMENT_STATUS_DEBT, true, 0, 1000);
        $imei = $this->createOrder('IMEI-ORDER', Order::PAYMENT_STATUS_PAID, true, 2000, 0);

        $this->addQuantities($paid, [1]);
        $this->addQuantities($partial, [2, 3, 1]);
        $this->addQuantities($debt, [5]);
        OrderDetail::create([
            'order_id' => $imei->id,
            'product_id' => 20,
            'product_imei_id' => 101,
            'quantity' => 1,
            'price' => 1000,
        ]);
        OrderDetail::create([
            'order_id' => $imei->id,
            'product_id' => 20,
            'product_imei_id' => 102,
            'quantity' => 1,
            'price' => 1000,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/admin/order');

        $response->assertOk();
        $html = (string) $response->json('html');

        $this->assertStringContainsString('PAID-ORDER', $html);
        $this->assertStringContainsString('PARTIAL-ORDER', $html);
        $this->assertStringContainsString('DEBT-ORDER', $html);
        $this->assertStringContainsString('IMEI-ORDER', $html);
        $this->assertStringContainsString('Đã thanh toán', $html);
        $this->assertStringContainsString('Thanh toán một phần', $html);
        $this->assertStringContainsString('Còn nợ', $html);
        $this->assertMatchesRegularExpression(
            '/PARTIAL-ORDER.*?order-col-quantity">\s*6\s*<\/td>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/IMEI-ORDER.*?order-col-quantity">\s*2\s*<\/td>/s',
            $html
        );
        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()), 'Order list query count must stay bounded.');
    }

    public function test_payment_status_filter_does_not_use_workflow_status_or_payment_method(): void
    {
        $this->createOrder('PAID-WORKFLOW-FALSE', Order::PAYMENT_STATUS_PAID, false, 1000, 0);
        $this->createOrder(
            'PARTIAL-CASH-WORKFLOW-TRUE',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            400,
            600,
            Order::PAYMENT_METHOD_CASH
        );
        $this->createOrder('DEBT-WORKFLOW-TRUE', Order::PAYMENT_STATUS_DEBT, true, 0, 1000);

        $response = $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/admin/order?payment_status=partial');

        $response->assertOk();
        $html = (string) $response->json('html');

        $this->assertStringContainsString('PARTIAL-CASH-WORKFLOW-TRUE', $html);
        $this->assertStringNotContainsString('PAID-WORKFLOW-FALSE', $html);
        $this->assertStringNotContainsString('DEBT-WORKFLOW-TRUE', $html);
    }

    public function test_list_and_detail_share_the_same_canonical_payment_badge(): void
    {
        foreach ([
            Order::PAYMENT_STATUS_PAID => ['Đã thanh toán', 'bg-success'],
            Order::PAYMENT_STATUS_PARTIAL => ['Thanh toán một phần', 'bg-warning text-dark'],
            Order::PAYMENT_STATUS_DEBT => ['Còn nợ', 'bg-danger'],
        ] as $status => [$label, $class]) {
            $order = new Order(['payment_status' => $status]);

            $this->assertSame($label, $order->paymentStatusLabel());
            $this->assertSame($class, $order->paymentStatusBadgeClass());
        }

        $detailOrder = $this->createOrder(
            'DETAIL-PARTIAL',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            400,
            600
        );
        $detailView = app(OrderController::class)->show((string) $detailOrder->id);

        $this->assertSame('admin.order.detail', $detailView->name());
        $this->assertSame(Order::PAYMENT_STATUS_PARTIAL, $detailView->getData()['order']->payment_status);
    }

    private function createOrder(
        string $code,
        string $paymentStatus,
        bool $workflowStatus,
        int $paidAmount,
        int $debtAmount,
        string $paymentMethod = Order::PAYMENT_METHOD_DEBT
    ): Order {
        return Order::create([
            'user_id' => $this->admin->id,
            'branch_id' => $this->admin->branch_id,
            'code' => $code,
            'name' => 'Khách kiểm thử',
            'total_money' => $paidAmount + $debtAmount,
            'payment_method' => $paymentMethod,
            'paid_amount' => $paidAmount,
            'debt_amount' => $debtAmount,
            'payment_status' => $paymentStatus,
            'status' => $workflowStatus,
            'created_by' => $this->admin->id,
        ]);
    }

    private function addQuantities(Order $order, array $quantities): void
    {
        foreach ($quantities as $index => $quantity) {
            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $index + 1,
                'quantity' => $quantity,
                'price' => 100,
            ]);
        }
    }
}
