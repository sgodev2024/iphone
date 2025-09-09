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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('company_name');
            $table->dropColumn('address');
            // Thêm cột company_id và thiết lập khóa ngoại
            $table->unsignedBigInteger('company_id')->after('id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers_tabled', function (Blueprint $table) {
            $table->string('company_name');
            $table->string('address');
            // Xóa cột company_id và khóa ngoại
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
