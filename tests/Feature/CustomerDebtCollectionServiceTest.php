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
use App\Models\Roles;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerDebtCollectionService;
use App\Services\OrderPaymentHistoryService;
use App\Services\TransactionBusinessListService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $fullAccessRole = Roles::create(['name' => 'store']);
        $this->owner->forceFill(['role_id' => $fullAccessRole->id])->save();
        $this->otherOwner->forceFill(['role_id' => $fullAccessRole->id])->save();
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

    public function test_cash_add_form_is_a_unified_receipt_payment_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.save'));

        $response->assertOk()
            ->assertSee('id="cash-transaction-type"', false)
            ->assertSee('Loại giao dịch')
            ->assertSee('id="cash-operation"', false)
            ->assertSee('Nghiệp vụ')
            ->assertSee('Thu công nợ khách hàng')
            ->assertSee('Trả công nợ nhà cung cấp')
            ->assertDontSee('Phiếu tiền mặt thông thường')
            ->assertDontSee('id="entry-mode"', false)
            ->assertSee('id="customer-debt-panel"', false)
            ->assertSee('id="supplier-debt-panel"', false)
            ->assertSee('Tài khoản tiền mặt canonical')
            ->assertSee("formData.set('import_coupon_id', $('#supplier-import-id').val())", false)
            ->assertSee("replace(/\\./g, '')", false);
    }

    public function test_bank_add_form_reuses_collection_mode_and_lists_only_active_direct_children_of_112(): void
    {
        $defaultBank = Account::create([
            'code' => '112DEF',
            'name' => 'Default Bank Child',
            'status' => true,
            'is_default' => true,
            'parent_id' => $this->bankParent->id,
        ]);
        Account::create([
            'code' => '112OFF',
            'name' => 'Inactive Bank Child',
            'status' => false,
            'parent_id' => $this->bankParent->id,
        ]);
        $unrelatedParent = Account::create(['code' => '211', 'name' => 'Unrelated', 'status' => true]);
        Account::create([
            'code' => '211CHILD',
            'name' => 'Unrelated Child',
            'status' => true,
            'parent_id' => $unrelatedParent->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('admin.transactions.bank.save'));

        $response->assertOk()
            ->assertSee('Phiếu ngân hàng thông thường')
            ->assertSee('value="customer_debt_collection"', false)
            ->assertSee('id="collection-money-account"', false)
            ->assertSee("value=\"{$this->bank->id}\"", false)
            ->assertSee("value=\"{$defaultBank->id}\"", false)
            ->assertDontSee("value=\"{$this->bankParent->id}\"", false)
            ->assertDontSee('Inactive Bank Child')
            ->assertDontSee('Unrelated Child')
            ->assertSee('data-payment-method="bank_transfer"', false)
            ->assertSee("formData.set('money_account_id', $('#collection-money-account').val())", false);
    }

    public function test_bank_collection_post_uses_selected_child112_and_allocates_700k_as_500k_plus_200k(): void
    {
        $first = $this->createCanonicalOrder(500000, '2026-08-01', 'BANK-A');
        $second = $this->createCanonicalOrder(600000, '2026-08-05', 'BANK-B');
        $payload = [
            'client_id' => $this->client->id,
            'amount' => '700000',
            'collection_date' => '2026-08-15',
            'payment_method' => 'bank_transfer',
            'money_account_id' => $this->bank->id,
            'note' => 'Thu từ Bank UI',
            'idempotency_key' => $this->uuid(39),
        ];

        $response = $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), $payload)
            ->assertOk()
            ->assertJsonPath('replayed', false)
            ->assertJsonPath('collection.payment_method', 'bank_transfer')
            ->assertJsonPath('collection.money_account.id', $this->bank->id)
            ->assertJsonPath('collection.money_account.code', $this->bank->code)
            ->assertJsonPath('collection.allocations.0.order_id', $first->id)
            ->assertJsonPath('collection.allocations.0.allocated_amount', '500000.00')
            ->assertJsonPath('collection.allocations.1.order_id', $second->id)
            ->assertJsonPath('collection.allocations.1.allocated_amount', '200000.00')
            ->assertJsonPath('collectible_total', '400000.00');

        $collection = CustomerDebtCollection::firstOrFail();
        $payments = Transaction::query()->where('collection_id', $collection->id)->get();
        $this->assertCount(2, $payments);
        $this->assertSame(700000, (int) DB::table('transaction_entries')
            ->whereIn('transaction_id', $payments->pluck('id'))
            ->where('account_id', $this->bank->id)
            ->sum('debit_amount'));
        $this->assertSame(700000, (int) DB::table('transaction_entries')
            ->whereIn('transaction_id', $payments->pluck('id'))
            ->where('account_id', $this->receivable->id)
            ->where('tableable_type', Client::class)
            ->where('tableable_id', $this->client->id)
            ->sum('credit_amount'));

        foreach ($payments as $payment) {
            $this->assertSame('credit_notice', $payment->type);
            $this->assertSame('order', $payment->document_type);
            $this->assertSame(Transaction::STATUS_COMPLETED, $payment->status);
        }

        $this->assertSame(0, (int) $first->fresh()->debt_amount);
        $this->assertSame(400000, (int) $second->fresh()->debt_amount);

        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), $payload)
            ->assertOk()
            ->assertJsonPath('replayed', true)
            ->assertJsonPath('collection.id', $response->json('collection.id'));
        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), array_merge($payload, [
                'idempotency_key' => $this->uuid(48),
            ]))
            ->assertUnprocessable();

        $this->assertDatabaseCount('customer_debt_collections', 1);
        $this->assertSame(2, Transaction::where('collection_id', $collection->id)->count());
    }

    public function test_bank_collection_rejects_every_invalid_account_without_fallback(): void
    {
        $this->createCanonicalOrder(1000000, '2026-08-01', 'BANK-INVALID');
        $inactive = Account::create([
            'code' => '112OFF',
            'name' => 'Inactive Bank',
            'status' => false,
            'parent_id' => $this->bankParent->id,
        ]);
        $unrelatedParent = Account::create(['code' => '211', 'name' => 'Unrelated', 'status' => true]);
        $unrelatedChild = Account::create([
            'code' => '211CHILD',
            'name' => 'Wrong Child',
            'status' => true,
            'parent_id' => $unrelatedParent->id,
        ]);
        $base = [
            'client_id' => $this->client->id,
            'amount' => '100000',
            'collection_date' => '2026-08-15',
            'payment_method' => 'bank_transfer',
        ];

        foreach ([
            $this->bankParent->id,
            $this->cash->id,
            $this->receivable->id,
            $inactive->id,
            $unrelatedChild->id,
            999999,
        ] as $index => $invalidAccountId) {
            $this->actingAs($this->owner)
                ->postJson(route('admin.debts.customer.collections.store'), array_merge($base, [
                    'money_account_id' => $invalidAccountId,
                    'idempotency_key' => $this->uuid(40 + $index),
                ]))
                ->assertUnprocessable();
        }

        $foreignClient = Client::create([
            'user_id' => $this->otherOwner->id,
            'name' => 'Foreign bank client',
        ]);
        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), array_merge($base, [
                'client_id' => $foreignClient->id,
                'money_account_id' => $this->bank->id,
                'idempotency_key' => $this->uuid(47),
            ]))
            ->assertNotFound();

        $this->assertDatabaseCount('customer_debt_collections', 0);
        $this->assertSame(0, Transaction::where('type', 'credit_notice')->count());
    }

    public function test_bank_idempotency_conflicts_when_selected_account_changes(): void
    {
        $this->createCanonicalOrder(500000, '2026-08-01', 'BANK-IDEMPOTENT');
        $otherBank = Account::create([
            'code' => '112VCB',
            'name' => 'VCB',
            'status' => true,
            'parent_id' => $this->bankParent->id,
        ]);
        $payload = [
            'client_id' => $this->client->id,
            'amount' => '100000',
            'collection_date' => '2026-08-15',
            'payment_method' => 'bank_transfer',
            'money_account_id' => $this->bank->id,
            'idempotency_key' => $this->uuid(46),
        ];

        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), $payload)
            ->assertOk();
        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), array_merge($payload, [
                'money_account_id' => $otherBank->id,
            ]))
            ->assertConflict();

        $this->assertDatabaseCount('customer_debt_collections', 1);
        $this->assertSame($this->bank->id, CustomerDebtCollection::firstOrFail()->money_account_id);
    }

    public function test_collection_client_search_is_owner_scoped(): void
    {
        $owned = Client::create([
            'user_id' => $this->owner->id,
            'name' => 'Scoped Customer',
            'phone' => '0901000001',
        ]);
        Client::create([
            'user_id' => $this->otherOwner->id,
            'name' => 'Scoped Customer Other',
            'phone' => '0901000002',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson(route('admin.debts.customer.collections.clients', ['keyword' => 'Scoped']));

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $owned->id);
    }

    public function test_collection_preview_reports_ready_fifo_no_debt_and_reconciliation_blocked_states(): void
    {
        $first = $this->createCanonicalOrder(100000, '2026-08-01', 'FIFO-A');
        $second = $this->createCanonicalOrder(200000, '2026-08-02', 'FIFO-B');

        $this->actingAs($this->owner)
            ->getJson(route('admin.debts.customer.collections.preview', [
                'clientId' => $this->client->id,
                'collection_date' => '2026-08-15',
                'amount' => 150000,
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('can_collect', true)
            ->assertJsonPath('preview_allocations.0.order_id', $first->id)
            ->assertJsonPath('preview_allocations.1.order_id', $second->id);

        $noDebtClient = Client::create(['user_id' => $this->owner->id, 'name' => 'No debt']);
        $this->actingAs($this->owner)
            ->getJson(route('admin.debts.customer.collections.preview', [
                'clientId' => $noDebtClient->id,
                'collection_date' => '2026-08-15',
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'blocked')
            ->assertJsonPath('can_collect', false);

        $this->actingAs($this->owner)
            ->getJson(route('admin.debts.customer.collections.preview', [
                'clientId' => $this->client->id,
                'collection_date' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'blocked')
            ->assertJsonCount(0, 'items');

        $malformed = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => '2026-08-10',
            'type' => 'income',
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $malformed->entries()->create([
            'account_id' => $this->receivable->id,
            'credit_amount' => 10000,
            'tableable_type' => Client::class,
            'tableable_id' => $this->client->id,
        ]);

        $this->actingAs($this->owner)
            ->getJson(route('admin.debts.customer.collections.preview', [
                'clientId' => $this->client->id,
                'collection_date' => '2026-08-15',
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('status', 'blocked')
            ->assertJsonPath('can_collect', false)
            ->assertJsonStructure(['errors' => ['reconciliation']]);
    }

    public function test_collection_post_saves_attachment_once_returns_result_and_replays_same_key(): void
    {
        config([
            'filesystems.disks.public.root' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'iphone-phase7c-'.uniqid(),
        ]);
        Storage::forgetDisk('public');
        $order = $this->createCanonicalOrder(300000, '2026-08-01', 'ATTACH');
        $payload = [
            'client_id' => $this->client->id,
            'amount' => '100000',
            'collection_date' => '2026-08-15',
            'payment_method' => 'cash',
            'note' => 'Thu từ cash UI',
            'idempotency_key' => $this->uuid(37),
            'attachment' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('admin.debts.customer.collections.store'), $payload, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('replayed', false)
            ->assertJsonPath('collection.collection_number', 'PTCN-000001')
            ->assertJsonPath('collection.total_amount', '100000.00')
            ->assertJsonPath('collection.allocations.0.order_id', $order->id);

        $collection = CustomerDebtCollection::firstOrFail();
        $this->assertNotNull($collection->attachment);
        Storage::disk('public')->assertExists($collection->attachment);

        unset($payload['attachment']);
        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), $payload)
            ->assertOk()
            ->assertJsonPath('replayed', true)
            ->assertJsonPath('collection.id', $response->json('collection.id'));

        $this->assertSame(1, CustomerDebtCollection::count());
        $this->assertCount(1, Storage::disk('public')->allFiles('attachments/customer_debt_collections'));
        Storage::disk('public')->delete($collection->attachment);
    }

    public function test_collection_http_validation_requires_raw_vnd_and_rejects_unsafe_input(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01', 'VALIDATE');
        $base = [
            'client_id' => $this->client->id,
            'amount' => '50000',
            'collection_date' => '2026-08-15',
            'payment_method' => 'cash',
            'idempotency_key' => $this->uuid(38),
        ];

        foreach ([
            ['amount' => ''],
            ['amount' => '0'],
            ['amount' => '-1'],
            ['amount' => '100.000'],
            ['amount' => '100001'],
            ['collection_date' => '2026-08-16'],
            ['money_account_id' => $this->bank->id],
        ] as $invalid) {
            $this->actingAs($this->owner)
                ->postJson(route('admin.debts.customer.collections.store'), array_merge($base, $invalid))
                ->assertUnprocessable();
        }

        $foreignClient = Client::create([
            'user_id' => $this->otherOwner->id,
            'name' => 'Foreign client',
        ]);
        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.collections.store'), array_merge($base, [
                'client_id' => $foreignClient->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseCount('customer_debt_collections', 0);
    }

    public function test_old_per_order_public_route_is_gone_and_collection_history_is_one_row_per_collection(): void
    {
        $first = $this->createCanonicalOrder(500000, '2026-08-01', 'HISTORY-A');
        $second = $this->createCanonicalOrder(600000, '2026-08-02', 'HISTORY-B');

        $this->actingAs($this->owner)
            ->postJson(route('admin.debts.customer.payments.store'), [
                'order_id' => $first->id,
                'amount' => 100000,
            ])
            ->assertGone();

        $result = $this->service->collect($this->owner, $this->payload(700000, 'cash', $this->uuid(40)));

        $response = $this->actingAs($this->owner)
            ->get(route('admin.debts.customer.collections.index'))
            ->assertOk();
        $collections = $response->viewData('collections');

        $this->assertCount(1, $collections);
        $this->assertSame($result['collection']->id, $collections->first()->id);
        $this->assertSame(2, $collections->first()->allocations_count);
        $response->assertSee('PTCN-000001');

        $this->actingAs($this->owner)
            ->get(route('admin.debts.customer.collections.show', $result['collection']->id))
            ->assertOk()
            ->assertSee('<strong>ID:</strong> '.$result['collection']->id, false)
            ->assertSee('<strong>Mã phiếu:</strong> PTCN-000001', false)
            ->assertSee('HISTORY-A')
            ->assertSee('HISTORY-B')
            ->assertSee('500.000', false)
            ->assertSee('200.000', false);
    }

    public function test_order_history_uses_exact_allocations_across_multiple_collections(): void
    {
        $first = $this->createCanonicalOrder(500000, '2026-08-01', 'ORDER-A');
        $second = $this->createCanonicalOrder(600000, '2026-08-02', 'ORDER-B');
        $this->service->collect($this->owner, $this->payload(300000, 'cash', $this->uuid(41)));
        $this->service->collect($this->owner, $this->payload(400000, 'cash', $this->uuid(42)));

        $history = app(OrderPaymentHistoryService::class);
        $firstRows = $history->forOrder($first);
        $secondRows = $history->forOrder($second);

        $this->assertSame([300000, 200000], $firstRows->pluck('amount')->map(fn ($amount) => (int) $amount)->all());
        $this->assertSame([200000], $secondRows->pluck('amount')->map(fn ($amount) => (int) $amount)->all());
        $this->assertSame(['200000.00', '0.00'], $firstRows->pluck('remaining_after')->all());
        $this->assertSame('400000.00', $secondRows->first()['remaining_after']);
        $this->assertSame(['PTCN-000001', 'PTCN-000002'], $firstRows->pluck('collection_number')->all());
    }

    public function test_collection_owner_isolation_attachment_permission_and_integrity_warning(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01', 'SECURE');
        $collection = $this->service->collect(
            $this->owner,
            $this->payload(100000, 'cash', $this->uuid(43))
        )['collection'];
        DB::table('customer_debt_collections')->where('id', $collection->id)->update([
            'attachment' => 'proof.pdf',
            'total_amount' => '100001.00',
        ]);
        $disk = \Mockery::mock();
        $disk->shouldReceive('exists')->once()->with('proof.pdf')->andReturnTrue();
        $disk->shouldReceive('response')->once()->with('proof.pdf')->andReturn(response('proof'));
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);

        $this->actingAs($this->owner)
            ->get(route('admin.debts.customer.collections.show', $collection->id))
            ->assertOk()
            ->assertSee('Cảnh báo toàn vẹn');
        $this->actingAs($this->owner)
            ->get(route('admin.debts.customer.collections.attachment', $collection->id))
            ->assertOk();

        $this->actingAs($this->otherOwner)
            ->get(route('admin.debts.customer.collections.show', $collection->id))
            ->assertNotFound();
        $this->actingAs($this->otherOwner)
            ->get(route('admin.debts.customer.collections.attachment', $collection->id))
            ->assertNotFound();
        $foreignList = $this->actingAs($this->otherOwner)
            ->get(route('admin.debts.customer.collections.index', ['collection_number' => 'PTCN-000001']))
            ->assertOk()
            ->viewData('collections');
        $this->assertCount(0, $foreignList);
    }

    public function test_cash_and_bank_lists_group_collection_transactions_without_double_counting_generic_rows(): void
    {
        $this->createCanonicalOrder(500000, '2026-08-01', 'GROUP-A');
        $this->createCanonicalOrder(600000, '2026-08-02', 'GROUP-B');
        $cashCollection = $this->service->collect(
            $this->owner,
            $this->payload(700000, 'cash', $this->uuid(44))
        )['collection'];

        $generic = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => '2026-08-15',
            'description' => 'Generic cash row',
            'type' => 'income',
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $generic->entries()->create(['account_id' => $this->cash->id, 'debit_amount' => 50000, 'credit_amount' => 0]);
        $generic->entries()->create(['account_id' => $this->revenue->id, 'debit_amount' => 0, 'credit_amount' => 50000]);

        $otherClient = Client::create(['user_id' => $this->owner->id, 'name' => 'Khách Bank']);
        $this->createCanonicalOrder(500000, '2026-08-03', 'BANK-A', $otherClient);
        $this->createCanonicalOrder(600000, '2026-08-04', 'BANK-B', $otherClient);
        $bankPayload = $this->payload(700000, 'bank_transfer', $this->uuid(45));
        $bankPayload['client_id'] = $otherClient->id;
        $bankCollection = $this->service->collect($this->owner, $bankPayload)['collection'];

        $businessList = app(TransactionBusinessListService::class);
        $cashRows = $businessList->entries([$this->owner->id], collect([$this->cash->id]), '2026-08-01', '2026-08-31');
        $bankRows = $businessList->entries([$this->owner->id], collect([$this->bank->id]), '2026-08-01', '2026-08-31');

        $this->assertCount(2, $cashRows);
        $this->assertSame(1, $cashRows->where('collection_id', $cashCollection->id)->count());
        $this->assertSame(700000, (int) $cashRows->firstWhere('collection_id', $cashCollection->id)->debit_amount);
        $this->assertSame(50000, (int) $cashRows->firstWhere('collection_id', null)->debit_amount);
        $this->assertSame(750000, (int) $cashRows->sum('debit_amount'));
        $this->assertCount(1, $bankRows);
        $this->assertSame($bankCollection->id, (int) $bankRows->first()->collection_id);
        $this->assertSame(700000, (int) $bankRows->first()->debit_amount);
        $this->assertSame(2, Transaction::where('collection_id', $cashCollection->id)->count());
        $this->assertSame(2, Transaction::where('collection_id', $bankCollection->id)->count());
    }

    public function test_collection_child_transactions_cannot_be_opened_or_updated_individually_and_list_queries_are_bounded(): void
    {
        $this->createCanonicalOrder(100000, '2026-08-01', 'IMMUTABLE-TX');
        $collection = $this->service->collect(
            $this->owner,
            $this->payload(100000, 'cash', $this->uuid(46))
        )['collection'];
        $transaction = Transaction::where('collection_id', $collection->id)->firstOrFail();

        $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.save', ['transactionId' => $transaction->id]))
            ->assertConflict();
        $this->actingAs($this->owner)
            ->putJson(route('admin.transactions.cash.update'), [
                'transaction_id' => $transaction->id,
            ])
            ->assertConflict();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $request = Request::create('/admin/debts/customer/collections', 'GET');
        $request->setUserResolver(fn () => $this->owner);
        app(\App\Http\Controllers\Admin\CustomerDebtCollectionController::class)->index($request);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, $queryCount);
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
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::create('user_info', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('img_url')->nullable();
            $table->timestamps();
        });
        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('logo')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('receiver')->nullable();
            $table->string('qr')->nullable();
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
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
