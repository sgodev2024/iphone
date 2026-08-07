@extends('admin.layout.index')
@section('content')

    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Phiếu thu'],'url' => route('admin.quanlythuchi.receipts.index'),['label' => 'Danh sách']]" />
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
                    <a href="">Phiếu thu</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="">Danh sách</a>
                </li>
            </ul> --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Danh sách đã thu</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    @if (count($debtClient) > 0)
                                        <div class="col-sm-12 col-md-6">
                                            <div class="dataTables_length" id="basic-datatables_length">
                                                <a class="btn btn-primary"
                                                    href="{{ route('admin.quanlythuchi.receipts.add') }}">
                                                    <i style="padding: 0px 5px;" class="fas fa-plus"></i> Thêm phiếu thu
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6 mt-3" id="delete-selected-container" style="display: none;">
                                        <button id="btn-delete-selected" class="btn"
                                            style="background: rgb(242, 91, 91); color: white" data-model='Receipts'> Xóa
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="check-all"></th>
                                                    <th>STT</th>
                                                    <th>Mã phiếu</th>
                                                    <th>Khách hàng</th>
                                                    <th>Nội dung</th>
                                                    <th>Tiền thu</th>
                                                    <th>Ngày tạo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if ($receipts && $receipts->count() > 0)
                                                    @foreach ($receipts as $key => $value)
                                                        @if (is_object($value))
                                                            <tr>
                                                                <td><input type="checkbox" class="product-checkbox" value="{{ $value->id }}"></td> <!-- Checkbox item -->
                                                                <td>{{ $key + 1 }}</td>
                                                                <td><a style="color: black; font-weight:bold"
                                                                        href="{{ route('admin.quanlythuchi.receipts.detail', ['id' => $value->id]) }}">{{ $value->receipt_code ?? '' }}</a>
                                                                </td>
                                                                <td>{{ $value->client->name ?? '' }}</td>
                                                                <td>{{ $value->content ?? '' }}</td>
                                                                <td>{{ number_format($value->amount_spent) ?? '' }}</td>
                                                                <td>{{ $value->date_spent ?? '' }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td class="text-center" colspan="7">Chưa có phiếu thu nào</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                        @if ($receipts instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            {{ $receipts->links('vendor.pagination.custom') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
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
                    title: 'Khách hàng',
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
