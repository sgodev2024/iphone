<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'status')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('status')->default(false)->after('note');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'status')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
