<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>

        body{
            font-family: DejaVu Sans, sans-serif;
        }
        .table {
            width: 100%;
            table-layout: fixed;
        }

        .table th,
        .table td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table {
            width: 100%;
            border-collapse: collapse;

        }

        .table th,
        .table td {
            padding: 8px;

            border: 1px solid #ddd;

            text-align: right;

        }

        .table th {
            background-color: #f2f2f2;

        }

        #pdf table {
            font-size: 13px;
        }

        .font-size{
            font-size: 13px
        }


    </style>
</head>

<body>
    <div id="pdf" class=" mt-2">
        <div style="margin: 0px;">
            <span style="color: gray; font-size: 12px; margin: 0px;">{{ \Carbon\Carbon::now()->format('d-m-Y H:i') }}</span>
        </div>
        <div class="text-center">
            <p style="font-size: 23px; margin: 0px">Báo cáo lợi nhận theo hàng hóa </p>
            <div class="font-size">
                @if ($filter == 6)
                <span>Từ ngày {{ $startDate }} đến ngày  {{ $endDate }}</span>
                @else
                <span>
                    @if ($filter == 1)  Hôm nay    @endif
                    @if ($filter == 2)  Tuần này    @endif
                    @if ($filter == 3)  Tháng này    @endif
                    @if ($filter == 4)  Quý này    @endif
                    @if ($filter == 5)   Năm nay   @endif
                </span>
                @endif

            </div>
            <div class="font-size">
                <span>Chi nhánh : {{ $storage }}</span>
            </div>
        </div>
        <div class="mb-2sss" style="text-align: right;">
            <i style="font-size: 12px;  ">(Đã phân bổ giảm giá hóa đơn , giảm giá phiếu trả)</i>
        </div>
        <table class="table table-hover" id="reportTable">
            <thead>
                <tr>
                    <th>Mã hàng</th>
                    <th>Tên hàng</th>
                    <th>SL Bán</th>
                    <th>Doanh thu</th>
                    <th>Tổng vốn</th>
                    <th>Lới nhuận</th>
                    <th>Tỷ suất </th>
                </tr>
            </thead>
            <tbody id="reportTableBody">
                @foreach ($listprofit as $item)
                <tr>
                    <td>{{ $item['product']->code }}</td>
                    <td>{{ $item['product']->name }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['product']->price * $item['quantity'], 2) }}</td>
                    <td>{{ number_format($item['product']->price_buy * $item['quantity'], 2) }}</td>
                    <td
                        class="{{ ($item['product']->price * $item['quantity'] - $item['product']->price_buy * $item['quantity']) >= 0 ? 'profit' : 'loss' }}">
                        {{ number_format($item['product']->price * $item['quantity'] - $item['product']->price_buy *
                        $item['quantity'], 2) }}
                    </td>
                    <td>
                        @php
                        $revenue = $item['product']->price * $item['quantity'];
                        $cost = $item['product']->price_buy * $item['quantity'];
                        $profit = $revenue - $cost;
                        $profitMargin = ($cost > 0) ? (100 * $profit / $cost) : 0;
                        @endphp
                        {{ number_format($profitMargin, 2) }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
