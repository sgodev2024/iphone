<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('order_details', 'product_imei_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table): void {
            $table->foreignId('product_imei_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_imeis')
                ->nullOnDelete();

            $table->index(
                ['product_imei_id', 'storage_id'],
                'order_details_product_imei_storage_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('order_details', 'product_imei_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table): void {
            $table->dropIndex('order_details_product_imei_storage_index');
            $table->dropConstrainedForeignId('product_imei_id');
        });
    }
};
