@php
    $typeLabels = [
        'other' => 'Khác',
        'income' => 'Phiếu thu',
        'expense' => 'Phiếu chi',
        'debit_notice' => 'Báo nợ (Rút tiền)',
        'credit_notice' => 'Báo có (Nộp tiền)',
    ];
@endphp

@forelse ($transactions as $t)
    <tr>
        <td><input type="checkbox" class="item-checkbox" value="{{ $t->transaction_id }}"></td>
        <td>{{ $t->transaction_id }} | {{ \Carbon\Carbon::parse($t->transaction_date)->format('d/m/Y') }}</td>
        <td>{{ $typeLabels[$t->transaction_type] ?? $t->transaction_type }}</td>
        <td>
            {{ $t->object_name ?? '-' }}
            @if ($t->object_phone)
                <br><small class="text-muted">{{ $t->object_phone }}</small>
            @endif
        </td>

        <td>{{ $t->document_type ?? '-' }}</td>
        <td class="text-end">{{ formatPrice($t->amount) }}</td>
        <td>{{ $t->debit_account ?? '-' }}</td>
        <td>{{ $t->credit_account ?? '-' }}</td>
        <td>{{ $t->note ?? '' }}</td>
        <td>
            @if ($t->attachment)
                <a href="{{ asset("storage/$t->attachment") }}" target="_blank"
                    class="text-primary fw-bold text-decoration-none">
                    <i class="bi bi-file-earmark-text me-1"></i> Xem file đính kèm
                </a>
            @else
                -
            @endif
        </td>
        <td class="text-end position-relative">
            <div class="dropdown">
                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item" href="" target="_blank">
                            <i class="fas fa-print me-2"></i>
                            In phiếu
                        </a>
                    </li>
                    <li>
                        <button data-transaction-id="{{ $t->transaction_id }}" type="button"
                            class="dropdown-item text-danger action-delete">
                            <i class="fas fa-trash-alt me-2"></i>
                            Xoá
                        </button>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center text-muted">Không có dữ liệu</td>
    </tr>
@endforelse
