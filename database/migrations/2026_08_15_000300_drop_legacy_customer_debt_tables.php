<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('receipts_detail');
        Schema::dropIfExists('customer_debts_detail');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('customer_debts');
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_debts')) {
            Schema::create('customer_debts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->decimal('amount', 15, 2);
                $table->string('description')->nullable();
                $table->timestamps();
                $table->string('code')->unique()->nullable();

                $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('content');
                $table->decimal('amount_spent', 15, 2);
                $table->date('date_spent');
                $table->timestamps();
                $table->string('receipt_code')->unique()->nullable();
            });
        }

        if (! Schema::hasTable('customer_debts_detail')) {
            Schema::create('customer_debts_detail', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('customer_debts_id');
                $table->string('content');
                $table->decimal('amount', 15, 2);
                $table->timestamps();

                $table->foreign('customer_debts_id')
                    ->references('id')
                    ->on('customer_debts')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('receipts_detail')) {
            Schema::create('receipts_detail', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('receipt_id');
                $table->string('content');
                $table->decimal('amount', 10, 2);
                $table->date('date');
                $table->timestamps();

                $table->foreign('receipt_id')->references('id')->on('receipts')->cascadeOnDelete();
            });
        }
    }
};
