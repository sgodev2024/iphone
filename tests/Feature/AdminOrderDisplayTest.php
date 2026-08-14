<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Services\OrderPaymentHistoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminOrderDisplayTest extends TestCase
{
    private User $admin;

    private int $receivableAccountId;

    private int $cashAccountId;

    private int $bankParentAccountId;

    private int $bankAccountId;

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

        Schema::create('user_info', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('img_url')->nullable();
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

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
            $table->string('document_type')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('transaction_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        $this->admin = User::create([
            'name' => 'Admin order test',
            'email' => 'admin-order@example.test',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 10,
        ]);

        $this->receivableAccountId = DB::table('accounts')->insertGetId([
            'code' => '131',
            'name' => 'Phải thu khách hàng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedPaymentAccounts();
    }

    private function seedPaymentAccounts(): void
    {
        $this->cashAccountId = $this->createAccount('111', 'Cash');
        $this->bankParentAccountId = $this->createAccount('112', 'Bank');
        $this->bankAccountId = $this->createAccount('112TEST', 'Test bank', $this->bankParentAccountId);
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
        $this->assertLessThanOrEqual(7, count(DB::getQueryLog()), 'Order list query count must stay bounded.');
    }

    public function test_order_summary_counts_and_sums_total_money_without_ledger_duplication(): void
    {
        $first = $this->createOrder(
            'SUMMARY-PAID',
            Order::PAYMENT_STATUS_PAID,
            true,
            2000000,
            0,
            Order::PAYMENT_METHOD_CASH
        );
        $second = $this->createOrder(
            'SUMMARY-PARTIAL',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            1500000,
            1500000,
            Order::PAYMENT_METHOD_BANK_TRANSFER
        );
        $third = $this->createOrder(
            'SUMMARY-DEBT',
            Order::PAYMENT_STATUS_DEBT,
            true,
            0,
            5000000,
            Order::PAYMENT_METHOD_DEBT
        );

        $this->createLedgerTransaction($first, 'sale', [
            ['debit_amount' => 2000000],
        ]);
        foreach ([500000, 500000] as $amount) {
            $this->createLedgerTransaction($first, 'income', [
                ['credit_amount' => $amount],
            ]);
        }

        $html = $this->getOrderListHtml();

        $this->assertStringContainsString('Tổng đơn hàng:', $html);
        $this->assertStringContainsString('>3</strong>', $html);
        $this->assertStringContainsString('Tổng doanh thu:', $html);
        $this->assertStringContainsString('10.000.000 VND', $html);
        $this->assertStringContainsString($first->code, $html);
        $this->assertStringContainsString($second->code, $html);
        $this->assertStringContainsString($third->code, $html);
    }

    public function test_order_summary_uses_the_same_filters_on_every_page(): void
    {
        for ($index = 1; $index <= 11; $index++) {
            $order = $this->createOrder(
                "SUMMARY-SCOPE-{$index}",
                Order::PAYMENT_STATUS_PARTIAL,
                true,
                500000,
                500000,
                Order::PAYMENT_METHOD_CASH,
                'Scoped customer',
                '0909111111',
                10,
                '2026-08-13 10:00:00'
            );

            if ($index === 1) {
                $this->createLedgerTransaction($order, 'sale', [
                    ['debit_amount' => 1000000],
                ]);
                foreach ([250000, 250000] as $amount) {
                    $this->createLedgerTransaction($order, 'income', [
                        ['credit_amount' => $amount],
                    ]);
                }
            }
        }

        $this->createOrder(
            'SUMMARY-SCOPE-EXCLUDED',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            9000000,
            0,
            Order::PAYMENT_METHOD_BANK_TRANSFER,
            'Scoped customer',
            '0909111111',
            10,
            '2026-08-13 10:00:00'
        );

        $query = [
            's' => 'Scoped customer',
            'date_range' => '13/08/2026 - 13/08/2026',
            'payment_status' => Order::PAYMENT_STATUS_PARTIAL,
            'payment_method' => Order::PAYMENT_METHOD_CASH,
        ];

        $pageOne = $this->getOrderListHtml($query + ['page' => 1]);
        $pageTwo = $this->getOrderListHtml($query + ['page' => 2]);

        foreach ([$pageOne, $pageTwo] as $html) {
            $this->assertStringContainsString('Tổng đơn hàng:', $html);
            $this->assertStringContainsString('>11</strong>', $html);
            $this->assertStringContainsString('11.000.000 VND', $html);
        }

        $this->assertStringContainsString('SUMMARY-SCOPE-1', $pageOne);
        $this->assertStringContainsString('SUMMARY-SCOPE-11', $pageTwo);
        $this->assertStringNotContainsString('SUMMARY-SCOPE-EXCLUDED', $pageOne);
        $this->assertStringNotContainsString('SUMMARY-SCOPE-EXCLUDED', $pageTwo);
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

    public function test_remaining_debt_uses_sale_debit_from_tk131_for_unpaid_order(): void
    {
        $order = $this->createOrder('LEDGER-UNPAID', Order::PAYMENT_STATUS_DEBT, true, 0, 999);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 3000000],
        ]);

        $html = $this->getOrderListHtml();

        $this->assertStringContainsString('CÒN NỢ', $html);
        $this->assertMatchesRegularExpression(
            '/LEDGER-UNPAID.*?order-col-debt">\s*<span class="text-danger">3\.000\.000 VND<\/span>/s',
            $html
        );
    }

    public function test_remaining_debt_subtracts_completed_partial_payment_from_tk131(): void
    {
        $order = $this->createOrder('LEDGER-PARTIAL', Order::PAYMENT_STATUS_PARTIAL, true, 7000000, 3000000);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 10000000],
        ]);
        $this->createLedgerTransaction($order, 'income', [
            ['credit_amount' => 7000000],
        ]);

        $html = $this->getOrderListHtml();

        $this->assertMatchesRegularExpression(
            '/LEDGER-PARTIAL.*?order-col-debt">\s*<span class="text-danger">3\.000\.000 VND<\/span>/s',
            $html
        );
    }

    public function test_fully_paid_order_has_a_blank_remaining_debt_cell(): void
    {
        $order = $this->createOrder('LEDGER-PAID', Order::PAYMENT_STATUS_PAID, true, 10000000, 0);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 10000000],
        ]);
        $this->createLedgerTransaction($order, 'credit_notice', [
            ['credit_amount' => 10000000],
        ]);

        $html = $this->getOrderListHtml();

        $this->assertMatchesRegularExpression(
            '/LEDGER-PAID.*?<td class="text-end fw-semibold order-col-debt">\s*<\/td>/s',
            $html
        );
        $this->assertStringNotContainsString('<span class="text-danger">10.000.000 VND</span>', $html);
    }

    public function test_multi_payment_sums_all_completed_payment_credits(): void
    {
        $order = $this->createOrder('LEDGER-MULTI-PAYMENT', Order::PAYMENT_STATUS_PARTIAL, true, 7000000, 3000000);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 10000000],
        ]);

        foreach ([4000000, 2000000, 1000000] as $amount) {
            $this->createLedgerTransaction($order, 'income', [
                ['credit_amount' => $amount],
            ]);
        }

        $html = $this->getOrderListHtml();

        $this->assertMatchesRegularExpression(
            '/LEDGER-MULTI-PAYMENT.*?order-col-debt">\s*<span class="text-danger">3\.000\.000 VND<\/span>/s',
            $html
        );
    }

    public function test_non_completed_payment_does_not_reduce_remaining_debt(): void
    {
        $order = $this->createOrder('LEDGER-PENDING-PAYMENT', Order::PAYMENT_STATUS_PARTIAL, true, 7000000, 3000000);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 10000000],
        ]);
        $this->createLedgerTransaction($order, 'income', [
            ['credit_amount' => 7000000],
        ]);
        $this->createLedgerTransaction($order, 'income', [
            ['credit_amount' => 3000000],
        ], 'pending');

        $html = $this->getOrderListHtml();

        $this->assertMatchesRegularExpression(
            '/LEDGER-PENDING-PAYMENT.*?order-col-debt">\s*<span class="text-danger">3\.000\.000 VND<\/span>/s',
            $html
        );
    }

    public function test_ledger_transactions_with_wrong_document_type_are_ignored(): void
    {
        $order = $this->createOrder('LEDGER-WRONG-DOCUMENT', Order::PAYMENT_STATUS_PARTIAL, true, 7000000, 3000000);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 10000000],
        ]);
        $this->createLedgerTransaction($order, 'income', [
            ['credit_amount' => 7000000],
        ], 'completed', 'invoice');

        $html = $this->getOrderListHtml();

        $this->assertMatchesRegularExpression(
            '/LEDGER-WRONG-DOCUMENT.*?order-col-debt">\s*<span class="text-danger">10\.000\.000 VND<\/span>/s',
            $html
        );
    }

    public function test_ledger_transactions_with_invalid_type_or_account_are_ignored(): void
    {
        $order = $this->createOrder('LEDGER-WRONG-TYPE-ACCOUNT', Order::PAYMENT_STATUS_PARTIAL, true, 7000000, 3000000);
        $this->createLedgerTransaction($order, 'sale', [
            ['debit_amount' => 10000000],
        ]);
        $this->createLedgerTransaction($order, 'expense', [
            ['credit_amount' => 4000000],
        ]);
        $wrongAccountId = $this->createAccount('5111');
        $this->createLedgerTransaction($order, 'income', [
            ['account_id' => $wrongAccountId, 'credit_amount' => 3000000],
        ]);
        $this->createLedgerTransaction($order, 'income', [
            ['credit_amount' => 3000000],
        ], 'completed', 'order', (string) ($order->id + 100));

        $html = $this->getOrderListHtml();

        $this->assertMatchesRegularExpression(
            '/LEDGER-WRONG-TYPE-ACCOUNT.*?order-col-debt">\s*<span class="text-danger">10\.000\.000 VND<\/span>/s',
            $html
        );
    }

    public function test_missing_sale_and_negative_remaining_debt_are_left_blank_as_anomalies(): void
    {
        $missingSale = $this->createOrder('LEDGER-MISSING-SALE', Order::PAYMENT_STATUS_PAID, true, 1000000, 0);
        $this->createLedgerTransaction($missingSale, 'income', [
            ['credit_amount' => 1000000],
        ]);

        $negative = $this->createOrder('LEDGER-NEGATIVE', Order::PAYMENT_STATUS_PAID, true, 2000000, 0);
        $this->createLedgerTransaction($negative, 'sale', [
            ['debit_amount' => 1000000],
        ]);
        $this->createLedgerTransaction($negative, 'income', [
            ['credit_amount' => 2000000],
        ]);

        $html = $this->getOrderListHtml();

        foreach (['LEDGER-MISSING-SALE', 'LEDGER-NEGATIVE'] as $code) {
            $this->assertMatchesRegularExpression(
                "/{$code}.*?<td class=\"text-end fw-semibold order-col-debt\">\\s*<\\/td>/s",
                $html
            );
        }
    }

    public function test_ledger_aggregation_does_not_duplicate_orders_and_pagination_stays_at_ten_rows(): void
    {
        for ($index = 1; $index <= 11; $index++) {
            $order = $this->createOrder(
                "LEDGER-PAGE-{$index}",
                Order::PAYMENT_STATUS_PARTIAL,
                true,
                7000000,
                3000000
            );
            $this->createLedgerTransaction($order, 'sale', [
                ['debit_amount' => 10000000],
            ]);

            foreach ([4000000, 2000000, 1000000] as $amount) {
                $this->createLedgerTransaction($order, 'income', [
                    ['credit_amount' => $amount],
                ]);
            }
        }

        $html = $this->getOrderListHtml(['s' => 'LEDGER-PAGE']);

        $this->assertSame(10, substr_count($html, '<td class="text-end fw-semibold order-col-debt">'));
        $this->assertStringContainsString('Trang 1 / 2', $html);
        $this->assertStringContainsString('s=LEDGER-PAGE', html_entity_decode($html));
    }

    public function test_search_date_payment_method_and_branch_filters_remain_intact(): void
    {
        $this->createOrder(
            'FILTER-CUSTOMER',
            Order::PAYMENT_STATUS_PAID,
            true,
            1000,
            0,
            Order::PAYMENT_METHOD_CASH,
            'Customer Searchable',
            '0909000000',
            10,
            '2026-08-05 10:00:00'
        );
        $this->createOrder(
            'FILTER-OTHER-BRANCH',
            Order::PAYMENT_STATUS_PAID,
            true,
            1000,
            0,
            Order::PAYMENT_METHOD_CASH,
            'Customer Searchable',
            '0909000000',
            99,
            '2026-08-05 10:00:00'
        );
        $this->createOrder(
            'FILTER-OLD',
            Order::PAYMENT_STATUS_PAID,
            true,
            1000,
            0,
            Order::PAYMENT_METHOD_BANK_TRANSFER,
            'Old Customer',
            '0888000000',
            10,
            '2026-07-01 10:00:00'
        );

        $this->assertStringContainsString('FILTER-CUSTOMER', $this->getOrderListHtml(['s' => 'Customer Searchable']));
        $this->assertStringNotContainsString('FILTER-OTHER-BRANCH', $this->getOrderListHtml(['s' => 'Customer Searchable']));
        $this->assertStringContainsString('FILTER-CUSTOMER', $this->getOrderListHtml(['s' => '0909000000']));
        $this->assertStringContainsString('FILTER-CUSTOMER', $this->getOrderListHtml(['date_range' => '01/08/2026 - 10/08/2026']));
        $this->assertStringNotContainsString('FILTER-OLD', $this->getOrderListHtml(['date_range' => '01/08/2026 - 10/08/2026']));
        $this->assertStringContainsString('FILTER-CUSTOMER', $this->getOrderListHtml(['payment_method' => Order::PAYMENT_METHOD_CASH]));
        $this->assertStringNotContainsString('FILTER-OLD', $this->getOrderListHtml(['payment_method' => Order::PAYMENT_METHOD_CASH]));
        $this->assertSame(1, substr_count($this->getOrderListHtml(), 'FILTER-CUSTOMER'));
    }

    public function test_detail_renders_checkout_partial_payment_from_tk131(): void
    {
        $order = $this->createOrder(
            'ORDER-PAYMENT-19',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            1000000,
            1000000
        );

        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'Thu tiền đơn hàng #19',
            createdBy: $this->admin->id,
            transactionDate: '2026-08-13'
        );

        $response = $this->getOrderDetailResponse($order);
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, substr_count($html, 'payment-history-row'));
        $this->assertStringContainsString('LỊCH SỬ THANH TOÁN', $html);
        $this->assertStringContainsString('13/08/2026', $html);
        $this->assertStringContainsString('1.000.000 VND', $html);
        $this->assertStringContainsString('Tiền mặt', $html);
        $this->assertStringContainsString('Admin order test', $html);
        $this->assertStringContainsString('Thu tiền đơn hàng #19', $html);
    }

    public function test_debt_order_without_payment_shows_empty_state(): void
    {
        $order = $this->createOrder(
            'ORDER-WITHOUT-PAYMENT',
            Order::PAYMENT_STATUS_DEBT,
            true,
            0,
            2000000
        );

        $response = $this->getOrderDetailResponse($order);
        $html = $response->getContent();

        $response->assertOk();
        $this->assertStringContainsString('LỊCH SỬ THANH TOÁN', $html);
        $this->assertStringContainsString('Chưa có lịch sử thanh toán', $html);
        $this->assertSame(0, substr_count($html, 'payment-history-row'));
    }

    public function test_fully_paid_order_keeps_payment_history_visible(): void
    {
        $order = $this->createOrder(
            'ORDER-FULLY-PAID',
            Order::PAYMENT_STATUS_PAID,
            true,
            3000000,
            0
        );

        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 3000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 3000000],
            ],
            'Thanh toán đủ',
            createdBy: $this->admin->id
        );

        $response = $this->getOrderDetailResponse($order);

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'payment-history-row'));
    }

    public function test_multi_payment_renders_one_row_per_transaction_and_maps_methods_from_accounts(): void
    {
        $order = $this->createOrder(
            'ORDER-MULTI-PAYMENT',
            Order::PAYMENT_STATUS_PAID,
            true,
            10000000,
            0
        );

        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 2000000],
                ['account_id' => $this->cashAccountId, 'debit_amount' => 2000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 4000000],
            ],
            'Payment 1',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'credit_notice',
            [
                ['account_id' => $this->bankAccountId, 'debit_amount' => 2000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 2000000],
            ],
            'Payment 2',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 4000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 4000000],
            ],
            'Payment 3',
            createdBy: $this->admin->id
        );

        $response = $this->getOrderDetailResponse($order);
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(3, substr_count($html, 'payment-history-row'));
        $this->assertSame(2, substr_count($html, 'Tiền mặt'));
        $this->assertSame(1, substr_count($html, 'Chuyển khoản'));
        $this->assertStringContainsString('Payment 1', $html);
        $this->assertStringContainsString('Payment 2', $html);
        $this->assertStringContainsString('Payment 3', $html);
    }

    public function test_payment_history_excludes_non_canonical_transactions_and_wrong_owner(): void
    {
        $order = $this->createOrder(
            'ORDER-PAYMENT-FILTERS',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            1000000,
            1000000
        );
        $revenueAccountId = $this->createAccount('5111', 'Revenue');
        $otherOwner = User::create([
            'name' => 'Other owner',
            'email' => 'other-owner@example.test',
            'password' => 'password',
        ]);

        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'VALID PAYMENT',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'PENDING PAYMENT',
            status: 'pending',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'FAILED PAYMENT',
            status: 'failed',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'sale',
            [
                ['account_id' => $this->receivableAccountId, 'debit_amount' => 1000000],
                ['account_id' => $revenueAccountId, 'credit_amount' => 1000000],
            ],
            'SALE TRANSACTION',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'WRONG DOCUMENT',
            documentType: 'invoice',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'WRONG OWNER',
            userId: $otherOwner->id,
            createdBy: $otherOwner->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $revenueAccountId, 'credit_amount' => 1000000],
            ],
            'NO CREDIT 131',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $revenueAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'NO DEBIT 111 OR 112',
            createdBy: $this->admin->id
        );
        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 999999],
            ],
            'UNBALANCED PAYMENT',
            createdBy: $this->admin->id
        );

        $response = $this->getOrderDetailResponse($order);
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, substr_count($html, 'payment-history-row'));
        $this->assertStringContainsString('VALID PAYMENT', $html);
        foreach ([
            'PENDING PAYMENT',
            'FAILED PAYMENT',
            'SALE TRANSACTION',
            'WRONG DOCUMENT',
            'WRONG OWNER',
            'NO CREDIT 131',
            'NO DEBIT 111 OR 112',
            'UNBALANCED PAYMENT',
        ] as $excludedDescription) {
            $this->assertStringNotContainsString($excludedDescription, $html);
        }
    }

    public function test_payment_history_does_not_crash_when_created_by_is_null(): void
    {
        $order = $this->createOrder(
            'ORDER-MISSING-CREATOR',
            Order::PAYMENT_STATUS_PARTIAL,
            true,
            500000,
            500000
        );

        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 500000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 500000],
            ],
            'PAYMENT WITHOUT CREATOR'
        );

        $response = $this->getOrderDetailResponse($order);

        $response->assertOk();
        $this->assertStringContainsString('Không xác định', $response->getContent());
    }

    public function test_payment_history_uses_one_aggregate_query_for_all_payment_rows(): void
    {
        $order = $this->createOrder(
            'ORDER-QUERY-COUNT',
            Order::PAYMENT_STATUS_PAID,
            true,
            1000000,
            0
        );

        $this->createPaymentTransaction(
            $order,
            'income',
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000],
                ['account_id' => $this->receivableAccountId, 'credit_amount' => 1000000],
            ],
            'QUERY COUNT PAYMENT',
            createdBy: $this->admin->id
        );

        DB::flushQueryLog();
        DB::enableQueryLog();

        $history = app(OrderPaymentHistoryService::class)->forOrder($order);
        $paymentQueries = collect(DB::getQueryLog())
            ->filter(static fn (array $query): bool => str_contains($query['query'], 'transactions'));

        $this->assertCount(1, $paymentQueries);
        $this->assertCount(1, $history);
    }

    private function getOrderDetailResponse(Order $order)
    {
        return $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->get('/admin/order/'.$order->id);
    }

    private function createPaymentTransaction(
        Order $order,
        string $type,
        array $entries,
        string $description,
        string $status = 'completed',
        string $documentType = 'order',
        ?int $userId = null,
        ?int $createdBy = null,
        ?string $transactionDate = null
    ): int {
        $transactionId = (int) DB::table('transactions')->insertGetId([
            'user_id' => $userId ?? $order->user_id,
            'created_by' => $createdBy,
            'transaction_date' => $transactionDate ?? now()->toDateString(),
            'description' => $description,
            'reference_number' => (string) $order->id,
            'type' => $type,
            'document_type' => $documentType,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($entries as $entry) {
            DB::table('transaction_entries')->insert([
                'transaction_id' => $transactionId,
                'account_id' => $entry['account_id'],
                'debit_amount' => $entry['debit_amount'] ?? 0,
                'credit_amount' => $entry['credit_amount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $transactionId;
    }

    private function createOrder(
        string $code,
        string $paymentStatus,
        bool $workflowStatus,
        int $paidAmount,
        int $debtAmount,
        string $paymentMethod = Order::PAYMENT_METHOD_DEBT,
        ?string $name = null,
        ?string $phone = null,
        ?int $branchId = null,
        ?string $createdAt = null
    ): Order {
        $order = Order::create([
            'user_id' => $this->admin->id,
            'branch_id' => $branchId ?? $this->admin->branch_id,
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

        if ($name !== null || $phone !== null || $createdAt !== null) {
            $order->forceFill(array_filter([
                'name' => $name,
                'phone' => $phone,
                'created_at' => $createdAt !== null ? \Carbon\Carbon::parse($createdAt) : null,
            ], static fn ($value): bool => $value !== null));
            $order->saveQuietly();
        }

        return $order;
    }

    private function getOrderListHtml(array $query = []): string
    {
        $queryString = $query === [] ? '' : '?'.http_build_query($query);

        $response = $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/admin/order'.$queryString);

        $response->assertOk();

        return (string) $response->json('html');
    }

    private function createAccount(string $code, ?string $name = null, ?int $parentId = null): int
    {
        return (int) DB::table('accounts')->insertGetId([
            'code' => $code,
            'name' => $name ?? "Account {$code}",
            'parent_id' => $parentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLedgerTransaction(
        Order $order,
        string $type,
        array $entries,
        string $status = 'completed',
        string $documentType = 'order',
        ?string $referenceNumber = null
    ): int {
        $transactionId = (int) DB::table('transactions')->insertGetId([
            'user_id' => $this->admin->id,
            'transaction_date' => now()->toDateString(),
            'description' => "Ledger test for order {$order->id}",
            'reference_number' => $referenceNumber ?? (string) $order->id,
            'type' => $type,
            'document_type' => $documentType,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($entries as $entry) {
            DB::table('transaction_entries')->insert([
                'transaction_id' => $transactionId,
                'account_id' => $entry['account_id'] ?? $this->receivableAccountId,
                'debit_amount' => $entry['debit_amount'] ?? 0,
                'credit_amount' => $entry['credit_amount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $transactionId;
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
