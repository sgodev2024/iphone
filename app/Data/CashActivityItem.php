<?php

namespace App\Data;

use Carbon\CarbonImmutable;

class CashActivityItem
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public string $businessNumber,
        public string $date,
        public CarbonImmutable $createdAt,
        public string $cashAccountLabel,
        public string $operationLabel,
        public string $counterAccountLabel,
        public ?string $objectLabel,
        public ?string $documentType,
        public ?string $referenceNumber,
        public ?string $description,
        public string $receiptAmount,
        public string $paymentAmount,
        public string $accountingStatus,
        public string $accountingStatusLabel,
        public ?string $creatorName,
        public ?string $attachmentUrl,
        public ?string $detailUrl,
    ) {
    }
}
