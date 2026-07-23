<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('name');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->bigInteger('level')->default(1);
                $table->unsignedBigInteger('status')->default(0);
                $table->unsignedInteger('parent_id')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique('code', 'cash_accounts_code_unique');
                $table->index('parent_id', 'cash_accounts__lft__rgt_parent_id_index');
                $table->index('created_by', 'cash_accounts_created_by_foreign');
                $table->foreign('created_by', 'accounts_ibfk_1')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        $this->restoreAccountingColumnsOnTransactions();

        if (! Schema::hasTable('transaction_entries')) {
            Schema::create('transaction_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id');
                $table->unsignedBigInteger('account_id');
                $table->decimal('debit_amount', 15, 2)->nullable()->default(0);
                $table->decimal('credit_amount', 15, 2)->nullable()->default(0);
                $table->string('tableable_type')->nullable();
                $table->unsignedBigInteger('tableable_id')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();

                $table->foreign('transaction_id')
                    ->references('id')
                    ->on('transactions')
                    ->cascadeOnDelete();
                $table->foreign('account_id')
                    ->references('id')
                    ->on('accounts');
                $table->index(['tableable_type', 'tableable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
        Schema::dropIfExists('accounts');

        if (Schema::hasTable('transactions')) {
            if (Schema::hasColumn('transactions', 'created_by')) {
                try {
                    Schema::table('transactions', function (Blueprint $table) {
                        $table->dropForeign('transactions_created_by_foreign');
                    });
                } catch (Throwable) {
                }
            }

            Schema::table('transactions', function (Blueprint $table) {
                foreach ([
                    'transaction_date',
                    'description',
                    'reference_number',
                    'type',
                    'document_type',
                    'attachment',
                    'created_by',
                ] as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function restoreAccountingColumnsOnTransactions(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'transaction_date')) {
                $table->date('transaction_date')->nullable();
            }

            if (! Schema::hasColumn('transactions', 'description')) {
                $table->string('description')->nullable();
            }

            if (! Schema::hasColumn('transactions', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }

            if (! Schema::hasColumn('transactions', 'type')) {
                $table->enum('type', ['income', 'expense', 'other', 'debit_notice', 'credit_notice'])
                    ->default('other');
            }

            if (! Schema::hasColumn('transactions', 'document_type')) {
                $table->string('document_type')->nullable();
            }

            if (! Schema::hasColumn('transactions', 'attachment')) {
                $table->string('attachment')->nullable();
            }

            if (! Schema::hasColumn('transactions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by', 'transactions_created_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }
};
