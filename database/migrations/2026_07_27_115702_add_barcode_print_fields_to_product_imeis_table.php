<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->string('barcode', 50)
                ->nullable()
                ->unique()
                ->after('imei');

            $table->timestamp('printed_at')
                ->nullable()
                ->after('status');

            $table->unsignedInteger('print_count')
                ->default(0)
                ->after('printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn([
                'barcode',
                'printed_at',
                'print_count',
            ]);
        });
    }
};
