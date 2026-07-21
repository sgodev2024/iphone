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
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Thao tác
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" id="bulk-delete">
                                            <i class="fa-solid fa-trash me-2"></i> Xóa đã chọn
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            {{-- Form tìm kiếm --}}
                            <form method="GET" action="{{ route('admin.importproduct.index') }}"
                                class="d-flex align-items-center">
                                <!-- Ô tìm kiếm -->
                                <input type="search" name="search" value="{{ request('search') }}"
                                    class="form-control me-2" style="width: 300px;" placeholder="Tìm kiếm...">
                                <!-- Nút reset -->
                                <button type="button" class="btn" id="btn-reset">
                                    <i class="fa-solid fa-rotate"></i>
                                </button>
                            </form>
                        </div>
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
                                        <th><input type="checkbox" id="select-all"></th>
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
                                                <input type="checkbox" class="row-checkbox" name="ids[]"
                                                    value="{{ $item->id }}">
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
@endsection

@push('script')
    <script>
        $(function() {
            const $selectAll = $('#select-all');
            const bulkDeleteUrl = @json(route('admin.importproduct.bulk-delete'));
            const indexUrl = @json(route('admin.importproduct.index'));

            const getRowCheckboxes = () => $('.row-checkbox');

            const updateSelectAllState = () => {
                const $rows = getRowCheckboxes();
                const total = $rows.length;
                const checked = $rows.filter(':checked').length;

                $selectAll.prop('checked', total > 0 && checked === total);
                $selectAll.prop('indeterminate', false);
            };

            $selectAll.on('change', function() {
                getRowCheckboxes().prop('checked', $(this).prop('checked'));
                updateSelectAllState();
            });

            $(document).on('change', '.row-checkbox', updateSelectAllState);

            $('#bulk-delete').on('click', function(e) {
                e.preventDefault();

                const ids = getRowCheckboxes()
                    .filter(':checked')
                    .map((i, el) => $(el).val())
                    .get();

                if (ids.length <= 0) {
                    return datgin.warning('Vui lòng chọn ít nhất một phiếu nhập cần xóa.');
                }

                Swal.fire({
                    title: 'Xác nhận xóa?',
                    text: 'Bạn có chắc chắn muốn xóa các phiếu nhập đã chọn không?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Vâng, xóa ngay!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: bulkDeleteUrl,
                        method: 'POST',
                        data: {
                            ids
                        },
                        success: (res) => {
                            datgin.success(res.message);
                            getRowCheckboxes().prop('checked', false);
                            updateSelectAllState();
                            window.location.reload();
                        },
                        error: (xhr) => {
                            datgin.error(xhr.responseJSON?.message ||
                                'Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                        }
                    });
                });
            });

            $('#btn-reset').on('click', function() {
                window.location.href = indexUrl;
            });

            updateSelectAllState();
        });
    </script>
@endpush
