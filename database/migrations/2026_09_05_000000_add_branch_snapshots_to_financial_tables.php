<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('user_id')
                ->constrained('branches')->restrictOnDelete();
            $table->index(
                ['branch_id', 'status', 'transaction_date'],
                'transactions_branch_status_date_index'
            );
            $table->index(
                ['branch_id', 'document_type', 'reference_number', 'status'],
                'transactions_branch_document_reference_status_index'
            );
        });

        Schema::table('cash_vouchers', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->index(
                ['branch_id', 'transaction_date', 'id'],
                'cash_vouchers_branch_date_id_index'
            );
            $table->index(
                ['branch_id', 'accounting_status', 'transaction_date'],
                'cash_vouchers_branch_status_date_index'
            );
        });

        Schema::table('bank_vouchers', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->index(
                ['branch_id', 'transaction_date', 'id'],
                'bank_vouchers_branch_date_id_index'
            );
            $table->index(
                ['branch_id', 'accounting_status', 'transaction_date'],
                'bank_vouchers_branch_status_date_index'
            );
            $table->index(
                ['branch_id', 'bank_account_id', 'transaction_date'],
                'bank_vouchers_branch_account_date_index'
            );
        });

        Schema::table('customer_debt_collections', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->index(
                ['branch_id', 'client_id', 'collection_date'],
                'customer_debt_collections_branch_client_date_index'
            );
            $table->index(
                ['branch_id', 'status', 'collection_date'],
                'customer_debt_collections_branch_status_date_index'
            );
        });

        Schema::table('customer_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->dropUnique('customer_debt_snapshots_business_unique');
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->unique(
                ['owner_id', 'branch_id', 'client_id', 'fiscal_year'],
                'customer_debt_snapshots_branch_business_unique'
            );
            $table->index(
                ['branch_id', 'fiscal_year', 'client_id'],
                'customer_debt_snapshots_branch_year_client_index'
            );
        });

        Schema::table('customer_debt_snapshot_states', function (Blueprint $table): void {
            $table->dropUnique('customer_debt_snapshot_states_business_unique');
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->unique(
                ['owner_id', 'branch_id', 'client_id'],
                'customer_debt_states_branch_business_unique'
            );
            $table->index(
                ['branch_id', 'dirty_from_year', 'client_id'],
                'customer_debt_states_branch_dirty_client_index'
            );
        });

        Schema::table('supplier_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->dropUnique('supplier_debt_snapshots_business_unique');
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->unique(
                ['owner_id', 'branch_id', 'company_id', 'fiscal_year'],
                'supplier_debt_snapshots_branch_business_unique'
            );
            $table->index(
                ['branch_id', 'fiscal_year', 'company_id'],
                'supplier_debt_snapshots_branch_year_company_index'
            );
        });

        Schema::table('supplier_debt_snapshot_states', function (Blueprint $table): void {
            $table->dropUnique('supplier_debt_states_business_unique');
            $table->foreignId('branch_id')->nullable()->after('owner_id')
                ->constrained('branches')->restrictOnDelete();
            $table->unique(
                ['owner_id', 'branch_id', 'company_id'],
                'supplier_debt_states_branch_business_unique'
            );
            $table->index(
                ['branch_id', 'dirty_from_year', 'company_id'],
                'supplier_debt_states_branch_dirty_company_index'
            );
        });

        Schema::table('supplier_debts', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('companies_id')
                ->constrained('branches')->restrictOnDelete();
            $table->index(
                ['branch_id', 'companies_id', 'created_at'],
                'supplier_debts_branch_company_created_index'
            );
        });

        $this->backfillCustomerDebtCollections();
        $this->backfillOrderTransactions();
        $this->backfillImportTransactions();
        $this->backfillCollectionTransactions();
    }

    public function down(): void
    {
        Schema::table('supplier_debts', function (Blueprint $table): void {
            $table->dropIndex('supplier_debts_branch_company_created_index');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('supplier_debt_snapshot_states', function (Blueprint $table): void {
            $table->dropIndex('supplier_debt_states_branch_dirty_company_index');
            $table->dropUnique('supplier_debt_states_branch_business_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(
                ['owner_id', 'company_id'],
                'supplier_debt_states_business_unique'
            );
        });

        Schema::table('supplier_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->dropIndex('supplier_debt_snapshots_branch_year_company_index');
            $table->dropUnique('supplier_debt_snapshots_branch_business_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(
                ['owner_id', 'company_id', 'fiscal_year'],
                'supplier_debt_snapshots_business_unique'
            );
        });

        Schema::table('customer_debt_snapshot_states', function (Blueprint $table): void {
            $table->dropIndex('customer_debt_states_branch_dirty_client_index');
            $table->dropUnique('customer_debt_states_branch_business_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(
                ['owner_id', 'client_id'],
                'customer_debt_snapshot_states_business_unique'
            );
        });

        Schema::table('customer_debt_yearly_snapshots', function (Blueprint $table): void {
            $table->dropIndex('customer_debt_snapshots_branch_year_client_index');
            $table->dropUnique('customer_debt_snapshots_branch_business_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(
                ['owner_id', 'client_id', 'fiscal_year'],
                'customer_debt_snapshots_business_unique'
            );
        });

        Schema::table('customer_debt_collections', function (Blueprint $table): void {
            $table->dropIndex('customer_debt_collections_branch_status_date_index');
            $table->dropIndex('customer_debt_collections_branch_client_date_index');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('bank_vouchers', function (Blueprint $table): void {
            $table->dropIndex('bank_vouchers_branch_account_date_index');
            $table->dropIndex('bank_vouchers_branch_status_date_index');
            $table->dropIndex('bank_vouchers_branch_date_id_index');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('cash_vouchers', function (Blueprint $table): void {
            $table->dropIndex('cash_vouchers_branch_status_date_index');
            $table->dropIndex('cash_vouchers_branch_date_id_index');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_branch_document_reference_status_index');
            $table->dropIndex('transactions_branch_status_date_index');
            $table->dropConstrainedForeignId('branch_id');
        });
    }

    private function backfillOrderTransactions(): void
    {
        DB::table('transactions')
            ->whereNull('branch_id')
            ->where('document_type', 'order')
            ->whereNotNull('reference_number')
            ->orderBy('id')
            ->chunkById(500, function ($transactions): void {
                $orderIds = $transactions
                    ->map(fn (object $transaction): ?int => ctype_digit((string) $transaction->reference_number)
                        ? (int) $transaction->reference_number
                        : null)
                    ->filter()
                    ->unique()
                    ->values();
                $branches = DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->whereNotNull('branch_id')
                    ->pluck('branch_id', 'id');

                foreach ($transactions as $transaction) {
                    $orderId = ctype_digit((string) $transaction->reference_number)
                        ? (int) $transaction->reference_number
                        : null;
                    $branchId = $orderId === null ? null : $branches->get($orderId);

                    if ($branchId !== null) {
                        DB::table('transactions')->where('id', $transaction->id)
                            ->update(['branch_id' => (int) $branchId]);
                    }
                }
            }, 'id', 'id');
    }

    private function backfillImportTransactions(): void
    {
        DB::table('transactions')
            ->whereNull('branch_id')
            ->whereIn('document_type', ['import', 'import_payment'])
            ->whereNotNull('reference_number')
            ->orderBy('id')
            ->chunkById(500, function ($transactions): void {
                $importIds = $transactions
                    ->map(fn (object $transaction): ?int => $this->importCouponId(
                        (string) $transaction->reference_number
                    ))
                    ->filter()
                    ->unique()
                    ->values();
                $branches = DB::table('import_coupon as import')
                    ->join('storages as storage', 'storage.id', '=', 'import.storage_id')
                    ->whereIn('import.id', $importIds)
                    ->whereNotNull('storage.branch_id')
                    ->pluck('storage.branch_id', 'import.id');

                foreach ($transactions as $transaction) {
                    $importId = $this->importCouponId((string) $transaction->reference_number);
                    $branchId = $importId === null ? null : $branches->get($importId);

                    if ($branchId !== null) {
                        DB::table('transactions')->where('id', $transaction->id)
                            ->update(['branch_id' => (int) $branchId]);
                    }
                }
            }, 'id', 'id');
    }

    private function backfillCustomerDebtCollections(): void
    {
        DB::table('customer_debt_collections')
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(250, function ($collections): void {
                foreach ($collections as $collection) {
                    $clientBranchId = DB::table('clients')
                        ->where('id', $collection->client_id)
                        ->value('branch_id');

                    if ($clientBranchId === null) {
                        continue;
                    }

                    $orderBranches = DB::table('customer_debt_collection_allocations as allocation')
                        ->join('orders as orders', 'orders.id', '=', 'allocation.order_id')
                        ->where('allocation.collection_id', $collection->id)
                        ->pluck('orders.branch_id');

                    if ($orderBranches->isEmpty()
                        || $orderBranches->contains(null)
                        || $orderBranches->unique()->count() !== 1
                        || (int) $orderBranches->first() !== (int) $clientBranchId
                    ) {
                        continue;
                    }

                    DB::table('customer_debt_collections')->where('id', $collection->id)
                        ->update(['branch_id' => (int) $clientBranchId]);
                }
            }, 'id', 'id');
    }

    private function backfillCollectionTransactions(): void
    {
        DB::table('transactions')
            ->whereNull('branch_id')
            ->whereNotNull('collection_id')
            ->orderBy('id')
            ->chunkById(500, function ($transactions): void {
                $branches = DB::table('customer_debt_collections')
                    ->whereIn('id', $transactions->pluck('collection_id')->filter()->unique())
                    ->whereNotNull('branch_id')
                    ->pluck('branch_id', 'id');

                foreach ($transactions as $transaction) {
                    $branchId = $branches->get($transaction->collection_id);

                    if ($branchId !== null) {
                        DB::table('transactions')
                            ->where('id', $transaction->id)
                            ->update(['branch_id' => (int) $branchId]);
                    }
                }
            }, 'id', 'id');
    }

    private function importCouponId(string $reference): ?int
    {
        return preg_match('/^IMP-(\d+)(?:-PAY-.+)?$/', $reference, $matches) === 1
            ? (int) $matches[1]
            : null;
    }
};
