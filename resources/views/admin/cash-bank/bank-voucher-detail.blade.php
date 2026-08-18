@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[
            ['label' => 'Thu chi ngân hàng', 'url' => route('admin.transactions.bank.index')],
            ['label' => $voucher->voucher_number],
        ]" />

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Chi tiết phiếu {{ $voucher->voucher_number }}</h5>
                <span class="badge bg-warning text-dark">Chờ hạch toán</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>ID:</strong> {{ $voucher->id }}</div>
                    <div class="col-md-6"><strong>Mã phiếu:</strong> {{ $voucher->voucher_number }}</div>
                    <div class="col-md-6"><strong>Ngày:</strong> {{ $voucher->transaction_date->format('d/m/Y') }}</div>
                    <div class="col-md-6"><strong>Loại giao dịch:</strong> {{ $voucher->direction === 'receipt' ? 'Thu tiền' : 'Chi tiền' }}</div>
                    <div class="col-md-6"><strong>Nghiệp vụ:</strong> {{ $voucher->direction === 'receipt' ? 'Thu tiền thông thường' : 'Chi tiền thông thường' }}</div>
                    <div class="col-md-6"><strong>Tài khoản ngân hàng:</strong> {{ $voucher->bankAccount->code }} - {{ $voucher->bankAccount->name }}</div>
                    <div class="col-md-6"><strong>Tài khoản đối ứng:</strong> Chưa hạch toán</div>
                    <div class="col-md-6"><strong>Loại chứng từ:</strong> {{ $voucher->document_type ?: '—' }}</div>
                    <div class="col-md-6"><strong>ID chứng từ:</strong> {{ $voucher->reference_number ?: '—' }}</div>
                    <div class="col-md-6"><strong>Số tiền:</strong> {{ formatPrice($voucher->amount) }}</div>
                    <div class="col-md-6"><strong>Trạng thái:</strong> Chờ hạch toán</div>
                    <div class="col-md-6"><strong>Người tạo:</strong> {{ $voucher->creator?->name ?: '—' }}</div>
                    <div class="col-md-6"><strong>Ngày tạo:</strong> {{ $voucher->created_at?->format('d/m/Y H:i:s') }}</div>
                    <div class="col-12"><strong>Ghi chú:</strong> {{ $voucher->description ?: '—' }}</div>
                    <div class="col-12">
                        <strong>File chứng từ:</strong>
                        @if ($voucher->attachment)
                            <a href="{{ route('admin.transactions.bank.vouchers.attachment', $voucher) }}" target="_blank">Xem file đính kèm</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.transactions.bank.index') }}" class="btn btn-outline-secondary btn-sm">Quay lại</a>
            </div>
        </div>
    </div>
@endsection
