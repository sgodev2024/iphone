@extends('admin.layout.index')

@section('content')
 

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
                    <a href="#">Nhóm khách hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Danh sách</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Nhóm khách hàng</h4>
                    </div>

                    <div class="card-body">
                        <div class="">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr role="row">
                                                    <th>Tên nhóm</th>
                                                    <th>Mã nhóm</th>
                                                    <th>Mô tả</th>
                                                    <th>Số lượng khách hàng</th>
                                                    <th>Ngày tạo</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($clientgroup as $item)
                                                    <tr>
                                                        <td>
                                                            <a style="color: black; font-weight:bold"
                                                                href="">{{ $item->name ?? "" }}</a>
                                                        </td>
                                                        <td>
                                                            <a style="color:black"
                                                                href="">
                                                                {{ $item->code ?? '' }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $item->description }}</td>
                                                        <td>
                                                            {{ count($item->client) ?? 0 }}
                                                        </td>

                                                        <td>{{ $item->created_at }} </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="text-center" colspan="5">Chưa có nhóm khách hàng nào</td>
                                                    </tr>
                                                @endforelse
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
@endsection
