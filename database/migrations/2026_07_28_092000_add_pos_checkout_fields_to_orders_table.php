<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('orders', 'discount_value')) {
                $table->unsignedBigInteger('discount_value')->default(0)->after('total_money');
            }

            if (! Schema::hasColumn('orders', 'discount_type')) {
                $table->string('discount_type', 20)->nullable()->after('discount_value');
            }

            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('discount_type');
            }

            if (! Schema::hasColumn('orders', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->default(0)->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'debt_amount')) {
                $table->unsignedBigInteger('debt_amount')->default(0)->after('paid_amount');
            }

            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 20)->default('paid')->after('debt_amount');
            }

            if (! Schema::hasColumn('orders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('note');
            }

            if (! Schema::hasColumn('orders', 'notification')) {
                $table->boolean('notification')->default(1)->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            foreach ([
                'email',
                'discount_value',
                'discount_type',
                'payment_method',
                'paid_amount',
                'debt_amount',
                'payment_status',
                'created_by',
                'notification',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
