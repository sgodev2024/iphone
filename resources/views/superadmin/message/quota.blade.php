@extends('superadmin.layout.index')

@section('content')
    <div class="container mt-4">
        <h1 class="mb-4">Thông tin ZNS Quota</h1>

        @if (isset($responseData) && !empty($responseData))
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Thông tin</th>
                        <th>Giá trị</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Hạn mức thông báo ngày</td>
                        <td>{{ $responseData['dailyQuota'] ?? 'Không có dữ liệu' }}</td>
                    </tr>
                    <tr>
                        <td>Số thông báo đã dùng</td>
                        <td>{{ $responseData['dailyQuota'] - $responseData['remainingQuota']}}</td>
                    </tr>
                    <tr>
                        <td>Số thông báo còn lại</td>
                        <td>{{ $responseData['remainingQuota'] ?? 'Không có dữ liệu' }}</td>
                    </tr>
                    <tr>
                        <td>Hạn mức thông báo khuyến mãi ngày</td>
                        <td>{{ $responseData['dailyQuotaPromotion'] ?? 'Không có dữ liệu' }}</td>
                    </tr>
                    <tr>
                        <td>Số thông báo khuyến mãi còn lại trong ngày</td>
                        <td>{{ $responseData['remainingQuotaPromotion'] ?? 'Không có dữ liệu' }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="alert alert-warning" role="alert">
                Không có dữ liệu hoặc lỗi khi lấy thông tin hạn ngạch.
            </div>
        @endif
    </div>
@endsection
