@extends('admin.layout.index')
@section('content')
<style>
    .breadcrumbs {
    background: #fff;
    padding: 0.75rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.breadcrumbs{
    list-style: none;
    display: inline;
    width: auto;
    border-left: 1px solid #efefef;

    margin-bottom: 0;
    padding-top: 8px;
    padding-bottom: 8px;
    height: 100%;
}
.detail_import i{
    display: inline-block;
    padding-right: 10px;
}
</style>
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.importproduct.index') }}">Phiếu thu</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Chi tiết phiếu thu</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h4 class="text-center mb-sm-0 font-size-18">Hóa đơn số {{ $receipts->receipt_code }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin khách hàng</b></h5>
                                <table class="table table-bordered table-hover detail_import">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-user"></i> Tên khách hàng</th>
                                            <td><div class="nowrap">{{ $receipts->client->name }}</div></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-phone"></i> Số điện thoại</th>
                                            <td><div class="nowrap">{{ $receipts->client->phone }}</div></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-envelope"></i> Email</th>
                                            <td><div class="nowrap">{{ $receipts->client->email }}</div></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-map-marker-alt"></i> Địa chỉ </th>
                                            <td><div class="nowrap">{{ $receipts->client->address }}</div></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin phiếu thu</b></h5>
                                <table class="table table-bordered table-hover detail_import">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-receipt"></i> Mã phiếu thu</th>
                                            <td><div class="nowrap">{{ $receipts->receipt_code }}</div></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-dollar-sign"></i> Tổng tiền</th>
                                            <td><div class="nowrap">{{ number_format($receipts->amount_spent) }}</div></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <table id="basic-datatables" class="display table table-striped table-hover dataTable"
                                role="grid" aria-describedby="basic-datatables_info">
                                <thead>
                                    <tr role="row">
                                        <th>STT</th>
                                        <th>Nội dung</th>
                                        <th>Tiền </th>
                                        <th>Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($receipts->detail as $key =>  $detail)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $detail->content }}</td>
                                            <td>{{ number_format($detail->amount) }}</td>
                                            <td>{{ $detail->date}}</td>

                                        </tr>
                                    @endforeach



                                </tbody>
                            </table>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.quanlythuchi.receipts.index') }}" class="btn btn-primary w-md">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
    .card {
        border-radius: 15px;
        overflow: hidden;
    }

    .card-header {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        background: linear-gradient(135deg, #6f42c1, #007bff);
    }

    .card-body {
        padding: 2rem;
        background-color: #f8f9fa;
    }

    .table th,
    .table td {
        vertical-align: middle;
        padding: 1rem;
        font-size: 1rem;
    }

    .table th {
        background-color: #e9ecef;
        font-weight: bold;
        color: #495057;
    }

    .table-hover tbody tr:hover {
        background-color: #dee2e6;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px);
    }

    .text-primary {
        color: #007bff !important;
    }

    .nowrap {
        white-space: nowrap;
        display: flex;
        justify-content: space-between;
    }
</style>
