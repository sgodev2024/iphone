<table id="basic-datatables" class="display table table-striped table-hover dataTable" role="grid"
    aria-describedby="basic-datatables_info">
    <thead>
        <tr>
            <th>STT</th>
            <th>Tên chiến dịch</th>
            <th>Tên template</th>
            <th>Ngày tạo</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        @if ($campaigns->count() > 0)
            @foreach ($campaigns as $key => $campaign)
                <tr>
                    <td>{{ ($campaigns->currentPage() - 1) * $campaigns->perPage() + $loop->index + 1 }}</td>
                    <td>{{ $campaign->name ?? 'N/A' }}</td>
                    <td>{{ $campaign->template->template_name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($campaign->created_at)->format('d/m/Y') }}</td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="toggle-status" data-id="{{ $campaign->id }}"
                                {{ $campaign->status == 1 ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>
                        <a class="btn btn-warning"
                            href="{{ route('super.campaign.detail', ['id' => $campaign->id]) }}">Sửa</a>
                        <a class="btn btn-danger btn-delete" data-id="{{ $campaign->id }}" href="#">Xóa</a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="text-center" colspan="5">
                    Chưa có chiến dịch nào
                </td>
            </tr>
        @endif
    </tbody>
</table>
