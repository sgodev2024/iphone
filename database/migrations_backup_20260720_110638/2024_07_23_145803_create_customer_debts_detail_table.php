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
        Schema::create('customer_debts_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_debts_id');
            $table->string('content');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            // Thiết lập khóa ngoại
            $table->foreign('customer_debts_id')->references('id')->on('customer_debts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_debts_detail');
    }
};
