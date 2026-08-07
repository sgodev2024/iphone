<!DOCTYPE html>
<html>

<head>
    <title>Báo cáo xuất nhập tồn</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        table,
        th,
        td {
            border: 1px solid #dee2e6;
        }

        th,
        td {
            padding: 0.75rem;
            vertical-align: middle;
            text-align: center;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 700;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>Báo cáo xuất nhập tồn</h1>
    <p>Từ ngày: {{ $start_date }} đến ngày: {{ $end_date }}</p>
    <p>Kho: {{ $storage_name }}</p>

    <table>
        <thead>
            <tr>
                <th>Mã hàng</th>
                <th>Tên hàng</th>
                <th>Tồn đầu kỳ</th>
                <th>Giá trị đầu kỳ</th>
                <th>SL Nhập</th>
                <th>Giá trị nhập</th>
                <th>SL Xuất</th>
                <th>Giá trị xuất</th>
                <th>Tồn cuối kỳ</th>
                <th>Giá trị cuối kỳ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData as $item)
                <tr>
                    <td>{{ $item->code }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->starting_inventory }}</td>
                    <td>{{ $item->starting_value }}</td>
                    <td>{{ $item->import_quantity }}</td>
                    <td>{{ $item->import_value }}</td>
                    <td>{{ $item->export_quantity }}</td>
                    <td>{{ $item->export_value }}</td>
                    <td>{{ $item->ending_inventory }}</td>
                    <td>{{ $item->ending_value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
