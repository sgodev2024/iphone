<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\ImportCoupon;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SupplierPaymentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class SupplierPaymentServiceTest extends TestCase
{
    private SupplierPaymentService $service;

    private User $owner;

    private User $otherOwner;

    private Company $company;

    private Account $cash;

    private Account $bankParent;

    private Account $activeBank;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->service = app(SupplierPaymentService::class);
        $this->owner = $this->createUser('owner@example.com');
        $this->otherOwner = $this->createUser('other@example.com');
        $this->company = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Company Phase 6C',
            'status' => true,
        ]);
        $this->createAccounts();
    }

    public function test_mixed_multi_payments_fully_settle_one_import_without_recreating_purchase(): void
    {
        $import = $this->createCanonicalImport(100000, 40000, 'cash', '2026-08-01');

        $bank = $this->service->pay($this->owner, $this->payload(
            $import,
            20000,
            'bank_transfer',
            '11111111-1111-4111-8111-111111111111',
            '2026-08-10',
            $this->activeBank->id
        ));
        $cash = $this->service->pay($this->owner, $this->payload(
            $import,
            40000,
            'cash',
            '22222222-2222-4222-8222-222222222222',
            '2026-08-12'
        ));

        $import->refresh();

        $this->assertFalse($bank['replayed']);
        $this->assertFalse($cash['replayed']);
        $this->assertSame(100000, (int) $import->paid_amount);
        $this->assertSame(0, (int) $import->debt_amount);
        $this->assertSame(ImportCoupon::PAYMENT_STATUS_PAID, $import->payment_status);
        $this->assertSame('cash', $import->payment_method);
        $this->assertSame(1, $this->purchaseCount($import));
        $this->assertSame(3, $this->paymentCount($import));
        $this->assertEntry($bank['transaction'], '331', 20000, 0, Company::class, $this->company->id);
        $this->assertEntry($bank['transaction'], '112MB', 0, 20000);
        $this->assertEntry($cash['transaction'], '331', 40000, 0, Company::class, $this->company->id);
        $this->assertEntry($cash['transaction'], '111', 0, 40000);
        $this->assertSame('2026-08-10', $bank['transaction']->transaction_date->toDateString());
        $this->assertAllTransactionsBalancedAndCompleted($import);
    }

    public function test_same_payment_method_can_be_used_repeatedly(): void
    {
        $import = $this->createCanonicalImport(100000, 0, 'debt');

        foreach ([20000, 30000, 50000] as $index => $amount) {
            $this->service->pay($this->owner, $this->payload(
                $import,
                $amount,
                'cash',
                sprintf('30000000-0000-4000-8000-%012d', $index + 1)
            ));
        }

        $this->assertSame(1, $this->purchaseCount($import));
        $this->assertSame(3, $this->paymentCount($import));
        $this->assertSame(100000, (int) $import->fresh()->paid_amount);
        $this->assertSame(0, (int) $import->fresh()->debt_amount);
    }

    public function test_ledger_recalculation_repairs_stale_import_aggregates(): void
    {
        $import = $this->createCanonicalImport(100000, 40000, 'cash');
        $import->forceFill([
            'paid_amount' => 1,
            'debt_amount' => 99999,
            'payment_status' => ImportCoupon::PAYMENT_STATUS_DEBT,
        ])->save();

        $this->service->pay($this->owner, $this->payload(
            $import,
            20000,
            'cash',
            '44444444-4444-4444-8444-444444444444'
        ));

        $this->assertSame(60000, (int) $import->fresh()->paid_amount);
        $this->assertSame(40000, (int) $import->fresh()->debt_amount);
        $this->assertSame(ImportCoupon::PAYMENT_STATUS_PARTIAL, $import->fresh()->payment_status);
    }

    public function test_zero_negative_overpayment_and_payment_after_full_settlement_are_rejected(): void
    {
        $import = $this->createCanonicalImport(100000, 60000, 'cash');
        $beforeTransactions = Transaction::count();

        foreach ([0, -1, 40001] as $index => $amount) {
            $this->expectValidation(function () use ($import, $amount, $index): void {
                $this->service->pay($this->owner, $this->payload(
                    $import,
                    $amount,
                    'cash',
                    sprintf('50000000-0000-4000-8000-%012d', $index + 1)
                ));
            });
        }

        $this->assertSame($beforeTransactions, Transaction::count());
        $this->assertSame(60000, (int) $import->fresh()->paid_amount);

        $this->service->pay($this->owner, $this->payload(
            $import,
            40000,
            'cash',
            '55555555-5555-4555-8555-555555555555'
        ));
        $afterSettlement = Transaction::count();

        $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
            $import,
            1,
            'cash',
            '56666666-6666-4666-8666-666666666666'
        )));

        $this->assertSame($afterSettlement, Transaction::count());
    }

    public function test_idempotency_replays_same_payload_and_conflicts_on_different_payload(): void
    {
        $import = $this->createCanonicalImport(100000);
        $key = '66666666-6666-4666-8666-666666666666';
        $payload = $this->payload($import, 20000, 'cash', $key);
        $beforeEntries = DB::table('transaction_entries')->count();

        $first = $this->service->pay($this->owner, $payload);
        $replay = $this->service->pay($this->owner, $payload);

        $this->assertFalse($first['replayed']);
        $this->assertTrue($replay['replayed']);
        $this->assertSame($first['transaction']->id, $replay['transaction']->id);
        $this->assertSame($beforeEntries + 2, DB::table('transaction_entries')->count());
        $this->assertSame(1, Transaction::where('idempotency_key', $key)->count());

        try {
            $this->service->pay($this->owner, $this->payload($import, 30000, 'cash', $key));
            $this->fail('The same idempotency key with a different payload must conflict.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertSame(1, Transaction::where('idempotency_key', $key)->count());
    }

    public function test_wrong_owner_cannot_pay_an_import(): void
    {
        $import = $this->createCanonicalImport(100000);
        $before = Transaction::count();

        try {
            $this->service->pay($this->otherOwner, $this->payload(
                $import,
                10000,
                'cash',
                '77777777-7777-4777-8777-777777777777'
            ));
            $this->fail('A different owner must not be able to pay this import.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }

        $this->assertSame($before, Transaction::count());
    }

    public function test_outstanding_imports_are_company_scoped_and_use_the_canonical_ledger(): void
    {
        $import = $this->createCanonicalImport(100000, 40000, 'cash');

        $rows = $this->service->outstandingImports($this->owner, $this->company->id);

        $this->assertSame([
            [
                'id' => $import->id,
                'code' => $import->coupon_code,
                'purchase_date' => '2026-08-01',
                'total' => 100000,
                'paid' => 40000,
                'remaining' => 60000,
            ],
        ], $rows);

        $this->expectException(ModelNotFoundException::class);
        $this->service->outstandingImports($this->otherOwner, $this->company->id);
    }

    public function test_company_and_owner_fields_from_payload_are_not_authoritative(): void
    {
        $import = $this->createCanonicalImport(100000);
        $otherCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Forged Company',
            'status' => true,
        ]);
        $payload = $this->payload(
            $import,
            10000,
            'cash',
            '78888888-8888-4888-8888-888888888888'
        ) + [
            'company_id' => $otherCompany->id,
            'supplier_id' => 999999,
            'owner_id' => $this->otherOwner->id,
            'remaining' => 999999999,
            'total_paid' => 999999999,
        ];

        $result = $this->service->pay($this->owner, $payload);

        $this->assertEntry(
            $result['transaction'],
            '331',
            10000,
            0,
            Company::class,
            $this->company->id
        );
        $this->assertDatabaseMissing('transaction_entries', [
            'transaction_id' => $result['transaction']->id,
            'tableable_type' => Company::class,
            'tableable_id' => $otherCompany->id,
        ]);
    }

    public function test_service_defensively_rejects_an_unsupported_payment_method(): void
    {
        $import = $this->createCanonicalImport(100000);
        $before = Transaction::count();

        $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
            $import,
            10000,
            'debt',
            '79999999-9999-4999-8999-999999999999'
        )));

        $this->assertSame($before, Transaction::count());
    }

    public function test_invalid_bank_accounts_are_rejected_without_ledger_mutation(): void
    {
        $import = $this->createCanonicalImport(100000);
        $inactive = Account::create([
            'code' => '112OFF',
            'name' => 'Inactive bank',
            'parent_id' => $this->bankParent->id,
            'status' => false,
            'is_default' => false,
        ]);
        $before = Transaction::count();
        $invalidIds = [999999, $inactive->id, $this->bankParent->id, $this->cash->id];

        foreach ($invalidIds as $index => $accountId) {
            $this->expectValidation(function () use ($import, $accountId, $index): void {
                $this->service->pay($this->owner, $this->payload(
                    $import,
                    10000,
                    'bank_transfer',
                    sprintf('80000000-0000-4000-8000-%012d', $index + 1),
                    '2026-08-12',
                    $accountId
                ));
            });
        }

        $this->assertSame($before, Transaction::count());
    }

    public function test_payment_date_must_be_between_purchase_date_and_today(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 12, 12, 0, 0, 'Asia/Ho_Chi_Minh'));

        try {
            $import = $this->createCanonicalImport(100000, 0, 'debt', '2026-08-01');
            $before = Transaction::count();
            $beforePurchase = Carbon::parse('2026-08-01')->subDay()->toDateString();
            $tomorrow = now()->addDay()->toDateString();
            $validDate = now()->subDays(2)->toDateString();

            $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
                $import,
                10000,
                'cash',
                '91111111-1111-4111-8111-111111111111',
                $beforePurchase
            )));
            $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
                $import,
                10000,
                'cash',
                '92222222-2222-4222-8222-222222222222',
                $tomorrow
            )));

            $result = $this->service->pay($this->owner, $this->payload(
                $import,
                10000,
                'cash',
                '93333333-3333-4333-8333-333333333333',
                $validDate
            ));

            $this->assertSame($before + 1, Transaction::count());
            $this->assertSame($validDate, $result['transaction']->transaction_date->toDateString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_missing_duplicate_mismatched_or_wrong_company_purchase_blocks_payment(): void
    {
        $missing = $this->createImportCoupon(100000);
        $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
            $missing,
            10000,
            'cash',
            'a1111111-1111-4111-8111-111111111111'
        )));

        $duplicate = $this->createCanonicalImport(100000);
        $this->createPurchase($duplicate, 100000, $this->company->id);
        $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
            $duplicate,
            10000,
            'cash',
            'a2222222-2222-4222-8222-222222222222'
        )));

        $mismatch = $this->createImportCoupon(100000);
        $this->createPurchase($mismatch, 90000, $this->company->id);
        $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
            $mismatch,
            10000,
            'cash',
            'a3333333-3333-4333-8333-333333333333'
        )));

        $otherCompany = Company::create([
            'user_id' => $this->owner->id,
            'name' => 'Wrong Company',
            'status' => true,
        ]);
        $wrongCompany = $this->createImportCoupon(100000);
        $this->createPurchase($wrongCompany, 100000, $otherCompany->id);
        $this->expectValidation(fn () => $this->service->pay($this->owner, $this->payload(
            $wrongCompany,
            10000,
            'cash',
            'a4444444-4444-4444-8444-444444444444'
        )));
    }

    public function test_payments_are_isolated_per_import_even_for_the_same_company(): void
    {
        $first = $this->createCanonicalImport(40000);
        $second = $this->createCanonicalImport(70000);

        $this->service->pay($this->owner, $this->payload(
            $first,
            30000,
            'cash',
            'b1111111-1111-4111-8111-111111111111'
        ));

        $this->assertSame(10000, $this->service->summary($this->owner, $first->id)['remaining']);
        $this->assertSame(70000, $this->service->summary($this->owner, $second->id)['remaining']);
        $this->assertSame(0, $this->paymentCount($second));
    }

    public function test_second_administrator_can_pay_any_branch_using_import_as_owner_and_branch_source(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('roles')->insert(['id' => 1, 'name' => 'administrator']);
        Schema::table('users', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        Schema::table('companies', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });
        Schema::table('import_coupon', fn (Blueprint $table) => $table->unsignedBigInteger('storage_id')->nullable());
        Schema::table('transactions', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());

        $storageId = DB::table('storages')->insertGetId([
            'user_id' => $this->owner->id,
            'branch_id' => 101,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->company->update(['branch_id' => 101]);
        $companyB = Company::create([
            'user_id' => $this->otherOwner->id,
            'branch_id' => 202,
            'name' => 'Company Branch B',
            'status' => true,
        ]);
        $companyRequest = \Illuminate\Http\Request::create(
            '/supplier-companies',
            'GET',
            ['keyword' => 'Company']
        );
        $companyRequest->setUserResolver(fn () => $this->otherOwner);
        $companyIds = collect(app(
            \App\Http\Controllers\Admin\SupplierPaymentController::class
        )->companies($companyRequest)->getData(true))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$this->company->id, $companyB->id], $companyIds);

        $import = $this->createCanonicalImport(100000);
        $import->update(['storage_id' => $storageId]);
        Transaction::query()
            ->where('reference_number', 'IMP-'.$import->id)
            ->update(['branch_id' => 101]);

        $this->assertSame(100000, $this->service->summary($this->otherOwner, $import->id)['remaining']);
        $this->assertSame($import->id, $this->service->outstandingImports(
            $this->otherOwner,
            $this->company->id
        )[0]['id']);

        $result = $this->service->pay($this->otherOwner, $this->payload(
            $import,
            25000,
            'cash',
            'fa111111-1111-4111-8111-111111111111'
        ));

        $this->assertSame($this->owner->id, $result['transaction']->user_id);
        $this->assertSame(101, $result['transaction']->branch_id);
        $this->assertSame($this->otherOwner->id, $result['transaction']->created_by);
        $this->assertSame(75000, $result['summary']['remaining']);
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
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('import_coupon', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('companies_id');
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('payment_ncc')->default(0);
            $table->string('payment_method')->default('debt');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_status')->default('debt');
            $table->string('coupon_code')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default(Transaction::STATUS_PENDING);
            $table->char('idempotency_key', 36)->nullable();
            $table->char('idempotency_hash', 64)->nullable();
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
    }

    private function createAccounts(): void
    {
        $this->cash = Account::create([
            'code' => '111', 'name' => 'Cash', 'status' => true, 'is_default' => true,
        ]);
        $this->bankParent = Account::create([
            'code' => '112', 'name' => 'Bank', 'status' => true, 'is_default' => true,
        ]);
        $this->activeBank = Account::create([
            'code' => '112MB',
            'name' => 'MBBank',
            'parent_id' => $this->bankParent->id,
            'status' => true,
            'is_default' => false,
        ]);
        Account::create(['code' => '156', 'name' => 'Goods', 'status' => true, 'is_default' => true]);
        Account::create(['code' => '331', 'name' => 'Payable', 'status' => true, 'is_default' => true]);
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

    private function createCanonicalImport(
        int $total,
        int $initialPaid = 0,
        string $method = 'debt',
        string $date = '2026-08-01'
    ): ImportCoupon {
        $import = $this->createImportCoupon($total, $initialPaid, $method);
        $this->createPurchase($import, $total, $this->company->id, $date);

        if ($initialPaid > 0) {
            $this->createInitialPayment($import, $initialPaid, $method, $date);
        }

        return $import;
    }

    private function createImportCoupon(
        int $total,
        int $initialPaid = 0,
        string $method = 'debt'
    ): ImportCoupon {
        return ImportCoupon::create([
            'user_id' => $this->owner->id,
            'companies_id' => $this->company->id,
            'total' => $total,
            'payment_ncc' => $initialPaid,
            'payment_method' => $method,
            'paid_amount' => $initialPaid,
            'debt_amount' => $total - $initialPaid,
            'payment_status' => $initialPaid === 0
                ? ImportCoupon::PAYMENT_STATUS_DEBT
                : ($initialPaid === $total ? ImportCoupon::PAYMENT_STATUS_PAID : ImportCoupon::PAYMENT_STATUS_PARTIAL),
        ]);
    }

    private function createPurchase(
        ImportCoupon $import,
        int $amount,
        int $companyId,
        string $date = '2026-08-01'
    ): Transaction {
        $transaction = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => $date,
            'description' => 'Purchase',
            'type' => 'expense',
            'document_type' => 'import',
            'reference_number' => 'IMP-'.$import->id,
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $transaction->entries()->create([
            'account_id' => Account::where('code', '156')->value('id'),
            'debit_amount' => $amount,
            'credit_amount' => 0,
        ]);
        $transaction->entries()->create([
            'account_id' => Account::where('code', '331')->value('id'),
            'debit_amount' => 0,
            'credit_amount' => $amount,
            'tableable_type' => Company::class,
            'tableable_id' => $companyId,
        ]);

        return $transaction;
    }

    private function createInitialPayment(
        ImportCoupon $import,
        int $amount,
        string $method,
        string $date
    ): Transaction {
        $transaction = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => $date,
            'description' => 'Initial payment',
            'type' => 'expense',
            'document_type' => 'import_payment',
            'reference_number' => 'IMP-'.$import->id.'-PAY-INITIAL',
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $transaction->entries()->create([
            'account_id' => Account::where('code', '331')->value('id'),
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'tableable_type' => Company::class,
            'tableable_id' => $this->company->id,
        ]);
        $transaction->entries()->create([
            'account_id' => $method === 'bank_transfer' ? $this->activeBank->id : $this->cash->id,
            'debit_amount' => 0,
            'credit_amount' => $amount,
        ]);

        return $transaction;
    }

    private function payload(
        ImportCoupon $import,
        int $amount,
        string $method,
        string $key,
        string $date = '2026-08-12',
        ?int $bankAccountId = null
    ): array {
        return [
            'import_coupon_id' => $import->id,
            'amount' => $amount,
            'payment_method' => $method,
            'bank_account_id' => $bankAccountId,
            'transaction_date' => $date,
            'idempotency_key' => $key,
        ];
    }

    private function purchaseCount(ImportCoupon $import): int
    {
        return Transaction::query()
            ->where('type', 'expense')
            ->where('document_type', 'import')
            ->where('reference_number', 'IMP-'.$import->id)
            ->count();
    }

    private function paymentCount(ImportCoupon $import): int
    {
        return Transaction::query()
            ->where('type', 'expense')
            ->where('document_type', 'import_payment')
            ->where('reference_number', 'like', 'IMP-'.$import->id.'-PAY-%')
            ->count();
    }

    private function assertEntry(
        Transaction $transaction,
        string $accountCode,
        int $debit,
        int $credit,
        ?string $tableableType = null,
        ?int $tableableId = null
    ): void {
        $accountId = Account::where('code', $accountCode)->value('id');

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $accountId,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'tableable_type' => $tableableType,
            'tableable_id' => $tableableId,
        ]);
    }

    private function assertAllTransactionsBalancedAndCompleted(ImportCoupon $import): void
    {
        $transactions = Transaction::query()
            ->where('reference_number', 'like', 'IMP-'.$import->id.'%')
            ->with('entries')
            ->get();

        foreach ($transactions as $transaction) {
            $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->status);
            $this->assertEquals(
                $transaction->entries->sum('debit_amount'),
                $transaction->entries->sum('credit_amount')
            );
        }
    }

    private function expectValidation(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
