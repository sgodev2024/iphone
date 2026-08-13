<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DebtController;
use App\Models\Company;
use App\Models\SupplierDebtYearlySnapshot;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Services\Accounting\SupplierDebtSnapshotService;
use App\Services\SupplierDebtReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierDebtSnapshotTest extends TestCase
{
    private SupplierDebtSnapshotService $service;

    private User $owner;

    private User $otherOwner;

    private Company $company;

    private Company $otherCompany;

    private int $payableAccountId;

    private int $cashAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->service = app(SupplierDebtSnapshotService::class);
        $this->owner = $this->createUser('owner@example.com');
        $this->otherOwner = $this->createUser('other@example.com');
        $this->company = $this->createCompany($this->owner, 'Company Alpha');
        $this->otherCompany = $this->createCompany($this->otherOwner, 'Company Beta');
        $this->payableAccountId = $this->createAccount('331', 'Payable');
        $this->cashAccountId = $this->createAccount('111', 'Cash');
    }

    public function test_builds_credit_opening_and_does_not_create_ledger_transactions(): void
    {
        $this->insertLedger('2026-01-10', '0.00', '10000000.00', $this->company->id);
        $this->insertLedger('2026-06-10', '6000000.00', '0.00', $this->company->id);
        $before = [
            DB::table('transactions')->count(),
            DB::table('transaction_entries')->count(),
        ];

        $result = $this->service->buildOwnerYear($this->owner->id, 2027);
        $snapshot = $this->snapshot(2027, $this->company->id);

        $this->assertSame(1, $result['built']);
        $this->assertSame('0.00', (string) $snapshot->opening_debit);
        $this->assertSame('4000000.00', (string) $snapshot->opening_credit);
        $this->assertSame('2026-12-31', $snapshot->source_through_date->toDateString());
        $this->assertSame($before, [
            DB::table('transactions')->count(),
            DB::table('transaction_entries')->count(),
        ]);
    }

    public function test_debit_nature_is_split_as_opening_debit(): void
    {
        $this->insertLedger('2026-01-10', '0.00', '10000000.00', $this->company->id);
        $this->insertLedger('2026-06-10', '12000000.00', '0.00', $this->company->id);

        $this->service->buildOwnerYear($this->owner->id, 2027);
        $snapshot = $this->snapshot(2027, $this->company->id);

        $this->assertSame('2000000.00', (string) $snapshot->opening_debit);
        $this->assertSame('0.00', (string) $snapshot->opening_credit);
    }

    public function test_zero_net_opening_has_zero_both_natures(): void
    {
        $this->insertLedger('2026-01-10', '5000000.00', '5000000.00', $this->company->id);

        $this->service->buildOwnerYear($this->owner->id, 2027);
        $snapshot = $this->snapshot(2027, $this->company->id);

        $this->assertSame('0.00', (string) $snapshot->opening_debit);
        $this->assertSame('0.00', (string) $snapshot->opening_credit);
    }

    public function test_only_completed_active_331_company_entries_in_same_owner_are_eligible(): void
    {
        $this->insertLedger('2026-01-01', '0.00', '1000000.00', $this->company->id, 'pending');
        $this->insertLedger('2026-01-02', '0.00', '2000000.00', $this->company->id, 'failed');
        $this->insertLedger('2026-01-03', '0.00', '3000000.00', $this->company->id, 'completed', null, 'App\\Models\\Supplier', 1);
        $this->insertLedger('2026-01-04', '0.00', '4000000.00', $this->company->id, 'completed', $this->cashAccountId);
        $this->insertLedger('2026-01-05', '0.00', '5000000.00', $this->otherCompany->id);
        $this->insertLedger('2026-01-06', '0.00', '6000000.00', $this->company->id);

        $this->service->buildOwnerYear($this->owner->id, 2027);
        $snapshot = $this->snapshot(2027, $this->company->id);

        $this->assertSame('6000000.00', (string) $snapshot->opening_credit);
        $this->assertNull(DB::table('supplier_debt_yearly_snapshots')->where('company_id', $this->otherCompany->id)->first());
    }

    public function test_lazy_build_is_idempotent_and_has_one_business_key_row(): void
    {
        $this->insertLedger('2026-02-01', '0.00', '4000000.00', $this->company->id);

        $first = $this->service->getOrBuild($this->owner->id, $this->company->id, 2027);
        $second = $this->service->getOrBuild($this->owner->id, $this->company->id, 2027);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DB::table('supplier_debt_yearly_snapshots')
            ->where('owner_id', $this->owner->id)
            ->where('company_id', $this->company->id)
            ->where('fiscal_year', 2027)
            ->count());
    }

    public function test_historical_entry_create_update_and_account_change_invalidate_state(): void
    {
        $this->service->buildOwnerYear($this->owner->id, 2027);
        $transaction = $this->createEloquentLedger('2026-03-01', '0.00', '1000000.00', $this->company->id);
        $state = $this->state();

        $this->assertSame(1, (int) $state->ledger_version);
        $this->assertSame(2027, (int) $state->dirty_from_year);

        $entry = $transaction->entries()->first();
        $entry->update(['credit_amount' => '2000000.00']);
        $this->assertSame(2, (int) $this->state()->ledger_version);
        $this->assertSame(2027, (int) $this->state()->dirty_from_year);

        $entry->update(['account_id' => $this->cashAccountId]);
        $this->assertSame(3, (int) $this->state()->ledger_version);
        $this->assertSame(2027, (int) $this->state()->dirty_from_year);
    }

    public function test_transaction_date_status_and_owner_changes_use_old_and_new_contributions(): void
    {
        $this->service->buildOwnerYear($this->owner->id, 2028);
        $transaction = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => '2025-03-01',
            'status' => Transaction::STATUS_PENDING,
            'type' => 'expense',
        ]);
        $transaction->entries()->create([
            'account_id' => $this->payableAccountId,
            'debit_amount' => '0.00',
            'credit_amount' => '1000000.00',
            'tableable_type' => Company::class,
            'tableable_id' => $this->company->id,
        ]);
        $this->assertSame(0, (int) DB::table('supplier_debt_snapshot_states')->value('ledger_version'));
        $this->assertNull(DB::table('supplier_debt_snapshot_states')->value('dirty_from_year'));

        $transaction->update(['status' => Transaction::STATUS_COMPLETED]);
        $this->assertSame(2026, (int) $this->state()->dirty_from_year);
        $versionAfterCompletion = (int) $this->state()->ledger_version;

        $transaction->update(['transaction_date' => '2026-03-01']);
        $this->assertSame(2026, (int) $this->state()->dirty_from_year);
        $this->assertGreaterThan($versionAfterCompletion, (int) $this->state()->ledger_version);
    }

    public function test_transaction_delete_invalidates_completed_supplier_ledger(): void
    {
        $transaction = $this->createEloquentLedger('2025-03-01', '0.00', '1000000.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2027);
        $version = (int) $this->state()->ledger_version;

        $transaction->delete();

        $this->assertGreaterThan($version, (int) $this->state()->ledger_version);
        $this->assertSame(2026, (int) $this->state()->dirty_from_year);
    }

    public function test_completed_to_failed_invalidates_future_snapshot_and_description_only_does_not(): void
    {
        $transaction = $this->createEloquentLedger('2026-03-01', '0.00', '1000000.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2027);
        $versionBeforeDescription = (int) $this->state()->ledger_version;
        $transaction->update(['description' => 'Non-accounting edit']);
        $this->assertSame($versionBeforeDescription, (int) $this->state()->ledger_version);

        $transaction->update(['status' => Transaction::STATUS_FAILED]);
        $this->assertSame($versionBeforeDescription + 1, (int) $this->state()->ledger_version);
        $this->assertSame(2027, (int) $this->state()->dirty_from_year);
    }

    public function test_entry_company_change_invalidates_both_company_states(): void
    {
        $transaction = $this->createEloquentLedger('2026-03-01', '0.00', '1000000.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2027);
        $second = $this->createCompany($this->owner, 'Company Gamma');
        $this->service->buildOwnerYear($this->owner->id, 2027);

        $transaction->entries()->first()->update(['tableable_id' => $second->id]);

        $this->assertSame(2027, (int) $this->state()->dirty_from_year);
        $this->assertSame(2027, (int) DB::table('supplier_debt_snapshot_states')
            ->where('company_id', $second->id)
            ->value('dirty_from_year'));
    }

    public function test_cross_year_report_uses_from_year_snapshot_and_live_period_once(): void
    {
        $this->insertLedger('2025-12-31', '0.00', '10000000.00', $this->company->id);
        $this->insertLedger('2026-12-20', '2000000.00', '0.00', $this->company->id);
        $this->insertLedger('2027-01-10', '1000000.00', '0.00', $this->company->id);

        $row = app(SupplierDebtReportService::class)
            ->report($this->owner, '2026-12-15', '2027-01-15')
            ->first();

        $this->assertSame('0.00', $row->opening_debit);
        $this->assertSame('10000000.00', $row->opening_credit);
        $this->assertSame('3000000.00', $row->period_debit);
        $this->assertSame('7000000.00', $row->ending_credit);
    }

    public function test_legacy_supplier_tables_are_not_read_and_company_without_rep_is_valid(): void
    {
        Schema::create('supplier_debts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('supplier_debts_detail', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('supplier_debts_id');
            $table->decimal('amount', 20, 2)->default(0);
            $table->timestamps();
        });
        DB::table('supplier_debts')->insert(['user_id' => $this->owner->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->insertLedger('2026-01-01', '0.00', '4000000.00', $this->company->id);

        $this->service->buildOwnerYear($this->owner->id, 2027);

        $this->assertSame('4000000.00', (string) $this->snapshot(2027, $this->company->id)->opening_credit);
    }

    public function test_rebuild_bootstraps_from_full_ledger_without_predecessor(): void
    {
        $this->insertLedger('2024-01-01', '0.00', '7000000.00', $this->company->id);
        $this->service->rebuildOwnerFromLedgerYear($this->owner->id, 2025);

        $this->assertSame('7000000.00', (string) $this->snapshot(2026, $this->company->id)->opening_credit);
    }

    public function test_missing_or_inactive_account_331_fails_without_snapshot_rows(): void
    {
        DB::table('accounts')->where('id', $this->payableAccountId)->update(['status' => false]);

        $this->expectExceptionMessage('331');
        try {
            $this->service->buildOwnerYear($this->owner->id, 2027);
        } finally {
            $this->assertSame(0, DB::table('supplier_debt_yearly_snapshots')->count());
        }
    }

    public function test_cross_owner_company_entry_is_not_added_to_supplier_state(): void
    {
        $this->createEloquentLedger('2026-03-01', '0.00', '1000000.00', $this->otherCompany->id, 'completed', $this->owner->id);

        $this->assertNull(DB::table('supplier_debt_snapshot_states')->first());
        $this->service->buildOwnerYear($this->owner->id, 2027);
        $snapshotCount = DB::table('supplier_debt_yearly_snapshots')->where('owner_id', $this->owner->id)->count();

        $this->assertSame(1, $snapshotCount);
        $this->assertSame('0.00', (string) $this->snapshot(2027, $this->company->id)->opening_credit);
    }

    public function test_incremental_carry_forward_and_historical_rebuild_cascade_years(): void
    {
        $this->insertLedger('2025-01-01', '0.00', '5000000.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2026);
        $this->insertLedger('2026-01-01', '2000000.00', '0.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2027);
        $this->service->buildOwnerYear($this->owner->id, 2028);

        $this->assertSame('3000000.00', (string) $this->snapshot(2027, $this->company->id)->opening_credit);
        $this->assertSame('3000000.00', (string) $this->snapshot(2028, $this->company->id)->opening_credit);

        $changed = $this->createEloquentLedger('2025-06-01', '0.00', '1000000.00', $this->company->id);
        $this->service->rebuildOwnerFromLedgerYear($this->owner->id, 2025);
        $this->assertSame('4000000.00', (string) $this->snapshot(2027, $this->company->id)->opening_credit);
        $this->assertSame('4000000.00', (string) $this->snapshot(2028, $this->company->id)->opening_credit);
        $this->assertNotNull($changed);
    }

    public function test_report_uses_snapshot_opening_and_live_period_without_fake_transactions(): void
    {
        $this->insertLedger('2026-08-01', '0.00', '10000000.00', $this->company->id);
        $this->insertLedger('2026-08-01', '4000000.00', '0.00', $this->company->id);
        $this->insertLedger('2026-08-10', '2000000.00', '0.00', $this->company->id);
        $this->insertLedger('2026-08-20', '4000000.00', '0.00', $this->company->id);
        $beforeTransactions = DB::table('transactions')->count();

        $row = app(SupplierDebtReportService::class)
            ->report($this->owner, '2026-08-15', '2026-08-31')
            ->first();

        $this->assertSame('0.00', $row->opening_debit);
        $this->assertSame('4000000.00', $row->opening_credit);
        $this->assertSame('4000000.00', $row->period_debit);
        $this->assertSame('0.00', $row->period_credit);
        $this->assertSame('0.00', $row->ending_debit);
        $this->assertSame('0.00', $row->ending_credit);
        $this->assertSame($beforeTransactions, DB::table('transactions')->count());
    }

    public function test_explicit_2027_date_filter_keeps_six_million_snapshot_out_of_empty_period(): void
    {
        $this->insertLedger('2026-08-12', '0.00', '66000000.00', $this->company->id);
        $this->insertLedger('2026-08-13', '60000000.00', '0.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2027);

        $request = Request::create('/admin/debts/supplier', 'GET', [
            'from_date' => '2027-01-01',
            'to_date' => '2027-02-28',
        ], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $request->setUserResolver(fn (): User => $this->owner);

        $response = app(DebtController::class)->supplier(
            $request,
            app(SupplierDebtReportService::class)
        );
        $rows = $response->getData(true);
        $snapshot = $this->snapshot(2027, $this->company->id);

        $this->assertSame(0, DB::table('transactions')
            ->whereBetween('transaction_date', ['2027-01-01', '2027-02-28'])
            ->count());
        $this->assertSame('0.00', (string) $snapshot->opening_debit);
        $this->assertSame('6000000.00', (string) $snapshot->opening_credit);
        $this->assertSame('2026-12-31', $snapshot->source_through_date->toDateString());
        $this->assertCount(1, $rows);
        $this->assertSame([
            'opening_debit' => '0.00',
            'opening_credit' => '6000000.00',
            'period_debit' => '0.00',
            'period_credit' => '0.00',
            'ending_debit' => '0.00',
            'ending_credit' => '6000000.00',
        ], array_intersect_key($rows[0], array_flip([
            'opening_debit',
            'opening_credit',
            'period_debit',
            'period_credit',
            'ending_debit',
            'ending_credit',
        ])));
    }

    public function test_reconcile_returns_zero_for_canonical_ledger(): void
    {
        $this->insertLedger('2026-01-01', '1000000.00', '3000000.00', $this->company->id);
        $this->service->buildOwnerYear($this->owner->id, 2027);

        $this->assertSame('0.00', $this->service->reconcileSnapshot($this->snapshot(2027, $this->company->id)));
    }

    public function test_exact_decimal_above_ten_billion_is_preserved(): void
    {
        $this->insertLedger('2026-01-01', '0.00', '9999999999.97', $this->company->id);
        $this->insertLedger('2026-01-02', '9999999999.96', '0.00', $this->company->id);

        $this->service->buildOwnerYear($this->owner->id, 2027);

        $this->assertSame('0.01', (string) $this->snapshot(2027, $this->company->id)->opening_credit);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('role_id')->default(1);
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('transaction_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 24, 2)->default(0);
            $table->decimal('credit_amount', 24, 2)->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
        Schema::create('supplier_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('opening_debit', 20, 2)->default(0);
            $table->decimal('opening_credit', 20, 2)->default(0);
            $table->date('source_through_date');
            $table->unsignedBigInteger('source_version')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'company_id', 'fiscal_year']);
        });
        Schema::create('supplier_debt_snapshot_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('ledger_version')->default(0);
            $table->unsignedSmallInteger('dirty_from_year')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'company_id']);
        });
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
    }

    private function createCompany(User $owner, string $name): Company
    {
        return Company::create([
            'user_id' => $owner->id,
            'name' => $name,
            'phone' => '0900000000',
            'status' => true,
        ]);
    }

    private function createAccount(string $code, string $name): int
    {
        return (int) DB::table('accounts')->insertGetId([
            'code' => $code,
            'name' => $name,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertLedger(
        string $date,
        string $debit,
        string $credit,
        int $companyId,
        string $status = 'completed',
        ?int $accountId = null,
        string $tableableType = Company::class,
        ?int $tableableId = null,
        ?int $ownerId = null
    ): int {
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $ownerId ?? $this->owner->id,
            'transaction_date' => $date,
            'description' => 'Supplier snapshot test',
            'type' => 'expense',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transaction_entries')->insert([
            'transaction_id' => $transactionId,
            'account_id' => $accountId ?? $this->payableAccountId,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'tableable_type' => $tableableType,
            'tableable_id' => $tableableId ?? $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $transactionId;
    }

    private function createEloquentLedger(
        string $date,
        string $debit,
        string $credit,
        int $companyId,
        string $status = 'completed',
        ?int $ownerId = null
    ): Transaction {
        $transaction = Transaction::create([
            'user_id' => $ownerId ?? $this->owner->id,
            'transaction_date' => $date,
            'description' => 'Supplier snapshot event test',
            'type' => 'expense',
            'status' => $status,
        ]);
        $transaction->entries()->create([
            'account_id' => $this->payableAccountId,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'tableable_type' => Company::class,
            'tableable_id' => $companyId,
        ]);

        return $transaction->fresh('entries');
    }

    private function snapshot(int $year, int $companyId): object
    {
        $snapshot = SupplierDebtYearlySnapshot::query()
            ->where('owner_id', $this->owner->id)
            ->where('company_id', $companyId)
            ->where('fiscal_year', $year)
            ->first();
        $this->assertNotNull($snapshot);

        return $snapshot;
    }

    private function state(): object
    {
        $state = DB::table('supplier_debt_snapshot_states')
            ->where('owner_id', $this->owner->id)
            ->where('company_id', $this->company->id)
            ->first();
        $this->assertNotNull($state);

        return $state;
    }
}
