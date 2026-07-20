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
        Schema::table('sgo_campaigns', function (Blueprint $table) {
            $table->dropColumn('delay_date');
            $table->time('sent_time')->default('09:30'); // Sửa giá trị thời gian thành '09:30'
            $table->date('sent_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sgo_campaigns', function (Blueprint $table) {
            $table->integer('delay_date');
            $table->dropColumn('sent_time');
            $table->dropColumn('sent_date');
        });
    }
};
