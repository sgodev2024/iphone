<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL auto-commits CREATE TABLE before later ALTER statements. A failed
        // FK creation can therefore leave this migration pending with an empty,
        // partial table. It is safe to rebuild only while the migration is pending.
        if (Schema::hasTable('customer_debt_collection_allocations')) {
            Schema::drop('customer_debt_collection_allocations');
        }

        Schema::create('customer_debt_collection_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('order_id');
            $table->decimal('allocated_amount', 20, 2);
            $table->unsignedInteger('allocation_sequence');
            $table->decimal('remaining_after', 20, 2);
            $table->unsignedBigInteger('payment_transaction_id');
            $table->timestamps();

            $table->foreign('collection_id', 'cdca_collection_fk')
                ->references('id')->on('customer_debt_collections')->restrictOnDelete();
            $table->foreign('order_id', 'cdca_order_fk')
                ->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('payment_transaction_id', 'cdca_payment_transaction_fk')
                ->references('id')->on('transactions')->restrictOnDelete();

            $table->unique('payment_transaction_id', 'customer_debt_allocations_payment_transaction_unique');
            $table->unique(
                ['collection_id', 'allocation_sequence'],
                'customer_debt_allocations_collection_sequence_unique'
            );
            $table->index(['order_id', 'collection_id'], 'customer_debt_allocations_order_collection_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_collection_allocations');
    }
};
