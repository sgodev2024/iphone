<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->char('idempotency_key', 36)->nullable()->after('created_by');
            $table->char('idempotency_hash', 64)->nullable()->after('idempotency_key');

            $table->unique(
                ['user_id', 'idempotency_key'],
                'transactions_user_id_idempotency_key_unique'
            );
            $table->index(
                ['document_type', 'reference_number', 'status'],
                'transactions_document_reference_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_document_reference_status_index');
            $table->dropUnique('transactions_user_id_idempotency_key_unique');
            $table->dropColumn(['idempotency_key', 'idempotency_hash']);
        });
    }
};
