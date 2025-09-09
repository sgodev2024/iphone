@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => $title]]" />


        <form id="myForm">

            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="row gy-4">
                                <div class="col-md-12">
                                    <label for="company_name" class="form-label mb-1 fw-bold">Tên cửa hàng</label>
                                    <input type="text" class="form-control" name="company_name"
                                        value="{{ optional($config)->company_name }}" placeholder="Nhập tên cửa hàng">
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label mb-1 fw-bold">Email</label>
                                    <input type="email" class="form-control" name="email"
                                        value="{{ optional($config)->email }}" placeholder="Nhập email">
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label mb-1 fw-bold">Số điện thoại</label>
                                    <input type="phone" class="form-control" name="phone"
                                        value="{{ optional($config)->phone }}" placeholder="Nhập số điện thoại">
                                </div>

                                <div class="col-md-12">
                                    <label for="address" class="form-label mb-1 fw-bold">Địa chỉ</label>
                                    <textarea name="address" placeholder="Nhập địa chỉ" class="form-control">{{ optional($config)->address }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label mb-1 fw-bold">Mã số thuế</label>
                                    <input type="text" class="form-control" name="tax_number"
                                        value="{{ optional($config)->tax_number }}" placeholder="Nhập mã số thuế">
                                </div>

                                <div class="col-md-6">
                                    <label for="receiver" class="form-label mb-1 fw-bold">Chủ tài khoản</label>
                                    <input type="text" class="form-control" name="receiver"
                                        value="{{ optional($config)->receiver }}" placeholder="Nhập chủ tài khoản">
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_account_number" class="form-label mb-1 fw-bold">Số tài khoản ngân
                                        hàng</label>
                                    <input type="text" class="form-control" name="bank_account_number"
                                        value="{{ optional($config)->bank_account_number }}"
                                        placeholder="Nhập số tài khoản ngân hàng">
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_id" class="form-label mb-1 fw-bold">Ngân hàng</label>
                                    <select name="bank_id" class="form-select form-control">
                                        <option value="">--- Chọn ngân hàng ---</option>
                                        @foreach ($banks as $bankId => $bankName)
                                            <option value="{{ $bankId }}" @selected(optional($config)->bank_id == $bankId)>
                                                {{ $bankName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Xuất bản</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
                            <a href="/admin/config" class="btn btn-outline-secondary"><i
                                    class="fa-solid fa-arrow-rotate-left"></i> Quay lại</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Logo cửa hàng</h5>
                        </div>
                        <div class="card-body">
                            <img class="img-fluid img-thumbnail w-100" id="preview-logo" style="cursor: pointer"
                                src="{{ showImage(optional($config)->logo) }}" alt=""
                                onclick="document.getElementById('logo').click();">
                            <input type="file" name="logo" id="logo" class="form-control d-none" accept="image/*"
                                onchange="previewImage(event, 'preview-logo')">
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
@endsection


@push('script')
    <script>
        $(function() {

            handleSubmit('#myForm', function(res) {
                window.location.reload();
            })

        })
    </script>
@endpush
