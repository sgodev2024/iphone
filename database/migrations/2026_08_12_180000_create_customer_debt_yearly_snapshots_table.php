<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedDecimal('opening_debit', 20, 2)->default(0);
            $table->unsignedDecimal('opening_credit', 20, 2)->default(0);
            $table->date('source_through_date');
            $table->unsignedBigInteger('source_version')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['owner_id', 'client_id', 'fiscal_year'],
                'customer_debt_snapshots_business_unique'
            );
            $table->index(
                ['owner_id', 'fiscal_year', 'client_id'],
                'customer_debt_snapshots_owner_year_client_index'
            );
            $table->foreign('owner_id', 'customer_debt_snapshots_owner_foreign')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('client_id', 'customer_debt_snapshots_client_foreign')
                ->references('id')->on('clients')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE customer_debt_yearly_snapshots '
                .'ADD CONSTRAINT customer_debt_snapshots_debit_nonnegative CHECK (opening_debit >= 0), '
                .'ADD CONSTRAINT customer_debt_snapshots_credit_nonnegative CHECK (opening_credit >= 0), '
                .'ADD CONSTRAINT customer_debt_snapshots_single_nature CHECK (opening_debit = 0 OR opening_credit = 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_yearly_snapshots');
    }
};
