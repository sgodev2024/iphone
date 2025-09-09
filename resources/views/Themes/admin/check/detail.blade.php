@extends('admin.layout.index')
@section('content')
    <style>
        .breadcrumbs {
            background: #fff;
            padding: 0.75rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .breadcrumbs a {
            color: #007bff;a
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumbs i {
            color: #6c757d;
        }

        #detail_kho{
            color: black !important;
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
                    <a href="{{ route('admin.check.index') }}">Phiếu kiểm kho</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a>Chi tiết</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h4 class="text-center mb-sm-0 font-size-18">Chi tiết phiếu kiểm số {{ $check->id }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin nhân viên</b></h5>
                                <table class="table table-bordered table-hover">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-user"></i>Người tạo</th>
                                            <td>
                                                <div class="nowrap"> <a style="color: black"
                                                        href="{{ route('admin.staff.edit', ['id' => $check->user->id]) }}">{{ $check->user->name }}</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-phone"></i> Số điện thoại</th>
                                            <td>
                                                <div class="nowrap">{{ $check->user->phone }}</div>
                                            </td>
                                        </tr>
                                        {{-- <tr>
                                            <th scope="row"><i class="fas fa-envelope"></i> Email</th>
                                            <td>
                                                <div class="nowrap">{{ $check->user->email }}</div>
                                            </td>
                                        </tr> --}}
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin phiếu kiểm</b></h5>
                                <table class="table table-bordered table-hover">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-receipt"></i> Mã phiếu kiểm</th>
                                            <td>
                                                <div class="nowrap">{{ $check->test_code }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-box-open"></i> Ngày kiểm </th>
                                            <td>
                                                <div class="nowrap">{{ $check->created_at }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <table id="basic-datatables" class="display table table-striped table-hover dataTable"
                                    role="grid" aria-describedby="basic-datatables_info">
                                    <thead>
                                        <tr role="row">
                                            <th>Mã hàng hóa </th>
                                            <th>Tên hàng hóa</th>
                                            <th>Tồn kho</th>
                                            <th>Thực tế</th>
                                            <th>Số lượng lệch</th>
                                            <th>Giá trị lệch</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($details as $detail)
                                            <tr>
                                                <td>{{ $detail->product->code }}</td>
                                                <td>{{ $detail->product->name }}</td>
                                                <td>{{ $detail->product->quantity }}</td>
                                                <td>{{ $detail->difference + $detail->product->quantity }}</td>
                                                <td>{{ $detail->difference }}</td>
                                                <td>{{ number_format($detail->gia_chenh_lech) }}</td>
                                            </tr>
                                        @endforeach



                                    </tbody>
                                </table>
                                <div style="display: flex; justify-content: flex-end; ">
                                    <div >
                                        <table cellspacing="0" id="detail_kho">
                                            <tbody style="line-height: 30px; ">
                                                <tr>
                                                    <td width="250" >Tổng thực tế ({{ $tongthucte }}) : </td>
                                                    <td width="110" >  {{ number_format($sum1)  }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="200">Tổng chênh lệch tăng ({{  $sltang }}) :</td>
                                                    <td>{{ number_format($sum2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="200" >Tổng chênh lệch giảm ({{ $slgiam }}) :</td>
                                                    <td>  {{ number_format($sum3) }} </td>
                                                </tr>
                                                <tr>
                                                    <td width="200" >Tổng chênh lệch ({{ $tong_lech }}) :</td>
                                                    <td>  {{ number_format($sum3 + $sum2)  }} </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.check.index') }}" class="btn btn-primary w-md">
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
