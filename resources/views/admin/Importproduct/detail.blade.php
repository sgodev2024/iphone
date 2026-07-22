@extends('admin.layout.index')
@section('content')
    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Nhập hàng','url' => route('admin.importproduct.index')], ['label' => 'Chi tiết phiếu nhập']]" />
            {{-- <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.importproduct.index') }}">Nhập hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Chi tiết phiếu nhập</a>
                </li>
            </ul> --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h4 class="text-center mb-sm-0 font-size-18">Hóa đơn số {{ $importdetail->coupon_code }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin nhà cung cấp</b></h5>
                                <table class="table table-bordered table-hover detail_import">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-user"></i> Tên khách hàng</th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->companyRelation->name }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-phone"></i> Số điện thoại</th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->companyRelation->phone }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-envelope"></i> Email</th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->companyRelation->email }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-map-marker-alt"></i> Địa chỉ </th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->companyRelation->address }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-center text-primary"><b>Thông tin đơn hàng</b></h5>
                                <table class="table table-bordered table-hover detail_import">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="fas fa-receipt"></i> Mã đơn hàng</th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->coupon_code }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-warehouse"></i> Kho nhập</th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->storage->name ?? '' }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-user-tie"></i> Tên nhân viên</th>
                                            <td>
                                                <div class="nowrap">{{ $importdetail->user->name }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-dollar-sign"></i> Tổng tiền</th>
                                            <td>
                                                <div class="nowrap">{{ number_format($importdetail->total) }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="fas fa-dollar-sign"></i> Đã trả</th>
                                            <td>
                                                <div class="nowrap">{{ number_format($importdetail->payment_ncc) }}</div>
                                            </td>
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
                                            <th>Mã hàng hóa </th>
                                            <th>Tên hàng hóa</th>
                                            <th>Số lượng nhập</th>
                                            <th>Đơn giá cũ</th>
                                            <th>Đơn giá mới</th>
                                            <th>IMEI</th>
                                            <th>Tổng tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($importdetail->details as $detail)
                                            <tr>
                                                <td>{{ $detail->product ? $detail->product->code : '' }}</td>
                                                <td>{{ $detail->product ? $detail->product->name : '' }}</td>
                                                <td>{{ $detail->quantity }}</td>
                                                <td>{{ number_format($detail->old_price) }}</td>
                                                <td>{{ number_format($detail->price) }}</td>
                                                <td>
                                                    @forelse ($detail->imeis as $imei)
                                                        <div class="font-monospace">{{ $imei->imei }}</div>
                                                    @empty
                                                        <span class="text-muted">Dữ liệu cũ</span>
                                                    @endforelse
                                                </td>
                                                <td>{{ number_format($detail->price * $detail->quantity) }}</td>

                                            </tr>
                                        @endforeach



                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.importproduct.index') }}" class="btn btn-primary w-md">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
