<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_debts_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_debts_id');
            $table->string('content');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            // Thiết lập khóa ngoại
            $table->foreign('supplier_debts_id')->references('id')->on('supplier_debts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_debts_detail');
    }
};
