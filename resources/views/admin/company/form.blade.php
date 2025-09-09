@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'nhà cung cấp', 'url' => route('admin.company.index')], ['label' => $title]]" />


        <form id="myForm">

            @if (!empty($company))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="row gy-4">
                                <div class="col-md-12">
                                    <label for="name" class="form-label mb-1 fw-bold">Tên nhà cung cấp</label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ optional($company)->name }}" placeholder="Nhập tên nhà cung cấp">
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label mb-1 fw-bold">Email</label>
                                    <input type="email" class="form-control" name="email"
                                        value="{{ optional($company)->email }}" placeholder="Nhập email">
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label mb-1 fw-bold">Số điện thoại</label>
                                    <input type="phone" class="form-control" name="phone"
                                        value="{{ optional($company)->phone }}" placeholder="Nhập số điện thoại">
                                </div>

                                <div class="col-md-12">
                                    <label for="address" class="form-label mb-1 fw-bold">Địa chỉ</label>
                                    <textarea name="address" placeholder="Nhập địa chỉ" class="form-control">{{ optional($company)->address }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label mb-1 fw-bold">Mã số thuế</label>
                                    <input type="text" class="form-control" name="tax_number"
                                        value="{{ optional($company)->tax_number }}" placeholder="Nhập mã số thuế">
                                </div>

                                <div class="col-md-6">
                                    <label for="city_id" class="form-label mb-1 fw-bold">Khu vực</label>
                                    <select name="city_id" class="form-select form-control">
                                        <option value="">--- Chọn khu vực ---</option>
                                        @foreach ($cities as $cityId => $cityName)
                                            <option value="{{ $cityId }}" @selected(optional($company)->city_id == $cityId)>
                                                {{ $cityName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_account" class="form-label mb-1 fw-bold">Số tài khoản ngân hàng</label>
                                    <input type="text" class="form-control" name="bank_account"
                                        value="{{ optional($company)->bank_account }}"
                                        placeholder="Nhập số tài khoản ngân hàng">
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_id" class="form-label mb-1 fw-bold">Ngân hàng</label>
                                    <select name="bank_id" class="form-select form-control">
                                        <option value="">--- Chọn ngân hàng ---</option>
                                        @foreach ($banks as $bankId => $bankName)
                                            <option value="{{ $bankId }}" @selected(optional($company)->bank_id == $bankId)>
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
                            <a href="/admin/company" class="btn btn-outline-secondary"><i
                                    class="fa-solid fa-arrow-rotate-left"></i> Quay lại</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Trạng thái</h5>
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select form-control">
                                <option value="1" @selected(optional($company)->status == 1)>Kích hoạt</option>
                                <option value="0" @selected(optional($company)->status == 0)>Không kích hoạt</option>
                            </select>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Ghi chú</h5>
                        </div>
                        <div class="card-body">
                            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú">{{ optional($company)->note }}</textarea>
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

            const url = '/admin/company' + '{{ !empty($company) ? "/{$company->id}" : '' }}'

            handleSubmit('#myForm', function(res) {
                window.location.href = '/admin/company';
            }, url)

        })
    </script>
@endpush
