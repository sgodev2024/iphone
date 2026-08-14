<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderPaymentHistoryService
{
    public function forOrder(Order $order): Collection
    {
        return DB::table('transactions as t')
            ->join('transaction_entries as te', 'te.transaction_id', '=', 't.id')
            ->join('accounts as a', 'a.id', '=', 'te.account_id')
            ->leftJoin('accounts as parent_account', 'parent_account.id', '=', 'a.parent_id')
            ->leftJoin('users as creator', 'creator.id', '=', 't.created_by')
            ->where('t.user_id', $order->user_id)
            ->where('t.document_type', 'order')
            ->where('t.reference_number', (string) $order->id)
            ->whereIn('t.type', ['income', 'credit_notice'])
            ->where('t.status', Transaction::STATUS_COMPLETED)
            ->select([
                't.id',
                't.transaction_date',
                't.description',
                'creator.name as creator_name',
            ])
            ->selectRaw(
                'SUM(CASE WHEN a.code = ? THEN COALESCE(te.credit_amount, 0) ELSE 0 END) AS amount',
                ['131']
            )
            ->selectRaw(
                "CASE
                    WHEN SUM(CASE WHEN te.debit_amount > 0 AND a.code = ? THEN te.debit_amount ELSE 0 END) > 0
                        THEN ?
                    WHEN SUM(CASE WHEN te.debit_amount > 0 AND parent_account.code = ? THEN te.debit_amount ELSE 0 END) > 0
                        THEN ?
                    ELSE ''
                END AS payment_method",
                ['111', 'Tiền mặt', '112', 'Chuyển khoản']
            )
            ->groupBy([
                't.id',
                't.transaction_date',
                't.description',
                't.created_by',
                'creator.name',
            ])
            ->havingRaw(
                'SUM(COALESCE(te.debit_amount, 0)) = SUM(COALESCE(te.credit_amount, 0))'
            )
            ->havingRaw(
                'SUM(CASE WHEN a.code = ? THEN COALESCE(te.credit_amount, 0) ELSE 0 END) > 0',
                ['131']
            )
            ->havingRaw(
                "SUM(CASE
                    WHEN te.debit_amount > 0 AND (a.code = ? OR parent_account.code = ?)
                        THEN te.debit_amount
                    ELSE 0
                END) > 0",
                ['111', '112']
            )
            ->orderBy('t.transaction_date')
            ->orderBy('t.id')
            ->get()
            ->map(static function (object $payment): array {
                return [
                    'id' => (int) $payment->id,
                    'transaction_date' => $payment->transaction_date
                        ? Carbon::parse($payment->transaction_date)->format('d/m/Y')
                        : '',
                    'amount' => (int) round((float) $payment->amount),
                    'payment_method' => (string) $payment->payment_method,
                    'creator_name' => $payment->creator_name ?: 'Không xác định',
                    'description' => (string) ($payment->description ?? ''),
                ];
            });
    }
}
