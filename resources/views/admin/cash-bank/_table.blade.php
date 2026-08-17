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
@forelse ($entries as $entry)
    @php
        $operationLabel = $entry->collection_id
            ? 'Thu công nợ khách hàng'
            : ($entry->document_type === 'import_payment'
                ? 'Trả công nợ nhà cung cấp'
                : 'Giao dịch ngân hàng đã hạch toán');
        $statusLabel = $entry->status === \App\Models\Transaction::STATUS_COMPLETED
            ? 'Đã hạch toán'
            : ($entry->status ?: '—');
        $statusClass = $entry->status === \App\Models\Transaction::STATUS_COMPLETED
            ? 'bg-success'
            : 'bg-secondary';
    @endphp
    <tr>
        <td class="cash-col-id text-center">{{ $entry->id }}</td>
        <td class="cash-col-date cash-date-cell">
            <span class="d-block">{{ \Carbon\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}</span>
        </td>
        <td class="cash-col-account">
            <span class="cash-cell-clamp d-block">{{ $entry->account_code ?? '-' }}</span>
            <span class="cash-cell-clamp d-block">{{ $entry->account_name ?? '-' }}</span>
        </td>
        <td class="cash-col-operation"><span class="cash-cell-clamp">{{ $operationLabel }}</span></td>
        <td class="cash-col-contra">
            <span class="cash-cell-clamp d-block">{{ $entry->contra_code ?? '-' }}</span>
            <span class="cash-cell-clamp d-block">{{ $entry->contra_name ?? '-' }}</span>
        </td>
        <td class="cash-col-party">
            <span class="cash-cell-clamp d-block">{{ $entry->related_party ?? '-' }}</span>
            <span class="cash-cell-clamp d-block">SĐT: {{ $entry->related_party_phone ?? '-' }}</span>
        </td>
        <td class="cash-col-document">
            @if ($entry->document_type || $entry->reference_number)
                <span class="cash-cell-clamp">
                    <span class="d-block">{{ $entry->document_type ?: '—' }}</span>
                    <span class="d-block text-muted">{{ $entry->reference_number ?: '—' }}</span>
                </span>
            @else
                <span class="cash-cell-clamp">—</span>
            @endif
        </td>
        <td class="cash-col-description"><span class="cash-cell-clamp">{{ $entry->description ?: '—' }}</span></td>
        <td class="cash-col-money cash-money-cell text-end">
            {{ $entry->debit_amount > 0 ? formatPrice($entry->debit_amount) : '—' }}
        </td>
        <td class="cash-col-money cash-money-cell text-end">
            {{ $entry->credit_amount > 0 ? formatPrice($entry->credit_amount) : '—' }}
        </td>
        <td class="cash-col-status">
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </td>
        <td class="cash-col-creator cash-creator-cell">
            <span class="cash-cell-clamp">{{ $entry->creator_name ?? '-' }}</span>
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
                —
            @endif
        </td>
        <td class="cash-col-action text-center">
            @if ($entry->collection_id)
                <a class="btn btn-sm btn-outline-primary"
                    href="{{ route('admin.debts.customer.collections.show', $entry->collection_id) }}">Chi tiết</a>
            @elseif (!empty($entry->supplier_import_id))
                <a class="btn btn-sm btn-outline-primary"
                    href="{{ route('admin.importproduct.importCoupon.detail', ['id' => $entry->supplier_import_id]) }}">Chi tiết</a>
            @else
                —
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="14" class="text-center cash-empty-cell">Không có dữ liệu</td>
    </tr>
@endforelse
@endif
