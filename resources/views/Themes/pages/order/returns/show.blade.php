@extends('Themes.layout_staff.app')

@section('content')
    @php
        $originalOrder = $orderReturn->originalOrder;
        $customerName = $originalOrder?->customer_display_name
            ?? $orderReturn->client?->name
            ?? 'Khách lẻ';
        $customerPhone = $originalOrder?->customer_display_phone
            ?? $orderReturn->client?->phone;
    @endphp

    <div class='container py-3 return-invoice-page'>
        <div class='return-invoice-actions d-flex justify-content-between align-items-center mb-3'>
            <a href='{{ route('staff.returns.index') }}' class='btn btn-outline-secondary'>
                <i class='fa-solid fa-arrow-left me-1'></i>
                Quay lại
            </a>
            <button type='button' class='btn btn-primary' onclick='window.print()'>
                <i class='fa-solid fa-print me-1'></i>
                In phiếu
            </button>
        </div>

        <div class='card shadow-sm return-invoice-card'>
            <div class='card-body p-4 p-lg-5'>
                <div class='text-center mb-4'>
                    <h2 class='mb-1'>PHIẾU ĐỔI / TRẢ HÀNG</h2>
                    <div class='fs-5 fw-semibold text-primary'>{{ $orderReturn->code }}</div>
                </div>

                <div class='row g-3 mb-4 return-invoice-meta'>
                    <div class='col-md-6'>
                        <div class='info-label'>Ngày tạo</div>
                        <div>{{ optional($orderReturn->created_at)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Trạng thái</div>
                        <div>
                            @if ($orderReturn->status === 'completed')
                                <span class='badge bg-success'>Hoàn thành</span>
                            @else
                                <span class='badge bg-secondary'>{{ $orderReturn->status }}</span>
                            @endif
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Đơn gốc</div>
                        <div class='fw-semibold'>{{ $originalOrder?->code ?? '-' }}</div>
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Đơn đổi</div>
                        <div class='fw-semibold'>{{ $orderReturn->exchangeOrder?->code ?? 'Không có' }}</div>
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Khách hàng</div>
                        <div class='fw-semibold'>{{ $customerName }}</div>
                        @if ($customerPhone)
                            <div class='text-muted'>{{ $customerPhone }}</div>
                        @endif
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Chi nhánh</div>
                        <div>{{ $orderReturn->branch?->name ?? ($orderReturn->branch_id ? 'Chi nhánh #'.$orderReturn->branch_id : '-') }}</div>
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Người thực hiện</div>
                        <div>{{ $orderReturn->creator?->name ?? '-' }}</div>
                    </div>
                    <div class='col-md-6'>
                        <div class='info-label'>Nhân viên bán đơn gốc</div>
                        <div>{{ $originalOrder?->user?->name ?? $orderReturn->user?->name ?? '-' }}</div>
                    </div>
                </div>

                <h5 class='mb-3'>Hàng khách trả</h5>
                <div class='table-responsive mb-4'>
                    <table class='table table-bordered align-middle return-detail-table'>
                        <thead class='table-light'>
                            <tr>
                                <th>STT</th>
                                <th>Sản phẩm</th>
                                <th>Mã SP / IMEI</th>
                                <th class='text-center'>Số lượng</th>
                                <th class='text-end'>Đơn giá gốc</th>
                                <th class='text-end'>Giảm giá</th>
                                <th class='text-end'>Giá trị trả</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orderReturn->details as $detail)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class='fw-semibold'>{{ $detail->product?->name ?? 'Sản phẩm không xác định' }}</div>
                                        @if ($detail->storage)
                                            <small class='text-muted'>Kho hoàn: {{ $detail->storage->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $detail->product?->code ?? '-' }}</div>
                                        @if ($detail->productImei)
                                            <small class='fw-semibold'>IMEI: {{ $detail->productImei->imei }}</small>
                                        @endif
                                    </td>
                                    <td class='text-center'>{{ $detail->quantity }}</td>
                                    <td class='text-end'>{{ number_format($detail->original_unit_price, 0, ',', '.') }}</td>
                                    <td class='text-end'>{{ number_format($detail->discount_amount, 0, ',', '.') }}</td>
                                    <td class='text-end fw-semibold'>{{ number_format($detail->return_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan='7' class='text-center text-muted py-4'>Không có chi tiết hàng trả.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class='row justify-content-end'>
                    <div class='col-lg-6 col-xl-5'>
                        <div class='settlement-box'>
                            <div class='settlement-row'>
                                <span>Giá trị hàng trả</span>
                                <strong>{{ number_format($orderReturn->return_amount, 0, ',', '.') }} VND</strong>
                            </div>
                            <div class='settlement-row'>
                                <span>Giá trị hàng đổi</span>
                                <strong>{{ number_format($orderReturn->exchange_amount, 0, ',', '.') }} VND</strong>
                            </div>
                            <div class='settlement-row'>
                                <span>Phí trả hàng</span>
                                <strong>{{ number_format($orderReturn->fee_amount, 0, ',', '.') }} VND</strong>
                            </div>
                            <div class='settlement-row settlement-total text-danger'>
                                <span>Hoàn khách</span>
                                <strong>{{ number_format($orderReturn->refund_amount, 0, ',', '.') }} VND</strong>
                            </div>
                            <div class='settlement-row settlement-total text-success'>
                                <span>Thu thêm</span>
                                <strong>{{ number_format($orderReturn->additional_payment, 0, ',', '.') }} VND</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class='mt-4'>
                    <div class='info-label'>Ghi chú</div>
                    <div class='note-box'>{{ $orderReturn->note ?: 'Không có ghi chú.' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .return-invoice-card {
            border: 0;
        }

        .return-invoice-meta .info-label,
        .return-invoice-page .info-label {
            color: #6c757d;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .return-detail-table th {
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .settlement-box,
        .note-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .settlement-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 6px 0;
        }

        .settlement-total {
            border-top: 1px solid #dee2e6;
            margin-top: 4px;
            padding-top: 10px;
        }

        @media print {
            #header,
            .return-invoice-actions,
            .custom-bclient-shadow,
            .swal2-container {
                display: none !important;
            }

            body {
                background: #fff !important;
                color: #000;
            }

            .return-invoice-page {
                max-width: none;
                padding: 0 !important;
            }

            .return-invoice-card {
                box-shadow: none !important;
            }

            .return-invoice-card .card-body {
                padding: 0 !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            .return-detail-table {
                page-break-inside: auto;
            }

            .return-detail-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
@endpush
