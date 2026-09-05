<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FinancialBranchMigrationIsolationTest extends TestCase
{
    private const FINANCIAL_TABLES = [
        'transactions',
        'cash_vouchers',
        'bank_vouchers',
        'customer_debt_collections',
        'customer_debt_yearly_snapshots',
        'customer_debt_snapshot_states',
        'supplier_debt_yearly_snapshots',
        'supplier_debt_snapshot_states',
        'supplier_debts',
    ];

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'customer_debt_collection_allocations',
            'transaction_entries',
            'supplier_debts_detail',
            ...array_reverse(self::FINANCIAL_TABLES),
            'import_coupon',
            'orders',
            'clients',
            'storages',
            'branches',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    public function test_financial_migration_backfills_only_proven_sources_and_enforces_branch_visibility(): void
    {
        $this->createLegacySchema();
        $this->seedLegacyEvidence();

        $migration = require database_path(
            'migrations/2026_09_05_000000_add_branch_snapshots_to_financial_tables.php'
        );
        $migration->up();

        foreach (self::FINANCIAL_TABLES as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'branch_id'), $table);

            $column = collect(DB::select("PRAGMA table_info('{$table}')"))
                ->firstWhere('name', 'branch_id');
            $this->assertSame(0, (int) $column->notnull, "{$table}.branch_id must be nullable");

            if (DB::connection()->getDriverName() !== 'sqlite') {
                $foreignKey = collect(Schema::getForeignKeys($table))
                    ->first(fn (array $key) => in_array('branch_id', $key['columns'], true));
                $this->assertNotNull($foreignKey, "{$table}.branch_id must have a foreign key");
                $this->assertSame('branches', $foreignKey['foreign_table']);
                $this->assertContains(strtoupper((string) $foreignKey['on_delete']), ['RESTRICT', 'NO ACTION']);
            }
        }

        foreach ([
            'transaction_entries',
            'customer_debt_collection_allocations',
            'supplier_debts_detail',
        ] as $childTable) {
            $this->assertFalse(Schema::hasColumn($childTable, 'branch_id'), $childTable);
        }

        $this->assertSame(1, (int) DB::table('transactions')->where('id', 1)->value('branch_id'));
        $this->assertNull(DB::table('transactions')->where('id', 2)->value('branch_id'));
        $this->assertSame(2, (int) DB::table('transactions')->where('id', 3)->value('branch_id'));
        $this->assertSame(2, (int) DB::table('transactions')->where('id', 4)->value('branch_id'));
        $this->assertNull(DB::table('transactions')->where('id', 5)->value('branch_id'));
        $this->assertNull(DB::table('transactions')->where('id', 6)->value('branch_id'));

        $this->assertSame(1, (int) DB::table('customer_debt_collections')->where('id', 1)->value('branch_id'));
        $this->assertNull(DB::table('customer_debt_collections')->where('id', 2)->value('branch_id'));
        $this->assertNull(DB::table('customer_debt_collections')->where('id', 3)->value('branch_id'));
        $this->assertSame(1, (int) DB::table('transactions')->where('id', 7)->value('branch_id'));

        foreach ([
            'cash_vouchers',
            'bank_vouchers',
            'customer_debt_yearly_snapshots',
            'customer_debt_snapshot_states',
            'supplier_debt_yearly_snapshots',
            'supplier_debt_snapshot_states',
            'supplier_debts',
        ] as $table) {
            $this->assertNull(DB::table($table)->where('id', 1)->value('branch_id'), $table);
        }

        $this->assertBranchVisibility();
    }

    private function assertBranchVisibility(): void
    {
        $administrator = User::query()->findOrFail(1);
        $adminStoreA = User::query()->findOrFail(2);
        $adminStoreWithoutBranch = User::query()->findOrFail(3);
        $context = app(BranchContext::class);

        foreach (self::FINANCIAL_TABLES as $offset => $table) {
            $branchAId = DB::table($table)->insertGetId(['branch_id' => 1]);
            $branchBId = DB::table($table)->insertGetId(['branch_id' => 2]);
            $legacyId = DB::table($table)->insertGetId(['branch_id' => null]);

            $visibleToA = $context->scope(DB::table($table), $adminStoreA)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $this->assertTrue($visibleToA->contains($branchAId), "{$table}: Branch A missing");
            $this->assertFalse($visibleToA->contains($branchBId), "{$table}: Branch B leaked");
            $this->assertFalse($visibleToA->contains($legacyId), "{$table}: legacy NULL leaked");

            $visibleToAdministrator = $context->scope(DB::table($table), $administrator)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $this->assertTrue($visibleToAdministrator->contains($branchAId));
            $this->assertTrue($visibleToAdministrator->contains($branchBId));
            $this->assertTrue($visibleToAdministrator->contains($legacyId));
        }

        try {
            $context->scope(DB::table('transactions'), $adminStoreWithoutBranch);
            $this->fail('Admin Store without a Branch must fail closed.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function createLegacySchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
        });
        Schema::create('import_coupon', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('storage_id')->nullable();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('document_type')->nullable();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
        });
        Schema::create('cash_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('accounting_status')->nullable();
        });
        Schema::create('bank_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('accounting_status')->nullable();
        });
        Schema::create('customer_debt_collections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('collection_date')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('customer_debt_collection_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('order_id');
        });
        Schema::create('customer_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedInteger('fiscal_year')->nullable();
            $table->unique(['owner_id', 'client_id', 'fiscal_year'], 'customer_debt_snapshots_business_unique');
        });
        Schema::create('customer_debt_snapshot_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedInteger('dirty_from_year')->nullable();
            $table->unique(['owner_id', 'client_id'], 'customer_debt_snapshot_states_business_unique');
        });
        Schema::create('supplier_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedInteger('fiscal_year')->nullable();
            $table->unique(['owner_id', 'company_id', 'fiscal_year'], 'supplier_debt_snapshots_business_unique');
        });
        Schema::create('supplier_debt_snapshot_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedInteger('dirty_from_year')->nullable();
            $table->unique(['owner_id', 'company_id'], 'supplier_debt_states_business_unique');
        });
        Schema::create('supplier_debts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('companies_id')->nullable();
            $table->timestamps();
        });
        Schema::create('transaction_entries', fn (Blueprint $table) => $table->id());
        Schema::create('supplier_debts_detail', fn (Blueprint $table) => $table->id());
    }

    private function seedLegacyEvidence(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'manager_id' => null, 'name' => 'Administrator', 'email' => 'root@example.test', 'password' => 'x', 'role_id' => 1, 'branch_id' => null],
            ['id' => 2, 'manager_id' => 1, 'name' => 'Store A', 'email' => 'a@example.test', 'password' => 'x', 'role_id' => 2, 'branch_id' => 1],
            ['id' => 3, 'manager_id' => 1, 'name' => 'No branch', 'email' => 'null@example.test', 'password' => 'x', 'role_id' => 2, 'branch_id' => null],
        ]);
        DB::table('branches')->insert([
            ['id' => 1, 'user_id' => 1],
            ['id' => 2, 'user_id' => 1],
        ]);
        DB::table('storages')->insert([
            ['id' => 1, 'user_id' => 1, 'branch_id' => 2],
            ['id' => 2, 'user_id' => 1, 'branch_id' => null],
        ]);
        DB::table('orders')->insert([
            ['id' => 1, 'branch_id' => 1],
            ['id' => 2, 'branch_id' => null],
            ['id' => 3, 'branch_id' => 2],
        ]);
        DB::table('clients')->insert([
            ['id' => 1, 'branch_id' => 1],
            ['id' => 2, 'branch_id' => 2],
            ['id' => 3, 'branch_id' => null],
        ]);
        DB::table('import_coupon')->insert([
            ['id' => 1, 'storage_id' => 1],
            ['id' => 2, 'storage_id' => 2],
        ]);
        DB::table('customer_debt_collections')->insert([
            ['id' => 1, 'client_id' => 1],
            ['id' => 2, 'client_id' => 1],
            ['id' => 3, 'client_id' => 3],
        ]);
        DB::table('customer_debt_collection_allocations')->insert([
            ['collection_id' => 1, 'order_id' => 1],
            ['collection_id' => 2, 'order_id' => 1],
            ['collection_id' => 2, 'order_id' => 3],
            ['collection_id' => 3, 'order_id' => 1],
        ]);
        DB::table('transactions')->insert([
            ['id' => 1, 'document_type' => 'order', 'reference_number' => '1', 'collection_id' => null],
            ['id' => 2, 'document_type' => 'order', 'reference_number' => '2', 'collection_id' => null],
            ['id' => 3, 'document_type' => 'import', 'reference_number' => 'IMP-1', 'collection_id' => null],
            ['id' => 4, 'document_type' => 'import_payment', 'reference_number' => 'IMP-1-PAY-INITIAL', 'collection_id' => null],
            ['id' => 5, 'document_type' => 'import', 'reference_number' => 'IMP-2', 'collection_id' => null],
            ['id' => 6, 'document_type' => 'other', 'reference_number' => 'legacy', 'collection_id' => null],
            ['id' => 7, 'document_type' => 'customer_collection', 'reference_number' => null, 'collection_id' => 1],
        ]);

        foreach ([
            'cash_vouchers',
            'bank_vouchers',
            'customer_debt_yearly_snapshots',
            'customer_debt_snapshot_states',
            'supplier_debt_yearly_snapshots',
            'supplier_debt_snapshot_states',
            'supplier_debts',
        ] as $table) {
            DB::table($table)->insert(['id' => 1]);
        }
    }
}
