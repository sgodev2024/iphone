@extends('admin.layout.index')
@section('content')

    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{route('admin.dashboard')}}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.client.index')}}">Khách hàng</a>
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
                        <h4 class="card-title" style="text-align: center;color:white">Thông tin khách hàng số {{$client->id}}</h4>
                    </div>
                    <div class="card-body">
                        <div class="">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">

                                <form id="editclient" action="{{ route('admin.client.update', ['id' => $client->id]) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @method('PUT')
                                    @csrf
                                    <div class="row">
                                        <div class="col-lg-6 mb-3">
                                            <label for="name" class="form-label">Tên khách hàng</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                value="{{ $client->name }}" required>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="name_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="phone" class="form-label">Số điện thoại</label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                value="{{ $client->phone }}" required>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="phone_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="{{ $client->email }}" required>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="email_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="gender" class="form-label">Giới tính</label>
                                            <select class="form-control" id="gender" name="gender" required>
                                                <option value="Male" {{ $client->gender == 'Male' ? 'selected' : '' }}>Nam
                                                </option>
                                                <option value="Female" {{ $client->gender == 'Female' ? 'selected' : '' }}>
                                                    Nữ</option>
                                            </select>
                                            <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                id="gender_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="dob" class="form-label">Ngày sinh</label>
                                            <input type="date" class="form-control" id="dob" name="dob"
                                                value="{{ $client->dob }}" required>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="dob_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="address" class="form-label">Địa chỉ</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                value="{{ $client->address }}" required>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="address_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="zip_code" class="form-label">Mã bưu điện</label>
                                            <input type="text" class="form-control" id="zip_code" name="zip_code"
                                                value="{{ $client->zip_code }}" required>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="zip_code_error"></span> </div>

                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label for="clientgroup_id" class="form-label">Nhóm khách hàng</label>
                                            <select class="form-control" name="clientgroup_id" id="clientgroup_id" required>
                                                <option value="">Chọn nhóm khách hàng</option>
                                                @foreach ($clientgroups as $item)
                                                <option {{ $client->clientgroup_id == $item->id ? 'selected' : '' }}
                                                    value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                                <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                                    id="clientgroup_id_error"></span> </div>

                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button type="button" onclick="editclient(event)" class="btn btn-primary w-md">Xác nhận</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script>
        function submitForm() {
            document.getElementById('editclient').submit();
        }
        var validateorder = {
        'name': {
            'element': document.getElementById('name'),
            'error': document.getElementById('name_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E037')
                },
            ]
        },
        'email': {
            'element': document.getElementById('email'),
            'error': document.getElementById('email_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E038')
                },
            ]
        },

        'phone': {
            'element': document.getElementById('phone'),
            'error': document.getElementById('phone_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E039')
                },
            ]
        },

        'address': {
            'element': document.getElementById('address'),
            'error': document.getElementById('address_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E040')
                },
            ]
        },


        'gender': {
            'element': document.getElementById('gender'),
            'error': document.getElementById('gender_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E044')
                },
            ]
        },

        'dob': {
            'element': document.getElementById('dob'),
            'error': document.getElementById('dob_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E042')
                },
            ]
        },
        'zip_code': {
            'element': document.getElementById('zip_code'),
            'error': document.getElementById('zip_code_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('E043')
                },
            ]
        },'clientgroup_id': {
            'element': document.getElementById('clientgroup_id'),
            'error': document.getElementById('clientgroup_id_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('KH001')
                },
            ]
        },

    }
    function editclient(event){
        event.preventDefault();
        if(validateAllFields(validateorder)){
            submitForm();
        }
    }
    </script>
@endsection
