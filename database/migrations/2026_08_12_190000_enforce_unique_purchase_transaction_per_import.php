<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMN = 'purchase_import_business_key';

    private const INDEX = 'transactions_purchase_import_business_key_unique';

    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            throw new RuntimeException('The transactions table does not exist.');
        }

        if (Schema::hasColumn('transactions', self::COLUMN)) {
            throw new RuntimeException('The purchase import business key column already exists.');
        }

        $hasDuplicatePurchase = DB::table('transactions')
            ->select('reference_number')
            ->where('type', 'expense')
            ->where('document_type', 'import')
            ->groupBy('reference_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicatePurchase) {
            throw new RuntimeException('Duplicate purchase transactions exist for at least one import.');
        }

        $businessKeyExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CASE WHEN type = 'expense' AND document_type = 'import' THEN (CASE WHEN reference_number IS NULL THEN '0:' ELSE '1:' END) || COALESCE(reference_number, '') ELSE NULL END"
            : "CASE WHEN type = 'expense' AND document_type = 'import' THEN CONCAT(CASE WHEN reference_number IS NULL THEN '0:' ELSE '1:' END, COALESCE(reference_number, '')) ELSE NULL END";

        Schema::table('transactions', function (Blueprint $table) use ($businessKeyExpression): void {
            $table->string(self::COLUMN, 257)
                ->nullable()
                ->virtualAs($businessKeyExpression)
                ->invisible();
            $table->unique(self::COLUMN, self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "' . self::INDEX . '"');
            DB::statement('ALTER TABLE "transactions" DROP COLUMN "' . self::COLUMN . '"');
        } else {
            DB::statement('DROP INDEX `' . self::INDEX . '` ON `transactions`');
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropColumn(self::COLUMN);
            });
        }
    }
};
