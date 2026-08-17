<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionBusinessListService
{
    public function entries(array $ownerIds, Collection $moneyAccountIds, string $from, string $to): Collection
    {
        return DB::table('transactions as t')
            ->whereIn('t.user_id', $ownerIds)
            ->join('transaction_entries as te', 'te.transaction_id', '=', 't.id')
            ->join('accounts as ma', 'ma.id', '=', 'te.account_id')
            ->join('transaction_entries as te_contra', function ($join): void {
                $join->on('te_contra.transaction_id', '=', 't.id')
                    ->whereColumn('te_contra.id', '!=', 'te.id');
            })
            ->join('accounts as contra_acc', 'contra_acc.id', '=', 'te_contra.account_id')
            ->leftJoin('clients as c', function ($join): void {
                $join->on('c.id', '=', 'te_contra.tableable_id')
                    ->where('te_contra.tableable_type', 'App\\Models\\Client');
            })
            ->leftJoin('suppliers as s', function ($join): void {
                $join->on('s.id', '=', 'te_contra.tableable_id')
                    ->where('te_contra.tableable_type', 'App\\Models\\Supplier');
            })
            ->leftJoin('companies as company', function ($join): void {
                $join->on('company.id', '=', 'te_contra.tableable_id')
                    ->where('te_contra.tableable_type', 'App\\Models\\Company');
            })
            ->join('users as u', 'u.id', '=', 't.created_by')
            ->leftJoin('customer_debt_collections as collection', 'collection.id', '=', 't.collection_id')
            ->leftJoin('clients as collection_client', 'collection_client.id', '=', 'collection.client_id')
            ->where('t.type', '!=', 'other')
            ->whereIn('te.account_id', $moneyAccountIds)
            ->whereDate('t.transaction_date', '>=', $from)
            ->whereDate('t.transaction_date', '<=', $to)
            ->groupBy('t.collection_id')
            ->groupByRaw('CASE WHEN t.collection_id IS NULL THEN t.id ELSE 0 END')
            ->selectRaw('MIN(t.id) as id')
            ->selectRaw('MAX(t.collection_id) as collection_id')
            ->selectRaw('MAX(t.transaction_date) as transaction_date')
            ->selectRaw('COALESCE(MAX(collection.created_at), MAX(t.created_at)) as created_at')
            ->selectRaw('COALESCE(MAX(collection.collection_number), MAX(t.reference_number)) as business_number')
            ->selectRaw('MAX(t.reference_number) as reference_number')
            ->selectRaw('MAX(t.description) as description')
            ->selectRaw('MAX(t.document_type) as document_type')
            ->selectRaw('MAX(t.status) as status')
            ->selectRaw('COALESCE(MAX(collection.attachment), MAX(t.attachment)) as attachment')
            ->selectRaw('MAX(u.name) as creator_name')
            ->selectRaw('MAX(te.id) as entry_id')
            ->selectRaw('COALESCE(MAX(collection_client.name), MAX(c.name), MAX(s.name), MAX(company.name)) as related_party')
            ->selectRaw('COALESCE(MAX(collection_client.phone), MAX(c.phone), MAX(s.phone), MAX(company.phone)) as related_party_phone')
            ->selectRaw('MAX(te_contra.tableable_type) as related_party_type')
            ->selectRaw('MAX(te_contra.tableable_id) as related_party_id')
            ->selectRaw('MAX(ma.code) as account_code')
            ->selectRaw('MAX(ma.name) as account_name')
            ->selectRaw('MAX(contra_acc.code) as contra_code')
            ->selectRaw('MAX(contra_acc.name) as contra_name')
            ->selectRaw('SUM(te.debit_amount) as debit_amount')
            ->selectRaw('SUM(te.credit_amount) as credit_amount')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
