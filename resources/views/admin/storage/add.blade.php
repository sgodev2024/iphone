@extends('admin.layout.index')
@section('content')

    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Kho hàng', 'url' => route('admin.storage.index')], ['label' => 'Thêm']]" />
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
                    <a href="{{ route('admin.storage.index') }}">Kho hàng</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Thêm</a>
                </li>
            </ul> --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Thêm kho hàng</h4>
                    </div>
                    <div class="card-body">
                        <div class="">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <form action="{{ route('admin.storage.create') }}" method="POST" id="addstorage"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="name" class="col-form-label form-label">Tên kho hàng:</label>
                                        <input type="text" class="form-control" name="name" id="name" required>
                                        <div class="col-lg-9"><span class="invalid-feedback d-block"
                                                style="font-weight: 500" id="name_error"></span> </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="location" class="col-form-label form-label">Địa điểm:</label>
                                        <input type="text" class="form-control" name="location" id="location" required>
                                        <div class="col-lg-9"><span class="invalid-feedback d-block"
                                                style="font-weight: 500" id="location_error"></span> </div>
                                    </div>
                                    <div class="mb-3 text-center">
                                        <button type="button" onclick="addstorage(event)"
                                            class="btn btn-primary">Lưu</button>
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
            document.getElementById('addstorage').submit();
        }
        var validateorder = {
            'name': {
                'element': document.getElementById('name'),
                'error': document.getElementById('name_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('S001')
                }, ]
            },
            'location': {
                'element': document.getElementById('location'),
                'error': document.getElementById('location_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('S002')
                }, ]
            },

        }

        function addstorage(event) {
            event.preventDefault();
            if (validateAllFields(validateorder)) {
                submitForm();
            }
        }
    </script>
@endsection
