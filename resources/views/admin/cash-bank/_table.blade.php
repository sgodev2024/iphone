@if ($type === 'cash')
    @forelse ($activities as $activity)
        <tr>
            <td class="cash-col-id text-center">{{ $activity->sourceId }}</td>
            <td class="cash-col-date cash-date-cell">{{ \Carbon\Carbon::parse($activity->date)->format('d/m/Y') }}</td>
            <td class="cash-col-account"><span class="cash-cell-clamp">{{ $activity->cashAccountLabel ?: '—' }}</span></td>
            <td class="cash-col-operation"><span class="cash-cell-clamp">{{ $activity->operationLabel }}</span></td>
            <td class="cash-col-contra"><span class="cash-cell-clamp">{{ $activity->counterAccountLabel }}</span></td>
            <td class="cash-col-party"><span class="cash-cell-clamp">{{ $activity->objectLabel ?: '—' }}</span></td>
            <td class="cash-col-document">
                @if ($activity->documentType || $activity->referenceNumber)
                    <span class="cash-cell-clamp">
                        <span class="d-block">{{ $activity->documentType ?: '—' }}</span>
                        <span class="d-block text-muted">{{ $activity->referenceNumber ?: '—' }}</span>
                    </span>
                @else
                    <span class="cash-cell-clamp">—</span>
                @endif
            </td>
            <td class="cash-col-description"><span class="cash-cell-clamp">{{ $activity->description ?: '—' }}</span></td>
            <td class="cash-col-money text-end">{{ \App\Support\DecimalAmount::compare($activity->receiptAmount, '0.00') > 0 ? formatPrice($activity->receiptAmount) : '—' }}</td>
            <td class="cash-col-money text-end">{{ \App\Support\DecimalAmount::compare($activity->paymentAmount, '0.00') > 0 ? formatPrice($activity->paymentAmount) : '—' }}</td>
            <td class="cash-col-status">
                <span class="badge {{ $activity->accountingStatus === 'pending_accounting' ? 'bg-warning text-dark' : 'bg-success' }}">
                    {{ $activity->accountingStatusLabel }}
                </span>
            </td>
            <td class="cash-col-creator"><span class="cash-cell-clamp">{{ $activity->creatorName ?: '—' }}</span></td>
            <td class="cash-col-file">
                @if ($activity->attachmentUrl)
                    <a href="{{ $activity->attachmentUrl }}" target="_blank">Xem file</a>
                @else
                    —
                @endif
            </td>
            <td class="cash-col-action text-center">
                @if ($activity->detailUrl)
                    <a class="btn btn-sm btn-outline-primary" href="{{ $activity->detailUrl }}">Chi tiết</a>
                @else
                    —
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="14" class="text-center">Không có dữ liệu</td></tr>
    @endforelse

    <tr class="table-light fw-bold">
        <td colspan="8" class="text-end">Đã hạch toán</td>
        <td class="text-end">{{ formatPrice($totals['posted_receipt']) }}</td>
        <td class="text-end">{{ formatPrice($totals['posted_payment']) }}</td>
        <td colspan="4"></td>
    </tr>
    <tr class="table-warning fw-bold">
        <td colspan="8" class="text-end">Chờ hạch toán (không thuộc ledger)</td>
        <td class="text-end">{{ formatPrice($totals['pending_receipt']) }}</td>
        <td class="text-end">{{ formatPrice($totals['pending_payment']) }}</td>
        <td colspan="4"></td>
    </tr>
@else
    @forelse ($activities as $activity)
        <tr>
        <td class="cash-col-id text-center">{{ $activity->sourceId }}</td>
        <td class="cash-col-date cash-date-cell">{{ \Carbon\Carbon::parse($activity->date)->format('d/m/Y') }}</td>
        <td class="cash-col-account"><span class="cash-cell-clamp">{{ $activity->bankAccountLabel ?: '—' }}</span></td>
        <td class="cash-col-operation"><span class="cash-cell-clamp">{{ $activity->operationLabel }}</span></td>
        <td class="cash-col-contra"><span class="cash-cell-clamp">{{ $activity->counterAccountLabel }}</span></td>
        <td class="cash-col-party"><span class="cash-cell-clamp">{{ $activity->objectLabel ?: '—' }}</span></td>
        <td class="cash-col-document">
            @if ($activity->documentType || $activity->referenceNumber)
                <span class="cash-cell-clamp">
                    <span class="d-block">{{ $activity->documentType ?: '—' }}</span>
                    <span class="d-block text-muted">{{ $activity->referenceNumber ?: '—' }}</span>
                </span>
            @else
                <span class="cash-cell-clamp">—</span>
            @endif
        </td>
        <td class="cash-col-description"><span class="cash-cell-clamp">{{ $activity->description ?: '—' }}</span></td>
        <td class="cash-col-money text-end">{{ \App\Support\DecimalAmount::compare($activity->receiptAmount, '0.00') > 0 ? formatPrice($activity->receiptAmount) : '—' }}</td>
        <td class="cash-col-money text-end">{{ \App\Support\DecimalAmount::compare($activity->paymentAmount, '0.00') > 0 ? formatPrice($activity->paymentAmount) : '—' }}</td>
        <td class="cash-col-status">
            <span class="badge {{ $activity->accountingStatus === 'pending_accounting' ? 'bg-warning text-dark' : 'bg-success' }}">
                {{ $activity->accountingStatusLabel }}
            </span>
        </td>
        <td class="cash-col-creator"><span class="cash-cell-clamp">{{ $activity->creatorName ?: '—' }}</span></td>
        <td class="cash-col-file">
            @if ($activity->attachmentUrl)
                <a href="{{ $activity->attachmentUrl }}" target="_blank">Xem file</a>
            @else
                —
            @endif
        </td>
        <td class="cash-col-action text-center">
            @if ($activity->detailUrl)
                <a class="btn btn-sm btn-outline-primary" href="{{ $activity->detailUrl }}">Chi tiết</a>
            @else
                —
            @endif
        </td>
    </tr>
    @empty
        <tr><td colspan="14" class="text-center cash-empty-cell">Không có dữ liệu</td></tr>
    @endforelse
@endif
