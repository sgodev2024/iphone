@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[
            ['label' => 'Công nợ khách hàng', 'url' => route('admin.debts.customer')],
            ['label' => 'Lịch sử thu công nợ', 'url' => route('admin.debts.customer.collections.index')],
            ['label' => $collection->collection_number],
        ]" />

        @if ($hasIntegrityMismatch)
            <div class="alert alert-danger" role="alert" data-integrity-warning>
                <strong>Cảnh báo toàn vẹn:</strong> tổng phiếu {{ formatExactMoney($collection->total_amount) }}
                không bằng tổng phân bổ {{ formatExactMoney($allocatedTotal) }}. Dữ liệu không được tự động điều chỉnh.
            </div>
        @endif

        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Phiếu thu công nợ {{ $collection->collection_number }}</h5>
                <span class="badge {{ $collection->status === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $collection->status === 'completed' ? 'Đã hoàn tất' : $collection->status }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Ngày thu:</strong> {{ $collection->collection_date?->format('d/m/Y') }}</div>
                    <div class="col-md-4"><strong>Khách hàng:</strong> {{ $collection->client?->name ?? 'Khách hàng đã xóa' }}</div>
                    <div class="col-md-4"><strong>Người thu:</strong> {{ $collection->creator?->name ?? 'Không xác định' }}</div>
                    <div class="col-md-4"><strong>Phương thức:</strong> {{ $collection->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' }}</div>
                    <div class="col-md-4"><strong>Tài khoản nhận:</strong> {{ $collection->moneyAccount?->code }} - {{ $collection->moneyAccount?->name }}</div>
                    <div class="col-md-4"><strong>Tổng thu:</strong> {{ formatExactMoney($collection->total_amount) }}</div>
                    <div class="col-md-8"><strong>Ghi chú:</strong> {{ $collection->note ?: '—' }}</div>
                    <div class="col-md-4">
                        <strong>Đính kèm:</strong>
                        @if ($collection->attachment)
                            <a href="{{ route('admin.debts.customer.collections.attachment', $collection->id) }}" target="_blank">Xem / tải file</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">Phân bổ theo đơn hàng</h5></div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Đơn hàng</th>
                            <th class="text-end">Số tiền phân bổ</th>
                            <th class="text-end">Còn nợ sau thu</th>
                            <th>Transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($collection->allocations as $allocation)
                            <tr class="collection-allocation-row">
                                <td>{{ $allocation->allocation_sequence }}</td>
                                <td>
                                    @if (auth()->user()?->hasPermission('order.detail'))
                                        <a href="{{ route('admin.order.show', $allocation->order_id) }}">
                                            {{ $allocation->order?->code ?: '#'.$allocation->order_id }}
                                        </a>
                                    @else
                                        {{ $allocation->order?->code ?: '#'.$allocation->order_id }}
                                    @endif
                                </td>
                                <td class="text-end">{{ formatExactMoney($allocation->allocated_amount) }}</td>
                                <td class="text-end">{{ formatExactMoney($allocation->remaining_after) }}</td>
                                <td>#{{ $allocation->payment_transaction_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Tổng phân bổ</td>
                            <td class="text-end">{{ formatExactMoney($allocatedTotal) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
