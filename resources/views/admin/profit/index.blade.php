@extends('admin.layout.index')

@section('content')
    <style>
        .profit-page .loader {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid #007bff;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .profit-page .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .profit-page .close:hover,
        .profit-page .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .profit-page #error {
            color: red;

        }

        .profit-page .modal-dialog {
            margin: 0 auto;
            max-width: 500px;
        }

        .profit-page .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profit-page .profit-table-hint,
        .profit-page .profit-page-arrow,
        .profit-page .profit-pagination-status {
            display: none;
        }

        .profit-page .profit-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .profit-page .profit-date-field input {
            flex: 1 1 0;
            min-width: 0;
        }
        @media (max-width: 767.98px) {
            .profit-page {
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin-right: auto;
                margin-left: auto;
                padding-right: 10px !important;
                padding-left: 10px !important;
                overflow-x: visible;
            }

            .profit-page .page-header {
                align-items: flex-start;
                margin-bottom: 10px;
            }

            .profit-page .page-header .breadcrumb,
            .profit-page .page-header .breadcrumbs {
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
            }

            .profit-page > .row {
                --bs-gutter-x: 0;
                margin-right: 0;
                margin-left: 0;
            }

            .profit-page > .row > [class*="col-"] {
                min-width: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .profit-page .card {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin-right: auto;
                margin-left: auto;
                overflow: visible;
            }

            .profit-page .card-header,
            .profit-page .card-body {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding-right: 10px;
                padding-left: 10px;
            }

            .profit-page .profit-title-header {
                padding-top: 12px;
                padding-bottom: 8px;
            }

            .profit-page .profit-report-title {
                margin-bottom: 0;
                font-size: 18px;
                line-height: 1.3;
                white-space: normal;
            }

            .profit-page .profit-toolbar {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 42px;
                gap: 8px;
                align-items: center;
                justify-content: normal !important;
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .profit-page .profit-date-field {
                grid-column: 1 / -1;
                width: 100%;
                min-width: 0;
            }

            .profit-page #dateFilter {
                width: 100% !important;
            }

            .profit-page .profit-search-row {
                display: contents !important;
                min-width: 0;
            }

            .profit-page .profit-search-input {
                grid-column: 1;
                width: 100% !important;
                min-width: 0;
                max-width: 100%;
                margin-right: 0 !important;
            }

            .profit-page #btn-reset {
                display: inline-flex;
                grid-column: 2;
                align-items: center;
                justify-content: center;
                width: 42px;
                min-width: 42px;
                height: 42px;
                min-height: 42px;
                padding: 0;
                border: 1px solid #d7dde7;
                border-radius: 4px;
                background: #fff;
                color: #495057;
            }

            .profit-page #dateFilter,
            .profit-page .profit-search-input,
            .profit-page #storageSelect,
            .profit-page #periodSelect {
                height: 40px;
                min-height: 40px;
                font-size: 14px;
            }

            .profit-page .profit-filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 0;
            }

            .profit-page .profit-filter-row > .profit-filter-field {
                flex: 0 0 100%;
                max-width: 100%;
                width: 100%;
                min-width: 0;
                padding-right: 0;
                padding-left: 0;
            }

            .profit-page .profit-filter-row label {
                margin-bottom: 4px;
                font-size: 13px;
            }

            .profit-page #storageSelect,
            .profit-page #periodSelect {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                text-overflow: ellipsis;
            }

            .profit-page .loader {
                margin-top: 4px;
            }

            .profit-page .profit-report-table-area {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: visible;
            }

            .profit-page .profit-item-count-row {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                margin-top: 8px;
                margin-bottom: 6px !important;
            }

            .profit-page #itemCount {
                font-weight: 600;
            }

            .profit-page .profit-table-hint {
                display: block;
                margin: 0 0 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.4;
            }

            .profit-page .profit-table-scroll {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .profit-page .profit-table {
                display: table !important;
                width: 100% !important;
                min-width: 1000px;
                table-layout: auto;
            }

            .profit-page .profit-table th,
            .profit-page .profit-table td {
                display: table-cell !important;
                vertical-align: middle;
                font-size: 13px;
            }

            .profit-page .profit-table th {
                white-space: nowrap;
            }

            .profit-page .profit-table th:nth-child(1),
            .profit-page .profit-table td:nth-child(1),
            .profit-page .profit-table th:nth-child(3),
            .profit-page .profit-table td:nth-child(3),
            .profit-page .profit-table th:nth-child(4),
            .profit-page .profit-table td:nth-child(4),
            .profit-page .profit-table th:nth-child(5),
            .profit-page .profit-table td:nth-child(5),
            .profit-page .profit-table th:nth-child(6),
            .profit-page .profit-table td:nth-child(6),
            .profit-page .profit-table th:nth-child(7),
            .profit-page .profit-table td:nth-child(7) {
                white-space: nowrap;
            }

            .profit-page .profit-table th:nth-child(1),
            .profit-page .profit-table td:nth-child(1) {
                min-width: 130px;
            }

            .profit-page .profit-table th:nth-child(2),
            .profit-page .profit-table td:nth-child(2) {
                min-width: 260px;
                white-space: normal;
                word-break: normal;
                overflow-wrap: break-word;
            }

            .profit-page .profit-table th:nth-child(3),
            .profit-page .profit-table td:nth-child(3) {
                min-width: 90px;
                text-align: center;
            }

            .profit-page .profit-table th:nth-child(4),
            .profit-page .profit-table td:nth-child(4),
            .profit-page .profit-table th:nth-child(5),
            .profit-page .profit-table td:nth-child(5),
            .profit-page .profit-table th:nth-child(6),
            .profit-page .profit-table td:nth-child(6) {
                min-width: 140px;
                text-align: right;
            }

            .profit-page .profit-table th:nth-child(7),
            .profit-page .profit-table td:nth-child(7) {
                min-width: 100px;
                text-align: right;
            }

            .profit-page #pagination {
                display: flex !important;
                align-items: center;
                justify-content: center !important;
                gap: 8px;
                width: 100%;
                max-width: 100%;
                margin-top: 12px !important;
                overflow-x: hidden;
            }

            .profit-page .profit-pagination-number {
                display: none;
            }

            .profit-page .profit-page-arrow,
            .profit-page .profit-pagination-status {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .profit-page .profit-page-arrow {
                width: 38px;
                min-width: 38px;
                height: 38px;
                padding: 0;
                font-size: 18px;
                line-height: 1;
            }

            .profit-page .profit-pagination-status {
                min-height: 38px;
                color: #495057;
                font-size: 13px;
                font-weight: 500;
                white-space: nowrap;
            }
        }
    </style>

    <div class="page-inner profit-page" id="profitPage" data-report-url="{{ route('admin.profit.getProfitReportByFilter') }}" data-pdf-url="{{ route('admin.profit.getProfitReportByFilterPDF') }}" data-csrf="{{ csrf_token() }}">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'BÁO CÁO'], ['label' => 'BÁO CÁO LỢI NHUẬN']]" />
            {{-- <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">BÁO CÁO</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">BÁO CÁO LỢI NHUẬN</a>
                </li>
            </ul> --}}
        </div>



        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-header profit-title-header">
                        <h4 style="color: rgb(15, 0, 0); text-align: center" class="card-title profit-report-title">Báo cáo lợi nhuận</h4>
                    </div>
                    <div class="card-header d-flex justify-content-between align-items-center profit-toolbar">
                        <div class="search-container profit-date-field d-flex gap-2">
                            <input type="date" id="startDate" class="form-control" aria-label="Từ ngày">
                            <input type="date" id="endDate" class="form-control" aria-label="Đến ngày">
                        </div>

                        <div class="d-flex justify-content-end align-items-center profit-search-row">
                            <input type="search" name="search" class="form-control me-2 profit-search-input" style="width: 300px;"
                                placeholder="Tìm kiếm...">

                            <button type="button" class="btn profit-reset-btn" id="btn-reset"> <i
                                    class="fa-solid fa-rotate"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="alert alert-warning">
                            Giá vốn hàng không IMEI đang dùng giá vốn sản phẩm hiện tại vì chưa có snapshot lúc bán.
                            Phiếu trả completed được ghi nhận theo ngày lập phiếu; phí và điều chỉnh của phiếu trả chưa được phân bổ theo sản phẩm.
                        </p>
                        <div class="form-group row profit-filter-row">
                            <div class="col-md-6 profit-filter-field">
                                <label for="storageSelect">Chọn kho:</label>
                                <select id="storageSelect" class="form-control">
                                    <option value="">--- Chọn kho ---</option>
                                    @foreach ($storages as $storage)
                                        <option value="{{ $storage->id }}" @selected($initialStorage?->id === $storage->id)>{{ $storage->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 profit-filter-field">
                                <label for="periodSelect">Chọn thời gian:</label>
                                <select id="periodSelect" class="form-control">
                                    <option value="all">Tất cả thời gian</option>
                                    <option value="1">Hôm nay</option>
                                    <option value="2">Tuần này</option>
                                    <option value="3">Tháng này</option>
                                    <option value="4">Quý này</option>
                                    <option value="5">Năm này</option>
                                    <option value="6">Chọn ngày</option>
                                </select>
                            </div>

                            <p id="profitFilterError" class="text-danger" role="alert" hidden></p>

                            <div class="loader" id="loader"></div>
                        </div>

                        <div class="profit-report-table-area">
                            <div
                                class="profit-item-count-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px">
                                <span id="itemCount">Số lượng mặt hàng: 0</span>
                                @can('report.profit.export_pdf')
                                    <button type="button" id="exportPdf" class="btn btn-primary" @disabled(!$initialStorage)>Xuất PDF</button>
                                @endcan
                            </div>

                            <p class="profit-table-hint">Vuốt ngang để xem đầy đủ báo cáo</p>
                            <div class="table-responsive profit-table-scroll">
                                <table class="table table-hover profit-table" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Mã hàng</th>
                                            <th>Tên hàng</th>
                                            <th>SL Bán</th>
                                            <th>Doanh thu</th>
                                            <th>Tổng vốn</th>
                                            <th>Lợi nhuận</th>
                                            <th>Tỷ suất</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportTableBody">
                                        <!-- Dữ liệu sẽ được chèn vào đây qua AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="pagination" class="d-flex justify-content-end mt-3 profit-pagination"></div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="{{ asset('assets/js/profit-report.js') }}?v={{ filemtime(public_path('assets/js/profit-report.js')) }}"></script>
@endpush