@extends('Themes.layout_staff.app')
@section('content')
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>


<style>

    .container {
        background-color: #fff;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .order-table {
        width: 100%;
        margin-top: 20px;
    }

    .order-table th,
    .order-table td {
        padding: 10px;
        text-align: center;
        vertical-align: middle;
    }

    .order-table thead {
        background-color: #007bff;
        color: #fff;
    }

    .order-table tbody tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .order-table tbody tr:hover {
        background-color: #e9ecef;
    }

    .pagination {
        justify-content: center;
    }

    #tieude {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #tieude div {
        text-align: left;
    }
    #tieude h2{
        display: block;
        margin: 0px auto;
    }
</style>
<div class="container">

    <div id="tieude">
        <div><a href="{{ route('staff.index') }}">Quay lại</a></div>
        <h2 class="text-center">Phiếu kiểm kho</h2>
        <div>
            <a class="btn btn-success"  href="{{ route('staff.Inventory.add') }}">
                <i class="fas fa-plus"></i>
                <span class="text-btn ng-binding">Kiểm kho</span>
            </a>
        </div>
    </div>
    <table class="table table-bordered order-table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Mã kiểm kho</th>
                <th scope="col">Thời gian</th>
                <th scope="col">Tổng chênh lệch</th>
                <th scope="col">Số lượng tăng</th>
                <th scope="col">Số lượng giảm</th>
                <th scope="col">Ghi chứ</th>
            </tr>
        </thead>
        <tbody id="order-data">
            @foreach ($inventory as $key => $item )
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $item->test_code }}</td>
                    <td>{{ $item->updated_at }}</td>
                    <td>{{ $item->tong_chenh_lech }}</td>
                    <td>{{ $item->sl_tang }}</td>
                    <td>{{ $item->sl_giam }}</td>
                    <td>{{ $item->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Phân trang -->
    <nav aria-label="Page navigation example">
        {{ $inventory->links('vendor.pagination.custom') }}
    </nav>
</div>


@endsection
