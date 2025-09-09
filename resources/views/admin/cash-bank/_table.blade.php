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
        <td>
            <input type="checkbox" class="item-checkbox" data-id="{{ $entry->id }}">
        </td>
        <td>{{ $entry->id }} | {{ \Carbon\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}</td>
        <td>{{ $entry->account_code }}<br>{{ $entry->account_name }}</td>
        <td>{{ $entry->contra_code }}<br>{{ $entry->contra_name }}</td>
        <td>
            {{ $entry->related_party ?? '-' }}
            <br>
            STD: {{ $entry->related_party_phone ?? '-' }}
        </td>
        <td class="text-end">
            {{ $entry->debit_amount > 0 ? formatPrice($entry->debit_amount) : '' }}
        </td>
        <td class="text-end">
            {{ $entry->credit_amount > 0 ? formatPrice($entry->credit_amount) : '' }}
        </td>
        <td>
            {{ $entry->creator_name ?? '-' }}
        </td>
        <td>
            @if ($entry->attachment)
                <a href="{{ asset('storage/' . $entry->attachment) }}" target="_blank"
                    class="text-primary fw-bold text-decoration-none">
                    <i class="bi bi-file-earmark-text me-1"></i> Xem file đính kèm
                </a>
            @endif
        </td>
        <td class="text-center position-relative">
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
        </td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="text-center">Không có dữ liệu</td>
    </tr>
@endforelse

<tr class="fw-bold">
    <td colspan="5" class="text-end fw-bold">Tổng</td>
    <td class="text-end fw-bold">{{ formatPrice($totalThu) }}</td>
    <td class="text-end fw-bold">{{ formatPrice($totalChi) }}</td>
    <td colspan="3"></td>
</tr>
