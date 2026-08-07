@forelse ($orderedAccounts as $account)
    <tr>
        {{-- Checkbox --}}
        <td class="text-center account-col-check">
            <input type="checkbox" class="item-checkbox" data-id="{{ $account->id }}">
        </td>

        {{-- STT --}}
        <td class="account-col-index">{{ $loop->iteration }}</td>

        {{-- Code (thụt lề theo cấp) --}}
        <td class="account-col-code">
            <span class="account-code-wrap" style="--account-level: {{ (int) $account->level_display }}">
                {{ $account->code }}
            </span>
        </td>

        {{-- Name --}}
        <td class="account-col-name">
            <span class="account-name-wrap" style="--account-level: {{ (int) $account->level_display }}">
                {!! $account->parent_id === 1 && !$account->is_default ? '<i class="fas fa-money-bill-wave"></i>' : '' !!}
                {!! $account->parent_id === 5 && !$account->is_default ? '<i class="fas fa-university"></i>' : '' !!}
                <span class="account-name-text" title="{{ $account->name }}">{{ $account->name }}</span>
            </span>
        </td>

        <td class="text-center account-col-default">
            {!! $account->is_default
                ? '<i class="fas fa-check text-success account-boolean-icon" title="Là tài khoản mặc định" aria-label="Là tài khoản mặc định"></i>'
                : '<i class="fas fa-times text-danger account-boolean-icon" title="Không phải tài khoản mặc định" aria-label="Không phải tài khoản mặc định"></i>' !!}
        </td>

        {{-- Status --}}
        <td class="text-center account-col-status">
            {!! $account->status
                ? '<i class="fas fa-check text-success account-boolean-icon" title="Đang hoạt động" aria-label="Đang hoạt động"></i>'
                : '<i class="fas fa-times text-danger account-boolean-icon" title="Ngừng hoạt động" aria-label="Ngừng hoạt động"></i>' !!}
        </td>

        {{-- Creator --}}
        <td class="text-center account-col-creator">{{ $account->creator?->full_name }}</td>

        {{-- Operation Dropdown --}}
        <td class="text-center account-col-action">
            <div class="dropdown account-action-dropdown">
                <button class="btn btn-sm btn-light account-action-btn" type="button" data-bs-toggle="dropdown"
                    data-bs-boundary="viewport" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
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
