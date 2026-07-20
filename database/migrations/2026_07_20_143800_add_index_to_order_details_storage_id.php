<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('order_details', 'storage_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->index('storage_id', 'order_details_storage_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('order_details', 'storage_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex('order_details_storage_id_index');
        });
    }
};
