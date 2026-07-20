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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique()->index();
            $table->string('password');
            $table->string('address')->nullable();
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('referrer_id')->nullable()->index();
            $table->string('phone')->unique()->index();
            $table->unsignedBigInteger('commission_id')->nullable();
            $table->foreign('commission_id')->references('id')->on('commissions');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending')->index();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
