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
        Schema::table('role_permission', function (Blueprint $table) {

            // Thêm cột permission_id
            $table->unsignedBigInteger('permission_id')->after('role_id');

            // Tạo khóa ngoại
            $table->foreign('permission_id')
                  ->references('id')
                  ->on('permissions')
                  ->onDelete('cascade');

            // Mỗi role chỉ được gán một permission một lần
            $table->unique(['role_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_permission', function (Blueprint $table) {

            // Xóa unique trước
            $table->dropUnique(['role_id', 'permission_id']);

            // Xóa khóa ngoại
            $table->dropForeign(['permission_id']);

            // Xóa cột
            $table->dropColumn('permission_id');
        });
    }
};