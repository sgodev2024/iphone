<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_debt_snapshot_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('ledger_version')->default(0);
            $table->unsignedSmallInteger('dirty_from_year')->nullable();
            $table->timestamps();

            $table->unique(
                ['owner_id', 'client_id'],
                'customer_debt_snapshot_states_business_unique'
            );
            $table->index(
                ['owner_id', 'dirty_from_year', 'client_id'],
                'customer_debt_states_owner_dirty_client_index'
            );
            $table->foreign('owner_id', 'customer_debt_states_owner_foreign')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('client_id', 'customer_debt_states_client_foreign')
                ->references('id')->on('clients')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_snapshot_states');
    }
};
