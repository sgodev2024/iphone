<div class="table-responsive">
    <table id="basic-datatables" class="table display table-striped table-hover">
        <thead>
            <tr>
                <th>STT</th>
                <th>Nhà cung cấp</th>
                {{-- <th>SĐT</th>
                <th>Email</th> --}}
                <th>Mã số thuế</th>
                <th>Tài khoản ngân hàng</th>
                {{-- <th>Địa chỉ</th> --}}
                <th style="text-align: center">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @if ($companies && $companies->count() > 0)
                @foreach ($companies as $key => $value)
                    @if (is_object($value))
                        <tr>
                            <td>{{ ($companies->currentPage() - 1) * $companies->perPage() + $loop->index + 1 }}</td>
                            <td><p>{{ $value->name ?? '' }}</p> <p>{{ $value->phone ?? '' }}</p> <p>{{ $value->email ?? '' }}</p></td>
                            {{-- <td>{{ $value->phone ?? '' }}</td>
                            <td>{{ $value->email ?? '' }}</td> --}}
                            <td>{{ $value->tax_number ?? '' }}</td>
                            <td>{{ $value->bank_account }} ({{ $value->bank->shortName }})</td>
                            {{-- <td>{{ $value->address ?? '' }}, {{ $value->city->name ?? '' }}</td> --}}
                            <td style="text-align:center">
                                <a class="btn btn-warning"
                                    href="{{ route('admin.company.detail', ['id' => $value->id]) }}"><i
                                        class="fa-solid fa-pen"></i></a>
                                @if ($value->hasRepresentative())
                                    <a class="btn btn-dark"
                                        href="{{ route('admin.supplier.index', ['company_id' => $value->id]) }}"><i
                                            class="fa-solid fa-user-group"></i></a>
                                @else
                                    <a class="btn btn-secondary"
                                        href="{{ route('admin.supplier.add', ['company_id' => $value->id]) }}"><i
                                            class="fa-solid fa-user-plus"></i></a>
                                @endif
                                <button class="btn btn-danger btn-delete" data-id="{{ $value->id }}"><i
                                        class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td class="text-center" colspan="8">Chưa có nhà cung cấp</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
