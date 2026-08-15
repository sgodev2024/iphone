<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DebtController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CustomerDebtLegacyCleanupTest extends TestCase
{
    public function test_legacy_runtime_classes_and_receipt_routes_are_removed(): void
    {
        foreach ([
            'app/Models/ClientDebt.php',
            'app/Models/ClientDebtsDetail.php',
            'app/Models/Receipts.php',
            'app/Models/ReceiptDetail.php',
            'app/Services/DebtKHService.php',
            'app/Models/Services/DebtKHService.php',
            'app/Services/ReceiptsService.php',
            'app/Models/Services/ReceiptsService.php',
            'app/Http/Controllers/Admin/ReceiptController.php',
            'app/Http/Controllers/Admin/DebtClientController.php',
        ] as $legacyFile) {
            $this->assertFileDoesNotExist(base_path($legacyFile));
        }

        foreach ([
            'admin.quanlythuchi.receipts.index',
            'admin.quanlythuchi.receipts.detail',
            'admin.quanlythuchi.receipts.add',
            'admin.quanlythuchi.receipts.addSubmit',
            'admin.quanlythuchi.receipts.debt',
        ] as $routeName) {
            $this->assertNull(Route::getRoutes()->getByName($routeName));
        }

        $legacyPayment = Route::getRoutes()->getByName('admin.debts.customer.payments.store');
        $this->assertNotNull($legacyPayment);
        $this->assertStringEndsWith('@legacyWriteDisabled', $legacyPayment->getActionName());

        $this->assertNotNull(Route::getRoutes()->getByName('admin.debts.customer'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.debts.customer.collections.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.debts.customer.collections.store'));

        $this->assertNull(Route::getRoutes()->getByName('admin.debts.beginning'));
        $openingTombstone = Route::getRoutes()->getByName('admin.debts.store');
        $this->assertNotNull($openingTombstone);
        $this->assertStringEndsWith('@store', $openingTombstone->getActionName());
    }

    public function test_unsafe_customer_opening_debt_is_a_410_tombstone(): void
    {
        $request = Request::create('/admin/debts/beginning', 'POST', [
            'object_type' => 'client',
        ]);

        try {
            (new DebtController())->store($request);
            $this->fail('Unsafe customer opening debt must remain disabled.');
        } catch (HttpException $exception) {
            $this->assertSame(410, $exception->getStatusCode());
        }
    }

    public function test_legacy_table_drop_migration_round_trips_without_touching_canonical_tables(): void
    {
        $originalConnection = DB::getDefaultConnection();
        $connection = 'phase7f_legacy_cleanup';

        config([
            "database.connections.{$connection}" => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge($connection);
        DB::setDefaultConnection($connection);

        try {
            Schema::create('clients', fn (Blueprint $table) => $table->id());

            foreach ([
                'transactions',
                'transaction_entries',
                'customer_debt_collections',
                'customer_debt_collection_allocations',
            ] as $tableName) {
                Schema::create($tableName, fn (Blueprint $table) => $table->id());
                DB::table($tableName)->insert(['id' => 1]);
            }

            $migration = require database_path(
                'migrations/2026_08_15_000300_drop_legacy_customer_debt_tables.php'
            );

            $migration->down();

            $this->assertTrue(Schema::hasColumns('customer_debts', [
                'id', 'client_id', 'amount', 'description', 'code', 'created_at', 'updated_at',
            ]));
            $this->assertTrue(Schema::hasColumns('customer_debts_detail', [
                'id', 'customer_debts_id', 'content', 'amount', 'created_at', 'updated_at',
            ]));
            $this->assertTrue(Schema::hasColumns('receipts', [
                'id', 'client_id', 'content', 'amount_spent', 'date_spent', 'receipt_code',
                'created_at', 'updated_at',
            ]));
            $this->assertTrue(Schema::hasColumns('receipts_detail', [
                'id', 'receipt_id', 'content', 'amount', 'date', 'created_at', 'updated_at',
            ]));

            $canonicalCounts = collect([
                'transactions',
                'transaction_entries',
                'customer_debt_collections',
                'customer_debt_collection_allocations',
            ])->mapWithKeys(fn (string $tableName) => [
                $tableName => DB::table($tableName)->count(),
            ])->all();

            $migration->up();

            foreach (['customer_debts', 'customer_debts_detail', 'receipts', 'receipts_detail'] as $tableName) {
                $this->assertFalse(Schema::hasTable($tableName));
            }

            foreach ($canonicalCounts as $tableName => $count) {
                $this->assertSame($count, DB::table($tableName)->count());
            }

            $migration->down();
            $migration->up();

            foreach (['customer_debts', 'customer_debts_detail', 'receipts', 'receipts_detail'] as $tableName) {
                $this->assertFalse(Schema::hasTable($tableName));
            }
        } finally {
            DB::purge($connection);
            DB::setDefaultConnection($originalConnection);
        }
    }
}
