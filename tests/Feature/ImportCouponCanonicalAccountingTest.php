<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ImportCouponController;
use App\Models\Company;
use App\Models\ImportCoupon;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

class ImportCouponCanonicalAccountingTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('transaction_entries');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('import_coupon');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('companies');

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        foreach ([
            ['code' => '111', 'name' => 'Cash', 'parent_id' => null, 'is_default' => true],
            ['code' => '112', 'name' => 'Bank', 'parent_id' => null, 'is_default' => true],
            ['code' => '156', 'name' => 'Goods', 'parent_id' => null, 'is_default' => true],
            ['code' => '331', 'name' => 'Payable', 'parent_id' => null, 'is_default' => true],
        ] as $account) {
            DB::table('accounts')->insert($account + [
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bankParentId = DB::table('accounts')->where('code', '112')->value('id');
        DB::table('accounts')->insert([
            'code' => '112MB',
            'name' => 'MBBank',
            'parent_id' => $bankParentId,
            'status' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('import_coupon', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('companies_id');
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('payment_ncc')->default(0);
            $table->string('payment_method');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('debt_amount')->default(0);
            $table->string('payment_status');
            $table->string('coupon_code')->nullable();
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

        $this->company = Company::create([
            'user_id' => 1,
            'name' => 'Canonical Company',
        ]);
    }

    public function test_unpaid_import_posts_only_full_purchase_to_company_payable(): void
    {
        $coupon = $this->coupon(0, ImportCoupon::PAYMENT_METHOD_DEBT, 100000);

        $this->record($coupon);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertEntry('import', '156', 100000, 0, null, null);
        $this->assertEntry('import', '331', 0, 100000, Company::class, $this->company->id);
        $this->assertSame(Transaction::STATUS_COMPLETED, Transaction::first()->status);
    }

    public function test_partial_cash_import_posts_full_purchase_and_separate_payment(): void
    {
        $coupon = $this->coupon(40000, ImportCoupon::PAYMENT_METHOD_CASH, 60000);

        $this->record($coupon);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertEntry('import', '331', 0, 100000, Company::class, $this->company->id);
        $this->assertEntry('import_payment', '331', 40000, 0, Company::class, $this->company->id);
        $this->assertEntry('import_payment', '111', 0, 40000, null, null);
        $this->assertBalancedTransactions();
    }

    public function test_full_bank_import_uses_the_selected_child_of_112(): void
    {
        $coupon = $this->coupon(100000, ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER, 0);
        $bankAccountId = (int) DB::table('accounts')->where('code', '112MB')->value('id');

        $this->record($coupon, $bankAccountId);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertEntry('import_payment', '112MB', 0, 100000, null, null);
        $this->assertBalancedTransactions();
    }

    public function test_purchase_application_guard_prevents_duplicate_posting(): void
    {
        $coupon = $this->coupon(0, ImportCoupon::PAYMENT_METHOD_DEBT, 100000);

        $this->record($coupon);

        $this->expectException(ValidationException::class);
        $this->record($coupon->fresh());
    }

    private function coupon(int $paidAmount, string $paymentMethod, int $debtAmount): ImportCoupon
    {
        return ImportCoupon::create([
            'user_id' => 1,
            'companies_id' => $this->company->id,
            'total' => 100000,
            'payment_ncc' => $paidAmount,
            'payment_method' => $paymentMethod,
            'paid_amount' => $paidAmount,
            'debt_amount' => $debtAmount,
            'payment_status' => $paidAmount === 0 ? ImportCoupon::PAYMENT_STATUS_UNPAID : ($debtAmount > 0 ? ImportCoupon::PAYMENT_STATUS_PARTIAL : ImportCoupon::PAYMENT_STATUS_PAID),
        ]);
    }

    private function record(ImportCoupon $coupon, ?int $bankAccountId = null): void
    {
        $reflection = new ReflectionClass(ImportCouponController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('recordAccountingEntries');
        $method->setAccessible(true);
        $method->invoke($controller, 1, 1, $coupon, $bankAccountId, '2026-08-12');
    }

    private function assertEntry(string $documentType, string $accountCode, int $debit, int $credit, ?string $tableableType, ?int $tableableId): void
    {
        $accountId = DB::table('accounts')->where('code', $accountCode)->value('id');
        $entry = TransactionEntry::query()
            ->where('account_id', $accountId)
            ->where('debit_amount', $debit)
            ->where('credit_amount', $credit)
            ->whereHas('transaction', fn ($query) => $query->where('document_type', $documentType))
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($tableableType, $entry->tableable_type);
        $this->assertSame($tableableId, $entry->tableable_id === null ? null : (int) $entry->tableable_id);
    }

    private function assertBalancedTransactions(): void
    {
        foreach (Transaction::query()->with('entries')->get() as $transaction) {
            $this->assertEquals(
                $transaction->entries->sum('debit_amount'),
                $transaction->entries->sum('credit_amount')
            );
            $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->status);
        }
    }
}
