<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerDebtReportTest extends TestCase
{
    private const FROM_DATE = '10/08/2026';

    private const TO_DATE = '20/08/2026';

    private int $receivableAccountId;

    private int $cashAccountId;

    private int $revenueAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['transaction_entries', 'transactions', 'clients', 'accounts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('level')->default(1);
            $table->unsignedBigInteger('status')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_default')->default(false);
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

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
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

        $this->receivableAccountId = $this->createAccount('131', 'Phải thu khách hàng');
        $this->cashAccountId = $this->createAccount('111', 'Tiền mặt');
        $this->revenueAccountId = $this->createAccount('5111', 'Doanh thu bán hàng');
    }

    public function test_case_1_nets_opening_debit_and_calculates_ending_debit(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case1');
        $clientId = $this->createClient($owner, 'Case 1');
        $this->addLedgerEntry($clientId, '2026-08-09', 100, 0);
        $this->addLedgerEntry($clientId, '2026-08-09', 0, 70);
        $this->addLedgerEntry($clientId, '2026-08-10', 50, 0);
        $this->addLedgerEntry($clientId, '2026-08-20', 0, 20);

        $this->assertReport($this->report($staff), 'Case 1', [30, 0, 50, 20, 60, 0]);
    }

    public function test_case_2_splits_opening_and_ending_credit(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case2');
        $clientId = $this->createClient($owner, 'Case 2');
        $this->addLedgerEntry($clientId, '2026-08-09', 0, 100);
        $this->addLedgerEntry($clientId, '2026-08-10', 20, 0);
        $this->addLedgerEntry($clientId, '2026-08-20', 0, 70);

        $this->assertReport($this->report($staff), 'Case 2', [0, 100, 20, 70, 0, 150]);
    }

    public function test_case_3_ending_balance_can_be_zero(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case3');
        $clientId = $this->createClient($owner, 'Case 3');
        $this->addLedgerEntry($clientId, '2026-08-09', 100, 0);
        $this->addLedgerEntry($clientId, '2026-08-11', 50, 0);
        $this->addLedgerEntry($clientId, '2026-08-12', 0, 150);

        $this->assertReport($this->report($staff), 'Case 3', [100, 0, 50, 150, 0, 0]);
    }

    public function test_case_4_carries_opening_balance_without_period_activity(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case4');
        $clientId = $this->createClient($owner, 'Case 4');
        $this->addLedgerEntry($clientId, '2026-08-09', 80, 0);

        $this->assertReport($this->report($staff), 'Case 4', [80, 0, 0, 0, 80, 0]);
    }

    public function test_case_5_debt_sale_increases_receivable(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case5');
        $clientId = $this->createClient($owner, 'Case 5');
        $this->addTransaction('2026-08-15', [
            $this->entry($clientId, $this->receivableAccountId, 100, 0),
            $this->entry(null, $this->revenueAccountId, 0, 100),
        ]);

        $this->assertReport($this->report($staff), 'Case 5', [0, 0, 100, 0, 100, 0]);
    }

    public function test_case_6_full_payment_clears_receivable(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case6');
        $clientId = $this->createClient($owner, 'Case 6');
        $this->addTransaction('2026-08-15', [
            $this->entry($clientId, $this->receivableAccountId, 100, 0),
            $this->entry(null, $this->revenueAccountId, 0, 100),
        ]);
        $this->addTransaction('2026-08-16', [
            $this->entry(null, $this->cashAccountId, 100, 0),
            $this->entry($clientId, $this->receivableAccountId, 0, 100),
        ]);

        $this->assertReport($this->report($staff), 'Case 6', [0, 0, 100, 100, 0, 0]);
    }

    public function test_case_7_ignores_entries_from_other_accounts(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case7');
        $clientId = $this->createClient($owner, 'Case 7');
        $this->addLedgerEntry($clientId, '2026-08-15', 25, 0);
        $this->addLedgerEntry($clientId, '2026-08-15', 400, 0, $this->cashAccountId);

        $this->assertReport($this->report($staff), 'Case 7', [0, 0, 25, 0, 25, 0]);
    }

    public function test_case_8_scopes_clients_to_the_logged_users_owner_without_n_plus_one(): void
    {
        [$ownerA, $staffA] = $this->createOwnerAndStaff('owner-a');
        [$ownerB] = $this->createOwnerAndStaff('owner-b');
        $clientA = $this->createClient($ownerA, 'Owner A Client');
        $clientB = $this->createClient($ownerB, 'Owner B Client');
        $this->addLedgerEntry($clientA, '2026-08-15', 100, 0);
        $this->addLedgerEntry($clientB, '2026-08-15', 200, 0);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->report($staffA, 'Owner');
        $ledgerQueryCount = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'transaction_entries'))
            ->count();
        DB::disableQueryLog();

        $response->assertOk();
        $this->assertSame(['Owner A Client'], collect($response->json())->pluck('client_name')->all());
        $this->assertSame(1, $ledgerQueryCount);
    }

    public function test_case_9_includes_both_boundary_dates_and_excludes_after_end(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case9');
        $clientId = $this->createClient($owner, 'Case 9');
        $this->addLedgerEntry($clientId, '2026-08-09', 10, 0);
        $this->addLedgerEntry($clientId, '2026-08-10', 20, 0);
        $this->addLedgerEntry($clientId, '2026-08-20', 0, 5);
        $this->addLedgerEntry($clientId, '2026-08-21', 100, 0);

        $this->assertReport($this->report($staff), 'Case 9', [10, 0, 20, 5, 25, 0]);
    }

    public function test_case_10_keeps_soft_deleted_clients_with_ledger_activity(): void
    {
        [$owner, $staff] = $this->createOwnerAndStaff('case10');
        $clientId = $this->createClient($owner, 'Deleted Client');
        $this->addLedgerEntry($clientId, '2026-08-15', 100, 0);
        DB::table('clients')->where('id', $clientId)->update(['deleted_at' => now()]);

        $this->assertReport($this->report($staff), 'Deleted Client', [0, 0, 100, 0, 100, 0]);
    }

    public function test_report_fails_clearly_when_active_account_131_is_missing(): void
    {
        [, $staff] = $this->createOwnerAndStaff('missing-131');
        DB::table('accounts')->where('id', $this->receivableAccountId)->update(['status' => 0]);

        $this->report($staff)
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'Không tìm thấy tài khoản phải thu khách hàng (131) đang hoạt động.',
            ]);
    }

    private function report(User $user, ?string $name = null)
    {
        $query = http_build_query(array_filter([
            'date_range' => self::FROM_DATE.' - '.self::TO_DATE,
            'name' => $name,
        ], fn ($value) => $value !== null));

        return $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/admin/debts/customer?'.$query);
    }

    private function assertReport($response, string $clientName, array $amounts): void
    {
        $response->assertOk();
        $report = collect($response->json())->firstWhere('client_name', $clientName);
        $this->assertNotNull($report, "Missing debt report for {$clientName}.");
        $this->assertEquals($amounts, [
            $report['opening_debit'],
            $report['opening_credit'],
            $report['period_debit'],
            $report['period_credit'],
            $report['ending_debit'],
            $report['ending_credit'],
        ]);
    }

    private function createOwnerAndStaff(string $key): array
    {
        $owner = User::create([
            'name' => "Owner {$key}",
            'email' => "owner-{$key}@example.com",
            'password' => 'password',
            'role_id' => 2,
        ]);
        $staff = User::create([
            'manager_id' => $owner->id,
            'name' => "Staff {$key}",
            'email' => "staff-{$key}@example.com",
            'password' => 'password',
            'role_id' => 3,
        ]);

        return [$owner, $staff];
    }

    private function createClient(User $owner, string $name): int
    {
        $sequence = DB::table('clients')->count() + 1;

        return DB::table('clients')->insertGetId([
            'user_id' => $owner->id,
            'code' => 'KH'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'phone' => '09'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccount(string $code, string $name): int
    {
        return DB::table('accounts')->insertGetId([
            'code' => $code,
            'name' => $name,
            'level' => 1,
            'status' => 1,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addLedgerEntry(
        int $clientId,
        string $date,
        float $debit,
        float $credit,
        ?int $accountId = null
    ): void {
        $this->addTransaction($date, [
            $this->entry($clientId, $accountId ?? $this->receivableAccountId, $debit, $credit),
        ]);
    }

    private function addTransaction(string $date, array $entries): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'transaction_date' => $date,
            'description' => 'Debt report test',
            'type' => 'test',
            'document_type' => 'order',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($entries as $entry) {
            DB::table('transaction_entries')->insert(array_merge($entry, [
                'transaction_id' => $transactionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function entry(?int $clientId, int $accountId, float $debit, float $credit): array
    {
        return [
            'account_id' => $accountId,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'tableable_type' => $clientId ? Client::class : null,
            'tableable_id' => $clientId,
            'note' => 'Debt report test',
        ];
    }
}
