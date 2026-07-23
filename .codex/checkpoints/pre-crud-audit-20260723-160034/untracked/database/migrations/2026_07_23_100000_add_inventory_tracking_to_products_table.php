<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TRACKING_IMEI = 'imei';

    private const TRACKING_QUANTITY = 'quantity';

    public function up(): void
    {
        if (! Schema::hasColumn('products', 'inventory_tracking')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('inventory_tracking', 20)->nullable()->after('quantity')->index();
            });
        }

        $this->ensureQuantityValuesAreNumeric();
        $this->assignInventoryTracking();
        $this->rebuildImeiManagedStorageFromImeis();
        $this->syncProductQuantitiesFromStorage();
        $this->changeQuantityToUnsignedInteger();
        $this->requireInventoryTracking();
        $this->addInventoryTrackingCheckConstraint();
    }

    public function down(): void
    {
        $this->dropInventoryTrackingCheckConstraint();

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `products` MODIFY `quantity` varchar(255) NULL DEFAULT '0'");
        }

        if (Schema::hasColumn('products', 'inventory_tracking')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('inventory_tracking');
            });
        }
    }

    private function ensureQuantityValuesAreNumeric(): void
    {
        DB::table('products')
            ->whereNull('quantity')
            ->orWhereRaw('TRIM(CAST(quantity AS CHAR)) = ""')
            ->update(['quantity' => 0]);

        $invalidCount = $this->invalidQuantityQuery()->count();

        if ($invalidCount > 0) {
            throw new \RuntimeException(
                "products.quantity contains {$invalidCount} non-numeric value(s); clean them before changing the column type."
            );
        }
    }

    private function invalidQuantityQuery(): Builder
    {
        $query = DB::table('products')->whereNotNull('quantity');

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return $query->whereRaw('TRIM(CAST(quantity AS CHAR)) NOT REGEXP "^[0-9]+$"');
        }

        return $query->whereRaw("TRIM(CAST(quantity AS TEXT)) NOT GLOB '[0-9]*'");
    }

    private function assignInventoryTracking(): void
    {
        if (Schema::hasTable('product_imeis')) {
            DB::table('products')
                ->whereIn('id', function ($query) {
                    $query->select('product_id')
                        ->from('product_imeis')
                        ->whereNotNull('product_id')
                        ->distinct();
                })
                ->update(['inventory_tracking' => self::TRACKING_IMEI]);
        }

        DB::table('products')
            ->whereNull('inventory_tracking')
            ->update(['inventory_tracking' => self::TRACKING_QUANTITY]);
    }

    private function rebuildImeiManagedStorageFromImeis(): void
    {
        if (
            ! Schema::hasTable('product_imeis')
            || ! Schema::hasTable('product_storage')
            || ! Schema::hasTable('import_detail')
            || ! Schema::hasTable('import_coupon')
        ) {
            return;
        }

        $imeiProductIds = DB::table('products')
            ->where('inventory_tracking', self::TRACKING_IMEI)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($imeiProductIds->isEmpty()) {
            return;
        }

        $unplaceableProductIds = DB::table('product_imeis')
            ->leftJoin('import_detail', 'import_detail.id', '=', 'product_imeis.import_detail_id')
            ->leftJoin('import_coupon', 'import_coupon.id', '=', 'import_detail.import_id')
            ->whereIn('product_imeis.product_id', $imeiProductIds)
            ->where('product_imeis.status', 'in_stock')
            ->whereNull('import_coupon.storage_id')
            ->pluck('product_imeis.product_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        $rebuildProductIds = $imeiProductIds->diff($unplaceableProductIds)->values();

        if ($rebuildProductIds->isEmpty()) {
            return;
        }

        DB::table('product_storage')
            ->whereIn('product_id', $rebuildProductIds)
            ->delete();

        $now = now();
        $rows = DB::table('product_imeis')
            ->join('import_detail', 'import_detail.id', '=', 'product_imeis.import_detail_id')
            ->join('import_coupon', 'import_coupon.id', '=', 'import_detail.import_id')
            ->select([
                'product_imeis.product_id',
                'import_coupon.storage_id',
                DB::raw('COUNT(*) as quantity'),
            ])
            ->whereIn('product_imeis.product_id', $rebuildProductIds)
            ->where('product_imeis.status', 'in_stock')
            ->whereNotNull('import_coupon.storage_id')
            ->groupBy('product_imeis.product_id', 'import_coupon.storage_id')
            ->get()
            ->map(fn ($row) => [
                'product_id' => (int) $row->product_id,
                'storage_id' => (int) $row->storage_id,
                'quantity' => (int) $row->quantity,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('product_storage')->insert($rows);
        }
    }

    private function syncProductQuantitiesFromStorage(): void
    {
        if (! Schema::hasTable('product_storage')) {
            DB::table('products')->update(['quantity' => 0]);

            return;
        }

        DB::table('products')
            ->orderBy('id')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $quantity = (int) DB::table('product_storage')
                        ->where('product_id', $product->id)
                        ->sum('quantity');

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['quantity' => $quantity]);
                }
            });
    }

    private function changeQuantityToUnsignedInteger(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `products` MODIFY `quantity` int unsigned NOT NULL DEFAULT 0');
        }
    }

    private function requireInventoryTracking(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `products` MODIFY `inventory_tracking` varchar(20) NOT NULL');
        }
    }

    private function addInventoryTrackingCheckConstraint(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            DB::statement(
                "ALTER TABLE `products` ADD CONSTRAINT `products_inventory_tracking_check` CHECK (`inventory_tracking` IN ('imei', 'quantity'))"
            );
        } catch (\Throwable) {
        }
    }

    private function dropInventoryTrackingCheckConstraint(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `products` DROP CHECK `products_inventory_tracking_check`');
        } catch (\Throwable) {
            try {
                DB::statement('ALTER TABLE `products` DROP CONSTRAINT `products_inventory_tracking_check`');
            } catch (\Throwable) {
            }
        }
    }
};
