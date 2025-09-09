<table id="basic-datatables" class="display table table-striped table-hover dataTable" role="grid"
    aria-describedby="basic-datatables_info">
    <thead>
        <tr>
            <th>STT</th>
            <th>Nhà cung cấp</th>
            <th>SĐT</th>
            <th>Email</th>
            <th style="text-align: center">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @if ($suppliers && $suppliers->count() > 0)
            @foreach ($suppliers as $key => $value)
                @if (is_object($value))
                    <tr>
                        <td>{{ ($suppliers->currentPage() - 1) * $suppliers->perPage() + $loop->index + 1 }}
                        </td>
                        <td>{{ $value->name ?? '' }}</td>
                        <td>{{ $value->phone ?? '' }}</td>
                        <td>{{ $value->email ?? '' }}</td>
                        <td style="text-align:center">
                            <a class="btn btn-warning"
                                href="{{ route('admin.supplier.detail', ['id' => $value->id]) }}"><i
                                    class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-danger btn-delete" data-id="{{ $value->id }}"><i
                                    class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                @endif
            @endforeach
        @else
            <tr>
                <td class="text-center" colspan="6">
                    <div class="">
                        Chưa có nhà cung cấp
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>
