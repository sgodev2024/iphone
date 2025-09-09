@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'NHẬP HÀNG'], ['label' => 'DANH SÁCH PHIẾU NHẬP']]" />
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    {{-- Header --}}
                    <div class="card-header d-flex justify-content-between align-items-center">
                        {{-- Form tìm kiếm --}}
                        <form method="GET" action="{{ route('admin.importproduct.index') }}"
                            class="d-flex align-items-center">
                            <!-- Ô tìm kiếm -->
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control me-2"
                                style="width: 250px;" placeholder="Tìm kiếm...">
                            <!-- Nút reset -->
                            <button type="button" class="btn" id="btn-reset"> <i
                                    class="fa-solid fa-rotate"></i></button>
                        </form>
                        {{-- Nút nhập hàng --}}
                        <a class="btn btn-success" href="{{ route('admin.importproduct.add') }}">
                            <i class="fa-solid fa-plus"></i> Nhập hàng
                        </a>
                    </div>
                    {{-- Body --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="basic-datatables" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
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
                                    @forelse ($import as $key => $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="product-checkbox" value="{{ $item->id }}">
                                            </td>
                                            <td>{{ $import->firstItem() + $key }}</td>
                                            <td>
                                                <a style="font-weight: 900; color: black"
                                                    href="{{ route('admin.importproduct.importCoupon.detail', ['id' => $item->id]) }}">
                                                    {{ $item->coupon_code }}
                                                </a>
                                            </td>
                                            <td>{{ $item->user->name }}</td>
                                            <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $item->company->name ?? '' }}</td>
                                            <td>{{ number_format($item->total, 0, ',', '.') }} đ</td>
                                            <td>{{ $item->payment_ncc ? number_format($item->payment_ncc, 0, ',', '.') : 0 }}
                                                đ</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Không có dữ liệu</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="d-flex justify-content-end">
                            {{ $import->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
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
