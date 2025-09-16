@extends('admin.layout.index')
@section('content')
    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Công nợ'],['label' => 'Khách hàng']]" />
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
                    <a href="">Công nợ</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="">Khách hàng</a>
                </li>
            </ul> --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Danh sách công nợ khách hàng</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                {{-- <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <div class="dataTables_length" id="basic-datatables_length">

                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <form action="{{ route('admin.client.filter') }}" method="GET">
                                            <div class="dataTables_filter">
                                                <label>Tìm kiếm</label>
                                                <input type="text" name="phone" class="form-control form-control-sm"
                                                    placeholder="Nhập số điện thoại" value="{{ old('phone') }}">
                                            </div>
                                        </form>
                                    </div>
                                </div> --}}
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr>
                                                    <th>STT</th>
                                                    <th>Mã công nợ</th>
                                                    <th>Tên khách hàng</th>
                                                    <th>Điện thoại</th>
                                                    <th>Tiền nợ</th>
                                                    <th>Nội dung</th>
                                                    <th>Ngày tạo</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if ($debtclients && $debtclients->count() > 0)
                                                    @foreach ($debtclients as $key => $value)
                                                        @if (is_object($value))
                                                            <tr>
                                                                <td>{{ $key + 1 }}</td>
                                                                <td><a href="{{ route('admin.debts.client.detail', ['id' => $value->id]) }}">{{ $value->code?? '' }}</a></td>
                                                                <td>{{ $value->client->name ?? '' }}</td>
                                                                <td>{{ $value->client->phone ?? '' }}</td>
                                                                <td>{{ number_format($value->amount) ?? '' }}</td>
                                                                <td>{{ $value->description ?? '' }}</td>

                                                                <td>{{ $value->created_at ?? '' }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td class="text-center" colspan="7">
                                                            <div class="">
                                                                Chưa có công nợ khách hàng
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                        {{-- @if ($clients instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            {{ $clients->links('vendor.pagination.custom') }}
                                        @endif --}}
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
