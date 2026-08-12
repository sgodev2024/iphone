<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMN = 'sale_order_business_key';

    private const INDEX = 'transactions_sale_order_business_key_unique';

    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            throw new \RuntimeException('The transactions table does not exist.');
        }

        if (Schema::hasColumn('transactions', self::COLUMN)) {
            throw new \RuntimeException('The sale order business key column already exists.');
        }

        $hasDuplicateSale = DB::table('transactions')
            ->select('reference_number')
            ->where('type', 'sale')
            ->where('document_type', 'order')
            ->groupBy('reference_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateSale) {
            throw new \RuntimeException('Duplicate sale transactions exist for at least one order.');
        }

        $saleOrderKeyExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CASE WHEN type = 'sale' AND document_type = 'order' THEN (CASE WHEN reference_number IS NULL THEN '0:' ELSE '1:' END) || COALESCE(reference_number, '') ELSE NULL END"
            : "CASE WHEN type = 'sale' AND document_type = 'order' THEN CONCAT(CASE WHEN reference_number IS NULL THEN '0:' ELSE '1:' END, COALESCE(reference_number, '')) ELSE NULL END";

        Schema::table('transactions', function (Blueprint $table) use ($saleOrderKeyExpression): void {
            $table->string(self::COLUMN, 257)
                ->nullable()
                ->virtualAs($saleOrderKeyExpression)
                ->invisible();
            $table->unique(self::COLUMN, self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX);
            $table->dropColumn(self::COLUMN);
        });
    }
};
