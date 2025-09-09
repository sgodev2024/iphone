<table id="staff-table" class="table table-hover table-striped table-bordered mt-3">
    <thead>
        <tr role="row">
            <th style="width: 10px;"><input type="checkbox" id="check-all"></th>
            <th style="width: 12%"># | NGÀY TẠO</th>
            <th>TÊN CHI NHÁNH</th>
            <th>ĐỊA CHỈ</th>
            <th style="width: 18%">SỐ ĐIÊN THOẠI | EMAIL</th>
            <th style="width: 12%">TRẠNG THÁI</th>
            <th style="width: 10%">HÀNH ĐỘNG</th>
        </tr>
    </thead>
    <tbody>
        @if ($branchs->isNotEmpty())
            @foreach ($branchs as $branch)
                <tr>
                    <td>
                        <input type="checkbox" class="checked-item" value="{{ $branch->id }}">
                    </td>
                    <td>
                        {{ $loop->iteration }} | {{ $branch->created_at->format('d/m/Y') }}
                    </td>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->address }}</td>
                    <td>
                        {{ $branch->phone ?? '-' }} <br> {{ $branch->email ?? '-' }}
                    </td>
                    <td>{{ $branch->status_text }}</td>

                    <td>
                        <div class="d-flex gap-2 justify-content-center">
                            <button class="btn btn-primary btn-sm btn-edit" data-id="{{ $branch->id }}">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>

                            <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $branch->id }}">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>

                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="text-center" colspan="7">
                    <div class="">
                        Không có chi nhánh
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row">
    <div class="col-sm-12" id="pagination">
        {{ $branchs->links('vendor.pagination.custom') }}
    </div>
</div>
