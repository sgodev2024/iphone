<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 3%"><input type="checkbox" id="check-all"></th>
            <th style="width: 14%"># | Ngày tạo</th>
            <th style="width: 10%">Logo</th>
            <th style="width: 25%">Thông tin</th>
            <th>Mô tả</th>
            <th style="width: 12%">Trạng thái</th>
            <th class="text-center" style="width: 12%">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($brands as $index => $brand)
            <tr>
                <td>
                    <input type="checkbox" class="checked-item" value="{{ $brand->id }}">
                </td>
                <td>
                    {{ ($brands->currentPage() - 1) * $brands->perPage() + $loop->iteration }}
                    | {{ $brand->created_at->format('d/m/Y') }}
                </td>
                <td class="text-center">
                    <img src="{{ showImage($brand->logo) }}" alt="logo" class="img-thumbnail"
                        style="width: 50px; height: 50px; object-fit: contain;">
                </td>
                <td>
                    <div class="text-muted">{{ $brand->name }}</div>
                    {{-- <div class="text-muted small">{{ $brand->email }}</div>
                    <div class="text-muted small">{{ $brand->phone }}</div> --}}
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 200px;" title="{{ $brand->description }}">
                        {{ $brand->description ?? '-' }}
                    </div>
                </td>
                <td>
                    {!! $brand->status
                        ? '<span class="badge bg-success">Kích hoạt</span>'
                        : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                </td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="/admin/brand/{{ $brand->id }}/edit" class="btn btn-primary btn-sm btn-show"
                            data-id="{{ $brand->id }}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $brand->id }}">
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

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $brands->links('vendor.pagination.custom') }}
    </div>
</div>
