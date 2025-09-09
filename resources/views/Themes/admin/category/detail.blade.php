@extends('admin.layout.index')
@section('content')
    <style>
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
                    <a href="{{route('admin.category.index')}}">Danh mục</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Sửa</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white;">Danh mục số {{ $category->id }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <form action="{{ route('admin.category.update', ['id' => $category->id]) }}" id='editcategory' method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="mb-12">
                                                <label for="example-text-input" class="form-label">Danh Mục</label>
                                                <input  class="form-control" id="name" name="name" value="{{ $category->name }}" type="text" >
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                id="name_error"></span> </div>
                                            </div>
                                            <div class="mb-12">
                                                <label for="example-text-input" class="form-label">Mô tả</label>
                                                <textarea  class="form-control" name="description" id="description" rows="4">{!! $category->description !!}</textarea>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="description_error"></span> </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div style="margin: 20px; text-align: center">
                                                <button type="button" onclick="editcategory(event)" class="btn btn-primary w-md">Xác nhận</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script> --}}
    <script>
        function submitForm() {
            document.getElementById('editcategory').submit();
        }
        CKEDITOR.replace('description');
        var validateorder = {
        'name': {
            'element': document.getElementById('name'),
            'error': document.getElementById('name_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E015')
                },
            ]
        },
        'description': {
            'element': document.getElementById('description'),
            'error': document.getElementById('description_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E016')
                },
            ]
        },

    }
    function editcategory(event){
        event.preventDefault();
        if(validateAllFields(validateorder)){
            submitForm();
        }
    }
    </script>
@endsection
