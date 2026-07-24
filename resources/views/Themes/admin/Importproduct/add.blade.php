@extends('admin.layout.index')

@section('content')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .icon-bell:before {
            content: "\f0f3";
            font-family: FontAwesome;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, #6f42c1, #007bff);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .breadcrumbs {
            background: #fff;
            padding: 0.75rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .breadcrumbs a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumbs i {
            color: #6c757d;
        }

        .table-responsive {
            margin-top: 1rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th,
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .btn-warning,
        .btn-danger,
        .btn-primary {
            padding: 5px 15px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .btn-warning:hover,
        .btn-danger:hover,
        .btn-primary:hover {
            transform: scale(1.05);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef;
        }

        .dataTables_info,
        .dataTables_paginate {
            margin-top: 1rem;
        }

        .pagination .page-link {
            color: #007bff;
        }

        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        .pagination .page-item:hover .page-link {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .pagination .page-item.active .page-link,
        .pagination .page-item .page-link {
            transition: all 0.3s ease;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group .form-control {
            padding-left: 30px;
            /* Adjust padding for left icon */
            padding-right: 30px;
            /* Adjust padding for right icon */
        }

        .input-group .search-icon {
            position: absolute;
            top: 50%;
            left: 10px;
            /* Adjust the left position as needed */
            transform: translateY(-50%);
            color: #aaa;
            /* Adjust icon color as needed */
        }

        .input-group .list-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            /* Adjust the right position as needed */
            transform: translateY(-50%);
            color: #aaa;
            /* Adjust icon color as needed */
            cursor: pointer;
        }

        .input-group .form-control:focus {
            outline: none;
            box-shadow: none;
            border-color: #ccc;
            /* Adjust border color on focus as needed */
        }

        .numberInput {
            width: 100px;
        }

        #category_kho {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #category_kho h2 {
            color: #343a40;
            margin-bottom: 20px;
            font-weight: bold;
        }

        #category_kho label {
            padding: 0px 25px;
        }

        #category_kho .form-control {
            border-radius: 20px;
            padding: 10px 20px;
            font-size: 1.1em;
        }

        #category_kho .form-check-input {
            margin: 0px;
        }

        #category_kho .form-check-label {
            font-size: 1.1em;
        }

        #category_kho .form-check {
            margin-bottom: 10px;
        }


        input[type="checkbox"] {
            width: 15px;
            height: 15px;
        }

        .delete {
            cursor: pointer;
        }

        .results {
            list-style-type: none;
            padding: 0;
            width: 600px;
            margin-top: 10px;
            border: 1px solid #ccc;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            position: absolute;
            background-color: white;
            z-index: 1000;
            font-family: sans-serif;
            font-size: 14px;
        }

        .results li {
            padding: 10px;
            border-bottom: 1px solid #ccc;

        }

        .results li:last-child {
            border-bottom: none;
        }

        .results li:hover {
            background-color: #f0f0f0;
        }

        .no-results {
            text-align: center;
            color: #888;
        }

        .results p {
            margin: 0px;
        }

        .form-wrapper {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group .control-label {
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
        }

        .form-group .form-wrap {
            margin-top: 5px;
        }

        .wrap-note textarea {
            height: 100px;
        }

        .datetime-input {
            width: auto;
        }

        @media (max-width: 767px) {
            .dataTables_wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            #basic-datatables_wrapper {
                margin-bottom: 20px;
            }

            .dataTables_wrapper table {
                width: 100%;
                min-width: 600px;

            }
        }
    </style>

    <div class="page-inner">
        <div class="page-header">
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
                    <a href="#">Thêm</a>
                </li>
            </ul>
        </div>

        <div class="row" id="all">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Nhập hàng</h4>
                    </div>

                    <div class="card-body">
                        <div class="" style="min-height: 400px">
                            <div>
                                <form action="">
                                    <div class="input-group" style="margin-bottom: 20px; position: relative;">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" class="form-control" placeholder="Tìm kiếm sản phẩm"
                                            name="search" id="search">
                                        <i class="fas fa-list list-icon" data-toggle="modal"
                                            data-target="#listcategory"></i>
                                    </div>
                                    <ul class="results" id="results">
                                        @if ($products)
                                            @foreach ($products as $item)
                                                <li data-id="{{ $item->id }}"
                                                    data-category-id="{{ $item->category_id }}" class="product_inventory">
                                                    <div style="display: flex; ">
                                                        <div class="mr-4">
                                                            <img style="width: 80px ; height: 70px;"
                                                                src="{{ !empty($item->images) && isset($item->images[0]->image_path) ? asset($item->images[0]->image_path) : '' }}"
                                                                alt="">

                                                        </div>
                                                        <div class="ovh">
                                                            <p class="txtB ng-binding">{{ $item->name }} <span
                                                                    class="sugg-attr ng-binding"> </span>
                                                                <span class="sugg-unit ng-binding"></span>
                                                            </p>
                                                            <p class="ng-binding">
                                                                <span class="ng-binding"> <span
                                                                        style="padding-right: 20px">{{ $item->code }}</span>Giá
                                                                    : {{ $item->price }}</span>
                                                            </p> <span class="ng-binding">Tồn: {{ $item->quantity }}</span>
                                                            <span class="split txtC"></span>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @endif
                                        <li class="no-results"
                                            style="display: none; text-align: center; color: #888; padding: 15px;">
                                            Không tìm thấy sản phẩm phù hợp
                                        </li>
                                    </ul>
                                </form>
                                <div class="modal fade" id="listcategory" tabindex="-1" role="dialog"
                                    aria-labelledby="listcategoryLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content" style="max-width:440px; margin: 0px auto;">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="listcategoryLabel">Chọn nhóm hàng</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body" id="category_kho">

                                                <div class="row">
                                                    <div class="col-lg-12 mb-3" id="searh_category">
                                                        <input type="text" class="form-control"
                                                            id="search_category_input" placeholder="Tìm kiếm nhóm hàng">
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="form-check" style="margin: 0;">
                                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                                            <label class="form-check-label" for="selectAll"
                                                                style="font-size: 14px">
                                                                Chọn tất cả loại hàng
                                                            </label>
                                                        </div>
                                                        <form id="checkboxForm_category">
                                                            @if (!empty($category))
                                                                @foreach ($category as $index => $item)
                                                                    <div class="form-check category-item"
                                                                        style="margin:0px; padding-top:4px; padding-bottom:4px;">
                                                                        <input class="form-check-input category-checkbox"
                                                                            type="checkbox" value="{{ $item->id }}"
                                                                            id="checkbox_{{ $item->id }}">
                                                                        <label class="form-check-label"
                                                                            for="checkbox_{{ $item->id }}">
                                                                            {{ $item->name }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </form>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary miss_model"
                                                    data-dismiss="modal">Bỏ qua</button>
                                                <button type="button" class="btn btn-primary submit_hang">Xong</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover dataTable" role="grid"
                                            aria-describedby="basic-datatables_info">
                                            <thead>
                                                <tr role="row">
                                                    <th></th>
                                                    <th>STT</th>
                                                    <th>Mã hàng hóa</th>
                                                    <th>Tên hàng</th>
                                                    <th>Số lượng</th>
                                                    <th>Đơn giá</th>
                                                    <th>Thành tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody id="import-data-product">

                                            </tbody>
                                        </table>

                                    </div>
                                </div>
                            </div>
                            <!-- End Table -->
                            <div id="next" style="display: flex; justify-content: end">
                                <a class="btn btn-primary" data-toggle="modal" id="tieptuc" style="display: none"
                                    data-target="#exampleModal">Tiếp tục</a>
                            </div>
                            <!-- Modal -->
                            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content" style="max-width:440px; margin: 0px auto;">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Thông tin chi tiết</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="{{ route('admin.importproduct.importCoupon.add') }}" id="addimport"
                                            method="post">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="form-wrapper form-labels-220">
                                                    <div class="form-group">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            {{ $user->name }}
                                                        </div>
                                                        <div class="pull-right">
                                                            <input type="datetime-local" id="datetime" name="datetime"
                                                                class="datetime-input" value="2024-07-18T16:24">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Nhà cung cấp
                                                        </div>
                                                        <div class="pull-right">
                                                            <select name="supplier" id="supplier" style="width: 195px;">
                                                                <option value="">--- Chọn nhà cung cấp ---</option>
                                                                @foreach ($supplier as $key => $value)
                                                                    <option value="{{ $value->id }}">
                                                                        {{ $value->name }}</option>
                                                                @endforeach
                                                            </select>


                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Kho hàng
                                                        </div>
                                                        <div class="pull-right">
                                                            <select name="storage" id="storage" style="width: 195px;">
                                                                <option value="">--- Chọn nhà kho hàng ---</option>
                                                                @foreach ($storage as $key => $value)
                                                                    <option value="{{ $value->id }}">
                                                                        {{ $value->name }}</option>
                                                                @endforeach
                                                            </select>


                                                        </div>
                                                    </div>
                                                    <div class="form-group" style="margin: 0px; padding: 0;">
                                                        <div class="col-lg-12"><span
                                                                class="invalid-feedback d-block pull-right"
                                                                style="font-weight: 500; text-align: end"
                                                                id="supplier_error"></span></div>
                                                    </div>

                                                    <div class="form-group">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Tổng tiền hàng
                                                        </div>
                                                        <div class="pull-right cantra">
                                                            100000
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Cần trả nhà cung cấp
                                                        </div>
                                                        <div class="pull-right cantra">
                                                            100000
                                                        </div>
                                                    </div>
                                                    <input type="text" id='total_input' name="total"
                                                        style="display: none;">
                                                    <div class="form-group">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Tiền trả nhà cung cấp
                                                        </div>
                                                        <div class="pull-right" style="width: 80px;">
                                                            <div style="border-bottom: 1px solid; text-align: end; color: #007bff"
                                                                id='tientra' class="editable" contenteditable="true">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="text" id='payment' value="" name="totalncc"
                                                        style="display: none;">

                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary"
                                                    onclick="submitadd(event)">Lưu
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var validateorder = {
            'supplier': {
                'element': document.getElementById('supplier'),
                'error': document.getElementById('supplier_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E018')
                }, ]
            },
        }

        function submitadd(event) {
            event.preventDefault();
            if (validateAllFields(validateorder)) {
                document.getElementById('addimport').submit();
            }
        }
    </script>

    <script>
        var $j = jQuery.noConflict();

        $j(document).ready(function() {
            var appliedCategoryIds = [];

            function filterProducts() {
                var query = $j('#search').val().toLowerCase().trim();
                var visibleCount = 0;

                $j('#results li.product_inventory').each(function() {
                    var $li = $j(this);
                    var catId = String($li.data('category-id') || '');
                    var text = $li.text().toLowerCase();

                    var matchCat = (appliedCategoryIds.length === 0) || appliedCategoryIds.includes(catId);
                    var matchQuery = (query.length === 0) || text.includes(query);

                    if (matchCat && matchQuery) {
                        $li.show();
                        visibleCount++;
                    } else {
                        $li.hide();
                    }
                });

                if (visibleCount > 0) {
                    $j('.no-results').hide();
                } else {
                    $j('.no-results').show();
                }
            }

            function syncModalCheckboxes() {
                var total = $j('#checkboxForm_category .category-checkbox').length;
                if (appliedCategoryIds.length === 0 || appliedCategoryIds.length === total) {
                    $j('#selectAll').prop('checked', true);
                    $j('#checkboxForm_category .category-checkbox').prop('checked', true);
                } else {
                    $j('#selectAll').prop('checked', false);
                    $j('#checkboxForm_category .category-checkbox').each(function() {
                        var val = String($j(this).val());
                        $j(this).prop('checked', appliedCategoryIds.includes(val));
                    });
                }
            }

            $j.ajax({
                url: '{{ route('admin.importproduct.import') }}',
                type: 'GET',
                success: function(data) {
                    updateimport(data.import, data.total);
                    var category = $j('#checkboxForm_category');
                    category.empty();
                    var cantra = $j('.cantra');
                    var tientra = $j('#tientra');
                    var payment = $j('#payment');
                    payment.val(data.total);
                    cantra.empty();
                    cantra.html(data.total);
                    tientra.empty();
                    tientra.html(data.total);
                    var list_category = data.category || [];
                    list_category.forEach(function(item, index) {
                        var categoryHtml = `
                        <div class="form-check category-item" style='margin:0px; padding-top:4px; padding-bottom:4px;'>
                            <input class="form-check-input category-checkbox" type="checkbox" value="${item.id}" id="${'checkbox_' + item.id}">
                            <label class="form-check-label" for="${'checkbox_' + item.id}">
                               ${item.name}
                            </label>
                        </div>
                    `;
                        category.append(categoryHtml);
                    });
                    syncModalCheckboxes();
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ' + status + error);
                }
            });

            $j('.product_inventory').click(function(e) {
                e.preventDefault();
                var product = $(this).data('id');

                $j.ajax({
                    url: '{{ route('admin.importproduct.import.add') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product: product,
                    },
                    success: function(data) {
                        $('#search').val('');
                        $j('#results').hide();
                        updateimport(data.import, data.total);
                        var cantra = $j('.cantra');
                        var tientra = $j('#tientra');
                        cantra.empty();
                        cantra.html(data.total);
                        tientra.empty();
                        tientra.html(data.total);
                        var total_input = $('#total_input');
                        total_input.val(data.total);
                        var payment = $j('#payment');
                        payment.val(data.total);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            });

            $j(document).on('input', '.numberInput', function(e) {
                e.preventDefault();
                var value = $j(this).val();
                var tr = $j(this).closest('tr');
                var dataId = tr.data('id');
                var total = tr.find('.total');
                $j.ajax({
                    url: '{{ route('admin.importproduct.import.update') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        value: value,
                        dataId: dataId
                    },
                    success: function(data) {
                        updateimport(data.import, data.total);

                        var total_input = $('#total_input');
                        total_input.val(data.total);
                        var cantra = $j('.cantra');
                        var tientra = $j('#tientra');
                        cantra.empty();
                        cantra.html(data.total);
                        tientra.empty();
                        tientra.html(data.total);
                        var payment = $j('#payment');
                        payment.val(data.total);

                    },
                });

            });

            $j("#search").on("keyup input focus", function() {
                filterProducts();
                $j("#results").show();
            });

            $j(document).on('click', function(e) {
                if (!$j(e.target).closest('#search, #results, .list-icon, #listcategory').length) {
                    $j('#results').hide();
                }
            });

            $j(document).on('change', '#selectAll', function() {
                var isChecked = $j(this).is(':checked');
                $j('#checkboxForm_category .category-checkbox').prop('checked', isChecked);
            });

            $j(document).on('change', '#checkboxForm_category .category-checkbox', function() {
                var total = $j('#checkboxForm_category .category-checkbox').length;
                var checked = $j('#checkboxForm_category .category-checkbox:checked').length;
                $j('#selectAll').prop('checked', total > 0 && total === checked);
            });

            $j(document).on('keyup input', '#search_category_input', function() {
                var q = $j(this).val().toLowerCase().trim();
                $j('#checkboxForm_category .category-item').each(function() {
                    var labelText = $j(this).text().toLowerCase();
                    if (!q || labelText.includes(q)) {
                        $j(this).show();
                    } else {
                        $j(this).hide();
                    }
                });
            });

            $j('table').on('click', '.delete i', function(e) {
                e.preventDefault();
                var id = $j(this).closest('tr').data('id');
                var productId = $j(this).closest('tr').data('product');
                var warehouse = $j('#inventory-data-product');
                var confirmDelete = confirm("Bạn có chắc chắn muốn xóa sản phẩm có mã " + '#' + productId +
                    " không ?");
                if (confirmDelete) {
                    $j.ajax({
                        url: '{{ route('admin.importproduct.import.delete') }}',
                        method: 'GET',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: id,
                        },
                        success: function(data) {
                            updateimport(data.import, data.total);
                            var total_input = $('#total_input');
                            total_input.val(data.total);
                            var cantra = $j('.cantra');
                            var tientra = $j('#tientra');
                            cantra.empty();
                            cantra.html(data.total);
                            tientra.empty();
                            tientra.html(data.total);
                            var payment = $j('#payment');
                            payment.val(data.total);

                        },
                    });
                }
            });

            // chọn danh sách sản phẩm theo loại
            $j('.submit_hang').on('click', function(e) {
                e.preventDefault();
                var checked = $j('#checkboxForm_category .category-checkbox:checked');
                var total = $j('#checkboxForm_category .category-checkbox');

                if (checked.length === 0 || checked.length === total.length) {
                    appliedCategoryIds = [];
                } else {
                    appliedCategoryIds = [];
                    checked.each(function() {
                        appliedCategoryIds.push(String($j(this).val()));
                    });
                }

                $j('#listcategory').modal('hide');
                filterProducts();
                $j('#results').show();
            });

            $j('#listcategory').on('show.bs.modal', function() {
                syncModalCheckboxes();
            });

            $j('.miss_model').on('click', function() {
                syncModalCheckboxes();
            });

            $j(document).on('input', '.giaban', function() {
                var dataId = $j(this).closest('tr').data('id');
                var productId = $j(this).closest('tr').data('product');
                var value = parseInt($(this).text(), 10);
                $j.ajax({
                    url: '{{ route('admin.importproduct.import.update.price') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        value: value,
                        dataId: dataId
                    },
                    success: function(data) {
                        updateimport(data.import, data.total);

                        var total_input = $('#total_input');
                        total_input.val(data.total);
                        var cantra = $j('.cantra');
                        var tientra = $j('#tientra');
                        cantra.empty();
                        cantra.html(data.total);
                        tientra.empty();
                        tientra.html(data.total);
                        var payment = $j('#payment');
                        payment.val(data.total);

                    },
                });

            });

            function updateimport(importproduct, total) {
                var importhtml = $j('#import-data-product');
                var tieptuc = $j('#tieptuc');
                var total_input = $('#total_input');
                total_input.val(total);
                if (total <= 0) {
                    tieptuc.css('display', 'none');
                } else {
                    tieptuc.css('display', 'block');
                }
                importhtml.empty();

                if (importproduct.length === 0) {

                } else {
                    $.each(importproduct, function(index, item) {
                        var productHtml = `
                        <tr data-id='${item.id}'  data-product='${item.product.code}'>
                            <td class='delete'><i class="fas fa-trash-alt"></i></td>
                            <td>${ index + 1 }</td>
                            <td>${item.product.code}</td>
                            <td>${item.product.name}</td>
                            <td><input style='text-align: center;' type="number" class="numberInput" name="quantity" value='${item.quantity !== null ? item.quantity : ''}' oninput="this.value = this.value.replace(/[^0-9]/g, '');" ></td>
                            <td class="giaban" contenteditable="true" oninput="this.innerText = this.innerText.replace(/[^0-9.]/g, '')">${item.price !== null ? item.price : ''}</td>
                            <td class="total">${item.total !== null ? item.total : ''}</td>
                        </tr>
                    `;
                        importhtml.append(productHtml);
                    });
                }
            }
        });
    </script>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#checkboxForm_category .form-check-input');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        document.getElementById('tientra').addEventListener('input', function(e) {
            const selection = window.getSelection();
            const range = document.createRange();
            let caretPos = selection.getRangeAt(0).startOffset;

            this.innerText = this.innerText.replace(/[^0-9]/g, '');

            range.setStart(this.firstChild, Math.min(caretPos, this.innerText.length));
            range.setEnd(this.firstChild, Math.min(caretPos, this.innerText.length));
            selection.removeAllRanges();
            selection.addRange(range);
        });


        document.getElementById('tientra').addEventListener('input', function() {
            var tientraValue = this.innerText;
            document.getElementById('payment').value = tientraValue;
        });
    </script>
@endsection
