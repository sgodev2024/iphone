@forelse ($orderedAccounts as $account)
    <tr>
        {{-- Checkbox --}}
        <td class="text-center">
            <input type="checkbox" class="item-checkbox" data-id="{{ $account->id }}">
        </td>

        {{-- STT --}}
        <td>{{ $loop->iteration }}</td>

        {{-- Code (thụt lề theo cấp) --}}
        <td>
            {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $account->level_display) !!}
            {{ $account->code }}
        </td>

        {{-- Name --}}
        <td> {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $account->level_display) !!}
            {!! $account->parent_id === 1 && !$account->is_default ? '<i class="fas fa-money-bill-wave"></i>' : '' !!}
            {!! $account->parent_id === 5 && !$account->is_default ? '<i class="fas fa-university"></i>' : '' !!}
            {{ $account->name }}</td>

        <td class="text-center">
            {!! $account->is_default
                ? '<i class="fas fa-check text-success"></i>'
                : '<i class="fas fa-times text-danger"></i>' !!}
        </td>

        {{-- Status --}}
        <td class="text-center">
            {!! $account->status ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}
        </td>

        {{-- Creator --}}
        <td class="text-center">{{ $account->creator?->full_name }}</td>

        {{-- Operation Dropdown --}}
        <td class="text-center">
            <div class="dropdown">
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="javascript:void(0);" class="dropdown-item btn-add-child" data-id="{{ $account->id }}"
                            data-name="{{ $account->name }}">
                            <i class="fas fa-plus text-primary"></i> Thêm tài khoản con
                        </a>

                    </li>
                    <li>
                        <a href="javascript:void(0);" class="dropdown-item btn-edit-account"
                            data-id="{{ $account->id }}" data-code="{{ $account->code }}"
                            data-name="{{ $account->name }}" data-status="{{ $account->status }}"
                            data-parent-id="{{ $account->parent_id }}"
                            data-parent-name="{{ $account->parent?->name }}">
                            <i class="fas fa-edit text-warning"></i> Sửa
                        </a>

                    </li>
                    {{-- <li>
                        <a href="javascript:void(0);" class="dropdown-item btn-delete-account text-danger"
                            data-id="{{ $account->id }}">
                            <i class="fas fa-trash"></i> Xóa
                        </a>
                    </li> --}}
                </ul>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center">Không có dữ liệu</td>
    </tr>
@endforelse
