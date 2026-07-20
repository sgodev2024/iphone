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
        Schema::table('config', function (Blueprint $table) {
            // Xóa các cột không cần thiết
            $table->dropColumn(['name', 'phone', 'email', 'address', 'tin']);

            // Thêm cột receiver
            $table->string('receiver');

            // Thêm cột user_id và thiết lập khóa ngoại
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config', function (Blueprint $table) {
            // Thêm lại các cột đã xóa
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tin')->nullable();

            // Xóa cột receiver
            $table->dropColumn('receiver');

            // Xóa khóa ngoại và cột user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
