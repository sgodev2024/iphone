@extends('admin.layout.index')
@section('content')
<style>
    .breadcrumbs {
        background: #fff;
        padding: 0.75rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .breadcrumbs {
        list-style: none;
        display: inline;
        width: auto;
        border-left: 1px solid #efefef;
        margin-bottom: 0;
        padding-top: 8px;
        padding-bottom: 8px;
        height: 100%;
    }

    .detail_import i {
        display: inline-block;
        padding-right: 10px;
    }

    .card {
        border-radius: 15px;
        overflow: hidden;
    }

    .card-header {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        background: linear-gradient(135deg, #6f42c1, #007bff);
    }

    .card-body {
        padding: 2rem;
        background-color: #f8f9fa;
    }

    .table th,
    .table td {
        vertical-align: middle;
        padding: 1rem;
        font-size: 1rem;
    }

    .table th {
        background-color: #e9ecef;
        font-weight: bold;
        color: #495057;
    }

    .table-hover tbody tr:hover {
        background-color: #dee2e6;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px);
    }

    .text-primary {
        color: #007bff !important;
    }

    .nowrap {
        white-space: nowrap;
        display: flex;
        justify-content: space-between;
    }
    #print-button{
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    @media print {
        .container{
            width:100% ;
        }
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .no-print {
            display: none !important;
            width: 0 !important;
        }

        #print {
            position: relative;
            width: 100%;
            height: 100%;
            page-break-inside: avoid;
            overflow: hidden;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;

        }

        #print * {
            visibility: visible;
        }

        .sidebar,
        .header,
        .breadcrumbs {
            display: none !important;
        }
    }
</style>
<div class="page-inner">
    {{-- <div class="page-header no-print">
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="icon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="icon-arrow-right"></i>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.importproduct.index') }}">Nhập hàng</a>
            </li>
            <li class="separator">
                <i class="icon-arrow-right"></i>
            </li>
            <li class="nav-item">
                <a href="#">Chi tiết phiếu nhập</a>
            </li>
        </ul>
    </div> --}}
    <div class="row">
        <div class="col-md-12">
            <button id="print-button" class="no-print btn btn-primary">In phiếu báo cáo</button>
            <div class="row" id="print">
                <div class="col-md-12">
                    <div class="card shadow-lg">
                        <div class="card-header bg-gradient-primary text-white">
                            <h4 class="text-center mb-sm-0 font-size-18">Báo cáo tồn kho</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="text-center text-primary"><b>Thống kê</b></h5>
                                    <table class="table table-bordered table-hover detail_import">
                                        <tbody>
                                            <tr>
                                                <th scope="row"> Tổng số lượng tồn kho hiện tại</th>
                                                <td>
                                                    <div class="nowrap">{{ $tongtonkho }}</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row"> Tổng số lượng nhập</th>
                                                <td>
                                                    <div class="nowrap">{{ $tongluongnhap }}</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row"> Tổng số lượng bán</th>
                                                <td>
                                                    <div class="nowrap">{{ $tongluongban }}</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row"> Tổng tiền đã bán</th>
                                                <td>
                                                    <div class="nowrap">{{ number_format($tongtiendaban )}}</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row"> Tổng tiền tồn hàng</th>
                                                <td>
                                                    <div class="nowrap">{{ number_format($tongtintonghang) }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <table id="basic-datatables"
                                        class="display table table-striped table-hover dataTable" role="grid"
                                        aria-describedby="basic-datatables_info">
                                        <thead>
                                            <tr role="row">
                                                <th>Mã sản phẩm</th>
                                                <th>Tồn kho</th>
                                                <th>Đã nhập</th>
                                                <th>Đã bán</th>
                                                <th>Gía bán </th>
                                                <th>Tiền tồn hàng</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($stocks as $item)
                                            <tr>
                                                <td>{{ $item->code}}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $item->total_imports }}</td>
                                                <td>{{ $item->total_orders }}</td>
                                                <td>{{ number_format($item->price_buy) }}</td>
                                                <td>{{ number_format($item->price_buy * $item->quantity) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- <script>

        document.getElementById('print-button').addEventListener('click', function() {
            // Mở cửa sổ mới với nội dung của trang detail.blade.php
            var printWindow = window.open('{{ route('admin.report.debt.print') }}', '_blank');

            // Đợi trang mới được tải hoàn tất trước khi thực hiện lệnh in
            printWindow.onload = function() {
                printWindow.print();
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            };
        });
</script> --}}
<script>
    document.getElementById('print-button').addEventListener('click', function() {
        var printWindow = window.open('{{ route('admin.report.debt.print') }}', '_blank');

        printWindow.onload = function() {
            if (printWindow.document.readyState === 'complete') {
                printWindow.print();
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            } else {
                setTimeout(function() {
                    printWindow.print();
                    printWindow.onafterprint = function() {
                        printWindow.close();
                    };
                }, 1000);
            }
        };
    });
</script>
@endsection
