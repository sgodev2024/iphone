<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SupplierBranchSchemaMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->string('name');
            $table->index('branch_id', 'companies_branch_id_index');
        });
        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);
    }

    public function test_migration_fails_closed_when_a_supplier_has_no_branch(): void
    {
        DB::table('companies')->insert([
            'branch_id' => null,
            'name' => 'Legacy Supplier',
        ]);

        $this->expectException(RuntimeException::class);
        $this->migration()->up();
    }

    public function test_migration_rejects_existing_cross_branch_product_supplier_links(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
        });
        Schema::create('company_product', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('company_id');
        });
        $companyId = DB::table('companies')->insertGetId([
            'branch_id' => 1,
            'name' => 'Supplier A',
        ]);
        $productId = DB::table('products')->insertGetId(['branch_id' => 2]);
        DB::table('company_product')->insert([
            'product_id' => $productId,
            'company_id' => $companyId,
        ]);

        $this->expectException(RuntimeException::class);
        $this->migration()->up();
    }

    public function test_migration_rejects_existing_cross_branch_import_supplier_links(): void
    {
        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
        });
        Schema::create('import_coupon', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('storage_id');
            $table->unsignedBigInteger('companies_id');
        });
        $companyId = DB::table('companies')->insertGetId([
            'branch_id' => 1,
            'name' => 'Supplier A',
        ]);
        $storageId = DB::table('storages')->insertGetId(['branch_id' => 2]);
        DB::table('import_coupon')->insert([
            'storage_id' => $storageId,
            'companies_id' => $companyId,
        ]);

        $this->expectException(RuntimeException::class);
        $this->migration()->up();
    }
    public function test_migration_makes_branch_required_restricts_delete_and_keeps_names_branch_local(): void
    {
        DB::table('companies')->insert([
            'branch_id' => 1,
            'name' => 'Shared Supplier',
        ]);

        $this->migration()->up();

        $branchColumn = collect(DB::select("PRAGMA table_info('companies')"))
            ->firstWhere('name', 'branch_id');
        $this->assertSame(1, (int) $branchColumn->notnull);

        $branchForeign = collect(DB::select("PRAGMA foreign_key_list('companies')"))
            ->first(fn (object $foreign): bool => ($foreign->from ?? null) === 'branch_id');
        $this->assertNotNull($branchForeign);

        $indexes = collect(DB::select("PRAGMA index_list('companies')"))
            ->pluck('name');
        $this->assertTrue($indexes->contains('companies_branch_id_index'));
        $this->assertTrue($indexes->contains('companies_branch_name_unique'));

        DB::table('companies')->insert([
            'branch_id' => 2,
            'name' => 'Shared Supplier',
        ]);
        $this->assertSame(2, DB::table('companies')->where('name', 'Shared Supplier')->count());

        try {
            DB::table('companies')->insert([
                'branch_id' => 1,
                'name' => 'Shared Supplier',
            ]);
            $this->fail('A duplicate Supplier name was accepted inside one Branch.');
        } catch (QueryException) {
            $this->assertSame(2, DB::table('companies')->where('name', 'Shared Supplier')->count());
        }

        $this->expectException(QueryException::class);
        DB::table('branches')->where('id', 1)->delete();
    }

    private function migration(): object
    {
        return require database_path(
            'migrations/2026_09_10_120000_enforce_company_branch_isolation.php'
        );
    }
}