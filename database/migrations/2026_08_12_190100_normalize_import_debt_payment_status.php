<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_coupon') || ! Schema::hasColumn('import_coupon', 'payment_status')) {
            return;
        }

        // "unpaid" was the Phase 6A label. Keep historical rows intact while
        // making the new canonical debt status explicit.
        DB::table('import_coupon')
            ->where('payment_status', 'unpaid')
            ->update(['payment_status' => 'debt']);
    }

    public function down(): void
    {
        // Do not rewrite business history during rollback.
    }
};
