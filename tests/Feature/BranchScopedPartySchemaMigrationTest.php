<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BranchScopedPartySchemaMigrationTest extends TestCase
{
    public function test_party_branch_columns_can_be_added_without_backfill(): void
    {
        Schema::dropIfExists('clients');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('branches');

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        $migration = require database_path('migrations/2026_08_20_000100_add_branch_id_to_clients_and_companies_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('clients', 'branch_id'));
        $this->assertTrue(Schema::hasColumn('companies', 'branch_id'));

        if (DB::connection()->getDriverName() === 'sqlite') {
            $clientBranchColumn = collect(DB::select("PRAGMA table_info('clients')"))->firstWhere('name', 'branch_id');
            $companyBranchColumn = collect(DB::select("PRAGMA table_info('companies')"))->firstWhere('name', 'branch_id');

            $this->assertSame(0, (int) $clientBranchColumn->notnull);
            $this->assertSame(0, (int) $companyBranchColumn->notnull);
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $migration->down();

            $this->assertFalse(Schema::hasColumn('clients', 'branch_id'));
            $this->assertFalse(Schema::hasColumn('companies', 'branch_id'));
        }
    }
}
