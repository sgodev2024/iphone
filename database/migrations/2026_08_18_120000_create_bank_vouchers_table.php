<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('voucher_number', 32);
            $table->enum('direction', ['receipt', 'payment']);
            $table->enum('operation', ['generic_receipt', 'generic_payment']);
            $table->date('transaction_date');
            $table->foreignId('bank_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 20, 2);
            $table->string('document_type')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('description')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('accounting_status', ['pending_accounting'])->default('pending_accounting');
            $table->foreignId('counter_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('accounting_transaction_id')->nullable()->constrained('transactions')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'voucher_number'], 'bank_vouchers_owner_number_unique');
            $table->index(
                ['owner_id', 'transaction_date', 'id'],
                'bank_vouchers_owner_date_id_index'
            );
            $table->index(
                ['owner_id', 'accounting_status', 'transaction_date'],
                'bank_vouchers_owner_status_date_index'
            );
            $table->index(
                ['owner_id', 'created_at', 'id'],
                'bank_vouchers_owner_created_id_index'
            );
            $table->index(
                ['owner_id', 'bank_account_id', 'transaction_date'],
                'bank_vouchers_owner_account_date_index'
            );
            $table->unique('accounting_transaction_id', 'bank_vouchers_accounting_transaction_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_vouchers');
    }
};
