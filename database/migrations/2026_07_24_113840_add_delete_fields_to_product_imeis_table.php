<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->softDeletes();

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('delete_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['deleted_by', 'delete_reason', 'deleted_at']);
        });
    }
};
