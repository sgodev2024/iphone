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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('store_name')->nullable();
            $table->unsignedBigInteger('field_id')->nullable();

            $table->foreign('field_id')->references('id')->on('fields');

            // Xóa các cột không cần thiết
            $table->dropColumn('referral_code');
            $table->dropColumn('referrer_id');
            $table->dropColumn('commission_id');
            $table->dropColumn('district_id');
            $table->dropColumn('ward_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_name');
            $table->dropColumn('tax_code');
            $table->dropColumn('store_name');
            $table->dropForeign(['field_id']);
            $table->dropColumn('field_id');

            // Thêm lại các cột đã xóa trong phương thức up
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->unsignedBigInteger('commission_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('ward_id')->nullable();
        });
    }
};
