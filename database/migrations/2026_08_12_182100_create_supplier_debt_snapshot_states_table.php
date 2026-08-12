<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_debt_snapshot_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('ledger_version')->default(0);
            $table->unsignedSmallInteger('dirty_from_year')->nullable();
            $table->timestamps();

            $table->unique(
                ['owner_id', 'company_id'],
                'supplier_debt_states_business_unique'
            );
            $table->index(
                ['owner_id', 'dirty_from_year', 'company_id'],
                'supplier_debt_states_owner_dirty_company_index'
            );
            $table->foreign('owner_id', 'supplier_debt_states_owner_foreign')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('company_id', 'supplier_debt_states_company_foreign')
                ->references('id')->on('companies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_debt_snapshot_states');
    }
};
