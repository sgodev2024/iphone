<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f1f1f1;
        }

        .container {
            width: 80%;
            max-width: 1200px;
            background: #fff;
            /* border-radius: 10px; */
            /* box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); */
            padding: 2rem;
            margin: 1rem;
        }

        .breadcrumbs {
            background: #fff;
            padding: 0.75rem;
            /* border-radius: 10px; */
            /* box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); */
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
            color: #fff;
            padding: 1rem;
        }

        .card-body {
            padding: 2rem;
            background-color: #f8f9fa;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 1rem;
            font-size: 1rem;
            border-bottom: 1px solid #dee2e6;
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
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
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

        @media print {

            /* Ẩn tiêu đề và chân trang trong chế độ in */
            @page {
                margin: 0;
            }

            /* Ẩn phần tử có thể xuất hiện trong tiêu đề */
            header,
            footer {
                display: none;
            }

            /* Đảm bảo các phần khác không bị ảnh hưởng */
            body {
                margin: 0;
                padding: 0;
            }

            .container {
                width: 100%;
                padding: 0;
            }
        }
#report{
    margin: 10px 0px
}
        #report .table {
            border-collapse: collapse;
            /* Đảm bảo không có khoảng cách giữa các viền */
        }

        #report .table th,
        #report .table td {
            border: none;
            /* Loại bỏ đường viền của ô */
            padding: 1rem;
            text-align: center;
        }

        #report .table th {
            background-color: #e9ecef;
            font-weight: bold;
        }

        #report .table td {
            background-color: #f8f9fa;
        }

        /* Căn giữa nội dung trong ô */
        #report .table .nowrap {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container">
        <table id="basic-datatables" class="display table table-striped table-hover dataTable" role="grid"
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
        <div id="report">
            <h3 style="text-align: center">Thống kê</h3>
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
</body>

</html>
