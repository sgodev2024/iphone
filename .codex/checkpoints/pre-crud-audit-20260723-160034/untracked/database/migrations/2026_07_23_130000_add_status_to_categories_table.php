<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories') || Schema::hasColumn('categories', 'status')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('status')->default(true);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'status')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
