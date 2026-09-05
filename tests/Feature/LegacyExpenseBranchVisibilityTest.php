<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ExpenseController;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LegacyExpenseBranchVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['supplier_debts', 'expense', 'companies', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
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
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('expense', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('companies_id')->nullable();
            $table->string('content')->nullable();
            $table->timestamps();
        });
        Schema::create('supplier_debts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('companies_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function test_legacy_expense_reads_are_branch_scoped_and_administrators_are_global(): void
    {
        $administratorA = $this->user(1, null, 'admin-a@example.test');
        $administratorB = $this->user(1, null, 'admin-b@example.test');
        $adminStoreA = $this->user(2, 101, 'store-a@example.test');
        $adminStoreB = $this->user(2, 202, 'store-b@example.test');
        $adminStoreNull = $this->user(2, null, 'store-null@example.test');

        $companyA = DB::table('companies')->insertGetId(['user_id' => $administratorA->id, 'branch_id' => 101, 'name' => 'A']);
        $companyB = DB::table('companies')->insertGetId(['user_id' => $administratorA->id, 'branch_id' => 202, 'name' => 'B']);
        $companyLegacy = DB::table('companies')->insertGetId(['user_id' => $administratorA->id, 'branch_id' => null, 'name' => 'Legacy']);
        $expenseA = DB::table('expense')->insertGetId(['companies_id' => $companyA, 'content' => 'A']);
        $expenseB = DB::table('expense')->insertGetId(['companies_id' => $companyB, 'content' => 'B']);
        $expenseLegacy = DB::table('expense')->insertGetId(['companies_id' => $companyLegacy, 'content' => 'Legacy']);
        $debtA = DB::table('supplier_debts')->insertGetId(['companies_id' => $companyA, 'branch_id' => 101]);
        $debtB = DB::table('supplier_debts')->insertGetId(['companies_id' => $companyB, 'branch_id' => 202]);
        $debtLegacy = DB::table('supplier_debts')->insertGetId(['companies_id' => $companyLegacy, 'branch_id' => null]);

        $this->assertSame([$expenseA], $this->ids('expenseQuery', $adminStoreA));
        $this->assertSame([$expenseB], $this->ids('expenseQuery', $adminStoreB));
        $this->assertSame([$debtA], $this->ids('supplierDebtQuery', $adminStoreA));
        $this->assertSame([$debtB], $this->ids('supplierDebtQuery', $adminStoreB));
        foreach ([$administratorA, $administratorB] as $administrator) {
            $this->assertEqualsCanonicalizing([$expenseA, $expenseB, $expenseLegacy], $this->ids('expenseQuery', $administrator));
            $this->assertEqualsCanonicalizing([$debtA, $debtB, $debtLegacy], $this->ids('supplierDebtQuery', $administrator));
        }

        try {
            $this->ids('expenseQuery', $adminStoreNull);
            $this->fail('Admin Store without Branch must fail closed.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function user(int $roleId, ?int $branchId, string $email): User
    {
        return User::create(['name' => $email, 'email' => $email, 'password' => 'password', 'role_id' => $roleId, 'branch_id' => $branchId]);
    }

    /** @return list<int> */
    private function ids(string $method, User $actor): array
    {
        $request = Request::create('/admin/expense', 'GET');
        $request->setUserResolver(static fn (): User => $actor);
        $this->app->instance('request', $request);

        $reflection = new \ReflectionMethod(ExpenseController::class, $method);
        /** @var Builder $query */
        $query = $reflection->invoke(app(ExpenseController::class), $actor);

        return $query->orderBy('id')->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }
}
