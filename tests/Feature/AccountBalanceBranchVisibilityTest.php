<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountBalanceBranchVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('transaction_entries');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('config');
        Schema::dropIfExists('user_info');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('role_id');
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
            $table->string('logo')->nullable();
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('level')->default(1);
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('transaction_date');
            $table->string('type');
        });
        Schema::create('transaction_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
        });

        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('CONCAT', static fn (...$values): string => implode('', $values), -1);
        $pdo->sqliteCreateFunction('GREATEST', static fn (...$values): float => max($values), -1);
    }

    public function test_balance_sums_after_branch_filter_and_is_global_for_both_administrators(): void
    {
        $administratorA = $this->user(1, null, 'administrator-a@example.test');
        $administratorB = $this->user(1, null, 'administrator-b@example.test');
        $adminStoreA = $this->user(2, 101, 'store-a@example.test');
        $adminStoreB = $this->user(2, 202, 'store-b@example.test');

        $accountId = DB::table('accounts')->insertGetId([
            'code' => '111',
            'name' => 'Cash',
            'level' => 1,
        ]);
        $this->ledger($adminStoreA->id, 101, $accountId, 100, 60);
        $this->ledger($adminStoreB->id, 202, $accountId, 500, 300);

        $this->assertBalance($adminStoreA, 100, 60);
        $this->assertBalance($adminStoreB, 500, 300);
        $this->assertBalance($administratorA, 600, 360);
        $this->assertBalance($administratorB, 600, 360);
    }

    private function user(int $roleId, ?int $branchId, string $email): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => $roleId,
            'branch_id' => $branchId,
        ]);
    }

    private function ledger(int $userId, int $branchId, int $accountId, int $debit, int $credit): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'transaction_date' => '2026-09-05',
            'type' => 'income',
        ]);
        DB::table('transaction_entries')->insert([
            'transaction_id' => $transactionId,
            'account_id' => $accountId,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
        ]);
    }

    private function assertBalance(User $actor, int $expectedDebit, int $expectedCredit): void
    {
        $request = Request::create('/admin/accounts/balance', 'GET', [
            'dateRange' => '01/09/2026 - 30/09/2026',
            'searchInput' => '111',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(static fn (): User => $actor);

        $html = app(AccountController::class)->balance($request)->getData(true)['html'];

        $this->assertStringContainsString('<strong>'.number_format($expectedDebit, 0, ',', '.').'</strong>', $html);
        $this->assertStringContainsString('<strong>'.number_format($expectedCredit, 0, ',', '.').'</strong>', $html);
    }
}
