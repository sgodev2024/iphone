<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedDecimal('opening_debit', 20, 2)->default(0);
            $table->unsignedDecimal('opening_credit', 20, 2)->default(0);
            $table->date('source_through_date');
            $table->unsignedBigInteger('source_version')->default(0);
            $table->dateTime('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['owner_id', 'company_id', 'fiscal_year'],
                'supplier_debt_snapshots_business_unique'
            );
            $table->index(
                ['owner_id', 'fiscal_year', 'company_id'],
                'supplier_debt_snapshots_owner_year_company_index'
            );
            $table->index(
                ['company_id', 'fiscal_year'],
                'supplier_debt_snapshots_company_year_index'
            );
            $table->foreign('owner_id', 'supplier_debt_snapshots_owner_foreign')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('company_id', 'supplier_debt_snapshots_company_foreign')
                ->references('id')->on('companies')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE supplier_debt_yearly_snapshots '
                .'ADD CONSTRAINT supplier_debt_snapshots_debit_nonnegative CHECK (opening_debit >= 0), '
                .'ADD CONSTRAINT supplier_debt_snapshots_credit_nonnegative CHECK (opening_credit >= 0), '
                .'ADD CONSTRAINT supplier_debt_snapshots_single_nature CHECK (opening_debit = 0 OR opening_credit = 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_debt_yearly_snapshots');
    }
};
