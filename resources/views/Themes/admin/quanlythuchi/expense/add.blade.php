@extends('admin.layout.index')

@section('content')
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
        text-align: center;
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

    .form-label {
        font-weight: 500;
    }

    .form-control,
    .form-select {
        border-radius: 5px;
        box-shadow: none;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    .add_product>div {
        margin-top: 20px;
    }

    .modal-footer {
        justify-content: center;
        border-top: none;
    }

    textarea.form-control {
        height: auto;
    }

    #description {
        border-radius: 5px;
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
                <a href="{{ route('admin.quanlythuchi.expense.index') }}">Quản lý chi</a>
            </li>
            <li class="separator">
                <i class="icon-arrow-right"></i>
            </li>
            <li class="nav-item">
                <a href="#">Thêm</a>
            </li>
        </ul>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" style="text-align: center; color:#fff">Thêm phiếu chi</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.quanlythuchi.expense.addSubmit') }}" method="post" id="addexpense" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="content">Nhà cung cấp:</label>
                                    <select class="form-control" name="supplier" id="supplier">
                                        <option value="">--- Chọn nhà cung cấp ---</option>
                                        @foreach ($debtNcc as $item)
                                        <option value="{{ $item->companies_id }}">{{ $item->company->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                        id="supplier_error"></span> </div>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_spent">Số tiền :</label>
                                    <input type="number" class="form-control" min="1000" value="1000" id="amount_spent" name="amount_spent" required>
                                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                        id="amount_spent_error"></span> </div>
                                </div>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="content">Nội dung:</label>
                                    <input type="text" class="form-control" id="content" name="content" >
                                </div>
                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                    id="content_error"></span> </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-primary" onclick="addexpense(event)">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $("#supplier").change(function(){
            var supplier = $(this).val();

            if(supplier > 0){
                $.ajax({
                    url: '{{ route('admin.quanlythuchi.expense.debt') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        supplier: supplier,
                    },
                    success: function(data) {
                        $('#amount_spent').val(parseInt(data.replace(",", ""), 10));
                        $('#amount_spent').attr('max', parseInt(data.replace(",", ""), 10));
                    },
                });
            }else{
                $('#amount_spent').val();
            }

        })
    })
</script>
<script>
    var validateorder = {
        'supplier': {
            'element': document.getElementById('supplier'),
            'error': document.getElementById('supplier_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('TC001')
                },
            ]
        },
        'amount_spent': {
            'element': document.getElementById('amount_spent'),
            'error': document.getElementById('amount_spent_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('TC002')
                },
            ]
        },
        'content': {
            'element': document.getElementById('content'),
            'error': document.getElementById('content_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('TC004')
                },
            ]
        },
    }
    function addexpense(event){
        event.preventDefault();
        if(validateAllFields(validateorder)){
            document.getElementById('addexpense').submit();
        }
    }
</script>
@endsection
