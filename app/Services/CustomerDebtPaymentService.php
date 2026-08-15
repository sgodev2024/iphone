<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Backward-compatible per-order adapter.
 *
 * CustomerDebtCollectionService is the only canonical public write flow. This
 * adapter keeps the Phase 7A endpoint working until its UI/routes are removed.
 */
class CustomerDebtPaymentService
{
    public function __construct(private readonly CustomerDebtCollectionService $collections) {}

    public function collect(User $actor, array $data): array
    {
        $ownerId = (int) $actor->ownerId();
        $order = Order::query()
            ->whereKey((int) ($data['order_id'] ?? 0))
            ->where('user_id', $ownerId)
            ->firstOrFail();

        if (! $order->client_id) {
            throw ValidationException::withMessages([
                'order_id' => 'Đơn không có khách hàng không thể phát sinh công nợ.',
            ]);
        }

        $result = $this->collections->collect($actor, [
            'client_id' => (int) $order->client_id,
            'amount' => $data['amount'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'money_account_id' => $data['bank_account_id'] ?? null,
            'collection_date' => $data['transaction_date'] ?? null,
            'note' => null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'expected_first_order_id' => (int) $order->id,
        ]);
        $allocation = $result['collection']->allocations->first();

        return [
            'transaction' => $allocation->paymentTransaction,
            'order' => $allocation->order->fresh(),
            'remaining' => (int) $allocation->order->fresh()->debt_amount,
            'replayed' => $result['replayed'],
            'collection' => $result['collection'],
        ];
    }

    public function outstandingOrders(User $actor, int $clientId): Collection
    {
        return $this->collections->preview($actor, $clientId)['items']
            ->map(fn (array $item): array => [
                'id' => $item['id'],
                'code' => $item['code'],
                'transaction_date' => $item['sale_date'],
                'total_money' => $this->wholeAmount($item['total']),
                'paid_amount' => $this->wholeAmount($item['paid']),
                'remaining' => $this->wholeAmount($item['remaining']),
            ]);
    }

    public function bankAccounts(): Collection
    {
        return $this->collections->bankAccounts();
    }

    private function wholeAmount(string $amount): int
    {
        return (int) substr($amount, 0, -3);
    }
}
