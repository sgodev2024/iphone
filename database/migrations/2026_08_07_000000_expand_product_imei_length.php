<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_imeis') || ! Schema::hasColumn('product_imeis', 'imei')) {
            return;
        }

        Schema::table('product_imeis', function (Blueprint $table): void {
            $table->string('imei', 50)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_imeis') || ! Schema::hasColumn('product_imeis', 'imei')) {
            return;
        }

        Schema::table('product_imeis', function (Blueprint $table): void {
            $table->string('imei', 15)->change();
        });
    }
};
