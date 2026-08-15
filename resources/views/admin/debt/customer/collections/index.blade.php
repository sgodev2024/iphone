@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[
            ['label' => 'Công nợ khách hàng', 'url' => route('admin.debts.customer')],
            ['label' => 'Lịch sử thu công nợ'],
        ]" />

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" for="from_date">Từ ngày</label>
                        <input class="form-control" id="from_date" name="from_date" type="date"
                            value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="to_date">Đến ngày</label>
                        <input class="form-control" id="to_date" name="to_date" type="date"
                            value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="customer">Khách hàng</label>
                        <input class="form-control" id="customer" name="customer" value="{{ request('customer') }}"
                            placeholder="Tên, mã, SĐT">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="payment_method">Phương thức</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">Tất cả</option>
                            <option value="cash" @selected(request('payment_method') === 'cash')>Tiền mặt</option>
                            <option value="bank_transfer" @selected(request('payment_method') === 'bank_transfer')>Chuyển khoản</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="collection_number">Số phiếu</label>
                        <input class="form-control" id="collection_number" name="collection_number"
                            value="{{ request('collection_number') }}" placeholder="PTCN-...">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">Lọc</button>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.debts.customer.collections.index') }}">Xóa</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Lịch sử các lần thu công nợ</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Số phiếu</th>
                            <th>Ngày thu</th>
                            <th>Khách hàng</th>
                            <th>Phương thức</th>
                            <th>Tài khoản nhận</th>
                            <th class="text-end">Tổng thu</th>
                            <th class="text-center">Phân bổ</th>
                            <th>Người thu</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collections as $collection)
                            <tr class="customer-debt-collection-row" data-collection-id="{{ $collection->id }}">
                                <td class="fw-semibold">{{ $collection->collection_number }}</td>
                                <td>{{ $collection->collection_date?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="d-block">{{ $collection->client?->name ?? 'Khách hàng đã xóa' }}</span>
                                    <small class="text-muted">{{ $collection->client?->phone ?: '—' }}</small>
                                </td>
                                <td>
                                    @if ($collection->payment_method === 'cash')
                                        <span class="badge bg-success">Tiền mặt</span>
                                    @else
                                        <span class="badge bg-primary">Chuyển khoản</span>
                                    @endif
                                </td>
                                <td>{{ $collection->moneyAccount?->code }} - {{ $collection->moneyAccount?->name }}</td>
                                <td class="text-end fw-semibold">{{ formatExactMoney($collection->total_amount) }}</td>
                                <td class="text-center">{{ $collection->allocations_count }}</td>
                                <td>{{ $collection->creator?->name ?? 'Không xác định' }}</td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('admin.debts.customer.collections.show', $collection->id) }}">Chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Không có lịch sử thu công nợ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($collections->hasPages())
                <div class="card-footer">{{ $collections->links() }}</div>
            @endif
        </div>
    </div>
@endsection
