<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistoricalCostSnapshotMigrationTest extends TestCase
{
    public function test_migration_adds_nullable_snapshots_without_backfilling_legacy_sales(): void
    {
        Schema::dropIfExists('order_details');
        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('quantity');
        });
        DB::table('order_details')->insert(['id' => 1, 'price' => 100000, 'quantity' => 2]);

        $migration = require database_path('migrations/2026_09_18_000000_add_cost_snapshots_to_order_details_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('order_details', ['cost_unit_snapshot', 'cost_total_snapshot']));
        $this->assertDatabaseHas('order_details', [
            'id' => 1,
            'price' => 100000,
            'quantity' => 2,
            'cost_unit_snapshot' => null,
            'cost_total_snapshot' => null,
        ]);

        $migration->down();
        $this->assertFalse(Schema::hasColumn('order_details', 'cost_unit_snapshot'));
        $this->assertFalse(Schema::hasColumn('order_details', 'cost_total_snapshot'));
    }
    public function test_source_migration_preserves_existing_snapshots(): void
    {
        Schema::dropIfExists('order_details');
        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->decimal('cost_unit_snapshot', 20, 2)->nullable();
            $table->decimal('cost_total_snapshot', 20, 2)->nullable();
        });
        DB::table('order_details')->insert([
            ['id' => 1, 'cost_unit_snapshot' => 100, 'cost_total_snapshot' => 200],
            ['id' => 2, 'cost_unit_snapshot' => null, 'cost_total_snapshot' => null],
        ]);

        $migration = require database_path('migrations/2026_09_18_000100_add_cost_snapshot_source_to_order_details_table.php');
        $migration->up();

        $this->assertDatabaseHas('order_details', [
            'id' => 1, 'cost_unit_snapshot' => 100, 'cost_total_snapshot' => 200,
            'cost_snapshot_source' => null,
        ]);
        $this->assertDatabaseHas('order_details', [
            'id' => 2, 'cost_unit_snapshot' => null, 'cost_total_snapshot' => null,
            'cost_snapshot_source' => null,
        ]);

        $migration->down();
        $this->assertFalse(Schema::hasColumn('order_details', 'cost_snapshot_source'));
    }
}
