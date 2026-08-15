<?php

namespace App\Services;

use App\Models\Order;
use App\Support\DecimalAmount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderPaymentHistoryService
{
    public function forOrder(Order $order): Collection
    {
        return DB::table('customer_debt_collection_allocations as allocation')
            ->join('customer_debt_collections as collection', 'collection.id', '=', 'allocation.collection_id')
            ->join('transactions as payment', 'payment.id', '=', 'allocation.payment_transaction_id')
            ->join('accounts as money_account', 'money_account.id', '=', 'collection.money_account_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'collection.created_by')
            ->where('allocation.order_id', $order->id)
            ->where('collection.owner_id', $order->user_id)
            ->where('collection.status', 'completed')
            ->whereColumn('payment.collection_id', 'collection.id')
            ->whereColumn('payment.user_id', 'collection.owner_id')
            ->where('payment.document_type', 'order')
            ->where('payment.reference_number', (string) $order->id)
            ->whereIn('payment.type', ['income', 'credit_notice'])
            ->where('payment.status', 'completed')
            ->select([
                'allocation.id',
                'allocation.collection_id',
                'allocation.payment_transaction_id',
                'allocation.allocated_amount',
                'allocation.remaining_after',
                'collection.collection_number',
                'collection.collection_date',
                'collection.payment_method',
                'collection.note',
                'money_account.code as account_code',
                'money_account.name as account_name',
                'payment.description as payment_description',
                'creator.name as creator_name',
            ])
            ->orderBy('collection.collection_date')
            ->orderBy('collection.id')
            ->orderBy('allocation.allocation_sequence')
            ->get()
            ->map(static function (object $payment): array {
                return [
                    'id' => (int) $payment->id,
                    'collection_id' => (int) $payment->collection_id,
                    'payment_transaction_id' => (int) $payment->payment_transaction_id,
                    'collection_number' => (string) $payment->collection_number,
                    'transaction_date' => $payment->collection_date
                        ? Carbon::parse($payment->collection_date)->format('d/m/Y')
                        : '',
                    'amount' => DecimalAmount::normalize((string) $payment->allocated_amount),
                    'remaining_after' => DecimalAmount::normalize((string) $payment->remaining_after),
                    'payment_method' => $payment->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản',
                    'account' => trim((string) $payment->account_code.' - '.(string) $payment->account_name, ' -'),
                    'creator_name' => $payment->creator_name ?: 'Không xác định',
                    'description' => (string) ($payment->note ?: $payment->payment_description ?: ''),
                ];
            });
    }
}
