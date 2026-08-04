<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->foreignId('storage_id')
                ->nullable()
                ->after('product_id')
                ->constrained('storages')
                ->restrictOnDelete();

            $table->index([
                'storage_id',
                'product_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropIndex([
                'storage_id',
                'product_id',
                'status',
            ]);
            $table->dropColumn('storage_id');
        });
    }
};