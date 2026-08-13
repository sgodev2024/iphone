<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Client;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class CustomerDebtPaymentService
{
    public function collect(User $actor, array $data): array
    {
        $ownerId = $actor->ownerId();
        $payload = $this->normalizedPayload($data);
        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
        $idempotencyKey = (string) $data['idempotency_key'];

        try {
            return DB::transaction(function () use (
                $actor,
                $ownerId,
                $payload,
                $payloadHash,
                $idempotencyKey
            ): array {
                $order = Order::query()
                    ->whereKey($payload['order_id'])
                    ->where('user_id', $ownerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = Transaction::query()
                    ->where('user_id', $ownerId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return $this->replayResult($existing, $payloadHash, $order);
                }

                $client = $this->resolveOrderClient($order, $ownerId);
                $this->validatePaymentDate($order, $payload['transaction_date']);

                if ($payload['amount'] <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Số tiền thu phải lớn hơn 0.',
                    ]);
                }

                $receivableAccount = $this->resolveRequiredAccount('131', 'tài khoản phải thu khách hàng');
                $saleDebit = $this->validatedSaleDebit($order, $client, $receivableAccount);
                $paidBefore = $this->completedPaymentCredit($order, $receivableAccount);
                $remaining = $saleDebit - $paidBefore;

                if ($remaining <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Đơn hàng đã được thanh toán đủ.',
                    ]);
                }

                if ($payload['amount'] > $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => 'Số tiền thu không được lớn hơn công nợ còn lại.',
                    ]);
                }

                $moneyAccount = $payload['payment_method'] === Order::PAYMENT_METHOD_BANK_TRANSFER
                    ? $this->resolveBankAccount($payload['bank_account_id'])
                    : $this->resolveRequiredAccount('111', 'tài khoản tiền mặt');
                $transactionType = $payload['payment_method'] === Order::PAYMENT_METHOD_BANK_TRANSFER
                    ? 'credit_notice'
                    : 'income';
                $paymentNote = $payload['payment_method'] === Order::PAYMENT_METHOD_BANK_TRANSFER
                    ? 'Chuyển khoản'
                    : 'Tiền mặt';

                $transaction = Transaction::create([
                    'user_id' => $ownerId,
                    'transaction_date' => $payload['transaction_date'],
                    'description' => "Thu công nợ đơn hàng #{$order->id}",
                    'type' => $transactionType,
                    'document_type' => 'order',
                    'reference_number' => (string) $order->id,
                    'created_by' => $actor->id,
                    'status' => Transaction::STATUS_COMPLETED,
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_hash' => $payloadHash,
                ]);

                $transaction->entries()->create([
                    'account_id' => $moneyAccount->id,
                    'debit_amount' => $payload['amount'],
                    'credit_amount' => 0,
                    'note' => $paymentNote,
                ]);
                $transaction->entries()->create([
                    'account_id' => $receivableAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $payload['amount'],
                    'tableable_type' => Client::class,
                    'tableable_id' => $client->id,
                    'note' => $paymentNote,
                ]);

                $paidAfter = $this->completedPaymentCredit($order, $receivableAccount);
                $remainingAfter = $saleDebit - $paidAfter;
                $paymentStatus = $remainingAfter <= 0
                    ? Order::PAYMENT_STATUS_PAID
                    : ($paidAfter > 0 ? Order::PAYMENT_STATUS_PARTIAL : Order::PAYMENT_STATUS_DEBT);

                $order->forceFill([
                    'paid_amount' => $paidAfter,
                    'debt_amount' => $remainingAfter,
                    'payment_status' => $paymentStatus,
                ])->save();

                return $this->result($transaction, $order->fresh(), false);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = Transaction::query()
                ->where('user_id', $ownerId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $existing) {
                throw $exception;
            }

            $order = Order::query()
                ->whereKey($payload['order_id'])
                ->where('user_id', $ownerId)
                ->firstOrFail();

            return $this->replayResult($existing, $payloadHash, $order);
        }
    }

    public function outstandingOrders(User $actor, int $clientId): Collection
    {
        $ownerId = $actor->ownerId();
        $client = Client::withTrashed()
            ->whereKey($clientId)
            ->where('user_id', $ownerId)
            ->firstOrFail();
        $receivableAccount = $this->resolveRequiredAccount('131', 'tài khoản phải thu khách hàng');

        return Order::query()
            ->where('user_id', $ownerId)
            ->where('client_id', $client->id)
            ->orderBy('created_at')
            ->get()
            ->map(function (Order $order) use ($client, $receivableAccount): ?array {
                try {
                    $saleDebit = $this->validatedSaleDebit($order, $client, $receivableAccount);
                } catch (Throwable) {
                    return null;
                }

                $paid = $this->completedPaymentCredit($order, $receivableAccount);
                $remaining = $saleDebit - $paid;

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    'id' => (int) $order->id,
                    'code' => $order->code,
                    'transaction_date' => $order->created_at?->toDateString(),
                    'total_money' => $saleDebit,
                    'paid_amount' => $paid,
                    'remaining' => $remaining,
                ];
            })
            ->filter()
            ->values();
    }

    public function bankAccounts(): Collection
    {
        $parent = $this->resolveRequiredAccount('112', 'tài khoản ngân hàng');

        return Account::query()
            ->where('parent_id', $parent->id)
            ->where('status', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function normalizedPayload(array $data): array
    {
        $paymentMethod = (string) $data['payment_method'];

        return [
            'order_id' => (int) $data['order_id'],
            'amount' => (int) $data['amount'],
            'payment_method' => $paymentMethod,
            'bank_account_id' => $paymentMethod === Order::PAYMENT_METHOD_BANK_TRANSFER
                ? (int) ($data['bank_account_id'] ?? 0)
                : null,
            'transaction_date' => Carbon::createFromFormat('Y-m-d', (string) $data['transaction_date'])
                ->format('Y-m-d'),
        ];
    }

    private function resolveOrderClient(Order $order, int $ownerId): Client
    {
        if (! $order->client_id) {
            throw ValidationException::withMessages([
                'order_id' => 'Đơn không có khách hàng không thể phát sinh công nợ.',
            ]);
        }

        $client = Client::withTrashed()
            ->whereKey($order->client_id)
            ->where('user_id', $ownerId)
            ->first();

        if (! $client) {
            throw ValidationException::withMessages([
                'order_id' => 'Khách hàng của đơn không thuộc phạm vi chủ sở hữu hiện tại.',
            ]);
        }

        return $client;
    }

    private function validatePaymentDate(Order $order, string $transactionDate): void
    {
        $orderDate = $order->created_at?->toDateString();
        $today = now()->toDateString();

        if ($transactionDate > $today) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Ngày thu không được lớn hơn ngày hiện tại.',
            ]);
        }

        if ($orderDate && $transactionDate < $orderDate) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Ngày thu phải từ ngày tạo đơn đến ngày hiện tại.',
            ]);
        }
    }

    private function validatedSaleDebit(Order $order, Client $client, Account $receivableAccount): int
    {
        $revenueAccount = $this->resolveRequiredAccount('5111', 'tài khoản doanh thu bán hàng');
        $sales = Transaction::query()
            ->with('entries')
            ->where('user_id', $order->user_id)
            ->where('document_type', 'order')
            ->where('reference_number', (string) $order->id)
            ->where('type', 'sale')
            ->where('status', Transaction::STATUS_COMPLETED)
            ->get();

        if ($sales->count() !== 1) {
            throw ValidationException::withMessages([
                'order_id' => 'Đơn hàng phải có đúng một bút toán bán hàng completed trước khi thu nợ.',
            ]);
        }

        $sale = $sales->first();
        $entries = $sale->entries;
        $debit = (int) round($entries->sum('debit_amount'));
        $credit = (int) round($entries->sum('credit_amount'));
        $receivableDebit = (int) round($entries
            ->where('account_id', $receivableAccount->id)
            ->sum('debit_amount'));
        $clientReceivableDebit = (int) round($entries
            ->where('account_id', $receivableAccount->id)
            ->where('tableable_type', Client::class)
            ->where('tableable_id', $client->id)
            ->sum('debit_amount'));
        $revenueCredit = (int) round($entries
            ->where('account_id', $revenueAccount->id)
            ->sum('credit_amount'));

        if ($debit !== $credit
            || $receivableDebit !== (int) $order->total_money
            || $clientReceivableDebit !== $receivableDebit
            || $revenueCredit !== $receivableDebit
        ) {
            throw ValidationException::withMessages([
                'order_id' => 'Bút toán bán hàng không cân bằng hoặc không khớp tổng tiền/khách hàng của đơn.',
            ]);
        }

        return $receivableDebit;
    }

    private function completedPaymentCredit(Order $order, Account $receivableAccount): int
    {
        return (int) round(DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('t.user_id', $order->user_id)
            ->where('t.document_type', 'order')
            ->where('t.reference_number', (string) $order->id)
            ->where('t.status', Transaction::STATUS_COMPLETED)
            ->whereIn('t.type', ['income', 'credit_notice'])
            ->where('te.account_id', $receivableAccount->id)
            ->sum('te.credit_amount'));
    }

    private function resolveBankAccount(?int $bankAccountId): Account
    {
        $parent = $this->resolveRequiredAccount('112', 'tài khoản ngân hàng');
        $account = Account::query()
            ->whereKey($bankAccountId)
            ->where('parent_id', $parent->id)
            ->where('status', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Tài khoản ngân hàng phải là tài khoản đang hoạt động trực tiếp dưới 112.',
            ]);
        }

        return $account;
    }

    private function resolveRequiredAccount(string $code, string $label): Account
    {
        $account = Account::query()->where('code', $code)->where('status', true)->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'account' => "Không tìm thấy {$label} ({$code}) đang hoạt động.",
            ]);
        }

        return $account;
    }

    private function replayResult(Transaction $transaction, string $payloadHash, Order $order): array
    {
        if (! hash_equals((string) $transaction->idempotency_hash, $payloadHash)) {
            throw new ConflictHttpException('Idempotency key đã được dùng với payload khác.');
        }

        return $this->result($transaction, $order->fresh(), true);
    }

    private function result(Transaction $transaction, Order $order, bool $replayed): array
    {
        return [
            'transaction' => $transaction->fresh('entries'),
            'order' => $order,
            'remaining' => (int) $order->debt_amount,
            'replayed' => $replayed,
        ];
    }
}
