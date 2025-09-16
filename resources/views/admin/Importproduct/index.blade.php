@extends('admin.layout.index')

@section('content')


<div class="page-inner">
    <div class="page-header">
        <x-breadcrumb :items="[['label' => 'NHẬP HÀNG'], ['label' => 'DANH SÁCH PHIẾU NHẬP']]" />
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
                <a href="#">NHẬP HÀNG</a>
            </li>
            <li class="separator">
                <i class="icon-arrow-right"></i>
            </li>
            <li class="nav-item">
                <a href="#">DANH SÁCH PHIẾU NHẬP</a>
            </li>
        </ul> --}}
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" style="text-align: center; color:white">Phiếu nhập hàng</h4>
                </div>

                <div class="card-body">
                    <div class="">
                        <div id="import">
                            <a class="btn btn-primary" href="{{ route('admin.importproduct.add') }}"><i
                                    style="padding: 0px 5px;" class="fas fa-plus"></i>Nhập hàng</a>
                        </div>
                        <div class="mt-2" id="delete-selected-container" style="display: none;">
                            <button id="btn-delete-selected" class="btn" style="background: rgb(242, 91, 91); color: white" data-model='ImportCoupon'> Xóa </button>
                        </div>
                        <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                            <div class="row">
                                <div class="col-sm-12">
                                    <table id="basic-datatables"
                                        class="display table table-striped table-hover dataTable" role="grid"
                                        aria-describedby="basic-datatables_info">
                                        <thead>
                                            <tr role="row">
                                                <th><input type="checkbox" id="check-all"></th>
                                                <th>STT</th>
                                                <th>Mã đơn hàng</th>
                                                <th>Nhân viên</th>
                                                <th>Ngày tạo</th>
                                                <th>Nhà cung cấp</th>
                                                <th>Tổng tiền</th>
                                                <th>Đã trả</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($import)
                                                @foreach ($import as $key => $item )
                                                <tr role="row">
                                                    <td><input type="checkbox" class="product-checkbox" value="{{ $item->id }}"></td> <!-- Checkbox item -->
                                                    <td>{{ $key+1 }}</td> <!-- Checkbox item -->
                                                    <td><a style="font-weight: 900; color: black" href="{{ route('admin.importproduct.importCoupon.detail', ['id'=> $item->id]) }}">{{ $item->coupon_code }}</a></td>
                                                    <td>{{ $item->user->name }}</td>
                                                    <td>{{ $item->created_at }}</td>
                                                    <td>{{ $item->company->name ?? '' }}</td>
                                                    <td>{{ number_format($item->total, 0, ',', '.') }} đ</td>
                                                    <td>{{ $item->payment_ncc ? number_format($item->payment_ncc, 0, ',', '.') : 0  }} đ</td>
                                                </tr>
                                                @endforeach
                                            @else

                                            @endif
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->

                                </div>
                            </div>
                        </div>
                        <!-- End Table -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-notify/0.2.0/js/bootstrap-notify.min.js"></script>
    @if (session('success'))
        <script>
            $(document).ready(function() {
                $.notify({
                    icon: 'icon-bell',
                    title: 'Sản phẩm',
                    message: '{{ session('success') }}',
                }, {
                    type: 'secondary',
                    placement: {
                        from: "bottom",
                        align: "right"
                    },
                    time: 1000,
                });
            });
        </script>
    @endif
@endsection
