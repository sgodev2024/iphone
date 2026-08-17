@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[
            ['label' => 'Thu chi tiền mặt', 'url' => route('admin.transactions.cash.index')],
            ['label' => 'Giao dịch #'.$transaction->id],
        ]" />

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Chi tiết giao dịch tiền mặt #{{ $transaction->id }}</h5>
                <span class="badge {{ $transaction->status === \App\Models\Transaction::STATUS_COMPLETED ? 'bg-success' : 'bg-secondary' }}">
                    {{ $transaction->status ?: '—' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>ID:</strong> {{ $transaction->id }}</div>
                    <div class="col-md-6"><strong>Ngày:</strong> {{ $transaction->transaction_date?->format('d/m/Y') ?: '—' }}</div>
                    <div class="col-md-6"><strong>Loại giao dịch:</strong> {{ $transaction->type ?: '—' }}</div>
                    <div class="col-md-6"><strong>Nghiệp vụ:</strong> {{ $operationLabel }}</div>
                    <div class="col-md-6"><strong>Số tiền:</strong> {{ \App\Support\DecimalAmount::compare($totalAmount, '0.00') > 0 ? formatPrice($totalAmount) : '—' }}</div>
                    <div class="col-md-6"><strong>Loại chứng từ:</strong> {{ $transaction->document_type ?: '—' }}</div>
                    <div class="col-md-6"><strong>ID chứng từ:</strong> {{ $transaction->reference_number ?: '—' }}</div>
                    <div class="col-md-6"><strong>Người tạo:</strong> {{ $transaction->creator?->name ?: '—' }}</div>
                    <div class="col-md-6"><strong>Trạng thái hạch toán:</strong> {{ $transaction->status ?: '—' }}</div>
                    <div class="col-12">
                        <strong>Nội dung:</strong>
                        <div class="text-break" style="white-space: pre-wrap;">{{ $transaction->description ?: '—' }}</div>
                    </div>
                    <div class="col-12">
                        <strong>File:</strong>
                        @if ($transaction->attachment)
                            <a href="{{ asset('storage/'.$transaction->attachment) }}" target="_blank">Xem file đính kèm</a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <hr>

                <h6 class="mb-3">Các dòng hạch toán</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tài khoản</th>
                                <th>Đối tượng</th>
                                <th class="text-end">Nợ</th>
                                <th class="text-end">Có</th>
                                <th>Diễn giải dòng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transaction->entries as $entry)
                                <tr>
                                    <td>{{ $entry->account?->code }} - {{ $entry->account?->name }}</td>
                                    <td>
                                        @if ($entry->tableable)
                                            {{ $entry->tableable->name ?? class_basename($entry->tableable_type).' #'.$entry->tableable_id }}
                                        @elseif ($entry->tableable_id)
                                            {{ class_basename($entry->tableable_type).' #'.$entry->tableable_id }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">{{ formatPrice($entry->debit_amount) }}</td>
                                    <td class="text-end">{{ formatPrice($entry->credit_amount) }}</td>
                                    <td class="text-break" style="white-space: pre-wrap;">{{ $entry->note ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Không có dòng hạch toán</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.transactions.cash.index') }}" class="btn btn-outline-secondary btn-sm">Quay lại</a>
            </div>
        </div>
    </div>
@endsection
