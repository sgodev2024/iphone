@extends('admin.layout.index')

@section('content')


    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Phiếu kiểm kho', 'url' => route('admin.check.index')], ['label' => 'Danh sách']]" />
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
                    <a href="{{ route('admin.check.index') }}">PHIẾU KIỂM KHO</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.check.index') }}">DANH SÁCH</a>
                </li>
            </ul> --}}
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Danh sách phiếu kiểm kho</h4>
                    </div>

                    <div class="card-body">
                        <div class="">
                            <!-- Filter Form -->
                            <form action="{{ route('admin.check.filter') }}" method="GET">
                                <div class="row">
                                    <!-- Start Date Input -->
                                    <div class="col-md-4 mb-3">
                                        <label for="start_date">Ngày bắt đầu</label>
                                        <input type="date" name="startDate" id="start_date" class="form-control"
                                            value="{{ old('start_date') }}">
                                    </div>

                                    <!-- End Date Input -->
                                    <div class="col-md-4 mb-3">
                                        <label for="end_date">Ngày kết thúc</label>
                                        <input type="date" name="endDate" id="end_date" class="form-control"
                                            value="{{ old('end_date') }}">
                                    </div>

                                    <!-- Phone Number Input -->
                                    <div class="col-md-4 mb-3">
                                        <label for="phone">Tìm số điện thoại</label>
                                        <input type="text" name="phone" id="phone" class="form-control"
                                            placeholder="Nhập số điện thoại" value="{{ old('phone') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="text-center mt-2">
                                        <div class="d-inline-block">
                                            <button type="submit" class="btn btn-primary">  <i class="fa fa-search"></i></button>
                                        </div>
                                        <div class="d-inline-block ml-2">
                                            <button type="button"
                                                onclick="window.location.href='{{ route('admin.check.index') }}'"
                                                class="btn btn-danger">  <i class="fa fa-sync"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="col-sm-12 col-md-6" id="delete-selected-container" style="display: none;">
                                <button id="btn-delete-selected" class="btn" style="background: rgb(242, 91, 91); color: white" data-model='CheckInventory'> Xóa </button>
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
                                                    <th>Mã phiếu kiểm</th>
                                                    <th>Người tạo</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Ghi chú</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($check as $item)
                                                    <tr>
                                                        <td><input type="checkbox" class="product-checkbox" value="{{ $item->id }}"></td>
                                                        <td>
                                                            <a style="color: black; font-weight:bold"
                                                                href="{{ route('admin.check.detail', ['id' => $item->id]) }}">{{ $item->test_code }}</a>
                                                        </td>
                                                        <td><a style="color: black"
                                                                href="{{ route('admin.staff.edit', ['id' => $item->user->id]) }}">{{ $item->user->name ?? '' }}
                                                        </td>
                                                        <td>{{ $item->created_at->format('d/m/y') }}</td>
                                                        <td>{{ $item->note }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="text-center" colspan="6">Không có phiếu kiểm nào</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <!-- Pagination -->
                                        {{ $check->appends(request()->query())->links('vendor.pagination.custom') }}
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
