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
@php
    $totalThu = 0;
    $totalChi = 0;
@endphp

@forelse ($entries as $entry)
    @php
        if ($entry->debit_amount > 0) {
            $totalThu += $entry->debit_amount;
        }
        if ($entry->credit_amount > 0) {
            $totalChi += $entry->credit_amount;
        }
    @endphp
    <tr>
        <td class="cash-col-check text-center">
            @if (!$entry->collection_id)
                <input type="checkbox" class="item-checkbox" data-id="{{ $entry->id }}">
            @endif
        </td>
        <td class="cash-col-id">
            {{ $entry->id }}
        </td>
        <td class="cash-col-date cash-date-cell">
            <span class="d-block">{{ \Carbon\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}</span>
        </td>
        <td class="cash-col-account">
            <span class="cash-cell-line d-block">{{ $entry->account_code ?? '-' }}</span>
            <span class="cash-cell-line d-block">{{ $entry->account_name ?? '-' }}</span>
        </td>
        <td class="cash-col-contra">
            <span class="cash-cell-line d-block">{{ $entry->contra_code ?? '-' }}</span>
            <span class="cash-cell-line d-block">{{ $entry->contra_name ?? '-' }}</span>
        </td>
        <td class="cash-col-party">
            <span class="cash-cell-line d-block">{{ $entry->related_party ?? '-' }}</span>
            <span class="cash-cell-line d-block">SĐT: {{ $entry->related_party_phone ?? '-' }}</span>
        </td>
        <td class="cash-col-money cash-money-cell text-end">
            {{ $entry->debit_amount > 0 ? formatPrice($entry->debit_amount) : ($type === 'cash' ? '—' : '') }}
        </td>
        <td class="cash-col-money cash-money-cell text-end">
            {{ $entry->credit_amount > 0 ? formatPrice($entry->credit_amount) : ($type === 'cash' ? '—' : '') }}
        </td>
        <td class="cash-col-creator cash-creator-cell">
            {{ $entry->creator_name ?? '-' }}
        </td>
        <td class="cash-col-file">
            @if ($entry->attachment)
                <a href="{{ $entry->collection_id
                    ? route('admin.debts.customer.collections.attachment', $entry->collection_id)
                    : asset('storage/' . $entry->attachment) }}"
                    target="_blank" class="cash-file-link text-primary fw-bold text-decoration-none">
                    <i class="bi bi-file-earmark-text me-1"></i> Xem file đính kèm
                </a>
            @else
                @if ($type === 'cash')
                    <span class="cash-cell-line d-block">—</span>
                @endif
            @endif
        </td>
        <td class="cash-col-action text-center position-relative">
            @if ($entry->collection_id)
                <a class="btn btn-sm btn-outline-primary"
                    href="{{ route('admin.debts.customer.collections.show', $entry->collection_id) }}">Chi tiết</a>
            @else
                <button type="button" class="btn btn-sm btn-light action-toggle-btn">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="action-menu list-group position-absolute shadow-sm rounded"
                    style="display: none; min-width: 150px; z-index: 1000;">
                    <li class="list-group-item action-print cursor-pointer">In phiếu</li>
                    <li class="list-group-item action-edit cursor-pointer"
                        data-url="{{ route("admin.transactions.$type.save", ['transactionId' => $entry->id]) }}">
                        Sửa
                    </li>
                </ul>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center cash-empty-cell">Không có dữ liệu</td>
    </tr>
@endforelse

<tr class="fw-bold cash-total-row">
    <td colspan="6" class="text-end fw-bold cash-total-label">Tổng</td>
    <td class="text-end fw-bold cash-total-money">{{ formatPrice($totalThu) }}</td>
    <td class="text-end fw-bold cash-total-money">{{ formatPrice($totalChi) }}</td>
    <td colspan="3"></td>
</tr>
@endif
