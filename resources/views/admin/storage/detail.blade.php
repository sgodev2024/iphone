@extends('admin.layout.index')

@section('content')
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Kho hàng', 'url' => route('admin.storage.index')], ['label' => 'Sản phẩm trong kho']]" />

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
                    <a href="{{ route('admin.storage.index') }}">Kho hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="">Sản phẩm trong kho</a>
                </li>
            </ul> --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title text-center text-white">
                            Danh sách sản phẩm trong kho {{ $storage->name ?? 'Không xác định' }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12" id="product-table">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr role="row">
                                                    <th>STT</th>
                                                    <th>Tên</th>
                                                    <th>Số lượng(đơn vị)</th>
                                                    <th>Giá nhập</th>
                                                    <th>Giá bán</th>
                                                </tr>
                                            </thead>
                                            @if ($product->count() > 0)
                                                <tbody>
                                                    @foreach ($product as $key => $value)
                                                        <tr id="product-{{ $value->id }}">
                                                            <td>{{ ($product->currentPage() - 1) * $product->perPage() + $loop->index + 1 }}
                                                            </td>
                                                            <td>{{ $value->product->name ?? 'Chưa có tên' }}</td>
                                                            <td>{{ $value->quantity ?? '0' }}
                                                                {{ $value->product->product_unit ?? 'đơn vị' }}</td>
                                                            <td>{{ number_format($value->product->price) ?? '0' }} đ</td>
                                                            <td>{{ number_format($value->product->price_buy) ?? '0' }} đ</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            @else
                                                <tbody>
                                                    <tr>
                                                        <td class="text-center" colspan="5">Kho hàng chưa có sản phẩm nào
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12" id="pagination">
                                        {{ $product->links('vendor.pagination.custom') }}
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
