<table class="table table-hover table-striped table-bordered mt-3" role="grid">
    <thead>
        <tr>
            <th style="width: 3%"><input type="checkbox" id="check-all"></th>
            <th style="width: 14%"># | Ngày tạo</th>
            <th>Thông tin công ty</th>
            <th style="width: 25%">Địa chỉ</th>
            <th style="width: 12%">Trạng thái</th>
            <th style="width: 12%" class="text-center">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($companies as $company)
            <tr>
                <td><input type="checkbox" class="checked-item" value="{{ $company->id }}"></td>
                <td>
                    {{ ($companies->currentPage() - 1) * $companies->perPage() + $loop->iteration }}
                    | {{ $company->created_at->format('d/m/Y') }}
                </td>
                <td>
                    <div><strong>{{ $company->name }}</strong></div>
                    <div>Email: {{ $company->email }}</div>
                    <div>Phone: {{ $company->phone }}</div>
                </td>
                <td>{{ $company->address }}</td>
                <td>
                    {!! $company->status
                        ? '<span class="badge bg-success">Kích hoạt</span>'
                        : '<span class="badge bg-danger">Không kích hoạt</span>' !!}
                </td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="/admin/company/{{ $company->id }}/edit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $company->id }}">
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

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $companies->links('vendor.pagination.custom') }}
    </div>
</div>
