<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Client;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerSaleBackfillService
{
    public const EXPECTED_OWNER_ID = 23;

    public const EXPECTED_SALE_TOTAL = 75951110;

    public const EXPECTED_PAYMENT_TOTAL = 73951110;

    public const APPROVED_ORDER_IDS = [
        46, 47, 48, 49, 50,
        51, 52, 53, 54, 55,
        56, 57, 58, 59, 60,
    ];

    public const PHASE_3E_EXPECTED_OWNER_ID = 28;

    public const PHASE_3E_EXPECTED_SALE_TOTAL = 48555555;

    public const PHASE_3E_APPROVED_ORDER_IDS = [
        30, 31, 33, 35, 37, 38, 39, 40, 41, 42, 45,
    ];

    private const BATCHES = [
        'phase_3b' => [
            'label' => 'Phase 3B',
            'owner_id' => self::EXPECTED_OWNER_ID,
            'sale_total' => self::EXPECTED_SALE_TOTAL,
            'sale_created_by' => self::EXPECTED_OWNER_ID,
            'order_created_by' => self::EXPECTED_OWNER_ID,
            'payment_created_by' => self::EXPECTED_OWNER_ID,
            'payment_metrics' => [
                'count' => 14,
                'credit_131' => self::EXPECTED_PAYMENT_TOTAL,
                'debit_111' => 73151110,
                'debit_112' => 800000,
            ],
            'require_order_detail' => false,
            'orders' => [
                46 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                47 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                48 => ['client_id' => 11, 'total' => 9500000, 'paid' => 9500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                49 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                50 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                51 => ['client_id' => 11, 'total' => 25555555, 'paid' => 25555555, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                52 => ['client_id' => null, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                53 => ['client_id' => null, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                54 => ['client_id' => 11, 'total' => 2000000, 'paid' => 0, 'debt' => 2000000, 'method' => 'debt', 'payment_status' => 'debt', 'payment_type' => null],
                55 => ['client_id' => 12, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                56 => ['client_id' => 12, 'total' => 25555555, 'paid' => 25555555, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                57 => ['client_id' => 11, 'total' => 900000, 'paid' => 900000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                58 => ['client_id' => 12, 'total' => 600000, 'paid' => 600000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                59 => ['client_id' => 11, 'total' => 540000, 'paid' => 540000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid', 'payment_type' => 'income'],
                60 => ['client_id' => 11, 'total' => 800000, 'paid' => 800000, 'debt' => 0, 'method' => 'bank_transfer', 'payment_status' => 'paid', 'payment_type' => 'credit_notice'],
            ],
        ],
        'phase_3e' => [
            'label' => 'Phase 3E',
            'owner_id' => self::PHASE_3E_EXPECTED_OWNER_ID,
            'sale_total' => self::PHASE_3E_EXPECTED_SALE_TOTAL,
            'sale_created_by' => 30,
            'order_created_by' => null,
            'payment_created_by' => 30,
            'payment_metrics' => [
                'count' => 11,
                'credit_131' => self::PHASE_3E_EXPECTED_SALE_TOTAL,
                'debit_111' => 17000000,
                'debit_112' => 31555555,
            ],
            'require_order_detail' => true,
            'orders' => [
                30 => ['client_id' => 10, 'total' => 8000000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-28 14:55:32'],
                31 => ['client_id' => 10, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-28 15:00:39'],
                33 => ['client_id' => 10, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-28 15:05:02'],
                35 => ['client_id' => 9, 'total' => 25555555, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'credit_notice', 'created_at' => '2026-07-28 15:52:40'],
                37 => ['client_id' => 10, 'total' => 3000000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'credit_notice', 'created_at' => '2026-07-28 16:01:14'],
                38 => ['client_id' => 10, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-28 16:01:42'],
                39 => ['client_id' => 10, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'credit_notice', 'created_at' => '2026-07-29 09:56:15'],
                40 => ['client_id' => 10, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'credit_notice', 'created_at' => '2026-07-29 10:04:00'],
                41 => ['client_id' => 10, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-29 10:04:13'],
                42 => ['client_id' => 9, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-29 10:04:25'],
                45 => ['client_id' => 9, 'total' => 1500000, 'paid' => 0, 'debt' => 0, 'method' => null, 'payment_status' => 'paid', 'payment_type' => 'income', 'created_at' => '2026-07-29 14:29:59'],
            ],
        ],
    ];

    public function preview(array $orderIds): array
    {
        return $this->analyze($orderIds, false);
    }

    public function execute(array $orderIds): array
    {
        return DB::transaction(function () use ($orderIds): array {
            $analysis = $this->analyze($orderIds, true);
            $paymentBefore = $analysis['payment_metrics'];

            if ($analysis['state'] === 'already') {
                $analysis['execution'] = [
                    'created' => 0,
                    'transaction_ids' => [],
                    'payment_before' => $paymentBefore,
                    'payment_after' => $paymentBefore,
                ];

                return $analysis;
            }

            $created = [];
            $receivableAccount = $analysis['accounts']['131'];
            $revenueAccount = $analysis['accounts']['5111'];

            foreach ($analysis['orders'] as $order) {
                $transaction = Transaction::create([
                    'user_id' => (int) $order->user_id,
                    'transaction_date' => $order->created_at->toDateString(),
                    'description' => "Bán hàng theo đơn #{$order->id}",
                    'type' => 'sale',
                    'document_type' => 'order',
                    'reference_number' => (string) $order->id,
                    'created_by' => $analysis['batch']['sale_created_by'],
                ]);

                $transaction->entries()->create([
                    'account_id' => $receivableAccount->id,
                    'debit_amount' => $order->total_money,
                    'credit_amount' => 0,
                    'tableable_type' => $order->client_id ? Client::class : null,
                    'tableable_id' => $order->client_id,
                    'note' => 'Ghi nhận phải thu đơn hàng',
                ]);
                $transaction->entries()->create([
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $order->total_money,
                    'note' => 'Doanh thu bán hàng',
                ]);

                $created[(int) $order->id] = (int) $transaction->id;
            }

            $postValidation = $this->analyze($orderIds, false);
            $paymentAfter = $postValidation['payment_metrics'];

            if ($postValidation['state'] !== 'already'
                || count($created) !== count($analysis['batch']['orders'])
            ) {
                throw new RuntimeException('Post-validation failed: the complete sale batch was not created.');
            }

            if ($paymentBefore !== $paymentAfter) {
                throw new RuntimeException('Post-validation failed: existing payment accounting changed.');
            }

            $postValidation['execution'] = [
                'created' => count($created),
                'transaction_ids' => $created,
                'payment_before' => $paymentBefore,
                'payment_after' => $paymentAfter,
            ];

            return $postValidation;
        }, 3);
    }

    private function analyze(array $orderIds, bool $lockOrders): array
    {
        [$orderIds, $batchKey, $batch] = $this->validateRequestedOrderIds($orderIds);
        $accounts = $this->resolveAccounts();
        $ordersQuery = Order::query()->whereIn('id', $orderIds)->orderBy('id');

        if ($lockOrders) {
            $ordersQuery->lockForUpdate();
        }

        $orders = $ordersQuery->get();
        $errors = $this->validateOrders($orders, $batch);
        $references = array_map('strval', $orderIds);
        $saleTransactions = $this->transactionsForReferences($references, ['sale']);
        $paymentTransactions = $this->transactionsForReferences($references, ['income', 'credit_notice']);
        $rows = [];
        $saleStates = [];

        foreach ($orders as $order) {
            $expected = $batch['orders'][(int) $order->id];
            $orderSales = $saleTransactions->get((string) $order->id, collect());
            $orderPayments = $paymentTransactions->get((string) $order->id, collect());
            $saleState = 'missing';

            if ($orderSales->count() > 1) {
                $errors[] = "Order #{$order->id} has duplicate sale transactions.";
                $saleState = 'invalid';
            } elseif ($orderSales->count() === 1) {
                $saleErrors = $this->validateSaleTransaction(
                    $orderSales->first(),
                    $order,
                    $accounts['131'],
                    $accounts['5111'],
                    $batch
                );
                $saleState = $saleErrors === [] ? 'valid' : 'invalid';
                array_push($errors, ...$saleErrors);
            }

            $paymentErrors = $this->validatePaymentTransactions(
                $orderPayments,
                $order,
                $accounts,
                $expected,
                $batch
            );
            array_push($errors, ...$paymentErrors);
            $saleStates[(int) $order->id] = $saleState;
            $payment = $orderPayments->first();
            $rows[] = [
                'order_id' => (int) $order->id,
                'owner_id' => (int) $order->user_id,
                'client_id' => $order->client_id !== null ? (int) $order->client_id : null,
                'total' => (int) $order->total_money,
                'paid' => (int) $order->paid_amount,
                'debt' => (int) $order->debt_amount,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'created_at' => $order->created_at?->toDateTimeString(),
                'sale_transaction_id' => $orderSales->count() === 1 ? (int) $orderSales->first()->id : null,
                'payment_transaction_ids' => $orderPayments->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                'payment_evidence' => $payment
                    ? strtoupper($expected['payment_type']).' tx #'.$payment->id
                    : 'None',
                'sale_state' => $saleState,
                'transaction_date' => $order->created_at?->toDateString(),
            ];
        }

        $paymentMetrics = $this->paymentMetrics($paymentTransactions->flatten(1), $accounts);

        foreach ($batch['payment_metrics'] as $metric => $expectedAmount) {
            if ($paymentMetrics[$metric] !== $expectedAmount) {
                $errors[] = "Expected payment {$metric} {$expectedAmount}, found {$paymentMetrics[$metric]}.";
            }
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(PHP_EOL, array_unique($errors)));
        }

        $expectedCount = count($batch['orders']);
        $missingCount = collect($saleStates)->filter(fn (string $state): bool => $state === 'missing')->count();
        $validCount = collect($saleStates)->filter(fn (string $state): bool => $state === 'valid')->count();

        if ($missingCount === $expectedCount) {
            $state = 'missing';
        } elseif ($validCount === $expectedCount) {
            $state = 'already';
        } else {
            throw new RuntimeException(
                "Mixed batch state: {$validCount} already backfilled and {$missingCount} missing. Manual review required."
            );
        }

        return [
            'batch_key' => $batchKey,
            'batch' => $batch,
            'state' => $state,
            'orders' => $orders,
            'accounts' => $accounts,
            'rows' => $rows,
            'order_count' => $orders->count(),
            'sale_total' => (int) $orders->sum('total_money'),
            'planned_debit_131' => $state === 'missing' ? $batch['sale_total'] : 0,
            'planned_credit_5111' => $state === 'missing' ? $batch['sale_total'] : 0,
            'planned_credit_131' => 0,
            'planned_debit_111_112' => 0,
            'payment_metrics' => $paymentMetrics,
        ];
    }

    private function transactionsForReferences(array $references, array $types): Collection
    {
        return Transaction::query()
            ->with('entries')
            ->where('document_type', 'order')
            ->whereIn('reference_number', $references)
            ->whereIn('type', $types)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Transaction $transaction): string => (string) $transaction->reference_number);
    }

    private function validateRequestedOrderIds(array $orderIds): array
    {
        $normalized = collect($orderIds)
            ->map(function ($id): int {
                if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
                    throw new RuntimeException('Every --orders value must be a positive integer.');
                }

                return (int) $id;
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach (self::BATCHES as $batchKey => $batch) {
            if ($normalized === array_keys($batch['orders'])) {
                return [$normalized, $batchKey, $batch];
            }
        }

        throw new RuntimeException(
            'The requested orders must exactly match an explicit approved batch. Approved batches: '
            .collect(self::BATCHES)
                ->map(fn (array $batch): string => $batch['label'].'='.implode(',', array_keys($batch['orders'])))
                ->implode('; ').'.'
        );
    }

    private function resolveAccounts(): array
    {
        $accounts = Account::query()->whereIn('code', ['111', '112', '131', '5111'])->get()->keyBy('code');

        foreach (['111', '112', '131', '5111'] as $code) {
            $account = $accounts->get($code);

            if (! $account || ! (bool) $account->status) {
                throw new RuntimeException("Required active account {$code} was not found.");
            }
        }

        $bankAccounts = Account::query()
            ->where('parent_id', $accounts['112']->id)
            ->where('status', true)
            ->get();

        if ($bankAccounts->isEmpty()) {
            throw new RuntimeException('No active bank account exists below account 112.');
        }

        return [
            '111' => $accounts['111'],
            '112' => $accounts['112'],
            '131' => $accounts['131'],
            '5111' => $accounts['5111'],
            'bank_ids' => $bankAccounts->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        ];
    }

    private function validateOrders(Collection $orders, array $batch): array
    {
        $errors = [];
        $expectedCount = count($batch['orders']);

        if ($orders->count() !== $expectedCount) {
            $errors[] = "Expected {$expectedCount} approved orders, found {$orders->count()}.";
        }

        $ordersById = $orders->keyBy('id');
        $clientIds = collect($batch['orders'])->pluck('client_id')->filter()->unique()->values();
        $clients = Client::withTrashed()->whereIn('id', $clientIds)->get()->keyBy('id');
        $detailCounts = $batch['require_order_detail']
            ? DB::table('order_details')
                ->whereIn('order_id', array_keys($batch['orders']))
                ->selectRaw('order_id, COUNT(*) as detail_count')
                ->groupBy('order_id')
                ->pluck('detail_count', 'order_id')
            : collect();

        foreach ($batch['orders'] as $orderId => $expected) {
            $order = $ordersById->get($orderId);

            if (! $order) {
                $errors[] = "Approved order #{$orderId} does not exist.";

                continue;
            }

            $actualClientId = $order->client_id !== null ? (int) $order->client_id : null;
            $checks = [
                'user_id' => [(int) $order->user_id, $batch['owner_id']],
                'client_id' => [$actualClientId, $expected['client_id']],
                'total_money' => [(int) $order->total_money, $expected['total']],
                'paid_amount' => [(int) $order->paid_amount, $expected['paid']],
                'debt_amount' => [(int) $order->debt_amount, $expected['debt']],
                'payment_method' => [$order->payment_method, $expected['method']],
                'payment_status' => [(string) $order->payment_status, $expected['payment_status']],
                'status' => [(int) $order->status, 1],
                'created_by' => [
                    $order->created_by !== null ? (int) $order->created_by : null,
                    $batch['order_created_by'],
                ],
            ];

            if (array_key_exists('created_at', $expected)) {
                $checks['created_at'] = [$order->created_at?->toDateTimeString(), $expected['created_at']];
            }

            foreach ($checks as $field => [$actual, $approved]) {
                if ($actual !== $approved) {
                    $errors[] = "Order #{$orderId} {$field} changed: expected "
                        .var_export($approved, true).', found '.var_export($actual, true).'.';
                }
            }

            if (! $order->created_at) {
                $errors[] = "Order #{$orderId} has no historical created_at date.";
            }

            $client = $expected['client_id'] !== null ? $clients->get($expected['client_id']) : null;

            if ($expected['client_id'] !== null && ! $client) {
                $errors[] = "Order #{$orderId} client #{$expected['client_id']} does not exist.";
            } elseif ($client && (int) $client->user_id !== $batch['owner_id']) {
                $errors[] = "Order #{$orderId} client belongs to the wrong owner.";
            }

            if ($batch['require_order_detail'] && (int) ($detailCounts[$orderId] ?? 0) < 1) {
                $errors[] = "Order #{$orderId} no longer has an order detail.";
            }
        }

        if ((int) $orders->sum('total_money') !== $batch['sale_total']) {
            $errors[] = "Expected approved total {$batch['sale_total']}, found ".(int) $orders->sum('total_money').'.';
        }

        return $errors;
    }

    private function validateSaleTransaction(
        Transaction $transaction,
        Order $order,
        Account $receivableAccount,
        Account $revenueAccount,
        array $batch
    ): array {
        $errors = [];
        $prefix = "Sale transaction #{$transaction->id} for order #{$order->id}";

        if ((int) $transaction->user_id !== (int) $order->user_id
            || (int) $transaction->created_by !== $batch['sale_created_by']
            || $transaction->transaction_date?->toDateString() !== $order->created_at?->toDateString()
            || (string) $transaction->description !== "Bán hàng theo đơn #{$order->id}"
            || (string) $transaction->status !== 'pending'
        ) {
            $errors[] = "{$prefix} has invalid metadata.";
        }

        if ($transaction->entries->count() !== 2) {
            $errors[] = "{$prefix} must have exactly two entries.";

            return $errors;
        }

        $receivableEntries = $transaction->entries->where('account_id', $receivableAccount->id);
        $revenueEntries = $transaction->entries->where('account_id', $revenueAccount->id);
        $expectedType = $order->client_id ? Client::class : null;
        $receivable = $receivableEntries->first();
        $revenue = $revenueEntries->first();

        if ($receivableEntries->count() !== 1
            || ! $receivable
            || ! $this->amountEquals($receivable->debit_amount, $order->total_money)
            || ! $this->amountEquals($receivable->credit_amount, 0)
            || $receivable->tableable_type !== $expectedType
            || ($receivable->tableable_id !== null ? (int) $receivable->tableable_id : null)
                !== ($order->client_id !== null ? (int) $order->client_id : null)
        ) {
            $errors[] = "{$prefix} has an invalid Debit 131 entry.";
        }

        if ($revenueEntries->count() !== 1
            || ! $revenue
            || ! $this->amountEquals($revenue->debit_amount, 0)
            || ! $this->amountEquals($revenue->credit_amount, $order->total_money)
            || $revenue->tableable_type !== null
            || $revenue->tableable_id !== null
        ) {
            $errors[] = "{$prefix} has an invalid Credit 5111 entry.";
        }

        if (! $this->amountEquals($transaction->entries->sum('debit_amount'), $order->total_money)
            || ! $this->amountEquals($transaction->entries->sum('credit_amount'), $order->total_money)
        ) {
            $errors[] = "{$prefix} is not balanced.";
        }

        return $errors;
    }

    private function validatePaymentTransactions(
        Collection $transactions,
        Order $order,
        array $accounts,
        array $expected,
        array $batch
    ): array {
        $errors = [];
        $expectedType = $expected['payment_type'];

        if ($expectedType === null) {
            return $transactions->isEmpty()
                ? []
                : ["Order #{$order->id} must not have a payment transaction."];
        }

        if ($transactions->count() !== 1) {
            return ["Order #{$order->id} must have exactly one existing payment transaction."];
        }

        $transaction = $transactions->first();
        $moneyAccountIds = $expectedType === 'credit_notice'
            ? $accounts['bank_ids']
            : [(int) $accounts['111']->id];
        $paymentAmount = $expected['total'];

        if ($transaction->type !== $expectedType
            || (int) $transaction->user_id !== $batch['owner_id']
            || (int) $transaction->created_by !== $batch['payment_created_by']
            || $transaction->transaction_date?->toDateString() !== $order->created_at?->toDateString()
            || $transaction->created_at?->toDateTimeString() !== $order->created_at?->toDateTimeString()
            || (string) $transaction->description !== "Thu tiền đơn hàng #{$order->id}"
            || (string) $transaction->status !== 'pending'
        ) {
            $errors[] = "Order #{$order->id} existing payment metadata is invalid.";
        }

        if ($transaction->entries->count() !== 2) {
            $errors[] = "Order #{$order->id} existing payment must have exactly two entries.";

            return $errors;
        }

        $receivableEntries = $transaction->entries->where('account_id', $accounts['131']->id);
        $moneyEntries = $transaction->entries->filter(
            fn ($entry): bool => in_array((int) $entry->account_id, $moneyAccountIds, true)
        );
        $receivable = $receivableEntries->first();
        $money = $moneyEntries->first();
        $expectedClientType = $order->client_id ? Client::class : null;

        if ($receivableEntries->count() !== 1
            || ! $receivable
            || ! $this->amountEquals($receivable->debit_amount, 0)
            || ! $this->amountEquals($receivable->credit_amount, $paymentAmount)
            || $receivable->tableable_type !== $expectedClientType
            || ($receivable->tableable_id !== null ? (int) $receivable->tableable_id : null)
                !== ($order->client_id !== null ? (int) $order->client_id : null)
        ) {
            $errors[] = "Order #{$order->id} existing payment Credit 131 is invalid.";
        }

        if ($moneyEntries->count() !== 1
            || ! $money
            || ! $this->amountEquals($money->debit_amount, $paymentAmount)
            || ! $this->amountEquals($money->credit_amount, 0)
        ) {
            $errors[] = "Order #{$order->id} existing payment money debit is invalid.";
        }

        if (! $this->amountEquals($transaction->entries->sum('debit_amount'), $paymentAmount)
            || ! $this->amountEquals($transaction->entries->sum('credit_amount'), $paymentAmount)
        ) {
            $errors[] = "Order #{$order->id} existing payment is not balanced.";
        }

        return $errors;
    }

    private function paymentMetrics(Collection $transactions, array $accounts): array
    {
        $entries = $transactions->flatMap->entries;

        return [
            'count' => $transactions->count(),
            'credit_131' => (int) $entries->where('account_id', $accounts['131']->id)->sum('credit_amount'),
            'debit_111' => (int) $entries->where('account_id', $accounts['111']->id)->sum('debit_amount'),
            'debit_112' => (int) $entries
                ->filter(fn ($entry): bool => in_array((int) $entry->account_id, $accounts['bank_ids'], true))
                ->sum('debit_amount'),
        ];
    }

    private function amountEquals(mixed $actual, mixed $expected): bool
    {
        return abs((float) $actual - (float) $expected) < 0.01;
    }
}
