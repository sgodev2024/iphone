<table id="basic-datatables" class="display table table-striped table-hover dataTable" role="grid"
    aria-describedby="basic-datatables_info">
    <thead>
        <tr>
            <th>STT</th>
            <th>Tên khách hàng</th>
            <th>Nhóm khách hàng</th>
            <th>SĐT</th>
            <th>Email</th>
            <th>Địa chỉ</th>
            <th style="text-align: center">Hành động</th>
        </tr>
    </thead>
    <tbody>
        @if ($clients && $clients->count() > 0)
            @foreach ($clients as $key => $value)
                @if (is_object($value))
                    <tr>
                        <td>{{ ($clients->currentPage() - 1) * $clients->perPage() + $loop->index + 1 }}
                        </td>
                        <td>{{ $value->name ?? '' }}</td>
                        <td>{{ $value->clientgroup->name ?? '' }}</td>
                        <td>{{ $value->phone ?? '' }}</td>
                        <td>{{ $value->email ?? '' }}</td>
                        <td>{{ $value->address ?? '' }}</td>
                        <td style="text-align:center">
                            <a class="btn btn-warning"
                                href="{{ route('admin.client.detail', ['id' => $value->id]) }}"><i class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-danger btn-delete" data-id="{{ $value->id }}"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                @endif
            @endforeach
        @else
            <tr>
                <td class="text-center" colspan="6">
                    <div class="">
                        Chưa có khách hàng
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<script>
    $(document).ready(function() {
        // Xử lý link xóa khách hàng
        $('.btn-delete').click(function() {
            if (confirm('Bạn có chắc chắn muốn xóa?')) {
                var clientId = $(this).data('id');
                var deleteUrl = '{{ route('admin.client.delete', ['id' => ':id']) }}';
                deleteUrl = deleteUrl.replace(':id', clientId);

                $.ajax({
                    url: deleteUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#client-table').html(response
                                .table);
                            $.notify({
                                icon: 'icon-bell',
                                title: 'Khách hàng',
                                message: response.message,
                            }, {
                                type: 'success',
                                placement: {
                                    from: "bottom",
                                    align: "right"
                                },
                                time: 1000,
                            });
                        } else {
                            $.notify({
                                icon: 'icon-bell',
                                title: 'Khách hàng',
                                message: response.message,
                            }, {
                                type: 'danger',
                                placement: {
                                    from: "bottom",
                                    align: "right"
                                },
                                time: 1000,
                            });
                        }
                    },
                    error: function(xhr) {
                        $.notify({
                            icon: 'icon-bell',
                            title: 'Khách hàng',
                            message: 'Xóa khách hàng thất bại!',
                        }, {
                            type: 'danger',
                            placement: {
                                from: "bottom",
                                align: "right"
                            },
                            time: 1000,
                        });
                    }
                });
            }
        });
    });
</script>
