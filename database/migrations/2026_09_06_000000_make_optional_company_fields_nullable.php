<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('tax_number')->nullable()->change();
            $table->string('bank_account')->nullable()->change();
            $table->unsignedBigInteger('bank_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
            $table->string('tax_number')->nullable(false)->change();
            $table->string('bank_account')->nullable(false)->change();
            $table->unsignedBigInteger('bank_id')->nullable(false)->change();
        });
    }
};
