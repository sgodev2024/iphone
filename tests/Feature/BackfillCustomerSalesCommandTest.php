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

    private array $accountIds;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['transaction_entries', 'transactions', 'orders', 'clients', 'accounts', 'users'] as $table) {
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

    private function createPaymentTransaction(
        int $orderId,
        ?int $clientId,
        int $paid,
        string $method,
        string $createdAt
    ): void {
        $bank = $method === 'bank_transfer';
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => 23,
            'transaction_date' => substr($createdAt, 0, 10),
            'description' => "Thu tiền đơn hàng #{$orderId}",
            'reference_number' => (string) $orderId,
            'type' => $bank ? 'credit_notice' : 'income',
            'document_type' => 'order',
            'created_by' => 23,
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
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => 23,
            'transaction_date' => substr($createdAt, 0, 10),
            'description' => "Bán hàng theo đơn #{$orderId}",
            'reference_number' => (string) $orderId,
            'type' => 'sale',
            'document_type' => 'order',
            'created_by' => 23,
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

    private function saleTransactions()
    {
        return DB::table('transactions')->where('type', 'sale')->get();
    }

    private function paymentTransactions()
    {
        return DB::table('transactions')->whereIn('type', ['income', 'credit_notice'])->get();
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
}
