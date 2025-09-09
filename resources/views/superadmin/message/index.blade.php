@extends('superadmin.layout.index')

@section('content')
    <style>
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            white-space: nowrap;
        }

        .summary-section {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-table {
            border: 1px solid #ddd;
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .summary-table th {
            background-color: #f4f4f4;
        }

        .total-fees {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .no-data-message {
            text-align: center;
            padding: 20px;
            color: #888;
        }
    </style>

    <div class="container-fluid">
        <h2>Danh sách tin nhắn đã gửi</h2>

        @if ($messages->isEmpty())
            <div class="no-data-message">Không có tin nhắn nào để hiển thị.</div>
        @else
            <div class="summary-section">
                <div class="total-fees">
                    @foreach ($totalFeesByOa as $oaId => $totalFee)
                        <p><strong>Tổng phí:</strong>
                            {{ $totalFee }} đ</p>
                    @endforeach
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>OA</th>
                            <th>Tên</th>
                            <th>Số điện thoại</th>
                            <th>Ngày gửi</th>
                            <th>Template</th>
                            <th>Phí</th>
                            <th>Trạng thái</th>
                            <th>Thông báo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr>
                                <td>{{ $message->zaloOa->name }}</td>
                                <td>{{ $message->name }}</td>
                                <td>{{ $message->phone }}</td>
                                <td>{{ \Carbon\Carbon::parse($message->sent_at)->format('H:i:s d/m/Y') }}</td>
                                <td>{{ $message->template->template_name ?? 'N/A' }}</td>
                                <td>{{ $message->status == 1 ? $message->template->price ?? '0' : '0' }} đ</td>
                                <td>
                                    @if ($message->status == 1)
                                        Thành công
                                    @else
                                        Thất bại
                                    @endif
                                </td>
                                <td>{{ $message->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
