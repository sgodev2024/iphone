<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Services\CustomerSaleBackfillService;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillCustomerSalesCommandTest extends TestCase
{
    private const ORDER_LIST = '46,47,48,49,50,51,52,53,54,55,56,57,58,59,60';

    private const PHASE_3E_ORDER_LIST = '30,31,33,35,37,38,39,40,41,42,45';

    private const ORDERS = [
        46 => [11, 1500000, 1500000, 0, 'cash', 'paid', '2026-07-30 09:00:00'],
        47 => [11, 1500000, 1500000, 0, 'cash', 'paid', '2026-07-30 10:00:00'],
        48 => [11, 9500000, 9500000, 0, 'cash', 'paid', '2026-07-31 09:00:00'],
        49 => [11, 1500000, 1500000, 0, 'cash', 'paid', '2026-08-01 09:00:00'],
        50 => [11, 1500000, 1500000, 0, 'cash', 'paid', '2026-08-07 09:00:00'],
        51 => [11, 25555555, 25555555, 0, 'cash', 'paid', '2026-08-07 10:00:00'],
        52 => [null, 1500000, 1500000, 0, 'cash', 'paid', '2026-08-10 09:00:00'],
        53 => [null, 1500000, 1500000, 0, 'cash', 'paid', '2026-08-10 10:00:00'],
        54 => [11, 2000000, 0, 2000000, 'debt', 'debt', '2026-08-11 09:00:00'],
        55 => [12, 1500000, 1500000, 0, 'cash', 'paid', '2026-08-11 10:00:00'],
        56 => [12, 25555555, 25555555, 0, 'cash', 'paid', '2026-08-11 11:00:00'],
        57 => [11, 900000, 900000, 0, 'cash', 'paid', '2026-08-11 12:00:00'],
        58 => [12, 600000, 600000, 0, 'cash', 'paid', '2026-08-11 13:00:00'],
        59 => [11, 540000, 540000, 0, 'cash', 'paid', '2026-08-11 14:00:00'],
        60 => [11, 800000, 800000, 0, 'bank_transfer', 'paid', '2026-08-11 15:00:00'],
    ];

    private const PHASE_3E_ORDERS = [
        30 => [10, 8000000, 'income', '2026-07-28 14:55:32'],
        31 => [10, 1500000, 'income', '2026-07-28 15:00:39'],
        33 => [10, 1500000, 'income', '2026-07-28 15:05:02'],
        35 => [9, 25555555, 'credit_notice', '2026-07-28 15:52:40'],
        37 => [10, 3000000, 'credit_notice', '2026-07-28 16:01:14'],
        38 => [10, 1500000, 'income', '2026-07-28 16:01:42'],
        39 => [10, 1500000, 'credit_notice', '2026-07-29 09:56:15'],
        40 => [10, 1500000, 'credit_notice', '2026-07-29 10:04:00'],
        41 => [10, 1500000, 'income', '2026-07-29 10:04:13'],
        42 => [9, 1500000, 'income', '2026-07-29 10:04:25'],
        45 => [9, 1500000, 'income', '2026-07-29 14:29:59'],
    ];

    private array $accountIds;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['transaction_entries', 'transactions', 'order_details', 'orders', 'clients', 'accounts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createSchema();
        $this->seedApprovedBatch();
    }

    public function test_dry_run_does_not_write(): void
    {
        $transactionsBefore = DB::table('transactions')->count();
        $entriesBefore = DB::table('transaction_entries')->count();

        $this->runCommand()
            ->expectsOutputToContain('DRY RUN ONLY')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame($transactionsBefore, DB::table('transactions')->count());
        $this->assertSame($entriesBefore, DB::table('transaction_entries')->count());
        $this->assertSame(0, $this->saleTransactions()->count());
    }

    public function test_execute_creates_only_the_missing_sale_batch(): void
    {
        $paymentCount = $this->paymentTransactions()->count();
        $paymentCredit = $this->paymentCredit131();

        $this->runCommand(true)
            ->expectsOutputToContain('Backfilled all 15 approved sale transactions successfully.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(15, $this->saleTransactions()->count());
        $this->assertSame($paymentCount, $this->paymentTransactions()->count());
        $this->assertSame($paymentCredit, $this->paymentCredit131());
        $this->assertSame(CustomerSaleBackfillService::EXPECTED_SALE_TOTAL, $this->saleDebit131());
        $this->assertSame(CustomerSaleBackfillService::EXPECTED_SALE_TOTAL, $this->saleCredit5111());
    }

    public function test_debt_order_creates_sale_without_payment(): void
    {
        $this->runCommand(true)->assertExitCode(Command::SUCCESS);

        $this->assertSame(1, $this->saleTransactions()->where('reference_number', '54')->count());
        $this->assertSame(0, $this->paymentTransactions()->where('reference_number', '54')->count());
        $this->assertSame(2000000, $this->saleAmountForOrder(54, '131', 'debit_amount'));
        $this->assertSame(2000000, $this->saleAmountForOrder(54, '5111', 'credit_amount'));
    }

    public function test_customerless_orders_keep_null_tableable_link(): void
    {
        $this->runCommand(true)->assertExitCode(Command::SUCCESS);

        foreach ([52, 53] as $orderId) {
            $entry = $this->saleEntryForOrder($orderId, '131');
            $this->assertNull($entry->tableable_type);
            $this->assertNull($entry->tableable_id);
        }
    }

    public function test_execute_is_idempotent_after_success(): void
    {
        $this->runCommand(true)->assertExitCode(Command::SUCCESS);
        $transactionsAfterFirstRun = DB::table('transactions')->count();
        $entriesAfterFirstRun = DB::table('transaction_entries')->count();

        $this->runCommand(true)
            ->expectsOutputToContain('Already Backfilled')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame($transactionsAfterFirstRun, DB::table('transactions')->count());
        $this->assertSame($entriesAfterFirstRun, DB::table('transaction_entries')->count());
    }

    public function test_existing_invalid_sale_blocks_entire_batch(): void
    {
        $this->createSaleTransaction(46, 1);

        $this->runCommand(true)
            ->expectsOutputToContain('[BLOCK]')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(1, $this->saleTransactions()->count());
    }

    public function test_mixed_batch_blocks_without_partial_writes(): void
    {
        $this->createSaleTransaction(46, 1500000);

        $this->runCommand(true)
            ->expectsOutputToContain('Mixed batch state')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(1, $this->saleTransactions()->count());
        $this->assertSame(14, $this->paymentTransactions()->count());
    }

    public function test_wrong_owner_blocks_without_writes(): void
    {
        DB::table('orders')->where('id', 46)->update(['user_id' => 24]);

        $this->runCommand(true)
            ->expectsOutputToContain('user_id changed')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->saleTransactions()->count());
    }

    public function test_approved_total_mismatch_blocks_without_writes(): void
    {
        DB::table('orders')->where('id', 46)->update([
            'total_money' => 1500001,
            'paid_amount' => 1500001,
        ]);

        $this->runCommand(true)
            ->expectsOutputToContain('total_money changed')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->saleTransactions()->count());
    }

    public function test_backfill_uses_historical_order_date(): void
    {
        $this->runCommand(true)->assertExitCode(Command::SUCCESS);

        $transaction = $this->saleTransactions()->firstWhere('reference_number', '46');
        $this->assertNotNull($transaction);
        $this->assertSame('2026-07-30', substr($transaction->transaction_date, 0, 10));
    }

    public function test_command_rejects_any_order_outside_the_approved_whitelist(): void
    {
        $this->artisan('accounting:backfill-customer-sales', [
            '--orders' => self::ORDER_LIST.',61',
        ])
            ->expectsOutputToContain('must exactly match')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->saleTransactions()->count());
    }

    public function test_phase_3e_dry_run_validates_forensic_batch_without_writes(): void
    {
        $this->seedPhase3EApprovedBatch();
        $transactionsBefore = DB::table('transactions')->count();
        $entriesBefore = DB::table('transaction_entries')->count();

        $this->runPhase3ECommand()
            ->expectsOutputToContain('[PASS] Approved batch Phase 3E')
            ->expectsOutputToContain('[PASS] 11/11 orders found')
            ->expectsOutputToContain('Total: 48.555.555')
            ->expectsOutputToContain('Planned Debit 131: 48.555.555')
            ->expectsOutputToContain('Planned Credit 5111: 48.555.555')
            ->expectsOutputToContain('DRY RUN ONLY')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame($transactionsBefore, DB::table('transactions')->count());
        $this->assertSame($entriesBefore, DB::table('transaction_entries')->count());
        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_execute_creates_sale_only_and_preserves_source_data(): void
    {
        $this->seedPhase3EApprovedBatch();
        $ordersBefore = $this->phase3EOrderSnapshot();
        $clientsBefore = $this->phase3EClientSnapshot();
        $paymentBefore = $this->phase3EPaymentMetrics();

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('Backfilled all 11 approved sale transactions successfully.')
            ->expectsOutputToContain('Added Debit 131: 48.555.555')
            ->expectsOutputToContain('Added Credit 5111: 48.555.555')
            ->expectsOutputToContain('Added Credit 131: 0')
            ->expectsOutputToContain('Added Debit 111: 0')
            ->expectsOutputToContain('Added Debit 112: 0')
            ->assertExitCode(Command::SUCCESS);

        $sales = $this->phase3ESaleTransactions();
        $this->assertSame(11, $sales->count());
        $this->assertSame(22, DB::table('transaction_entries')->whereIn('transaction_id', $sales->pluck('id'))->count());
        $this->assertSame(CustomerSaleBackfillService::PHASE_3E_EXPECTED_SALE_TOTAL, $this->saleDebit131());
        $this->assertSame(CustomerSaleBackfillService::PHASE_3E_EXPECTED_SALE_TOTAL, $this->saleCredit5111());
        $this->assertSame($paymentBefore, $this->phase3EPaymentMetrics());
        $this->assertEquals($ordersBefore, $this->phase3EOrderSnapshot());
        $this->assertEquals($clientsBefore, $this->phase3EClientSnapshot());

        foreach (self::PHASE_3E_ORDERS as $orderId => [$clientId, $total, , $createdAt]) {
            $sale = $sales->firstWhere('reference_number', (string) $orderId);
            $this->assertNotNull($sale);
            $this->assertSame(28, (int) $sale->user_id);
            $this->assertSame(30, (int) $sale->created_by);
            $this->assertSame('pending', $sale->status);
            $this->assertSame(substr($createdAt, 0, 10), substr($sale->transaction_date, 0, 10));
            $this->assertSame($total, $this->saleAmountForOrder($orderId, '131', 'debit_amount'));
            $this->assertSame($total, $this->saleAmountForOrder($orderId, '5111', 'credit_amount'));
            $this->assertSame($clientId, (int) $this->saleEntryForOrder($orderId, '131')->tableable_id);
            $this->assertNull($this->saleEntryForOrder($orderId, '5111')->tableable_id);
        }

        $this->assertSame(28555555, $this->clientEntryTotal(9, 'sale', 'debit_amount'));
        $this->assertSame(28555555, $this->clientEntryTotal(9, ['income', 'credit_notice'], 'credit_amount'));
        $this->assertSame(20000000, $this->clientEntryTotal(10, 'sale', 'debit_amount'));
        $this->assertSame(20000000, $this->clientEntryTotal(10, ['income', 'credit_notice'], 'credit_amount'));
    }

    public function test_phase_3e_execute_is_idempotent(): void
    {
        $this->seedPhase3EApprovedBatch();
        $this->runPhase3ECommand(true)->assertExitCode(Command::SUCCESS);
        $transactionsAfterFirstRun = DB::table('transactions')->count();
        $entriesAfterFirstRun = DB::table('transaction_entries')->count();

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('Already Backfilled')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame($transactionsAfterFirstRun, DB::table('transactions')->count());
        $this->assertSame($entriesAfterFirstRun, DB::table('transaction_entries')->count());
    }

    public function test_phase_3e_rejects_client_8_order_outside_whitelist(): void
    {
        $this->seedPhase3EApprovedBatch();
        DB::table('clients')->insert(['id' => 8, 'user_id' => 28, 'name' => 'Client 8']);
        DB::table('orders')->insert([
            'id' => 28,
            'user_id' => 28,
            'client_id' => 8,
            'total_money' => 1500000,
            'created_at' => '2026-07-28 14:00:00',
            'updated_at' => '2026-07-28 14:00:00',
        ]);

        $this->artisan('accounting:backfill-customer-sales', [
            '--orders' => self::PHASE_3E_ORDER_LIST.',28',
            '--execute' => true,
        ])
            ->expectsOutputToContain('must exactly match an explicit approved batch')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
        $this->assertSame(0, DB::table('transactions')->where('type', 'sale')->where('reference_number', '28')->count());
    }

    public function test_phase_3e_wrong_owner_blocks_entire_batch(): void
    {
        $this->seedPhase3EApprovedBatch();
        DB::table('orders')->where('id', 30)->update(['user_id' => 29]);

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('user_id changed')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_wrong_client_including_client_8_blocks_entire_batch(): void
    {
        $this->seedPhase3EApprovedBatch();
        DB::table('clients')->insert(['id' => 8, 'user_id' => 28, 'name' => 'Client 8']);
        DB::table('orders')->where('id', 30)->update(['client_id' => 8]);

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('client_id changed')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_total_mismatch_blocks_entire_batch(): void
    {
        $this->seedPhase3EApprovedBatch();
        DB::table('orders')->where('id', 30)->update(['total_money' => 8000001]);

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('total_money changed')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_missing_payment_evidence_blocks_entire_batch(): void
    {
        $this->seedPhase3EApprovedBatch();
        $paymentId = DB::table('transactions')->where('type', 'income')->where('reference_number', '30')->value('id');
        DB::table('transaction_entries')->where('transaction_id', $paymentId)->delete();
        DB::table('transactions')->where('id', $paymentId)->delete();

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('must have exactly one existing payment transaction')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_payment_amount_mismatch_blocks_entire_batch(): void
    {
        $this->seedPhase3EApprovedBatch();
        $paymentId = DB::table('transactions')->where('type', 'income')->where('reference_number', '30')->value('id');
        DB::table('transaction_entries')
            ->where('transaction_id', $paymentId)
            ->where('account_id', $this->accountIds['131'])
            ->update(['credit_amount' => 7999999]);

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('existing payment Credit 131 is invalid')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_mixed_existing_sale_state_blocks_without_partial_writes(): void
    {
        $this->seedPhase3EApprovedBatch();
        [$clientId, $total, , $createdAt] = self::PHASE_3E_ORDERS[30];
        $this->createSaleTransactionWithValues(30, 28, $clientId, $total, $createdAt, 30);

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('Mixed batch state')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(1, $this->phase3ESaleTransactions()->count());
    }

    public function test_phase_3e_missing_order_detail_blocks_entire_batch(): void
    {
        $this->seedPhase3EApprovedBatch();
        DB::table('order_details')->where('order_id', 30)->delete();

        $this->runPhase3ECommand(true)
            ->expectsOutputToContain('no longer has an order detail')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->phase3ESaleTransactions()->count());
    }

    public function test_approved_profiles_reject_a_hybrid_phase_3b_phase_3e_batch(): void
    {
        $this->seedPhase3EApprovedBatch();

        $this->artisan('accounting:backfill-customer-sales', [
            '--orders' => self::ORDER_LIST.',30',
        ])
            ->expectsOutputToContain('must exactly match an explicit approved batch')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, $this->saleTransactions()->count());
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('status')->default(1);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('total_money');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('paid');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->default('other');
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transaction_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    private function seedApprovedBatch(): void
    {
        DB::table('users')->insert(['id' => 23, 'name' => 'Owner 23']);
        DB::table('clients')->insert([
            ['id' => 11, 'user_id' => 23, 'name' => 'Client 11'],
            ['id' => 12, 'user_id' => 23, 'name' => 'Client 12'],
        ]);

        $this->accountIds = [];
        $this->accountIds['111'] = $this->createAccount('111', 'Cash');
        $this->accountIds['112'] = $this->createAccount('112', 'Bank');
        $this->accountIds['112MB'] = $this->createAccount('112MB', 'MBBank', $this->accountIds['112']);
        $this->accountIds['131'] = $this->createAccount('131', 'Receivable');
        $this->accountIds['5111'] = $this->createAccount('5111', 'Revenue');

        foreach (self::ORDERS as $orderId => [$clientId, $total, $paid, $debt, $method, $paymentStatus, $createdAt]) {
            DB::table('orders')->insert([
                'id' => $orderId,
                'user_id' => 23,
                'client_id' => $clientId,
                'total_money' => $total,
                'paid_amount' => $paid,
                'debt_amount' => $debt,
                'payment_method' => $method,
                'payment_status' => $paymentStatus,
                'status' => 1,
                'created_by' => 23,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($paid > 0) {
                $this->createPaymentTransaction($orderId, $clientId, $paid, $method, $createdAt);
            }
        }
    }

    private function seedPhase3EApprovedBatch(): void
    {
        DB::table('users')->insert([
            ['id' => 28, 'name' => 'Owner 28'],
            ['id' => 30, 'name' => 'Creator 30'],
        ]);
        DB::table('clients')->insert([
            ['id' => 9, 'user_id' => 28, 'name' => 'Client 9', 'deleted_at' => null],
            ['id' => 10, 'user_id' => 28, 'name' => 'Client 10', 'deleted_at' => '2026-07-29 17:15:59'],
        ]);

        foreach (self::PHASE_3E_ORDERS as $orderId => [$clientId, $total, $paymentType, $createdAt]) {
            DB::table('orders')->insert([
                'id' => $orderId,
                'user_id' => 28,
                'client_id' => $clientId,
                'total_money' => $total,
                'paid_amount' => 0,
                'debt_amount' => 0,
                'payment_method' => null,
                'payment_status' => 'paid',
                'status' => 1,
                'created_by' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            DB::table('order_details')->insert([
                'order_id' => $orderId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $this->createPaymentTransaction(
                $orderId,
                $clientId,
                $total,
                $paymentType === 'credit_notice' ? 'bank_transfer' : 'cash',
                $createdAt,
                28,
                30
            );
        }
    }

    private function createPaymentTransaction(
        int $orderId,
        ?int $clientId,
        int $paid,
        string $method,
        string $createdAt,
        int $ownerId = 23,
        int $createdBy = 23
    ): void {
        $bank = $method === 'bank_transfer';
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $ownerId,
            'transaction_date' => substr($createdAt, 0, 10),
            'description' => "Thu tiền đơn hàng #{$orderId}",
            'reference_number' => (string) $orderId,
            'type' => $bank ? 'credit_notice' : 'income',
            'document_type' => 'order',
            'created_by' => $createdBy,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        DB::table('transaction_entries')->insert([
            [
                'transaction_id' => $transactionId,
                'account_id' => $bank ? $this->accountIds['112MB'] : $this->accountIds['111'],
                'debit_amount' => $paid,
                'credit_amount' => 0,
                'tableable_type' => null,
                'tableable_id' => null,
            ],
            [
                'transaction_id' => $transactionId,
                'account_id' => $this->accountIds['131'],
                'debit_amount' => 0,
                'credit_amount' => $paid,
                'tableable_type' => $clientId ? Client::class : null,
                'tableable_id' => $clientId,
            ],
        ]);
    }

    private function createSaleTransaction(int $orderId, int $amount): void
    {
        [$clientId, , , , , , $createdAt] = self::ORDERS[$orderId];
        $this->createSaleTransactionWithValues($orderId, 23, $clientId, $amount, $createdAt, 23);
    }

    private function createSaleTransactionWithValues(
        int $orderId,
        int $ownerId,
        ?int $clientId,
        int $amount,
        string $createdAt,
        int $createdBy
    ): void {
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $ownerId,
            'transaction_date' => substr($createdAt, 0, 10),
            'description' => "Bán hàng theo đơn #{$orderId}",
            'reference_number' => (string) $orderId,
            'type' => 'sale',
            'document_type' => 'order',
            'created_by' => $createdBy,
        ]);

        DB::table('transaction_entries')->insert([
            [
                'transaction_id' => $transactionId,
                'account_id' => $this->accountIds['131'],
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'tableable_type' => $clientId ? Client::class : null,
                'tableable_id' => $clientId,
            ],
            [
                'transaction_id' => $transactionId,
                'account_id' => $this->accountIds['5111'],
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'tableable_type' => null,
                'tableable_id' => null,
            ],
        ]);
    }

    private function createAccount(string $code, string $name, ?int $parentId = null): int
    {
        return DB::table('accounts')->insertGetId([
            'code' => $code,
            'name' => $name,
            'status' => 1,
            'parent_id' => $parentId,
        ]);
    }

    private function runCommand(bool $execute = false)
    {
        $arguments = ['--orders' => self::ORDER_LIST];

        if ($execute) {
            $arguments['--execute'] = true;
        }

        return $this->artisan('accounting:backfill-customer-sales', $arguments);
    }

    private function runPhase3ECommand(bool $execute = false)
    {
        $arguments = ['--orders' => self::PHASE_3E_ORDER_LIST];

        if ($execute) {
            $arguments['--execute'] = true;
        }

        return $this->artisan('accounting:backfill-customer-sales', $arguments);
    }

    private function saleTransactions()
    {
        return DB::table('transactions')->where('type', 'sale')->get();
    }

    private function paymentTransactions()
    {
        return DB::table('transactions')->whereIn('type', ['income', 'credit_notice'])->get();
    }

    private function phase3ESaleTransactions()
    {
        return DB::table('transactions')
            ->where('type', 'sale')
            ->whereIn('reference_number', array_map('strval', array_keys(self::PHASE_3E_ORDERS)))
            ->orderBy('reference_number')
            ->get();
    }

    private function phase3EOrderSnapshot()
    {
        return DB::table('orders')
            ->whereIn('id', array_keys(self::PHASE_3E_ORDERS))
            ->orderBy('id')
            ->get();
    }

    private function phase3EClientSnapshot()
    {
        return DB::table('clients')->whereIn('id', [9, 10])->orderBy('id')->get();
    }

    private function phase3EPaymentMetrics(): array
    {
        $references = array_map('strval', array_keys(self::PHASE_3E_ORDERS));
        $base = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->join('accounts as a', 'a.id', '=', 'te.account_id')
            ->whereIn('t.reference_number', $references)
            ->whereIn('t.type', ['income', 'credit_notice']);

        return [
            'count' => DB::table('transactions')
                ->whereIn('reference_number', $references)
                ->whereIn('type', ['income', 'credit_notice'])
                ->count(),
            'credit_131' => (int) (clone $base)->where('a.code', '131')->sum('te.credit_amount'),
            'debit_111' => (int) (clone $base)->where('a.code', '111')->sum('te.debit_amount'),
            'debit_112' => (int) (clone $base)->where('a.parent_id', $this->accountIds['112'])->sum('te.debit_amount'),
        ];
    }

    private function paymentCredit131(): int
    {
        return (int) DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->whereIn('t.type', ['income', 'credit_notice'])
            ->where('te.account_id', $this->accountIds['131'])
            ->sum('te.credit_amount');
    }

    private function saleDebit131(): int
    {
        return $this->saleEntryTotal('131', 'debit_amount');
    }

    private function saleCredit5111(): int
    {
        return $this->saleEntryTotal('5111', 'credit_amount');
    }

    private function saleEntryTotal(string $accountCode, string $column): int
    {
        return (int) DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('t.type', 'sale')
            ->where('te.account_id', $this->accountIds[$accountCode])
            ->sum("te.{$column}");
    }

    private function saleAmountForOrder(int $orderId, string $accountCode, string $column): int
    {
        return (int) DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('t.type', 'sale')
            ->where('t.reference_number', (string) $orderId)
            ->where('te.account_id', $this->accountIds[$accountCode])
            ->value("te.{$column}");
    }

    private function saleEntryForOrder(int $orderId, string $accountCode): object
    {
        $entry = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('t.type', 'sale')
            ->where('t.reference_number', (string) $orderId)
            ->where('te.account_id', $this->accountIds[$accountCode])
            ->select('te.*')
            ->first();

        $this->assertNotNull($entry);

        return $entry;
    }

    private function clientEntryTotal(int $clientId, string|array $types, string $column): int
    {
        return (int) DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->whereIn('t.type', (array) $types)
            ->where('te.account_id', $this->accountIds['131'])
            ->where('te.tableable_type', Client::class)
            ->where('te.tableable_id', $clientId)
            ->sum("te.{$column}");
    }
}
