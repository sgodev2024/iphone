<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\JournalEntryController;
use App\Models\Client;
use App\Models\CustomerDebtSnapshotState;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Services\Accounting\CustomerDebtSnapshotService;
use App\Support\DecimalAmount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerDebtYearlySnapshotTest extends TestCase
{
    private int $receivableAccountId;

    private int $otherAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'customer_debt_snapshot_states',
            'customer_debt_yearly_snapshots',
            'transaction_entries',
            'transactions',
            'clients',
            'accounts',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('role_id')->default(2);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->default('other');
            $table->string('document_type')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('completed');
            $table->string('idempotency_key')->nullable();
            $table->string('idempotency_hash')->nullable();
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
        Schema::create('customer_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedDecimal('opening_debit', 20, 2)->default(0);
            $table->unsignedDecimal('opening_credit', 20, 2)->default(0);
            $table->date('source_through_date');
            $table->unsignedBigInteger('source_version')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'client_id', 'fiscal_year']);
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

        $this->receivableAccountId = $this->account('131');
        $this->otherAccountId = $this->account('111');
    }

    public function test_debit_credit_and_zero_balances_carry_forward(): void
    {
        $owner = $this->owner('carry');
        $debitClient = $this->client($owner, 'Debit');
        $creditClient = $this->client($owner, 'Credit');
        $zeroClient = $this->client($owner, 'Zero');
        $this->rawEntry($owner, $debitClient, '2026-06-01', '10000000.00', '0.00');
        $this->rawEntry($owner, $debitClient, '2026-12-01', '0.00', '4000000.00');
        $this->rawEntry($owner, $creditClient, '2026-12-01', '0.00', '2000000.00');
        $this->rawEntry($owner, $zeroClient, '2026-12-01', '100.00', '100.00');
        $service = app(CustomerDebtSnapshotService::class);

        $debit = $service->getOrBuild($owner, $debitClient, 2027);
        $credit = $service->getOrBuild($owner, $creditClient, 2027);
        $zero = $service->getOrBuild($owner, $zeroClient, 2027);

        $this->assertSame('6000000.00', $debit->opening_debit);
        $this->assertSame('0.00', $debit->opening_credit);
        $this->assertSame('0.00', $credit->opening_debit);
        $this->assertSame('2000000.00', $credit->opening_credit);
        $this->assertSame('0.00', $zero->opening_debit);
        $this->assertSame('0.00', $zero->opening_credit);
    }

    public function test_historical_create_marks_dirty_and_lazy_rebuilds(): void
    {
        $owner = $this->owner('historical');
        $client = $this->client($owner, 'Historical');
        $this->rawEntry($owner, $client, '2026-01-01', '10000000.00', '4000000.00');
        $service = app(CustomerDebtSnapshotService::class);
        $this->assertSame('6000000.00', $service->getOrBuild($owner, $client, 2027)->opening_debit);

        $transaction = $this->transaction($owner, '2026-12-20');
        $entry = TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->receivableAccountId,
            'debit_amount' => '0.00',
            'credit_amount' => '1000000.00',
            'tableable_type' => Client::class,
            'tableable_id' => $client,
        ]);

        $state = CustomerDebtSnapshotState::where('client_id', $client)->firstOrFail();
        $this->assertSame(1, $state->ledger_version);
        $this->assertSame(2027, $state->dirty_from_year);
        $this->assertSame('5000000.00', $service->getOrBuild($owner, $client, 2027)->opening_debit);
        $this->assertNull($state->fresh()->dirty_from_year);

        $entry->update(['credit_amount' => '2000000.00']);
        $this->assertSame(2027, $state->fresh()->dirty_from_year);
        $this->assertSame('4000000.00', $service->getOrBuild($owner, $client, 2027)->opening_debit);
    }

    public function test_current_year_movement_does_not_mutate_opening_and_flows_to_next_year(): void
    {
        $owner = $this->owner('current-movement');
        $client = $this->client($owner, 'Current movement');
        $this->rawEntry($owner, $client, '2026-06-01', '6000000.00', '0.00');
        $service = app(CustomerDebtSnapshotService::class);
        $opening2027 = $service->getOrBuild($owner, $client, 2027);

        $transaction = $this->transaction($owner, '2027-02-01');
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->receivableAccountId,
            'debit_amount' => '0.00',
            'credit_amount' => '2000000.00',
            'tableable_type' => Client::class,
            'tableable_id' => $client,
        ]);

        $this->assertSame('6000000.00', $service->getOrBuild($owner, $client, 2027)->opening_debit);
        $this->assertSame('6000000.00', $opening2027->fresh()->opening_debit);
        $this->assertSame('4000000.00', $service->getOrBuild($owner, $client, 2028)->opening_debit);
    }

    public function test_cross_year_report_does_not_double_count_period_movement(): void
    {
        $owner = $this->owner('cross-year');
        $client = $this->client($owner, 'Cross year');
        $this->rawEntry($owner, $client, '2025-06-01', '10.00', '0.00');
        $this->rawEntry($owner, $client, '2026-12-15', '20.00', '0.00');
        $this->rawEntry($owner, $client, '2027-01-15', '0.00', '5.00');

        $row = app(CustomerDebtSnapshotService::class)
            ->report($owner, '2026-12-01', '2027-01-31')
            ->firstWhere('client_id', $client);

        $this->assertNotNull($row);
        $this->assertSame('10.00', $row->opening_debit);
        $this->assertSame('20.00', $row->period_debit);
        $this->assertSame('5.00', $row->period_credit);
        $this->assertSame('25.00', $row->ending_debit);
        $this->assertSame(
            $this->fullLedgerReference($client, '2026-12-01', '2027-01-31'),
            [
                $row->opening_debit,
                $row->opening_credit,
                $row->period_debit,
                $row->period_credit,
                $row->ending_debit,
                $row->ending_credit,
            ]
        );
    }

    public function test_date_client_and_account_moves_invalidate_old_and_new_contributions(): void
    {
        $owner = $this->owner('moves');
        $clientA = $this->client($owner, 'A');
        $clientB = $this->client($owner, 'B');
        $transaction = $this->transaction($owner, '2025-12-20');
        $entry = TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->receivableAccountId,
            'debit_amount' => '100.00',
            'credit_amount' => '0.00',
            'tableable_type' => Client::class,
            'tableable_id' => $clientA,
        ]);

        $transaction->update(['transaction_date' => '2026-01-05']);
        $entry->update(['tableable_id' => $clientB]);

        $this->assertSame(2026, CustomerDebtSnapshotState::where('client_id', $clientA)->value('dirty_from_year'));
        $this->assertSame(2027, CustomerDebtSnapshotState::where('client_id', $clientB)->value('dirty_from_year'));

        $entry->update(['account_id' => $this->otherAccountId]);
        $this->assertSame(2027, CustomerDebtSnapshotState::where('client_id', $clientB)->value('dirty_from_year'));
    }

    public function test_transaction_delete_invalidates_before_database_cascade_would_remove_entries(): void
    {
        $owner = $this->owner('delete');
        $client = $this->client($owner, 'Delete');
        $transaction = $this->transaction($owner, '2026-07-01');
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->receivableAccountId,
            'debit_amount' => '100.00',
            'credit_amount' => '0.00',
            'tableable_type' => Client::class,
            'tableable_id' => $client,
        ]);
        $versionBefore = CustomerDebtSnapshotState::where('client_id', $client)->value('ledger_version');

        $transaction->delete();

        $this->assertGreaterThan($versionBefore, CustomerDebtSnapshotState::where('client_id', $client)->value('ledger_version'));
        $this->assertSame(2027, CustomerDebtSnapshotState::where('client_id', $client)->value('dirty_from_year'));
    }

    public function test_soft_deleted_client_keeps_snapshot_and_report_balance(): void
    {
        $owner = $this->owner('soft-delete');
        $client = $this->client($owner, 'Soft deleted');
        $this->rawEntry($owner, $client, '2026-03-01', '321.00', '0.00');
        $service = app(CustomerDebtSnapshotService::class);
        $service->getOrBuild($owner, $client, 2027);
        DB::table('clients')->where('id', $client)->update(['deleted_at' => now()]);

        $snapshot = $service->getOrBuild($owner, $client, 2027);
        $row = $service->report($owner, '2027-01-01', '2027-01-31')->firstWhere('client_id', $client);

        $this->assertSame('321.00', $snapshot->opening_debit);
        $this->assertNotNull($row);
        $this->assertSame('321.00', $row->opening_debit);
    }

    public function test_cascade_chain_reconciles_exactly(): void
    {
        $owner = $this->owner('chain');
        $client = $this->client($owner, 'Chain');
        $this->rawEntry($owner, $client, '2025-03-01', '10000000.00', '0.00');
        $this->rawEntry($owner, $client, '2025-04-01', '10000000.00', '0.00');
        $service = app(CustomerDebtSnapshotService::class);
        $service->getOrBuild($owner, $client, 2026);
        $service->getOrBuild($owner, $client, 2027);
        $service->getOrBuild($owner, $client, 2028);

        $transaction = $this->transaction($owner, '2025-12-31');
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->receivableAccountId,
            'debit_amount' => '0.00',
            'credit_amount' => '0.01',
            'tableable_type' => Client::class,
            'tableable_id' => $client,
        ]);

        $this->assertSame(2026, CustomerDebtSnapshotState::where('client_id', $client)->value('dirty_from_year'));
        $snapshot2027 = $service->getOrBuild($owner, $client, 2027);
        $this->assertSame('19999999.99', $snapshot2027->opening_debit);
        $this->assertSame(2028, CustomerDebtSnapshotState::where('client_id', $client)->value('dirty_from_year'));
        $snapshot2028 = $service->getOrBuild($owner, $client, 2028);
        $this->assertSame('19999999.99', $snapshot2028->opening_debit);
        $this->assertSame(
            $snapshot2028->opening_debit,
            $service->fullLedgerOpeningNet($client, 2028)
        );
        $this->assertNull(CustomerDebtSnapshotState::where('client_id', $client)->value('dirty_from_year'));
    }

    public function test_journal_delete_cannot_delete_another_owners_transaction(): void
    {
        $ownerA = $this->owner('owner-a');
        $ownerB = $this->owner('owner-b');
        $transaction = $this->transaction($ownerB, '2026-01-01');
        $request = Request::create('/admin/journal-entries/destroy', 'DELETE', [
            'ids' => [$transaction->id],
        ]);
        $request->setUserResolver(fn () => User::findOrFail($ownerA));

        app(JournalEntryController::class)->destroy($request);

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'user_id' => $ownerB]);
    }

    private function owner(string $key): int
    {
        return DB::table('users')->insertGetId([
            'name' => $key,
            'email' => "{$key}@example.com",
            'password' => 'password',
            'role_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function client(int $ownerId, string $name): int
    {
        return DB::table('clients')->insertGetId([
            'user_id' => $ownerId,
            'code' => 'KH'.str_pad((string) (DB::table('clients')->count() + 1), 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'phone' => '0900000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function account(string $code): int
    {
        return DB::table('accounts')->insertGetId([
            'code' => $code,
            'name' => $code,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function transaction(int $ownerId, string $date): Transaction
    {
        return Transaction::create([
            'user_id' => $ownerId,
            'transaction_date' => $date,
            'type' => 'other',
            'status' => Transaction::STATUS_COMPLETED,
        ]);
    }

    private function rawEntry(
        int $ownerId,
        int $clientId,
        string $date,
        string $debit,
        string $credit
    ): void {
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $ownerId,
            'transaction_date' => $date,
            'type' => 'other',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transaction_entries')->insert([
            'transaction_id' => $transactionId,
            'account_id' => $this->receivableAccountId,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'tableable_type' => Client::class,
            'tableable_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function fullLedgerReference(int $clientId, string $fromDate, string $toDate): array
    {
        $totals = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('te.account_id', $this->receivableAccountId)
            ->where('te.tableable_type', Client::class)
            ->where('te.tableable_id', $clientId)
            ->where('t.transaction_date', '<=', $toDate)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.debit_amount ELSE 0 END), 0) opening_debit_total',
                [$fromDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.credit_amount ELSE 0 END), 0) opening_credit_total',
                [$fromDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date >= ? THEN te.debit_amount ELSE 0 END), 0) period_debit',
                [$fromDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date >= ? THEN te.credit_amount ELSE 0 END), 0) period_credit',
                [$fromDate]
            )
            ->first();
        $opening = DecimalAmount::splitNet(DecimalAmount::subtract(
            (string) $totals->opening_debit_total,
            (string) $totals->opening_credit_total
        ));
        $periodDebit = DecimalAmount::normalize((string) $totals->period_debit);
        $periodCredit = DecimalAmount::normalize((string) $totals->period_credit);
        $ending = DecimalAmount::splitNet(DecimalAmount::subtract(
            DecimalAmount::add($opening['debit'], $periodDebit),
            DecimalAmount::add($opening['credit'], $periodCredit)
        ));

        return [
            $opening['debit'],
            $opening['credit'],
            $periodDebit,
            $periodCredit,
            $ending['debit'],
            $ending['credit'],
        ];
    }
}
