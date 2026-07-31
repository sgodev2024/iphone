<div class="company-table-hint d-md-none">Vuốt ngang để xem đầy đủ bảng</div>
<div class="company-table-scroll">
    <table class="table table-hover table-striped table-bordered mt-3 company-table" role="grid">
        <thead>
            <tr>
                <th style="width: 3%"><input type="checkbox" id="check-all"></th>
                <th style="width: 14%" class="company-col-date"># | Ngày tạo</th>
                <th class="company-col-info">Thông tin công ty</th>
                <th style="width: 25%" class="company-col-address">Địa chỉ</th>
                <th style="width: 12%" class="company-col-status">Trạng thái</th>
                <th style="width: 12%" class="text-center company-col-actions">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($companies as $company)
                <tr>
                    <td><input type="checkbox" class="checked-item" value="{{ $company->id }}"></td>
                    <td class="company-col-date">
                        {{ ($companies->currentPage() - 1) * $companies->perPage() + $loop->iteration }}
                        | {{ $company->created_at->format('d/m/Y') }}
                    </td>
                    <td class="company-col-info">
                        <div><strong>{{ $company->name }}</strong></div>
                        <div class="company-contact-line">Email: {{ $company->email }}</div>
                        <div class="company-contact-line">Phone: {{ $company->phone }}</div>
                    </td>
                    <td class="company-col-address">{{ $company->address }}</td>
                    <td class="company-col-status">
                        {!! $company->status
                            ? '<span class="badge bg-success">Kích hoạt</span>'
                            : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                    </td>
                    <td class="company-col-actions">
                        <div class="d-flex gap-2 justify-content-center company-row-actions">
                            <a href="/admin/company/{{ $company->id }}/edit" class="btn btn-primary btn-sm company-action-btn"
                                title="Sửa" aria-label="Sửa">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button class="btn btn-danger btn-sm btn-delete company-action-btn" data-id="{{ $company->id }}"
                                title="Xóa" aria-label="Xóa">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="6">Không có công ty nào</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $companies->links('vendor.pagination.custom') }}
    </div>
</div>
