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
        Schema::table('sgo_zns_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('oa_id')->nullable();

            $table->foreign('oa_id')
            ->references('id')
            ->on('sgo_zalo_oas')
            ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sgo_zns_message', function (Blueprint $table) {
            $table->dropForeign(['oa_id']);
            $table->dropColumn('oa_id');
        });
    }
};
