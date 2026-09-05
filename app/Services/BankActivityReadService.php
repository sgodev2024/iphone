<?php

namespace App\Services;

use App\Data\BankActivityItem;
use App\Models\BankVoucher;
use App\Models\User;
use App\Support\DecimalAmount;
use App\Support\BranchContext;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BankActivityReadService
{
    public function __construct(
        private TransactionBusinessListService $transactionList,
        private BranchContext $branchContext
    ) {
    }

    /**
     * @param  array<int, int>  $transactionOwnerIds
     * @param  Collection<int, int>  $bankAccountIds
     * @return array{paginator: LengthAwarePaginator, totals: array<string, string>}
     */
    public function read(
        User $actor,
        array $transactionOwnerIds,
        Collection $bankAccountIds,
        string $from,
        string $to,
        int $page = 1,
        int $perPage = 25
    ): array {
        $posted = $this->transactionList
            ->entries($actor, $transactionOwnerIds, $bankAccountIds, $from, $to)
            ->map(fn (object $entry): BankActivityItem => $this->postedItem($entry));

        $pendingQuery = BankVoucher::query()
            ->with(['bankAccount:id,code,name', 'creator:id,name']);
        if (! Schema::hasColumn('bank_vouchers', 'branch_id')
            || ! $this->branchContext->isGlobal($actor)
        ) {
            $pendingQuery->where('owner_id', (int) $actor->ownerId());
        }
        $this->branchContext->scope($pendingQuery, $actor);
        $pending = $pendingQuery
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->whereIn('bank_account_id', $bankAccountIds)
            ->where('accounting_status', BankVoucher::STATUS_PENDING_ACCOUNTING)
            ->where(function ($query): void {
                $query
                    ->where(function ($query): void {
                        $query
                            ->where('operation', BankVoucher::OPERATION_GENERIC_RECEIPT)
                            ->where('direction', BankVoucher::DIRECTION_RECEIPT);
                    })
                    ->orWhere(function ($query): void {
                        $query
                            ->where('operation', BankVoucher::OPERATION_GENERIC_PAYMENT)
                            ->where('direction', BankVoucher::DIRECTION_PAYMENT);
                    });
            })
            ->get()
            ->map(fn (BankVoucher $voucher): BankActivityItem => $this->pendingItem($voucher));

        $activities = $posted
            ->concat($pending)
            ->sort(function (BankActivityItem $left, BankActivityItem $right): int {
                $dateComparison = strcmp($right->date, $left->date);

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                if (! $left->createdAt->equalTo($right->createdAt)) {
                    return $left->createdAt->greaterThan($right->createdAt) ? -1 : 1;
                }

                $idComparison = $right->sourceId <=> $left->sourceId;

                return $idComparison !== 0
                    ? $idComparison
                    : strcmp($right->sourceType, $left->sourceType);
            })
            ->values();

        return [
            'paginator' => new LengthAwarePaginator(
                $activities->forPage($page, $perPage)->values(),
                $activities->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            ),
            'totals' => [
                'posted_receipt' => $this->sum($posted, 'receiptAmount'),
                'posted_payment' => $this->sum($posted, 'paymentAmount'),
                'pending_receipt' => $this->sum($pending, 'receiptAmount'),
                'pending_payment' => $this->sum($pending, 'paymentAmount'),
            ],
        ];
    }

    private function postedItem(object $entry): BankActivityItem
    {
        $customerCollection = $entry->collection_id !== null;
        $supplierPayment = $entry->document_type === 'import_payment';
        $supplierImportId = $this->supplierImportId($entry);
        $detailUrl = $customerCollection
            ? route('admin.debts.customer.collections.show', $entry->collection_id)
            : ($supplierPayment && $supplierImportId !== null
                ? route('admin.importproduct.importCoupon.detail', ['id' => $supplierImportId])
                : null);
        $attachmentUrl = null;

        if ($entry->attachment) {
            $attachmentUrl = $customerCollection
                ? route('admin.debts.customer.collections.attachment', $entry->collection_id)
                : asset('storage/'.$entry->attachment);
        }

        return new BankActivityItem(
            sourceType: $customerCollection
                ? 'customer_collection'
                : ($supplierPayment ? 'supplier_payment' : 'posted_transaction'),
            sourceId: $customerCollection ? (int) $entry->collection_id : (int) $entry->id,
            businessNumber: (string) ($entry->business_number ?: '#'.$entry->id),
            date: substr((string) $entry->transaction_date, 0, 10),
            createdAt: CarbonImmutable::parse((string) $entry->created_at),
            bankAccountLabel: trim(($entry->account_code ?? '').' - '.($entry->account_name ?? ''), ' -'),
            operationLabel: $customerCollection
                ? 'Thu công nợ khách hàng'
                : ($supplierPayment ? 'Trả công nợ nhà cung cấp' : 'Giao dịch ngân hàng đã hạch toán'),
            counterAccountLabel: trim(($entry->contra_code ?? '').' - '.($entry->contra_name ?? ''), ' -'),
            objectLabel: $entry->related_party,
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

    private function pendingItem(BankVoucher $voucher): BankActivityItem
    {
        $isReceipt = $voucher->operation === BankVoucher::OPERATION_GENERIC_RECEIPT;

        return new BankActivityItem(
            sourceType: 'bank_voucher',
            sourceId: (int) $voucher->id,
            businessNumber: $voucher->voucher_number,
            date: substr((string) $voucher->getRawOriginal('transaction_date'), 0, 10),
            createdAt: CarbonImmutable::parse((string) $voucher->created_at),
            bankAccountLabel: "{$voucher->bankAccount->code} - {$voucher->bankAccount->name}",
            operationLabel: $isReceipt
                ? 'Thu tiền thông thường'
                : 'Chi tiền thông thường',
            counterAccountLabel: 'Chưa hạch toán',
            objectLabel: null,
            documentType: $voucher->document_type,
            referenceNumber: $voucher->reference_number,
            description: $voucher->description,
            receiptAmount: $isReceipt ? $voucher->amount : '0.00',
            paymentAmount: $isReceipt ? '0.00' : $voucher->amount,
            accountingStatus: BankVoucher::STATUS_PENDING_ACCOUNTING,
            accountingStatusLabel: 'Chờ hạch toán',
            creatorName: $voucher->creator?->name,
            attachmentUrl: $voucher->attachment
                ? route('admin.transactions.bank.vouchers.attachment', $voucher)
                : null,
            detailUrl: route('admin.transactions.bank.vouchers.show', $voucher),
        );
    }

    /** @param Collection<int, BankActivityItem> $items */
    private function sum(Collection $items, string $property): string
    {
        return $items->reduce(
            fn (string $total, BankActivityItem $item): string => DecimalAmount::add($total, $item->{$property}),
            '0.00'
        );
    }

    private function supplierImportId(object $entry): ?int
    {
        return preg_match('/^IMP-(\d+)-PAY-/', (string) $entry->reference_number, $matches)
            ? (int) $matches[1]
            : null;
    }
}
