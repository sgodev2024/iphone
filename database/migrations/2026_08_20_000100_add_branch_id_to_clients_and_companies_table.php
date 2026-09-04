<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
