<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_details', 'product_imei_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table): void {
            $table->unique('product_imei_id', 'order_details_product_imei_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('order_details', 'product_imei_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table): void {
            $table->dropUnique('order_details_product_imei_id_unique');
        });
    }
};
