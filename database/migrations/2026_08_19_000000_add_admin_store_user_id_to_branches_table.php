<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('admin_store_user_id')
                ->nullable()
                ->after('user_id')
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['admin_store_user_id']);
            $table->dropUnique(['admin_store_user_id']);
            $table->dropColumn('admin_store_user_id');
        });
    }
};
