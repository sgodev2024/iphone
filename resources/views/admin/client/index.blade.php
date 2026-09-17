@extends('admin.layout.index')

@section('content')
    <style>
        .client-page, .client-page #table-wrapper { min-width: 0; max-width: 100%; }
        .client-table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .client-table { min-width: 980px; }
        .client-row-actions { white-space: nowrap; }
        @media (max-width: 767.98px) {
            .client-toolbar { align-items: stretch !important; }
            .client-toolbar > * { width: 100%; }
            .client-table-hint { display: block !important; }
        }
    </style>

    <div class="page-inner client-page">
        <x-breadcrumb :items="[['label' => 'Khách hàng']]" />

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-2 justify-content-between client-toolbar">
                    <div class="d-flex flex-wrap gap-2 flex-grow-1">
                        <input type="search" name="search" class="form-control" style="max-width: 320px"
                            placeholder="Tìm theo tên, số điện thoại, email">

                        @if (auth()->user()->isAdministrator())
                            <select id="branch-filter" class="form-select" style="max-width: 260px">
                                <option value="">Tất cả cửa hàng</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <button type="button" class="btn btn-outline-secondary" id="btn-reset" title="Làm mới">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @can('client.create')
                            <a href="{{ route('admin.client.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus me-1"></i> Thêm khách hàng
                            </a>
                        @endcan
                        @can('client.import')
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#client-import-modal">
                                <i class="fa-solid fa-file-import me-1"></i> Import Excel
                            </button>
                        @endcan
                        @can('client.export')
                            <button type="button" class="btn btn-outline-success" id="btn-export">
                                <i class="fa-solid fa-file-excel me-1"></i> Xuất Excel
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="table-wrapper">
                    <div class="text-center py-4 text-muted">Đang tải danh sách khách hàng...</div>
                </div>
            </div>
        </div>
        @can('client.import')
            <div class="modal fade" id="client-import-modal" tabindex="-1" aria-labelledby="client-import-title"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('admin.client.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="client-import-title">Import khách hàng</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                @if (auth()->user()->isAdministrator())
                                    <div class="mb-3">
                                        <label for="import-branch-id" class="form-label">Cửa hàng đích <span class="text-danger">*</span></label>
                                        <select id="import-branch-id" name="branch_id" class="form-select" required>
                                            <option value="">-- Chọn cửa hàng --</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label for="client-import-file" class="form-label">File Excel (.xlsx, .xls)</label>
                                    <input type="file" id="client-import-file" name="file" class="form-control"
                                        accept=".xlsx,.xls" required>
                                </div>
                                <a href="{{ route('admin.client.import.template') }}">
                                    <i class="fa-solid fa-download me-1"></i> Tải file mẫu
                                </a>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary">Import Excel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('script')
    <script>
        $(function() {
            @if ($errors->has('file') || $errors->has('branch_id'))
                new bootstrap.Modal(document.getElementById('client-import-modal')).show();
            @endif
            const clientIndexUrl = @json(route('admin.client.index'));
            const clientExportUrl = @json(route('admin.client.export'));
            const $tableWrapper = $('#table-wrapper');
            const $searchInput = $('input[name="search"]');
            const $branchFilter = $('#branch-filter');
            let currentRequest = null;
            let debounceTimer = null;

            function currentFilters() {
                return {
                    s: $searchInput.val().trim(),
                    branch_id: $branchFilter.length ? $branchFilter.val() : ''
                };
            }

            function fetchClients(page = 1) {
                if (currentRequest) currentRequest.abort();

                $tableWrapper.css({ opacity: 0.55, pointerEvents: 'none' });
                currentRequest = $.ajax({
                    url: clientIndexUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: { ...currentFilters(), page },
                    success: function(response) {
                        $tableWrapper.html(response.html ?? '<div class="alert alert-warning mb-0">Không có dữ liệu.</div>');
                    },
                    error: function(xhr, textStatus) {
                        if (textStatus === 'abort') return;
                        const message = xhr.responseJSON?.message ?? 'Không thể tải danh sách khách hàng.';
                        $tableWrapper.html('<div class="alert alert-danger mb-0">' + message + '</div>');
                    },
                    complete: function() {
                        currentRequest = null;
                        $tableWrapper.css({ opacity: 1, pointerEvents: 'auto' });
                    }
                });
            }

            function debounceFetch() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchClients(1), 400);
            }

            $searchInput.on('input', debounceFetch);
            $branchFilter.on('change', () => fetchClients(1));

            $('#btn-reset').on('click', function() {
                $searchInput.val('');
                $branchFilter.val('');
                fetchClients(1);
            });

            $(document).on('click', '#table-wrapper .pagination a.page-link', function(event) {
                event.preventDefault();
                const href = $(this).attr('href');
                if (! href) return;
                fetchClients(Number(new URL(href, window.location.origin).searchParams.get('page')) || 1);
            });

            $(document).on('click', '.btn-delete-client', function() {
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Xác nhận ngừng hoạt động?',
                    text: 'Khách hàng sẽ không còn xuất hiện trong giao dịch mới; lịch sử cũ vẫn được giữ nguyên.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (! result.isConfirmed) return;
                    $.ajax({
                        url,
                        method: 'DELETE',
                        success: (response) => {
                            datgin.success(response.message);
                            fetchClients(1);
                        },
                        error: (xhr) => datgin.error(xhr.responseJSON?.message ?? 'Không thể xóa khách hàng.')
                    });
                });
            });

            $('#btn-export').on('click', function() {
                const url = new URL(clientExportUrl, window.location.origin);
                Object.entries(currentFilters()).forEach(([key, value]) => {
                    if (value) url.searchParams.set(key, value);
                });
                window.location.href = url.toString();
            });

            fetchClients();
        });
    </script>
@endpush
