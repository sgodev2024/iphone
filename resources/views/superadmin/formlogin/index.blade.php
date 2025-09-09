@extends('Themes.main.main')

@section('content')
@if (session('success'))
<div id="alert-success" class="alert alert-success position-fixed top-0 end-0 m-3" role="alert">
    {{ session('success') }}
</div>
@endif

<div class="bg-overlay"></div>
<!-- auth-page content -->
<div class="auth-page-content overflow-hidden pt-lg-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card overflow-hidden">
                    <div class="row g-0">
                        <div class="col-lg-6">
                            <div class="p-lg-5 p-4 auth-one-bg h-100">
                                <div class="bg-overlay"></div>
                                <div class="position-relative h-100 d-flex flex-column">
                                    <div class="mb-4">
                                        <a href="index.html" class="d-block">
                                            <img src="assets/images/logo-light.png" alt="" height="18">
                                        </a>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="mb-3">
                                            <i class="ri-double-quotes-l display-4 text-success"></i>
                                        </div>

                                        <div id="qoutescarouselIndicators" class="carousel slide"
                                            data-bs-ride="carousel">
                                            <div class="carousel-indicators">
                                                <button type="button" data-bs-target="#qoutescarouselIndicators"
                                                    data-bs-slide-to="0" class="active" aria-current="true"
                                                    aria-label="Slide 1"></button>
                                                <button type="button" data-bs-target="#qoutescarouselIndicators"
                                                    data-bs-slide-to="1" aria-label="Slide 2"></button>
                                                <button type="button" data-bs-target="#qoutescarouselIndicators"
                                                    data-bs-slide-to="2" aria-label="Slide 3"></button>
                                            </div>
                                            <div class="carousel-inner text-center text-white-50 pb-5">
                                                <div class="carousel-item active">
                                                    <p class="fs-15 fst-italic">" Tuyệt vời! Mã sạch, thiết kế sạch, dễ
                                                        tùy chỉnh. Cảm ơn rất nhiều!"</p>
                                                </div>
                                                <div class="carousel-item">
                                                    <p class="fs-15 fst-italic">
                                                        "Chủ đề này thực sự tuyệt vời với dịch vụ hỗ trợ khách hàng
                                                        tuyệt vời."</p>
                                                </div>
                                                <div class="carousel-item">
                                                    <p class="fs-15 fst-italic"> "Tuyệt vời! Mã sạch, thiết kế sạch, dễ
                                                        tùy chỉnh. Cảm ơn rất nhiều!"</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- end carousel -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->

                        <div class="col-lg-6">
                            <div class="p-lg-5 p-4">
                                <div>
                                    <h5 class="text-primary">Chào mừng trở lại !</h5>
                                    <p class="text-muted">Đăng nhập để tiếp tục sử dụng.</p>
                                </div>

                                <div class="mt-4">
                                    <form action="{{ route('super.login.submit') }}" method="post">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Email hoặc Số điện thoại</label>
                                            <input type="text" class="form-control" id="email" name="email"
                                                placeholder="Tên đăng nhập">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password-input">Mật Khẩu</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" id="password" name="password"
                                                    class="form-control pe-5 password-input" placeholder="Mật khẩu">
                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon"
                                                    type="button" id="password-addon"><i
                                                        class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value=""
                                                id="auth-remember-check">
                                            <label class="form-check-label" for="auth-remember-check">Lưu thông
                                                tin</label>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" type="submit">Đăng nhập</button>
                                        </div>
                                        <div
                                            style="margin: 0px; display: flex; justify-content: flex-end; padding: 10px 5px;">
                                            <a href="{{ route('forget-password') }}" class="text-muted">Quên mật
                                                khẩu?</a>
                                        </div>


                                    </form>
                                </div>

                                {{-- <div class="mt-5 text-center">
                                    <p class="mb-0">Chưa có tài khoản ? <a href="{{ route('register.index') }}"
                                            class="fw-semibold text-primary text-decoration-underline"> Đăng ký</a> </p>
                                </div> --}}
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->

        </div>
        <!-- end row -->
    </div>
    <!-- end container -->
</div>
<!-- end auth page content -->

<!-- footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center">
                    {{-- <p class="mb-0">&copy;
                        <script>
                            document.write(new Date().getFullYear())
                        </script> Velzon. Crafted with <i class="mdi mdi-heart text-danger"></i> by
                        Themesbrand
                    </p> --}}
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- end Footer -->
<script>
    $(document).ready(function() {
            $('#password-addon').on('click', function() {
                var passwordField = $('#password');
                var passwordFieldType = passwordField.attr('type');
                var eyeIcon = $('#eye-icon');

                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    eyeIcon.removeClass('ri-eye-fill').addClass('ri-eye-off-fill');
                } else {
                    passwordField.attr('type', 'password');
                    eyeIcon.removeClass('ri-eye-off-fill').addClass('ri-eye-fill');
                }
            });
        });

        $(document).ready(function() {
    // Lấy dữ liệu tài khoản đã lưu từ localStorage
    var savedAccounts = JSON.parse(localStorage.getItem('accounts')) || {};

    // Hàm để điền thông tin email và mật khẩu đã lưu
    function populateFields(email) {
        console.log('Populating fields for email:', email);
        if (savedAccounts[email]) {
            $('#email').val(email);
            $('#password').val(savedAccounts[email]);
            console.log('Password populated:', savedAccounts[email]);
        }
    }

    // Kiểm tra nếu chỉ có một tài khoản đã lưu và tự động điền
    if (Object.keys(savedAccounts).length === 1) {
        var email = Object.keys(savedAccounts)[0];
        console.log('Single saved account found:', email);
        populateFields(email);
        $('#auth-remember-check').prop('checked', true);
    }

    // Sự kiện thay đổi trên trường email để điền mật khẩu đã lưu
    $('#email').on('input', function() {
        var email = $(this).val();
        console.log('Email changed to:', email);
        populateFields(email);
    });

    // Xử lý sự kiện submit của form đăng nhập
    $('#login-form').submit(function(event) {
        var email = $('#email').val();
        var password = $('#password').val();
        var remember = $('#auth-remember-check').is(':checked');

        console.log('Form submitted with:', { email, password, remember });

        if (remember) {
            savedAccounts[email] = password;
            localStorage.setItem('accounts', JSON.stringify(savedAccounts));
            console.log('Account saved:', { email, password });
        } else {
            delete savedAccounts[email];
            localStorage.setItem('accounts', JSON.stringify(savedAccounts));
            console.log('Account removed:', email);
        }
    });
});



</script>
<script>
        document.addEventListener('DOMContentLoaded', function() {
            var alertSuccess = document.getElementById('alert-success');
            if (alertSuccess) {
                setTimeout(function() {
                    alertSuccess.style.display = 'none';
                }, 3000);
            }
        });
</script>

<!-- Add necessary icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/2.5.0/remixicon.css" rel="stylesheet">
@endsection
