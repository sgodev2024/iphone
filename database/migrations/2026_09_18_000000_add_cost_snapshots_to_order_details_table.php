<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table): void {
            $table->decimal('cost_unit_snapshot', 20, 2)->nullable();
            $table->decimal('cost_total_snapshot', 20, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table): void {
            $table->dropColumn(['cost_unit_snapshot', 'cost_total_snapshot']);
        });
    }
};
