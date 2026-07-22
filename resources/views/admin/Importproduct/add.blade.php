@extends('admin.layout.index')

@section('content')
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .numberInput {
            width: 100px;
        }

        #category_kho {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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

        .imei-entry-panel {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .imei-entry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 260px));
            gap: 8px 12px;
        }

        .imei-counter.is-incomplete {
            color: #dc3545;
        }

        .imei-entry-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 16px;
            font-size: 13px;
        }

        .money-input {
            max-width: 150px;
            text-align: right;
        }
    </style>

    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Nhập hàng'], ['label' => 'Thêm']]" />
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
                    <a href="{{ route('admin.importproduct.index') }}">Nhập hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Thêm</a>
                </li>
            </ul> --}}
        </div>

        <div class="row" id="all">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0" style="text-align: center; color:white">Nhập hàng</h4>
                    </div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Phiếu nhập chưa được lưu.</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (! empty($productQueryWarning))
                            <div class="alert alert-warning">
                                {{ $productQueryWarning }}
                            </div>
                        @endif
                        <div class="" style="min-height: 400px">
                            <div>
                                <form action="">
                                    <div class="input-group" style=" position: relative;">

                                        <input type="text" class="form-control" placeholder="Tìm kiếm sản phẩm"
                                            name="search" id="search">
                                        <i class="fas fa-list list-icon" data-toggle="modal"
                                            data-target="#listcategory"></i>
                                    </div>
                                    <ul class="results" id="results">
                                        @if ($products)
                                            @foreach ($products as $item)
                                                <li data-id="{{ $item->id }}" class="product_inventory">
                                                    <div style="display: flex; ">
                                                        <div class="mr-4">
                                                            <img style="width:80px; height:70px;"
                                                                src="{{ showImage($item->thumbnail) }}"
                                                                alt="Sản phẩm">
                                                        </div>
                                                        <div class="ovh">
                                                            <p class="txtB ng-binding">{{ $item->name }} <span
                                                                    class="sugg-attr ng-binding"> </span>
                                                                <span class="sugg-unit ng-binding"></span>
                                                            </p>
                                                            <p class="ng-binding">
                                                                <span class="ng-binding"> <span
                                                                        style="padding-right: 20px">{{ $item->code }}</span>Giá
                                                                    : {{ formatPrice($item->price) }} ₫</span>
                                                            </p> <span class="ng-binding">Tồn: {{ $item->quantity }}</span>
                                                            <span class="split txtC"></span>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @endif
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
                                                            placeholder="Tìm kiếm nhóm hàng">
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
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    value="" id="checkbox2">
                                                                <label class="form-check-label" for="checkbox2">
                                                                    Checkbox 2
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    value="" id="checkbox3">
                                                                <label class="form-check-label" for="checkbox3">
                                                                    Checkbox 3
                                                                </label>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary miss_model"
                                                    data-dismiss="modal">Bỏ qua</button>
                                                <button type="button" class="btn btn-primary submit_hang"
                                                    data-dismiss="modal">Xong</button>
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
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        Vui lòng nhập để tìm kiếm sản phẩm
                                                    </td>
                                                </tr>
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
                                                            <input type="datetime-local" class="form-control"
                                                                id="datetime" name="datetime"
                                                                value="{{ now()->format('Y-m-d\TH:i') }}">
                                                        </div>
                                                    </div>

                                                    <!-- Nhà cung cấp -->
                                                    <div class="form-group mt-2">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Nhà cung cấp
                                                        </div>
                                                        <div class="pull-right">
                                                            <select name="supplier" class="form-control" id="supplier"
                                                                style="width: 195px;">
                                                                <option value="">--- Chọn nhà cung cấp ---</option>
                                                                @foreach ($supplier as $value)
                                                                    <option value="{{ $value->id }}"
                                                                        @selected((string) old('supplier') === (string) $value->id)>
                                                                        {{ $value->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Kho hàng -->
                                                    <div class="form-group mt-3">
                                                        <div class="pull-left user-created control-label ng-binding">
                                                            <span><i class="fa fa-user-circle-o"
                                                                    title="Người tạo"></i></span>
                                                            Kho hàng
                                                        </div>
                                                        <div class="pull-right">
                                                            <select name="storage" class="form-control" id="storage"
                                                                style="width: 195px;">
                                                                <option value="">--- Chọn nhà kho hàng ---</option>
                                                                @foreach ($storage as $value)
                                                                    <option value="{{ $value->id }}"
                                                                        @selected((string) old('storage') === (string) $value->id)>
                                                                        {{ $value->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group" style="margin: 0px; padding: 0;">
                                                        <div class="col-lg-12">
                                                            <span class="invalid-feedback d-block pull-right"
                                                                style="font-weight: 500; text-align: end"
                                                                id="supplier_error"></span>
                                                        </div>
                                                    </div>

                                                    <!-- Tổng tiền hàng -->
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

                                                    <!-- Cần trả nhà cung cấp -->
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
                                                            <span><i class="fa fa-money"
                                                                    title="Tiền trả nhà cung cấp"></i></span>
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
                                                    onclick="submitadd(event)">Xác nhận nhập kho</button>
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
        const MAX_IMPORT_QUANTITY = @json(\App\Models\ProductImei::MAX_IMPORT_QUANTITY);
        const MAX_IMPORT_QUANTITY_MESSAGE = 'Mỗi lần chỉ được nhập tối đa 35 sản phẩm';
        const initialImeiValues = @json(old('imeis', []));
        const imeiValidationErrors = @json($errors->toArray());

        var validateorder = {
            'supplier': {
                'element': document.getElementById('supplier'),
                'error': document.getElementById('supplier_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    }
                }, ]
            },
        }

        function checkRequired(value) {
            return value !== null && value.trim() !== "";
        }

        function validateAllFields(fields) {
            let isValid = true;

            for (let key in fields) {
                let field = fields[key];
                let value = field.element.value.trim();

                field.validations.forEach(rule => {
                    if (!rule.func(value)) {
                        field.error.innerText = "Trường này bắt buộc!";
                        isValid = false;
                    } else {
                        field.error.innerText = "";
                    }
                });
            }

            return isValid;
        }

        function submitadd(event) {
            event.preventDefault();

            const firstMissingImei = Array.from(document.querySelectorAll('.imei-input'))
                .find(input => input.value.trim() === '');
            if (firstMissingImei) {
                firstMissingImei.focus();
                alert('Vui lòng nhập đầy đủ IMEI trước khi lưu phiếu nhập.');
                return;
            }

            if (validateAllFields(validateorder)) {
                document.getElementById('addimport').submit();
            }
        }
    </script>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script>
        var $j = jQuery.noConflict();

        function parseMoneyValue(value) {
            if (value === null || value === undefined) {
                return 0;
            }

            const text = String(value).trim();
            if (text === '') {
                return 0;
            }

            if (text.includes('₫') || /^\d{1,3}(\.\d{3})+/.test(text)) {
                return Number(text.replace(/\D/g, '')) || 0;
            }

            return Number(text.replace(/,/g, '')) || 0;
        }

        function formatMoneyValue(value) {
            const amount = Math.round(parseMoneyValue(value));

            return `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
        }

        $j(document).ready(function() {
            const imeiValues = {};
            Object.entries(initialImeiValues || {}).forEach(([importId, values]) => {
                imeiValues[importId] = Array.isArray(values) ? values.map(value => String(value)) : [];
            });

            $j.ajax({
                url: '{{ route('admin.importproduct.import') }}',
                type: 'GET',
                success: function(data) {
                    updateimport(data.import, data.total);
                    var category = $j('#checkboxForm_category');
                    category.empty();
                    updateReceiptTotal(data.total);
                    var list_category = data.category;
                    list_category.forEach(function(item, index) {
                        var categoryHtml = `
                        <div class="form-check" style='margin:0px; padding-top:0px;'>
                            <input class="form-check-input" type="checkbox" value="${item.id}" id="${'checkbox' + index}">
                            <label class="form-check-label" for="${'checkbox' + index}">
                               ${item.name}
                            </label>
                        </div>
                    `;
                        category.append(categoryHtml);
                    });
                },
                error: function(xhr, status, error) {
                    alert('Không thể tải danh sách sản phẩm đang nhập. Vui lòng thử lại.');
                }
            });

            $j('.product_inventory').click(function(e) {
                e.preventDefault();
                var product = $(this).data('id');
                var existingRow = $j(`#import-data-product tr[data-product="${product}"]`);

                if (existingRow.length) {
                    existingRow.addClass('table-warning');
                    existingRow.find('.numberInput').focus();
                    setTimeout(function() {
                        existingRow.removeClass('table-warning');
                    }, 1200);

                    $('#search').val('');
                    $j('#results').hide();
                    return;
                }

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
                        updateReceiptTotal(data.total);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            });

            $j(document).on('change', '.numberInput', function(e) {
                e.preventDefault();
                captureImeiValues();

                var input = $j(this);
                var value = parseInt(input.val(), 10);
                var tr = $j(this).closest('tr');
                var dataId = tr.data('id');
                var previousQuantity = parseInt(input.data('previous-quantity'), 10) || 1;
                var currentValues = imeiValues[dataId] || [];

                if (!Number.isInteger(value) || value < 1) {
                    input.val(previousQuantity);
                    alert(`Số lượng nhập phải từ 1 đến ${MAX_IMPORT_QUANTITY}.`);
                    return;
                }

                if (value > MAX_IMPORT_QUANTITY) {
                    value = MAX_IMPORT_QUANTITY;
                    input.val(value);
                    alert(MAX_IMPORT_QUANTITY_MESSAGE);
                }

                var removedValues = currentValues.slice(value).filter(imei => imei.trim() !== '');

                if (value < previousQuantity && removedValues.length > 0) {
                    var shouldReduce = confirm(
                        'Giảm số lượng sẽ xóa các ô IMEI đã có dữ liệu. Bạn có chắc chắn muốn tiếp tục?'
                    );

                    if (!shouldReduce) {
                        input.val(previousQuantity);
                        return;
                    }
                }

                imeiValues[dataId] = currentValues.slice(0, value);

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
                        updateReceiptTotal(data.total);

                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        const firstError = Object.values(errors).reduce((message, fieldErrors) => {
                            return message || (Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors);
                        }, '');

                        input.val(previousQuantity);
                        alert(firstError || xhr.responseJSON?.message ||
                            'Không thể cập nhật số lượng. Vui lòng thử lại.');
                    },
                });

            });

            $j(document).on('input', '.imei-input', function() {
                this.value = this.value.replace(/\D/g, '');
                captureImeiValues();
                updateImeiCounter($j(this).data('import-id'));
            });

            $j("#search").on("keyup", function() {
                var query = $j(this).val().toLowerCase();
                var hasResults = false;
                if (query.length > 0) {
                    $j("#results").show();
                    $j("#results li").each(function() {
                        var name = $j(this).text().toLowerCase();
                        if (name.includes(query)) {
                            $j(this).show();
                            hasResults = true;
                        } else if (!$j(this).hasClass("no-results")) {
                            $j(this).hide();
                        }
                    });
                    if (hasResults) {
                        $j(".no-results").hide();
                    } else {
                        $j(".no-results").show();
                    }
                } else {
                    $j("#results").hide();
                }
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
                            updateReceiptTotal(data.total);

                        },
                    });
                }
            });

            // chọn danh sách  sản phẩm theo loại
            $j('.submit_hang').on('click', function() {
                var atLeastOneChecked = $('#checkboxForm_category input[type="checkbox"]:checked').length >
                    0;
                if (!atLeastOneChecked) {
                    alert('Vui lòng chọn ít nhất một loại hàng!');
                    return false;
                }
                var selectedValues = [];
                $j('#checkboxForm_category input[type="checkbox"]:checked').each(function() {
                    selectedValues.push($(this).val());
                });
                $j.ajax({
                    url: '{{ route('admin.importproduct.import.addCategory') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        selectedValues: selectedValues,
                    },
                    success: function(data) {
                        $('input[type="checkbox"]').prop('checked', false);
                        updateimport(data.import, data.total);
                        updateReceiptTotal(data.total);
                    },
                });

            });

            $j(document).on('focus', '.giaban', function() {
                $j(this).val(String(Math.round(parseMoneyValue($j(this).data('raw-value')))));
            });

            $j(document).on('input', '.giaban', function() {
                this.value = this.value.replace(/\D/g, '');
            });

            $j(document).on('change', '.giaban', function() {
                var input = $j(this);
                var dataId = input.closest('tr').data('id');
                var value = Math.max(Math.round(parseMoneyValue(input.val())), 0);

                input.data('raw-value', value);
                input.val(formatMoneyValue(value));
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
                        updateReceiptTotal(data.total);

                    },
                });

            });

            function updateReceiptTotal(total) {
                const rawTotal = Math.round(parseMoneyValue(total));
                $('#total_input').val(rawTotal);
                $j('#payment').val(rawTotal);
                $j('.cantra').text(formatMoneyValue(rawTotal));
                $j('#tientra').text(formatMoneyValue(rawTotal));
            }

            function updateimport(importproduct, total) {
                captureImeiValues();
                var importhtml = $j('#import-data-product');
                var tieptuc = $j('#tieptuc');
                updateReceiptTotal(total || 0);
                if (parseMoneyValue(total) <= 0) {
                    tieptuc.css('display', 'none');
                } else {
                    tieptuc.css('display', 'block');
                }
                importhtml.empty();

                if (importproduct.length === 0) {} else {
                    $.each(importproduct, function(index, item) {
                        const product = item.product || {};
                        const productCode = product.code || item.code || '';
                        const productName = product.name || '';
                        const price = parseMoneyValue(item.price);
                        const rowTotal = parseMoneyValue(item.total);
                        var productHtml = `
                <tr data-id='${item.id}' data-product='${item.product_id ?? ""}'>
                    <td class='delete'>
                        <input type="hidden" form="addimport" name="items[${item.id}][product_id]" value="${item.product_id ?? ''}">
                        <input type="hidden" form="addimport" name="items[${item.id}][quantity]" value="${item.quantity ?? ''}">
                        <input type="hidden" form="addimport" name="items[${item.id}][import_price]" value="${price}">
                        <i class="fas fa-trash-alt"></i>
                    </td>
                    <td>${ index + 1 }</td>
                    <td>${escapeHtml(productCode)}</td>
                    <td>${escapeHtml(productName)}</td>
                    <td>
                        <input style='text-align: center;' type="number" min="1" max="${MAX_IMPORT_QUANTITY}"
                            class="numberInput"
                            name="quantity"
                            data-previous-quantity="${item.quantity}"
                            value='${item.quantity !== null ? item.quantity : ""}'
                            oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm money-input giaban"
                            data-raw-value="${price}" value="${formatMoneyValue(price)}">
                    </td>
                    <td class="total" data-raw-value="${rowTotal}">${formatMoneyValue(rowTotal)}</td>
                </tr>
                ${buildImeiPanel(item)}
            `;
                        importhtml.append(productHtml);
                        updateImeiCounter(item.id);
                    });
                }
            }

            function buildImeiPanel(item) {
                const quantity = Math.max(parseInt(item.quantity, 10) || 0, 0);
                const values = imeiValues[item.id] || [];
                const product = item.product || {};
                const productName = product.name || `#${item.product_id || ''}`;
                const productCode = product.code || '';
                const productUnit = product.product_unit || '';
                const fields = [];

                for (let index = 0; index < quantity; index++) {
                    const value = values[index] || '';
                    const errorKey = `imeis.${item.id}.${index}`;
                    const error = imeiValidationErrors[errorKey]?.[0] || '';

                    fields.push(`
                        <div>
                            <label class="form-label mb-1">Máy ${index + 1} – IMEI</label>
                            <input type="text" inputmode="numeric" maxlength="15" autocomplete="off"
                                form="addimport" name="imeis[${item.id}][]"
                                data-import-id="${item.id}"
                                class="form-control imei-input ${error ? 'is-invalid' : ''}"
                                value="${escapeHtml(value)}"
                                placeholder="Nhập IMEI gồm 15 chữ số">
                            ${error ? `<div class="invalid-feedback">${escapeHtml(error)}</div>` : ''}
                        </div>
                    `);
                }

                return `
                    <tr class="imei-detail-row" data-import-id="${item.id}">
                        <td></td>
                        <td colspan="6">
                            <div class="imei-entry-panel">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Danh sách IMEI - ${escapeHtml(productName)}</strong>
                                    <span class="imei-counter" data-import-id="${item.id}">Đã nhập 0/${quantity} IMEI</span>
                                </div>
                                <div class="imei-entry-meta text-muted mb-2">
                                    <span>Sản phẩm: <strong class="text-dark">${escapeHtml(productName)}</strong></span>
                                    ${productCode ? `<span>Mã sản phẩm: <strong class="text-dark">${escapeHtml(productCode)}</strong></span>` : ''}
                                    <span>Số lượng: <strong class="text-dark">${quantity}${productUnit ? ` ${escapeHtml(productUnit)}` : ''}</strong></span>
                                </div>
                                <div class="imei-entry-grid">${fields.join('')}</div>
                            </div>
                        </td>
                    </tr>
                `;
            }

            function captureImeiValues() {
                $j('.imei-input').each(function() {
                    const importId = String($j(this).data('import-id'));
                    const inputs = $j(`.imei-input[data-import-id="${importId}"]`);
                    imeiValues[importId] = inputs.map((index, input) => input.value).get();
                });
            }

            function updateImeiCounter(importId) {
                const inputs = $j(`.imei-input[data-import-id="${importId}"]`);
                const entered = inputs.filter((index, input) => input.value.trim() !== '').length;
                const counter = $j(`.imei-counter[data-import-id="${importId}"]`);

                counter.text(`Đã nhập ${entered}/${inputs.length} IMEI`);
                counter.toggleClass('is-incomplete', entered !== inputs.length);
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
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

        document.getElementById('tientra').addEventListener('focus', function() {
            this.innerText = String(Math.round(parseMoneyValue(this.innerText)));
        });

        document.getElementById('tientra').addEventListener('input', function() {
            this.innerText = this.innerText.replace(/\D/g, '');
            document.getElementById('payment').value = Math.round(parseMoneyValue(this.innerText));
        });

        document.getElementById('tientra').addEventListener('blur', function() {
            document.getElementById('payment').value = Math.round(parseMoneyValue(this.innerText));
            this.innerText = formatMoneyValue(this.innerText);
        });
    </script>
@endsection
