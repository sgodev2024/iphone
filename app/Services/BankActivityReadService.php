<?php

namespace App\Services;

use App\Data\BankActivityItem;
use App\Models\BankVoucher;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BankActivityReadService
{
    public function __construct(private TransactionBusinessListService $transactionList)
    {
    }

    /**
     * @param  array<int, int>  $transactionOwnerIds
     * @param  Collection<int, int>  $bankAccountIds
     */
    public function read(
        User $actor,
        array $transactionOwnerIds,
        Collection $bankAccountIds,
        string $from,
        string $to,
        int $page = 1,
        int $perPage = 25
    ): LengthAwarePaginator {
        $posted = $this->transactionList
            ->entries($transactionOwnerIds, $bankAccountIds, $from, $to)
            ->map(fn (object $entry): BankActivityItem => $this->postedItem($entry));

        $pending = BankVoucher::query()
            ->with(['bankAccount:id,code,name', 'creator:id,name'])
            ->where('owner_id', (int) $actor->ownerId())
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
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

        return new LengthAwarePaginator(
            $activities->forPage($page, $perPage)->values(),
            $activities->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
        return new BankActivityItem(
            sourceType: 'bank_voucher',
            sourceId: (int) $voucher->id,
            businessNumber: $voucher->voucher_number,
            date: substr((string) $voucher->getRawOriginal('transaction_date'), 0, 10),
            createdAt: CarbonImmutable::parse((string) $voucher->created_at),
            bankAccountLabel: "{$voucher->bankAccount->code} - {$voucher->bankAccount->name}",
            operationLabel: $voucher->direction === BankVoucher::DIRECTION_RECEIPT
                ? 'Thu tiền thông thường'
                : 'Chi tiền thông thường',
            counterAccountLabel: 'Chưa hạch toán',
            objectLabel: null,
            documentType: $voucher->document_type,
            referenceNumber: $voucher->reference_number,
            description: $voucher->description,
            receiptAmount: $voucher->direction === BankVoucher::DIRECTION_RECEIPT ? $voucher->amount : '0.00',
            paymentAmount: $voucher->direction === BankVoucher::DIRECTION_PAYMENT ? $voucher->amount : '0.00',
            accountingStatus: BankVoucher::STATUS_PENDING_ACCOUNTING,
            accountingStatusLabel: 'Chờ hạch toán',
            creatorName: $voucher->creator?->name,
            attachmentUrl: $voucher->attachment
                ? route('admin.transactions.bank.vouchers.attachment', $voucher)
                : null,
            detailUrl: route('admin.transactions.bank.vouchers.show', $voucher),
        );
    }

    private function supplierImportId(object $entry): ?int
    {
        return preg_match('/^IMP-(\d+)-PAY-/', (string) $entry->reference_number, $matches)
            ? (int) $matches[1]
            : null;
    }
}
