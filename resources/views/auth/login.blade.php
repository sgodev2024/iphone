<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGO Media - Đăng Nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('global/css/toastr.css') }}">
    <style>
        body {
            background-color: #e8e8e8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
        }

        .contact-section {
            background: url('{{ asset('assets/img/bg_login.png') }}') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 40px 30px;
            position: relative;
        }


        .contact-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }

        .contact-box {
            border: 2px dashed rgba(255, 255, 255, 0.5);
            padding: 25px;
            border-radius: 8px;
        }

        .contact-item {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .contact-label {
            font-weight: 600;
            min-width: 120px;
            font-size: 14px;
        }

        .contact-info {
            display: flex;
            gap: 5px
        }

        .phone-number {
            font-weight: bold;
            font-size: 14px;
        }

        .time-info {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .login-section {
            padding: 40px 30px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            max-width: 150px;
            height: auto;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control:focus {
            border-color: #2989d8;
            box-shadow: 0 0 0 0.2rem rgba(41, 137, 216, 0.25);
        }

        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
        }

        .btn-login {
            background: #1c5392;
            border: none;
            color: white !important;
            font-weight: bold;
            padding: 12px;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #2365b1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(41, 137, 216, 0.3);
        }

        .form-check-label {
            font-size: 14px;
            color: #666;
        }

        .password-toggle {
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #2989d8;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }

            .contact-section,
            .login-section {
                padding: 30px 20px;
            }

            .contact-title {
                font-size: 1.3rem;
            }

            .contact-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .contact-info {
                text-align: left;
            }
        }

        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.4);
            /* nền tối */
            z-index: 9999;
            display: none;
        }

        #loading {
            width: 48px;
            height: 48px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            border: 3px solid;
            border-color: #fff #fff transparent transparent;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        #loading::after,
        #loading::before {
            content: "";
            box-sizing: border-box;
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid;
            border-color: transparent transparent #ff3d00 #ff3d00;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            animation: rotationBack 0.5s linear infinite;
            transform-origin: center center;
        }

        #loading::before {
            width: 32px;
            height: 32px;
            border-color: #fff #fff transparent transparent;
            animation: rotation 1.5s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes rotationBack {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(-360deg);
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Contact Section -->
                <div class="col-lg-6 col-md-6">
                    <div class="contact-section h-100">
                        <h2 class="contact-title">LIÊN HỆ VỚI CHÚNG TÔI</h2>
                        <div class="contact-box">
                            <div class="contact-item">
                                <span class="contact-label">Hỗ trợ kỹ thuật:</span>
                                <div style="display: flex; flex-direction: column; gap: 3px; align-items: flex-end;">
                                    <div class="contact-info">
                                        <div class="phone-number">(024) 62 927 089</div>
                                        <div class="time-info">(24/7)</div>
                                    </div>
                                    <div class="contact-info">
                                        <div class="phone-number">0981 185 620</div>
                                        <div class="time-info">(24/7)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="contact-item">
                                <span class="contact-label">Hỗ trợ hóa đơn:</span>
                                <div style="display: flex; flex-direction: column; gap: 3px; align-items: flex-end;">
                                    <div class="contact-info">
                                        <div class="phone-number">(024) 62 927 089</div>
                                        <div class="time-info">(8h30 - 18h00)</div>
                                    </div>
                                    <div class="contact-info">
                                        <div class="phone-number">0912 399 322</div>
                                        <div class="time-info">(8h30 - 18h00)</div>
                                    </div>
                                </div>
                            </div>


                            <div class="contact-item">
                                <span class="contact-label">Hỗ trợ gia hạn:</span>
                                <div style="display: flex; flex-direction: column; gap: 3px; align-items: flex-end;">
                                    <div class="contact-info">
                                        <div class="phone-number">(024) 62 927 089</div>
                                        <div class="time-info">(8h30 - 18h00)</div>
                                    </div>
                                    <div class="contact-info">
                                        <div class="phone-number">0981 185 620</div>
                                        <div class="time-info">(8h30 - 18h00)</div>
                                    </div>
                                </div>
                            </div>


                            <div class="contact-item">
                                <span class="contact-label">Email:</span>
                                <div class="contact-info">
                                    <div class="phone-number">info@sgomedia.vn</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Login Section -->
                <div class="col-lg-6 col-md-6">
                    <div class="login-section">
                        <div class="logo-container text-center">
                            <img src="{{ asset('images/sgovn.png') }}" alt="SGO Media" class="logo"
                                style="max-width: 180px;">
                        </div>

                        <form method="POST" action="{{ route('auth.authenticate') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control py-2" id="email"
                                        placeholder="Địa chỉ Email" required>
                                    <small id="err-email" class="text-danger text-muted"></small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control py-2" id="password"
                                        placeholder="Password" required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </span>
                                </div>
                                <small id="err-email" class="text-danger text-muted"></small>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" name="remember" type="checkbox"
                                        id="rememberPassword">
                                    <label class="form-check-label" for="rememberPassword">
                                        Lưu mật khẩu
                                    </label>
                                </div>
                            </div>

                            <button type="submit" id="button-submit" class="btn btn-login">
                                ĐĂNG NHẬP
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay">
        <div id="loading"></div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('global/js/toastr.js') }}"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        $(function() {
            $('form').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this)

                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    beforeSend: () => {
                        $("#loadingOverlay").show();
                    },
                    success: (res) => {
                        window.location.href = res.data
                    },
                    error: (xhr) => {
                        datgin.error(xhr.responseJSON?.message ??
                            'Đã có lỗi xảy ra, vui lòng thử lại sau!');
                    },
                    complete: function() {
                        $("#loadingOverlay").hide();
                    },
                })
            })
        })
    </script>
</body>

</html>
