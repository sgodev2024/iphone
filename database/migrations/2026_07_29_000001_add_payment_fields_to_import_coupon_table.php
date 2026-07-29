<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_coupon')) {
            return;
        }

        $hasPaymentNcc = Schema::hasColumn('import_coupon', 'payment_ncc');

        Schema::table('import_coupon', function (Blueprint $table) use ($hasPaymentNcc) {
            if (! Schema::hasColumn('import_coupon', 'payment_method')) {
                $column = $table->string('payment_method', 30)->nullable();
                $hasPaymentNcc ? $column->after('payment_ncc') : $column->after('total');
            }

            if (! Schema::hasColumn('import_coupon', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->default(0)->after('payment_method');
            }

            if (! Schema::hasColumn('import_coupon', 'debt_amount')) {
                $table->unsignedBigInteger('debt_amount')->default(0)->after('paid_amount');
            }

            if (! Schema::hasColumn('import_coupon', 'payment_status')) {
                $table->string('payment_status', 20)->nullable()->after('debt_amount');
            }
        });

        DB::table('import_coupon')
            ->select(['id', 'total'])
            ->when($hasPaymentNcc, fn ($query) => $query->addSelect('payment_ncc'))
            ->orderBy('id')
            ->chunkById(100, function ($imports) use ($hasPaymentNcc) {
                foreach ($imports as $import) {
                    $total = max((int) ($import->total ?? 0), 0);
                    $paidAmount = max((int) ($hasPaymentNcc ? ($import->payment_ncc ?? 0) : 0), 0);
                    $paidAmount = min($paidAmount, $total);
                    $debtAmount = max($total - $paidAmount, 0);

                    DB::table('import_coupon')
                        ->where('id', $import->id)
                        ->update([
                            'paid_amount' => $paidAmount,
                            'debt_amount' => $debtAmount,
                            'payment_status' => $this->resolvePaymentStatus($total, $paidAmount),
                            'payment_method' => $paidAmount === 0 && $debtAmount > 0 ? 'debt' : null,
                        ]);
                }
            }, 'id');

        $this->ensureDefaultAccountingAccounts();
    }

    public function down(): void
    {
        if (! Schema::hasTable('import_coupon')) {
            return;
        }

        Schema::table('import_coupon', function (Blueprint $table) {
            foreach (['payment_status', 'debt_amount', 'paid_amount', 'payment_method'] as $column) {
                if (Schema::hasColumn('import_coupon', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function resolvePaymentStatus(int $total, int $paidAmount): string
    {
        if ($total > 0 && $paidAmount >= $total) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    private function ensureDefaultAccountingAccounts(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        foreach ([
            '111' => 'Tiền mặt',
            '112' => 'Tiền gửi ngân hàng',
            '156' => 'Hàng hóa',
            '331' => 'Phải trả nhà cung cấp',
        ] as $code => $name) {
            if (DB::table('accounts')->where('code', $code)->exists()) {
                continue;
            }

            $data = [
                'code' => $code,
                'name' => $name,
            ];

            foreach ([
                'level' => 1,
                'status' => 1,
                'is_default' => 1,
            ] as $column => $value) {
                if (Schema::hasColumn('accounts', $column)) {
                    $data[$column] = $value;
                }
            }

            if (Schema::hasColumn('accounts', 'created_at')) {
                $data['created_at'] = now();
            }

            if (Schema::hasColumn('accounts', 'updated_at')) {
                $data['updated_at'] = now();
            }

            DB::table('accounts')->insert($data);
        }
    }
};
