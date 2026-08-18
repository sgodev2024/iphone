<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DebtController;
use App\Models\Company;
use App\Models\User;
use App\Services\SupplierDebtReportService;
use App\Support\DecimalAmount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Tests\TestCase;

class SupplierDebtReportTest extends TestCase
{
    private SupplierDebtReportService $service;

    private User $owner;

    private User $otherOwner;

    private Company $company;

    private int $payableAccountId;

    private int $cashAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->createAuthorizationTablesForTests();
        $this->service = app(SupplierDebtReportService::class);
        $this->owner = $this->createUser('owner@example.com');
        $this->otherOwner = $this->createUser('other@example.com');
        $this->company = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Company Alpha',
            'phone' => '0900000001',
            'status' => true,
        ]);
        $this->payableAccountId = DB::table('accounts')->insertGetId([
            'code' => '331',
            'name' => 'Payable',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cashAccountId = DB::table('accounts')->insertGetId([
            'code' => '111',
            'name' => 'Cash',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_canonical_purchase_and_multiple_payments_reconcile_to_zero(): void
    {
        $this->addLedgerEntry('2026-08-01', '0.00', '9999999999.97', $this->company->id);
        $this->addLedgerEntry('2026-08-01', '4000000000.00', '0.00', $this->company->id);
        $this->addLedgerEntry('2026-08-10', '2000000000.00', '0.00', $this->company->id);
        $this->addLedgerEntry('2026-08-20', '3999999999.97', '0.00', $this->company->id);

        $rows = $this->service->report($this->owner, '2026-08-01', '2026-08-20');
        $row = $rows->first();

        $this->assertCount(1, $rows);
        $this->assertSame('Company Alpha', $row->company_name);
        $this->assertSame('9999999999.97', $row->period_credit);
        $this->assertSame('9999999999.97', $row->period_debit);
        $this->assertSame('0.00', $row->ending_debit);
        $this->assertSame('0.00', $row->ending_credit);
    }

    public function test_cross_date_and_opening_balances_follow_payable_nature(): void
    {
        $this->addLedgerEntry('2026-08-01', 0, 10000000, $this->company->id);
        $this->addLedgerEntry('2026-08-01', 4000000, 0, $this->company->id);
        $this->addLedgerEntry('2026-08-10', 2000000, 0, $this->company->id);
        $this->addLedgerEntry('2026-08-20', 4000000, 0, $this->company->id);

        $atTen = $this->service->report($this->owner, '2026-08-01', '2026-08-10')->first();
        $afterTen = $this->service->report($this->owner, '2026-08-15', '2026-08-31')->first();

        $this->assertSame('4000000.00', $atTen->ending_credit);
        $this->assertSame('4000000.00', $afterTen->opening_credit);
        $this->assertSame('4000000.00', $afterTen->period_debit);
        $this->assertSame('0.00', $afterTen->ending_credit);
    }

    public function test_multiple_imports_same_company_are_aggregated_in_one_payable_balance(): void
    {
        $this->addLedgerEntry('2026-08-01', '0.00', '4000000.00', $this->company->id);
        $this->addLedgerEntry('2026-08-02', '0.00', '7000000.00', $this->company->id);
        $this->addLedgerEntry('2026-08-03', '3000000.00', '0.00', $this->company->id);

        $row = $this->service->report($this->owner, '2026-08-01', '2026-08-31')->first();

        $this->assertSame('11000000.00', $row->period_credit);
        $this->assertSame('3000000.00', $row->period_debit);
        $this->assertSame('8000000.00', $row->ending_credit);
    }

    public function test_debit_balance_is_split_as_supplier_advance(): void
    {
        $this->addLedgerEntry('2026-08-01', 0, 10000000, $this->company->id);
        $this->addLedgerEntry('2026-08-02', 12000000, 0, $this->company->id);

        $row = $this->service->report($this->owner, '2026-08-01', '2026-08-31')->first();

        $this->assertSame('2000000.00', $row->ending_debit);
        $this->assertSame('0.00', $row->ending_credit);
    }

    public function test_report_is_company_based_owner_scoped_and_without_supplier_representative(): void
    {
        $otherCompany = Company::create([
            'user_id' => $this->otherOwner->id,
            'name' => 'Company Beta',
            'phone' => '0900000002',
            'status' => true,
        ]);
        $this->addLedgerEntry('2026-08-01', 0, 5000000, $this->company->id);
        $this->addLedgerEntry('2026-08-01', 0, 8000000, $otherCompany->id);

        $ownerRows = $this->service->report($this->owner, '2026-08-01', '2026-08-31');
        $filteredRows = $this->service->report($this->owner, '2026-08-01', '2026-08-31', 'Beta');

        $this->assertCount(1, $ownerRows);
        $this->assertSame('Company Alpha', $ownerRows->first()->company_name);
        $this->assertCount(0, $filteredRows);
    }

    public function test_only_completed_tk331_company_entries_are_reported(): void
    {
        $this->addLedgerEntry('2026-08-01', 0, 5000000, $this->company->id, 'pending');
        $this->addLedgerEntry('2026-08-02', 0, 6000000, $this->company->id, 'failed');
        $this->addLedgerEntry('2026-08-03', 0, 7000000, $this->company->id, 'completed', 'App\\Models\\Supplier', 999);
        $this->addLedgerEntry('2026-08-04', 0, 8000000, $this->company->id, 'completed');
        $this->addLedgerEntry('2026-08-05', 0, 9000000, $this->company->id, 'completed', Company::class, $this->company->id, 'income', $this->cashAccountId);

        $row = $this->service->report($this->owner, '2026-08-01', '2026-08-31')->first();

        $this->assertSame('8000000.00', $row->period_credit);
    }

    public function test_zero_balance_companies_are_hidden_but_period_activity_is_kept(): void
    {
        $zeroCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Company Zero',
            'status' => true,
        ]);
        $activityCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Company Activity',
            'status' => true,
        ]);
        $this->addLedgerEntry('2026-07-01', 1000000, 0, $zeroCompany->id);
        $this->addLedgerEntry('2026-07-02', 0, 1000000, $zeroCompany->id);
        $this->addLedgerEntry('2026-08-01', 1000000, 1000000, $activityCompany->id);

        $rows = $this->service->report($this->owner, '2026-08-01', '2026-08-31');

        $this->assertCount(1, $rows);
        $this->assertSame('Company Activity', $rows->first()->company_name);
    }

    public function test_report_uses_one_bulk_aggregate_query_after_account_resolution(): void
    {
        $this->addLedgerEntry('2026-08-01', 0, 1000000, $this->company->id);
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->service->report($this->owner, '2026-08-01', '2026-08-31');

        $this->assertSame(2, $queryCount);
    }

    public function test_report_reconciles_exactly_to_direct_tk331_ledger_sum(): void
    {
        $this->addLedgerEntry('2026-08-01', '0.00', '9999999999.45', $this->company->id);
        $this->addLedgerEntry('2026-08-02', '9999999999.44', '0.00', $this->company->id);

        $row = $this->service->report($this->owner, '2026-08-01', '2026-08-31')->first();
        $directCredit = '0.00';
        $directDebit = '0.00';
        foreach (DB::table('transaction_entries')->where('account_id', $this->payableAccountId)->get() as $entry) {
            $directCredit = DecimalAmount::add($directCredit, (string) $entry->credit_amount);
            $directDebit = DecimalAmount::add($directDebit, (string) $entry->debit_amount);
        }
        $directNet = DecimalAmount::subtract($directCredit, $directDebit);
        $reportNet = DecimalAmount::subtract($row->ending_credit, $row->ending_debit);

        $this->assertSame($directNet, $reportNet);
        $this->assertSame('0.01', $reportNet);
    }

    public function test_missing_or_inactive_331_fails_clearly(): void
    {
        DB::table('accounts')->where('id', $this->payableAccountId)->update(['status' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('331');

        $this->service->report($this->owner, '2026-08-01', '2026-08-31');
    }

    public function test_date_filter_regression_matrix_respects_every_boundary(): void
    {
        $this->addLedgerEntry('2025-12-31', '0.00', '1.00', $this->company->id);
        $this->addLedgerEntry('2026-01-01', '0.00', '2.00', $this->company->id);
        $this->addLedgerEntry('2026-01-31', '0.50', '0.00', $this->company->id);
        $this->addLedgerEntry('2026-06-14', '0.00', '3.00', $this->company->id);
        $this->addLedgerEntry('2026-06-15', '0.00', '4.00', $this->company->id);
        $this->addLedgerEntry('2026-12-31', '1.00', '0.00', $this->company->id);
        $this->addLedgerEntry('2027-01-15', '0.00', '5.00', $this->company->id);
        $this->addLedgerEntry('2027-01-16', '0.00', '99.00', $this->company->id);

        $cases = [
            ['2026-01-01', '2026-01-31', ['1.00', '0.50', '2.00', '2.50']],
            ['2026-06-15', '2026-12-31', ['5.50', '1.00', '4.00', '8.50']],
            ['2026-12-15', '2027-01-15', ['9.50', '1.00', '5.00', '13.50']],
            ['2028-01-01', '2028-01-31', ['112.50', '0.00', '0.00', '112.50']],
            ['2026-02-01', '2026-02-28', ['2.50', '0.00', '0.00', '2.50']],
            ['2026-06-15', '2026-06-15', ['5.50', '0.00', '4.00', '9.50']],
            ['2027-01-01', '2027-01-15', ['8.50', '0.00', '5.00', '13.50']],
        ];

        foreach ($cases as [$fromDate, $toDate, $expected]) {
            $row = $this->service->report($this->owner, $fromDate, $toDate)->first();

            $this->assertNotNull($row, "Missing report row for {$fromDate} to {$toDate}.");
            $this->assertSame($expected, [
                $row->opening_credit,
                $row->period_debit,
                $row->period_credit,
                $row->ending_credit,
            ], "Wrong date buckets for {$fromDate} to {$toDate}.");
        }
    }

    public function test_supplier_footer_sums_filtered_rows_without_cross_entity_netting(): void
    {
        $debitCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Footer Supplier Debit',
            'status' => true,
        ]);
        $creditCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Footer Supplier Credit',
            'status' => true,
        ]);
        $excludedCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Excluded Supplier',
            'status' => true,
        ]);

        $this->addLedgerEntry('2026-08-15', '10000000.00', '0.00', $debitCompany->id);
        $this->addLedgerEntry('2026-08-15', '0.00', '4000000.00', $creditCompany->id);
        $this->addLedgerEntry('2026-08-15', '90000000.00', '0.00', $excludedCompany->id);

        $request = Request::create('/admin/debts/supplier', 'GET', [
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-20',
            'name' => 'Footer Supplier',
        ]);
        $request->setUserResolver(fn (): User => $this->owner);
        $this->actingAs($this->owner);
        $this->disableAdminLayoutComposers();

        $view = app(DebtController::class)->supplier($request, $this->service);
        $footer = Str::between($view->render(), '<tfoot>', '</tfoot>');

        $this->assertStringContainsString('TỔNG CỘNG', $footer);
        $this->assertStringContainsString('10.000.000', $footer);
        $this->assertStringContainsString('4.000.000', $footer);
        $this->assertStringNotContainsString('90.000.000', $footer);
        $this->assertSame(7, substr_count($footer, '<td'));
        $this->assertSame(1, substr_count($footer, '<th'));
    }

    public function test_supplier_footer_renders_six_zero_totals_for_empty_result(): void
    {
        $request = Request::create('/admin/debts/supplier', 'GET', [
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-20',
            'name' => 'No matching supplier',
        ]);
        $request->setUserResolver(fn (): User => $this->owner);
        $this->actingAs($this->owner);
        $this->disableAdminLayoutComposers();

        $view = app(DebtController::class)->supplier($request, $this->service);
        $footer = Str::between($view->render(), '<tfoot>', '</tfoot>');

        $this->assertStringContainsString('TỔNG CỘNG', $footer);
        $this->assertSame(6, substr_count($footer, '<td class="text-end money-cell">0</td>'));
        $this->assertSame(7, substr_count($footer, '<td'));
        $this->assertSame(1, substr_count($footer, '<th'));
    }

    public function test_supplier_company_name_links_to_company_filtered_import_list(): void
    {
        $this->addLedgerEntry('2026-08-15', '0.00', '1000000.00', $this->company->id);

        $request = Request::create('/admin/debts/supplier', 'GET', [
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-20',
        ]);
        $request->setUserResolver(fn (): User => $this->owner);
        $this->actingAs($this->owner);
        $this->disableAdminLayoutComposers();

        $view = app(DebtController::class)->supplier($request, $this->service);
        $html = $view->render();
        $companyUrl = route('admin.importproduct.index', [
            'company_id' => $this->company->id,
            'outstanding_only' => 1,
        ]);

        $this->assertStringContainsString('href="'.$companyUrl.'"', html_entity_decode($html));
        $this->assertStringContainsString('supplier-name text-decoration-none', $html);
        $this->assertStringContainsString('Company Alpha', $html);
        $this->assertStringContainsString('SĐT: 0900000001', $html);
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
            $table->unsignedBigInteger('status')->default(1);
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

    private function disableAdminLayoutComposers(): void
    {
        Event::forget('composing: *');
        Event::forget('composing: admin.layout.header');
        View::share('config', null);
        View::share('notifications', collect());
        auth()->user()?->setRelation('userInfo', null);
    }

    private function addLedgerEntry(
        string $date,
        string|int $debit,
        string|int $credit,
        int $companyId,
        string $status = 'completed',
        string $tableableType = Company::class,
        ?int $tableableId = null,
        string $type = 'expense',
        ?int $accountId = null
    ): void {
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $this->owner->id,
            'transaction_date' => $date,
            'description' => 'Supplier report test',
            'type' => $type,
            'document_type' => 'test',
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
    }
}
