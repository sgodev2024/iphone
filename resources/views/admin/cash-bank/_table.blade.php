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
            @if (! $entry->collection_id)
                <input type="checkbox" class="item-checkbox" data-id="{{ $entry->id }}">
            @endif
        </td>
        <td class="cash-col-date cash-date-cell">
            @if ($entry->collection_id)
                <a class="fw-semibold" href="{{ route('admin.debts.customer.collections.show', $entry->collection_id) }}">
                    {{ $entry->reference_number }}
                </a>
                <span class="badge bg-info text-dark d-block mt-1">Thu công nợ</span>
            @else
                {{ $entry->id }}
            @endif
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
                    : asset('storage/' . $entry->attachment) }}" target="_blank"
                    class="cash-file-link text-primary fw-bold text-decoration-none">
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
        <td colspan="10" class="text-center cash-empty-cell">Không có dữ liệu</td>
    </tr>
@endforelse

<tr class="fw-bold cash-total-row">
    <td colspan="5" class="text-end fw-bold cash-total-label">Tổng</td>
    <td class="text-end fw-bold cash-total-money">{{ formatPrice($totalThu) }}</td>
    <td class="text-end fw-bold cash-total-money">{{ formatPrice($totalChi) }}</td>
    <td colspan="3"></td>
</tr>
