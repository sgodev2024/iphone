<div class="brand-table-scroll">
    <table class="table table-hover table-striped table-bordered mt-3 brand-table" role="grid">
        <thead>
            <tr>
                <th class="brand-col-check" style="width: 3%"><input type="checkbox" id="check-all"></th>
                <th class="brand-col-date" style="width: 14%"># | Ngày tạo</th>
                <th class="brand-col-logo" style="width: 10%">Logo</th>
                <th class="brand-col-info" style="width: 25%">Thông tin</th>
                <th class="brand-col-desc">Mô tả</th>
                <th class="brand-col-status" style="width: 12%">Trạng thái</th>
                <th class="text-center brand-col-actions" style="width: 12%">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($brands as $index => $brand)
                <tr>
                    <td class="brand-col-check">
                        <input type="checkbox" class="checked-item" value="{{ $brand->id }}">
                    </td>
                    <td class="brand-col-date">
                        {{ ($brands->currentPage() - 1) * $brands->perPage() + $loop->iteration }}
                        | {{ $brand->created_at->format('d/m/Y') }}
                    </td>
                    <td class="text-center brand-col-logo">
                        <img src="{{ showImage($brand->logo) }}" alt="logo" class="img-thumbnail brand-logo"
                            style="width: 50px; height: 50px; object-fit: contain;">
                    </td>
                    <td class="brand-col-info">
                        <div class="text-muted">{{ $brand->name }}</div>
                        {{-- <div class="text-muted small">{{ $brand->email }}</div>
                        <div class="text-muted small">{{ $brand->phone }}</div> --}}
                    </td>
                    <td class="brand-col-desc">
                        <div class="text-truncate brand-description" style="max-width: 200px;" title="{{ $brand->description }}">
                            {{ $brand->description ?? '-' }}
                        </div>
                    </td>
                    <td class="brand-col-status">
                        {!! $brand->status
                            ? '<span class="badge bg-success">Kích hoạt</span>'
                            : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                    </td>
                    <td class="brand-col-actions">
                        <div class="d-flex gap-2 justify-content-center brand-row-actions">
                            <a href="/admin/brand/{{ $brand->id }}/edit" class="btn btn-primary btn-sm btn-show brand-action-btn"
                                data-id="{{ $brand->id }}" title="Sửa">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button class="btn btn-danger btn-sm btn-delete brand-action-btn" data-id="{{ $brand->id }}"
                                title="Xóa">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="7">Không có thương hiệu</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $brands->links('vendor.pagination.custom') }}
    </div>
</div>
