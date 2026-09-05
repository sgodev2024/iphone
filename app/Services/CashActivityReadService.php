<?php

namespace App\Services;

use App\Data\CashActivityItem;
use App\Models\CashVoucher;
use App\Models\Company;
use App\Models\User;
use App\Support\DecimalAmount;
use App\Support\BranchContext;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashActivityReadService
{
    public function __construct(
        private TransactionBusinessListService $transactionList,
        private BranchContext $branchContext
    ) {
    }

    /**
     * @param  array<int, int>  $transactionOwnerIds
     * @param  Collection<int, int>  $cashAccountIds
     * @return array{paginator: LengthAwarePaginator, totals: array<string, string>}
     */
    public function read(
        User $actor,
        array $transactionOwnerIds,
        Collection $cashAccountIds,
        string $from,
        string $to,
        int $page = 1,
        int $perPage = 25
    ): array {
        $ledgerEntries = $this->transactionList
            ->entries($actor, $transactionOwnerIds, $cashAccountIds, $from, $to);
        $companyIds = $ledgerEntries
            ->where('related_party_type', Company::class)
            ->pluck('related_party_id')
            ->filter()
            ->unique();
        $companyNames = $companyIds->isEmpty()
            ? collect()
            : Company::query()->whereIn('id', $companyIds)->pluck('name', 'id');
        $posted = $ledgerEntries
            ->map(fn (object $entry): CashActivityItem => $this->postedItem($entry, $companyNames));

        $pendingQuery = CashVoucher::query()
            ->with(['cashAccount:id,code,name', 'creator:id,name'])
            ->where('owner_id', (int) $actor->ownerId());
        $this->branchContext->scope($pendingQuery, $actor);
        $pending = $pendingQuery
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->get()
            ->map(fn (CashVoucher $voucher): CashActivityItem => $this->pendingItem($voucher));

        $activities = $posted
            ->concat($pending)
            ->sort(function (CashActivityItem $left, CashActivityItem $right): int {
                $dateComparison = strcmp($right->date, $left->date);

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                if (! $left->createdAt->equalTo($right->createdAt)) {
                    return $left->createdAt->greaterThan($right->createdAt) ? -1 : 1;
                }

                return [$right->sourceId, $right->sourceType]
                    <=> [$left->sourceId, $left->sourceType];
            })
            ->values();

        $paginator = new LengthAwarePaginator(
            $activities->forPage($page, $perPage)->values(),
            $activities->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'paginator' => $paginator,
            'totals' => [
                'posted_receipt' => $this->sum($posted, 'receiptAmount'),
                'posted_payment' => $this->sum($posted, 'paymentAmount'),
                'pending_receipt' => $this->sum($pending, 'receiptAmount'),
                'pending_payment' => $this->sum($pending, 'paymentAmount'),
            ],
        ];
    }

    /** @param Collection<int, string> $companyNames */
    private function postedItem(object $entry, Collection $companyNames): CashActivityItem
    {
        $customerCollection = $entry->collection_id !== null;
        $supplierPayment = $entry->document_type === 'import_payment';
        $operationLabel = $customerCollection
            ? 'Thu công nợ khách hàng'
            : ($supplierPayment ? 'Trả công nợ nhà cung cấp' : 'Giao dịch tiền mặt đã hạch toán');

        $detailUrl = $customerCollection
            ? route('admin.debts.customer.collections.show', $entry->collection_id)
            : route('admin.transactions.cash.posted.show', [
                'transactionId' => (int) $entry->id,
            ]);
        $attachmentUrl = null;

        if ($entry->attachment) {
            $attachmentUrl = $customerCollection
                ? route('admin.debts.customer.collections.attachment', $entry->collection_id)
                : asset('storage/'.$entry->attachment);
        }

        return new CashActivityItem(
            sourceType: $customerCollection
                ? 'customer_collection'
                : ($supplierPayment ? 'supplier_payment' : 'posted_transaction'),
            sourceId: $customerCollection ? (int) $entry->collection_id : (int) $entry->id,
            businessNumber: (string) ($entry->business_number ?: '#'.$entry->id),
            date: substr((string) $entry->transaction_date, 0, 10),
            createdAt: CarbonImmutable::parse((string) $entry->created_at),
            cashAccountLabel: trim(($entry->account_code ?? '').' - '.($entry->account_name ?? ''), ' -'),
            operationLabel: $operationLabel,
            counterAccountLabel: trim(($entry->contra_code ?? '').' - '.($entry->contra_name ?? ''), ' -'),
            objectLabel: $entry->related_party_type === Company::class
                ? $companyNames->get((int) $entry->related_party_id)
                : $entry->related_party,
            documentType: $entry->document_type,
            referenceNumber: $entry->reference_number,
            description: $entry->description,
            receiptAmount: (string) $entry->debit_amount,
            paymentAmount: (string) $entry->credit_amount,
            accountingStatus: 'posted',
            accountingStatusLabel: 'Đã hạch toán',
            creatorName: $entry->creator_name,
            attachmentUrl: $attachmentUrl,
            detailUrl: $detailUrl,
        );
    }

    private function pendingItem(CashVoucher $voucher): CashActivityItem
    {
        return new CashActivityItem(
            sourceType: 'cash_voucher',
            sourceId: (int) $voucher->id,
            businessNumber: $voucher->voucher_number,
            date: substr((string) $voucher->getRawOriginal('transaction_date'), 0, 10),
            createdAt: CarbonImmutable::parse((string) $voucher->created_at),
            cashAccountLabel: "{$voucher->cashAccount->code} - {$voucher->cashAccount->name}",
            operationLabel: $voucher->direction === CashVoucher::DIRECTION_RECEIPT
                ? 'Thu tiền thông thường'
                : 'Chi tiền thông thường',
            counterAccountLabel: 'Chưa hạch toán',
            objectLabel: null,
            documentType: $voucher->document_type,
            referenceNumber: $voucher->reference_number,
            description: $voucher->description,
            receiptAmount: $voucher->direction === CashVoucher::DIRECTION_RECEIPT ? $voucher->amount : '0.00',
            paymentAmount: $voucher->direction === CashVoucher::DIRECTION_PAYMENT ? $voucher->amount : '0.00',
            accountingStatus: CashVoucher::STATUS_PENDING_ACCOUNTING,
            accountingStatusLabel: 'Chờ hạch toán',
            creatorName: $voucher->creator?->name,
            attachmentUrl: $voucher->attachment
                ? route('admin.transactions.cash.vouchers.attachment', $voucher)
                : null,
            detailUrl: route('admin.transactions.cash.vouchers.show', $voucher),
        );
    }

    /** @param Collection<int, CashActivityItem> $items */
    private function sum(Collection $items, string $property): string
    {
        return $items->reduce(
            fn (string $total, CashActivityItem $item): string => DecimalAmount::add($total, $item->{$property}),
            '0.00'
        );
    }
}
