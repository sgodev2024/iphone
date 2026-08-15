<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\CashTransactionController;
use App\Http\Controllers\Admin\DebtController;
use App\Models\Account;
use App\Models\Client;
use App\Models\CustomerDebtCollection;
use App\Models\CustomerDebtCollectionAllocation;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerDebtCollectionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CustomerDebtCollectionServiceTest extends TestCase
{
    private CustomerDebtCollectionService $service;

    private User $owner;

    private User $otherOwner;

    private Client $client;

    private Account $cash;

    private Account $bankParent;

    private Account $bank;

    private Account $receivable;

    private Account $revenue;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-15 12:00:00');
        Schema::dropAllTables();
        $this->createSchema();
        $this->service = app(CustomerDebtCollectionService::class);
        $this->owner = $this->createUser('owner@example.com');
        $this->otherOwner = $this->createUser('other@example.com');
        $this->client = Client::create(['user_id' => $this->owner->id, 'name' => 'Khách FIFO']);
        $this->createAccounts();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_one_order_partial_then_full_collection_syncs_ledger_aggregates(): void
    {
        $order = $this->createCanonicalOrder(600000, '2026-08-01');

        $partial = $this->service->collect($this->owner, $this->payload(200000, 'cash', $this->uuid(1)));

        $this->assertSame('200000.00', $partial['collection']->total_amount);
        $this->assertSame(200000, (int) $order->fresh()->paid_amount);
        $this->assertSame(400000, (int) $order->fresh()->debt_amount);
        $this->assertSame(Order::PAYMENT_STATUS_PARTIAL, $order->fresh()->payment_status);

        $full = $this->service->collect($this->owner, $this->payload(400000, 'cash', $this->uuid(2)));

        $this->assertSame(600000, (int) $order->fresh()->paid_amount);
        $this->assertSame(0, (int) $order->fresh()->debt_amount);
        $this->assertSame(Order::PAYMENT_STATUS_PAID, $order->fresh()->payment_status);
        $this->assertSame('0.00', $full['collectible_total']);
    }

    public function test_two_order_fifo_crossing_allocates_500k_then_200k_and_persists_history(): void
    {
        $first = $this->createCanonicalOrder(500000, '2026-08-01', 'A');
        $second = $this->createCanonicalOrder(600000, '2026-08-05', 'B');

        $result = $this->service->collect($this->owner, $this->payload(700000, 'cash', $this->uuid(3)));
        $collection = $result['collection'];

        $this->assertSame('PTCN-000001', $collection->collection_number);
        $this->assertSame(CustomerDebtCollection::STATUS_COMPLETED, $collection->status);
        $this->assertSame([1, 2], $collection->allocations->pluck('allocation_sequence')->all());
        $this->assertSame([$first->id, $second->id], $collection->allocations->pluck('order_id')->all());
        $this->assertSame(['500000.00', '200000.00'], $collection->allocations->pluck('allocated_amount')->all());
        $this->assertSame(['0.00', '400000.00'], $collection->allocations->pluck('remaining_after')->all());
        $this->assertSame(0, (int) $first->fresh()->debt_amount);
        $this->assertSame(400000, (int) $second->fresh()->debt_amount);
        $this->assertSame('400000.00', $result['collectible_total']);
        $this->assertSame('700000.00', $collection->allocations->reduce(
            fn (string $carry, $allocation): string => \App\Support\DecimalAmount::add($carry, $allocation->allocated_amount),
            '0.00'
        ));
    }

    public function test_three_order_fifo_and_tied_sale_dates_use_order_id_as_tie_break(): void
    {
        $first = $this->createCanonicalOrder(500000, '2026-08-01', 'A');
        $second = $this->createCanonicalOrder(600000, '2026-08-05', 'B');
        $third = $this->createCanonicalOrder(2000000, '2026-08-05', 'C');

        $result = $this->service->collect($this->owner, $this->payload(1400000, 'cash', $this->uuid(4)));

        $this->assertSame(
            [$first->id, $second->id, $third->id],
            $result['collection']->allocations->pluck('order_id')->all()
        );
        $this->assertSame(['500000.00', '600000.00', '300000.00'],
            $result['collection']->allocations->pluck('allocated_amount')->all());
        $this->assertSame(1700000, (int) $third->fresh()->debt_amount);
    }

    public function test_amount_smaller_than_oldest_order_only_touches_that_order(): void
    {
        $first = $this->createCanonicalOrder(500000, '2026-08-01');
        $second = $this->createCanonicalOrder(600000, '2026-08-02');

        $result = $this->service->collect($this->owner, $this->payload(100000, 'cash', $this->uuid(5)));

        $this->assertCount(1, $result['collection']->allocations);
        $this->assertSame($first->id, $result['collection']->allocations->first()->order_id);
        $this->assertSame(400000, (int) $first->fresh()->debt_amount);
        $this->assertSame(600000, (int) $second->fresh()->debt_amount);
    }

    public function test_cash_creates_balanced_dr111_cr131_and_one_transaction_per_order(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01');
        $this->createCanonicalOrder(100000, '2026-08-02');

        $collection = $this->service->collect(
            $this->owner,
            $this->payload(150000, 'cash', $this->uuid(6))
        )['collection'];

        $this->assertCount(2, $collection->allocations);
        $this->assertSame(2, Transaction::where('collection_id', $collection->id)->count());

        foreach ($collection->allocations as $allocation) {
            $transaction = $allocation->paymentTransaction;
            $this->assertSame('income', $transaction->type);
            $this->assertSame('order', $transaction->document_type);
            $this->assertSame((string) $allocation->order_id, $transaction->reference_number);
            $this->assertEntry($transaction, $this->cash, $allocation->allocated_amount, '0.00');
            $this->assertEntry(
                $transaction,
                $this->receivable,
                '0.00',
                $allocation->allocated_amount,
                Client::class,
                $this->client->id
            );
            $this->assertBalanced($transaction);
            $this->assertSame(36, strlen((string) $transaction->idempotency_key));
        }
    }

    public function test_bank_uses_selected_active_direct_child_of_112_and_rejects_invalid_accounts(): void
    {
        $this->createCanonicalOrder(300000, '2026-08-01');
        $payload = $this->payload(100000, 'bank_transfer', $this->uuid(7));
        $payload['money_account_id'] = $this->bank->id;
        $transaction = $this->service->collect($this->owner, $payload)['collection']
            ->allocations->first()->paymentTransaction;

        $this->assertSame('credit_notice', $transaction->type);
        $this->assertEntry($transaction, $this->bank, '100000.00', '0.00');
        $this->assertBalanced($transaction);

        $this->expectValidation(fn () => $this->service->collect($this->owner, array_merge(
            $this->payload(100000, 'bank_transfer', $this->uuid(8)),
            ['money_account_id' => $this->bankParent->id]
        )), 'money_account_id');
    }

    public function test_zero_negative_overpayment_no_debt_and_future_date_are_rejected(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01');

        foreach ([0, -1, 100001] as $index => $amount) {
            $this->expectValidation(
                fn () => $this->service->collect($this->owner, $this->payload($amount, 'cash', $this->uuid(20 + $index))),
                'amount'
            );
        }

        $this->expectValidation(fn () => $this->service->collect(
            $this->owner,
            array_merge($this->payload(1, 'cash', $this->uuid(24)), ['collection_date' => '2026-08-16'])
        ), 'collection_date');

        $this->service->collect($this->owner, $this->payload(100000, 'cash', $this->uuid(25)));
        $this->expectValidation(
            fn () => $this->service->collect($this->owner, $this->payload(1, 'cash', $this->uuid(26))),
            'amount'
        );
    }

    public function test_collection_date_before_sale_is_rejected_without_backdating_payment(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-10');

        $this->expectValidation(fn () => $this->service->collect($this->owner, array_merge(
            $this->payload(100000, 'cash', $this->uuid(27)),
            ['collection_date' => '2026-08-09']
        )), 'collection_date');
        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseCount('customer_debt_collections', 0);
    }

    public function test_cross_owner_and_customerless_orders_are_not_collectible(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01');

        try {
            $this->service->collect($this->otherOwner, array_merge(
                $this->payload(10000, 'cash', $this->uuid(28)),
                ['client_id' => $this->client->id]
            ));
            $this->fail('Cross-owner client collection must not be visible.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }

        $customerless = Order::create([
            'user_id' => $this->owner->id,
            'client_id' => null,
            'total_money' => 500000,
            'paid_amount' => 0,
            'debt_amount' => 500000,
            'payment_status' => Order::PAYMENT_STATUS_DEBT,
        ]);
        $this->assertFalse($this->service->preview($this->owner, $this->client->id)['items']
            ->contains('id', $customerless->id));
    }

    public function test_collection_idempotency_replays_without_duplicate_children_and_conflicts_on_payload_change(): void
    {
        $this->createCanonicalOrder(300000, '2026-08-01');
        $payload = $this->payload(100000, 'cash', $this->uuid(29));
        $first = $this->service->collect($this->owner, $payload);
        $counts = [
            CustomerDebtCollection::count(),
            CustomerDebtCollectionAllocation::count(),
            Transaction::count(),
            DB::table('transaction_entries')->count(),
        ];
        $replay = $this->service->collect($this->owner, $payload);

        $this->assertTrue($replay['replayed']);
        $this->assertSame($first['collection']->id, $replay['collection']->id);
        $this->assertSame($counts, [
            CustomerDebtCollection::count(),
            CustomerDebtCollectionAllocation::count(),
            Transaction::count(),
            DB::table('transaction_entries')->count(),
        ]);

        try {
            $this->service->collect($this->owner, array_merge($payload, ['amount' => 200000]));
            $this->fail('A reused collection key with a different payload must conflict.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
    }

    public function test_late_second_allocation_failure_rolls_back_everything(): void
    {
        $this->createCanonicalOrder(500000, '2026-08-01');
        $this->createCanonicalOrder(600000, '2026-08-02');
        $beforeTransactions = Transaction::count();
        $beforeEntries = DB::table('transaction_entries')->count();
        $failing = new class extends CustomerDebtCollectionService
        {
            protected function afterAllocationCreated(CustomerDebtCollectionAllocation $allocation, int $sequence): void
            {
                if ($sequence === 2) {
                    throw new \RuntimeException('Injected allocation failure');
                }
            }
        };

        try {
            $failing->collect($this->owner, $this->payload(700000, 'cash', $this->uuid(30)));
            $this->fail('The injected late error must escape.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected allocation failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('customer_debt_collections', 0);
        $this->assertDatabaseCount('customer_debt_collection_allocations', 0);
        $this->assertSame($beforeTransactions, Transaction::count());
        $this->assertSame($beforeEntries, DB::table('transaction_entries')->count());
        $this->assertSame(1100000, (int) Order::sum('debt_amount'));
    }

    public function test_reconciliation_mismatch_and_malformed_payment_block_collection(): void
    {
        $order = $this->createCanonicalOrder(200000, '2026-08-01');
        $bad = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => '2026-08-02',
            'type' => 'income',
            'document_type' => 'order',
            'reference_number' => (string) $order->id,
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $bad->entries()->create([
            'account_id' => $this->receivable->id,
            'debit_amount' => 0,
            'credit_amount' => 100000,
            'tableable_type' => Client::class,
            'tableable_id' => $this->client->id,
        ]);

        try {
            $this->service->collect($this->owner, $this->payload(100000, 'cash', $this->uuid(31)));
            $this->fail('Malformed payment must cause reconciliation mismatch.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'CUSTOMER_DEBT_RECONCILIATION_MISMATCH',
                $exception->errors()['reconciliation'][0]
            );
        }

        $this->assertSame(200000, (int) $order->fresh()->debt_amount);
        $this->assertDatabaseCount('customer_debt_collections', 0);
    }

    public function test_credit_balance_blocks_collection(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => '2026-08-01',
            'type' => 'other',
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $transaction->entries()->create([
            'account_id' => $this->receivable->id,
            'credit_amount' => 100000,
            'debit_amount' => 0,
            'tableable_type' => Client::class,
            'tableable_id' => $this->client->id,
        ]);

        $this->expectValidation(
            fn () => $this->service->collect($this->owner, $this->payload(1, 'cash', $this->uuid(32))),
            'reconciliation'
        );
    }

    public function test_order_aggregates_are_recomputed_from_ledger_not_incremented_from_stale_values(): void
    {
        $order = $this->createCanonicalOrder(500000, '2026-08-01');
        $order->forceFill(['paid_amount' => 499999, 'debt_amount' => 1, 'payment_status' => 'paid'])->save();

        $this->service->collect($this->owner, $this->payload(100000, 'cash', $this->uuid(33)));

        $this->assertSame(100000, (int) $order->fresh()->paid_amount);
        $this->assertSame(400000, (int) $order->fresh()->debt_amount);
        $this->assertSame(Order::PAYMENT_STATUS_PARTIAL, $order->fresh()->payment_status);
    }

    public function test_historical_collection_invalidates_next_year_snapshot_state(): void
    {
        $this->createCanonicalOrder(100000, '2025-12-01');
        $state = DB::table('customer_debt_snapshot_states')->where('client_id', $this->client->id)->first();
        $beforeVersion = (int) $state->ledger_version;

        $this->service->collect($this->owner, array_merge(
            $this->payload(50000, 'cash', $this->uuid(34)),
            ['collection_date' => '2025-12-20']
        ));

        $state = DB::table('customer_debt_snapshot_states')->where('client_id', $this->client->id)->first();
        $this->assertGreaterThan($beforeVersion, (int) $state->ledger_version);
        $this->assertSame(2026, (int) $state->dirty_from_year);
    }

    public function test_preview_is_fifo_read_only_and_uses_a_bounded_query_count(): void
    {
        foreach ([1, 2, 3, 4, 5] as $day) {
            $this->createCanonicalOrder(100000, sprintf('2026-08-%02d', $day));
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $preview = $this->service->preview($this->owner, $this->client->id, 250000, '2026-08-15');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(5, $preview['items']->count());
        $this->assertSame(['100000.00', '100000.00', '50000.00'],
            $preview['preview_allocations']->pluck('allocated_amount')->all());
        $this->assertLessThanOrEqual(12, $queryCount);
        $this->assertDatabaseCount('customer_debt_collections', 0);
    }

    public function test_generic_cash_bank_and_unsafe_customer_opening_writes_return_410(): void
    {
        foreach ([
            [new CashTransactionController, 'store', 'obj_type'],
            [new CashTransactionController, 'update', 'obj_type'],
            [new BankTransactionController, 'store', 'obj_type'],
            [new BankTransactionController, 'update', 'obj_type'],
            [new DebtController, 'store', 'object_type'],
        ] as [$controller, $method, $field]) {
            try {
                $controller->{$method}(Request::create('/', 'POST', [$field => 'client']));
                $this->fail("{$method} must reject customer TK131 bypass.");
            } catch (HttpException $exception) {
                $this->assertSame(410, $exception->getStatusCode());
            }
        }
    }

    public function test_completed_collection_and_its_allocations_are_immutable(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01');
        $collection = $this->service->collect(
            $this->owner,
            $this->payload(50000, 'cash', $this->uuid(35))
        )['collection'];

        $this->expectException(\LogicException::class);
        $collection->update(['note' => 'changed']);
    }

    public function test_exact_decimal_strings_are_preserved_without_float_arithmetic(): void
    {
        $order = $this->createCanonicalOrder(100001, '2026-08-01');

        $result = $this->service->collect(
            $this->owner,
            $this->payload('33333.00', 'cash', $this->uuid(36))
        );

        $this->assertSame('33333.00', $result['collection']->total_amount);
        $this->assertSame('33333.00', $result['collection']->allocations->first()->allocated_amount);
        $this->assertSame('66668.00', $result['collection']->allocations->first()->remaining_after);
        $this->assertSame(33333, (int) $order->fresh()->paid_amount);
        $this->assertSame(66668, (int) $order->fresh()->debt_amount);
    }

    private function createCanonicalOrder(
        int $amount,
        string $saleDate,
        ?string $code = null,
        ?Client $client = null
    ): Order {
        $client ??= $this->client;
        $order = Order::create([
            'user_id' => $this->owner->id,
            'client_id' => $client->id,
            'code' => $code,
            'total_money' => $amount,
            'paid_amount' => 0,
            'debt_amount' => $amount,
            'payment_status' => Order::PAYMENT_STATUS_DEBT,
            'created_by' => $this->owner->id,
        ]);
        $sale = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => $saleDate,
            'description' => "Sale #{$order->id}",
            'type' => 'sale',
            'document_type' => 'order',
            'reference_number' => (string) $order->id,
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $sale->entries()->create([
            'account_id' => $this->receivable->id,
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'tableable_type' => Client::class,
            'tableable_id' => $client->id,
        ]);
        $sale->entries()->create([
            'account_id' => $this->revenue->id,
            'debit_amount' => 0,
            'credit_amount' => $amount,
        ]);

        return $order;
    }

    private function payload(int|string $amount, string $method, string $key): array
    {
        return [
            'client_id' => $this->client->id,
            'amount' => $amount,
            'collection_date' => '2026-08-15',
            'payment_method' => $method,
            'money_account_id' => $method === 'bank_transfer' ? $this->bank->id : null,
            'note' => 'Thu nợ test',
            'idempotency_key' => $key,
        ];
    }

    private function uuid(int $number): string
    {
        return sprintf('70000000-0000-4000-8000-%012d', $number);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => 2,
            'status' => 'active',
        ]);
    }

    private function createAccounts(): void
    {
        $this->cash = Account::create(['code' => '111', 'name' => 'Cash', 'status' => true]);
        $this->bankParent = Account::create(['code' => '112', 'name' => 'Bank', 'status' => true]);
        $this->bank = Account::create([
            'code' => '112MB',
            'name' => 'MB',
            'status' => true,
            'parent_id' => $this->bankParent->id,
        ]);
        $this->receivable = Account::create(['code' => '131', 'name' => 'Receivable', 'status' => true]);
        $this->revenue = Account::create(['code' => '5111', 'name' => 'Revenue', 'status' => true]);
    }

    private function expectValidation(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail("Expected validation error for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    private function assertEntry(
        Transaction $transaction,
        Account $account,
        string $debit,
        string $credit,
        ?string $tableableType = null,
        ?int $tableableId = null
    ): void {
        $query = DB::table('transaction_entries')
            ->where('transaction_id', $transaction->id)
            ->where('account_id', $account->id)
            ->where('debit_amount', $debit)
            ->where('credit_amount', $credit);

        if ($tableableType !== null) {
            $query->where('tableable_type', $tableableType)->where('tableable_id', $tableableId);
        }

        $this->assertTrue($query->exists());
    }

    private function assertBalanced(Transaction $transaction): void
    {
        $totals = DB::table('transaction_entries')
            ->where('transaction_id', $transaction->id)
            ->selectRaw('SUM(debit_amount) AS debit_total, SUM(credit_amount) AS credit_total')
            ->first();

        $this->assertSame((string) $totals->debit_total, (string) $totals->credit_total);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('level')->default(1);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('code')->nullable();
            $table->decimal('total_money', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_status')->default('debt');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_debt_collections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('client_id');
            $table->string('collection_number', 32);
            $table->date('collection_date');
            $table->string('payment_method', 20);
            $table->unsignedBigInteger('money_account_id');
            $table->decimal('total_amount', 20, 2);
            $table->string('note')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status', 20)->default('pending');
            $table->char('idempotency_key', 36);
            $table->char('idempotency_hash', 64);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'idempotency_key']);
            $table->unique(['owner_id', 'collection_number']);
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->string('document_type')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('pending');
            $table->char('idempotency_key', 36)->nullable();
            $table->char('idempotency_hash', 64)->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
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
        Schema::create('customer_debt_collection_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('order_id');
            $table->decimal('allocated_amount', 20, 2);
            $table->unsignedInteger('allocation_sequence');
            $table->decimal('remaining_after', 20, 2);
            $table->unsignedBigInteger('payment_transaction_id');
            $table->timestamps();
            $table->unique('payment_transaction_id');
            $table->unique(['collection_id', 'allocation_sequence']);
        });
        Schema::create('customer_debt_snapshot_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('ledger_version')->default(0);
            $table->unsignedSmallInteger('dirty_from_year')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'client_id']);
        });
    }
}
