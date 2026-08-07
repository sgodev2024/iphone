<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('check_inventory', function (Blueprint $table) {
            $table->integer('tong_chenh_lech')->nullable();
            $table->integer('sl_tang')->nullable();
            $table->integer('sl_giam')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_inventory', function (Blueprint $table) {
            $table->dropColumn('tong_chenh_lech');
            $table->dropColumn('sl_tang');
            $table->dropColumn('sl_giam');
        });
    }
};
