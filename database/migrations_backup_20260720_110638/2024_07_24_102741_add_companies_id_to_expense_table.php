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
        Schema::table('expense', function (Blueprint $table) {
            $table->unsignedBigInteger('companies_id')->after('id'); // Thêm cột companies_id
            $table->unsignedBigInteger('supplier_id')->nullable()->change(); // Cho phép supplier_id có thể null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense', function (Blueprint $table) {
            $table->dropColumn('companies_id');
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change(); // Trả lại cột supplier_id về trạng thái ban đầu
        });
    }
};
