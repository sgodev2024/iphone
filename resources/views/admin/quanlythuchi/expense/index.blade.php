@extends('admin.layout.index')
@section('content')

    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Phiếu chi'], ['label' => 'Danh sách']]" />
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
                    <a href="">Phiếu chi</a>
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
                        <h4 class="card-title" style="text-align: center; color:white">Danh sách đã chi</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    @if (count($debtncc) > 0)
                                        <div class="col-sm-12 col-md-6">
                                            <div class="dataTables_length" id="basic-datatables_length">
                                                <a class="btn btn-primary"
                                                    href="{{ route('admin.quanlythuchi.expense.add') }}">
                                                    <i style="padding: 0px 5px;" class="fas fa-plus"></i> Thêm phiếu chi
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6 mt-3" id="delete-selected-container" style="display: none;">
                                        <button id="btn-delete-selected" class="btn"
                                            style="background: rgb(242, 91, 91); color: white" data-model='Expense'> Xóa
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
                                                    <th>Nhà cung cấp</th>
                                                    <th>Nội dung</th>
                                                    <th>Tiền chi</th>
                                                    <th>Ngày cập nhật</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if ($expenses && $expenses->count() > 0)
                                                    @foreach ($expenses as $key => $value)
                                                        @if (is_object($value))
                                                            <tr>
                                                                <td><input type="checkbox" class="product-checkbox"
                                                                        value="{{ $value->id }}"></td>
                                                                <!-- Checkbox item -->
                                                                <td>{{ $key + 1 }}</td>

                                                                <td><a style="color: black; font-weight:bold"
                                                                        href="{{ route('admin.quanlythuchi.expense.detail', ['id' => $value->id]) }}">{{ $value->expense_code ?? '' }}</a>
                                                                </td>
                                                                <td>{{ $value->company->name ?? '' }}</td>
                                                                <td>{{ $value->content ?? '' }}</td>
                                                                <td>{{ number_format($value->amount_spent) ?? '' }}</td>
                                                                <td>{{ $value->updated_at ?? '' }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td class="text-center" colspan="7">Chưa có phiếu chi nào</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                        @if ($expenses instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            {{ $expenses->links('vendor.pagination.custom') }}
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
