@extends('admin.layout.index')
@section('content')
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ 'admin.dashboard' }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="/admin/branchs">Chi nhánh</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Thêm</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Thêm chi nhánh mới</h4>
                    </div>
                    <div class="card-body">
                        <div class="">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <form method="post" action="{{ route('admin.branch.update', ['id' => $branch->id]) }}"
                                    id="editemployee">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="new-user-name">Tên chi nhánh:</label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                           value="{{ $branch->name }}"    required>
                                                    <div class="col-lg-9"><span class="invalid-feedback d-block"
                                                            style="font-weight: 500" id="name_error"></span> </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="status">Trạng thái:</label>
                                                    <select class="form-control" id="status" name="status" required>
                                                        <option value="1" {{ $branch->status == 1 ? 'selected' : '' }}>Hoạt động</option>
        <option value="0" {{ $branch->status == 0 ? 'selected' : '' }}>Không hoạt động</option>
                                                    </select>
                                                    <div class="col-lg-9">
                                                        <span class="invalid-feedback d-block" style="font-weight: 500"
                                                            id="status_error"></span>
                                                    </div>
                                                </div>


                                            </div>

                                        </div>
                                    </div>
                                    <div class="modal-footer" style="margin: 20px">
                                        <button style="text-align: center;" type="button" onclick="addemployee(event)"
                                            class="btn btn-primary">Lưu
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
    <script src="{{ asset('validator/validator.js') }}"></script>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script>
        function submitForm() {
            document.getElementById('editemployee').submit();
        }
        var validateorder = {
            'name': {
                'element': document.getElementById('name'),
                'error': document.getElementById('name_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('ER01')
                }, ]
            },
            'status': {
                'element': document.getElementById('status'),
                'error': document.getElementById('status_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('ER02') // bạn có thể đặt code riêng cho trạng thái
                }]
            }

        }

        function addemployee(event) {
            event.preventDefault();
            if (validateAllFields(validateorder)) {
                submitForm();
            }
        }
    </script>
@endsection
