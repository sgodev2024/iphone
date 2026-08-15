<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_debt_collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('collection_number', 32);
            $table->date('collection_date');
            $table->string('payment_method', 20);
            $table->foreignId('money_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('total_amount', 20, 2);
            $table->string('note')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status', 20)->default('pending');
            $table->char('idempotency_key', 36);
            $table->char('idempotency_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'idempotency_key'], 'customer_debt_collections_owner_idempotency_unique');
            $table->unique(['owner_id', 'collection_number'], 'customer_debt_collections_owner_number_unique');
            $table->index(
                ['owner_id', 'client_id', 'collection_date'],
                'customer_debt_collections_owner_client_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_collections');
    }
};
