@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Tài khoản', 'url' => route('admin.company.index')], ['label' => $title]]" />


        <form id="myForm">

            @if (!empty($user))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="row gy-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label mb-1 fw-bold">Tên tài khoản chi nhánh</label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ optional($user)->name }}" placeholder="Nhập tên tài khoản chi nhánh">
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label mb-1 fw-bold">Email</label>
                                    <input type="email" class="form-control" name="email"
                                        value="{{ optional($user)->email }}" placeholder="Nhập email">
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="password" class="form-label mb-1 fw-bold">Mật khẩu</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Nhập mật khẩu" value="">
                                    <i class="fa-regular fa-eye position-absolute toggle-password"
                                        style="top: 38px; right: 25px; cursor: pointer;"></i>
                                </div>


                                <div class="col-md-6">
                                    <label for="phone" class="form-label mb-1 fw-bold">Số điện thoại</label>
                                    <input type="phone" class="form-control" name="phone"
                                        value="{{ optional($user)->phone }}" placeholder="Nhập số điện thoại">
                                </div>

                                <div class="col-md-12">
                                    <label for="address" class="form-label mb-1 fw-bold">Địa chỉ</label>
                                    <textarea name="address" placeholder="Nhập địa chỉ" class="form-control">{{ optional($user)->address }}</textarea>
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
                                <option value="active" @selected(optional($user)->status === 'active')>Kích hoạt</option>
                                <option value="inactive" @selected(optional($user)->status === 'inactive')>Không kích hoạt</option>
                                <option value="locked" @selected(optional($user)->status === 'locked')>khóa tài khoản</option>
                            </select>
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

            const url = '{{ $api }}'

            handleSubmit('#myForm', function(res) {
                window.location.href = res.data.redirect
            }, url)

            $(document).on('click', '.toggle-password', function() {
                let input = $('#password');
                let type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);

                // đổi icon
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

        })
    </script>
@endpush
