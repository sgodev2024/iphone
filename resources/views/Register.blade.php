<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo tài khoản dùng thử miễn ph</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-select.custom-width {
            width: 100%;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }

        .toast {
            max-width: 350px;
            width: 100%;
            background-color: #eeeeee;
            /* Đổi màu nền của toast */
            color: #333;
            /* Đổi màu chữ của toast */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .toast-header {
            padding: 10px;
            background-color: #066896;
            /* Đổi màu nền của phần header */
            color: #fff;
            /* Đổi màu chữ của phần header */
            border-bottom: none;
        }

        .toast-header .me-auto {
            font-weight: bold;
        }

        .toast-body {
            padding: 10px;
        }

        .btn-close {
            color: #fff;
            opacity: 0.75;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            margin: 0;
        }

        .btn-close:focus {
            outline: none;
        }

        .btn-close:hover {
            opacity: 1;
        }


        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #066896, #eeeeee);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .register-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            display: flex;
            flex-direction: row;
        }

        .register-left {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 50px 20px;
            background: rgba(0, 0, 0, 0.4);
        }

        .register-left {
            background: url('{{ asset('images/background.jpg') }}') no-repeat center center;
            background-size: cover;
            color: white;
            text-align: center;
            padding: 50px 20px;
            flex: 1;
            background-size: cover;
            background-repeat: no-repeat;
            F display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-left h1 {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .register-left p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .register-right {
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 450px;
            /* Adjusted max-width */
            margin-right: 20px;
            /* Added margin for separation */
        }

        .register-right h4 {
            font-size: 24px;
            margin-bottom: 30px;
            font-weight: 500;
            color: #0062E6;
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-select {
            border-radius: 8px;
            padding: 10px 15px;
        }

        .btn-success {
            background: #0062E6;
            border: none;
            padding: 15px;
            font-size: 16px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .btn-success:hover {
            background: #004bb5;
        }

        @media (max-width: 768px) {
            .register-left {
                display: none;
                /* Hide register-left on smaller screens */
            }

            .register-right h4 {
                font-size: 20px;
                /* Giảm kích thước font chữ để phù hợp với thiết bị di động */
            }

            .form-select,
            .form-control {
                width: 100%;
                /* Đảm bảo các dropdown menu có độ rộng 100% trên thiết bị di động */
            }
        }
    </style>
</head>

<body>

    <div class="register-container">
        <div class="register-left">
            {{-- <h1>Quản lý dễ dàng</h1>
            <p>Bán hàng đơn giản</p>
            <p>Hỗ trợ đăng ký 1800 6162</p>
            <p>Đăng ký tài khoản dùng thử ngay để trải nghiệm những tính năng tuyệt vời của chúng tôi.</p>
            <p>Liên hệ với chúng tôi để được hỗ trợ tốt nhất.</p> --}}
        </div>
        <div class="register-right">
            <h4 style="text-align: center;">Tạo tài khoản dùng thử miễn phí</h4>
            <form action="{{ route('register.signup') }}" method="POST" id="registerForm">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Họ và tên <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Nhập họ và tên">
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="name_error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="dob" class="form-label">Ngày sinh <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dob" name="dob"
                                placeholder="Nhập ngày sinh">
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="dob_error"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Tên công ty</label>
                            <input type="text" class="form-control" id="company_name" name="company_name"
                                placeholder="Nhập tên công ty">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tax_code" class="form-label">Mã số thuế</label>
                            <input type="text" class="form-control" id="tax_code" name="tax_code"
                                placeholder="Nhập mã số thuế">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                placeholder="Nhập số điện thoại" value="{{ old('phone') }}">
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="phone_error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Nhập địa chỉ email" value="{{ old('email') }}">
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="email_error"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="city" class="form-label">Khu vực <span class="text-danger">*</span></label>
                            <select class="form-select" id="city" name="city">
                                <option value="">Chọn thành phố</option>
                                @foreach ($city as $cities)
                                    <option value="{{ $cities->id }}">{{ $cities->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="city_error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="field" class="form-label">Lĩnh vực hoạt động <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="field" name="field">
                                <option value="">Chọn lĩnh vực</option>
                                @foreach ($field as $fields)
                                    <option value="{{ $fields->id }}">{{ $fields->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="field_error"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="store_name" class="form-label">Tên cửa hàng <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="store_name" name="store_name"
                                placeholder="Nhập tên cửa hàng" oninput="updateDomain()">
                            <p id="store_domain1"></p>
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="store_name_error">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="address" class="form-label">Địa chỉ <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address"
                                placeholder="Nhập địa chỉ">
                            <div class="invalid-feedback d-block" style="font-weight: 500;" id="address_error"></div>
                        </div>
                    </div>
                </div>
                <div class="row" style="display: none">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="store_domain" class="form-label">Tên miền</label>
                            <input type="text" class="form-control" id="store_domain" name="store_domain"
                                placeholder="Tên miền của bạn" readonly>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button onclick="submitRegisterForm(event)" type="button" class="btn btn-success w-100">Đăng
                        ký</button>
                    <a href="{{ route('formlogin') }}">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
    <!-- Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-primary text-white">
                <strong class="me-auto">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-body">
                <!-- Nội dung toast sẽ được thêm vào đây bằng JavaScript -->
            </div>
        </div>
    </div>
    <!-- Toast khác -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div id="secondaryToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Lỗi</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="secondary-toast-body">
                <!-- Nội dung toast sẽ được thêm vào đây bằng JavaScript -->
            </div>
        </div>
    </div>

    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Thông báo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{ session('modal') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="{{ asset('validator/validator.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script>
        // Function to update the store domain based on the store name input
        function updateDomain() {
            var storeNameInput = document.getElementById('store_name');
            var storeDomainInput1 = document.getElementById('store_domain1');
            var storeDomainInput = document.getElementById('store_domain');
            var storeName = storeNameInput.value.trim();
            var domainSuffix = '.aicrm.vn'; // Adjust domain suffix as needed

            if (storeName !== '') {
                var storeDomain = storeName.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, "")
                    .replace(/\s+/g, '') + domainSuffix;
                storeDomainInput.value = storeDomain;
                storeDomainInput1.textContent = storeDomain;
                storeDomainInput1.style.visibility = 'visible';
            } else {
                storeDomainInput.value = '';
                storeDomainInput1.textContent = '';
                storeDomainInput1.style.visibility = 'hidden';
            }
        }

        // Define form fields and their validations
        var formRegister = {
            'name': {
                'element': document.getElementById('name'),
                'error': document.getElementById('name_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R042')
                }]
            },
            'phone': {
                'element': document.getElementById('phone'),
                'error': document.getElementById('phone_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R043')
                }, {
                    'func': function(value) {
                        return checkCharacterPhone(value);
                    },
                    'message': generateErrorMessage('R053')
                }]
            },
            'email': {
                'element': document.getElementById('email'),
                'error': document.getElementById('email_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R046')
                }, {
                    'func': function(value) {
                        return checkEmail(value);
                    },
                    'message': generateErrorMessage('R047')
                }]
            },
            'store_name': {
                'element': document.getElementById('store_name'),
                'error': document.getElementById('store_name_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R048')
                }]
            },
            'city': {
                'element': document.getElementById('city'),
                'error': document.getElementById('city_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R049')
                }]
            },
            'field': {
                'element': document.getElementById('field'),
                'error': document.getElementById('field_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R050')
                }]
            },
            'address': {
                'element': document.getElementById('address'),
                'error': document.getElementById('address_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R051')
                }]
            },
            'dob': {
                'element': document.getElementById('dob'),
                'error': document.getElementById('dob_error'),
                'validations': [{
                    'func': function(value) {
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('R054')
                }]
            }
        };

        // Function to show a toast message
        function showToast(message) {
            var toastElement = document.getElementById('liveToast');
            var toastBody = document.getElementById('toast-body');
            toastBody.textContent = message;
            var toast = new bootstrap.Toast(toastElement);
            toast.show();
        }

        // Function to show a success alert using SweetAlert2
        function showAlert(message) {
            Swal.fire({
                title: 'Thông báo',
                text: message,
                icon: 'success',
                confirmButtonText: 'Đóng'
            });
        }

        // Function to show a secondary toast message (for errors)
        function showSecondaryToast(message) {
            var toastElement = document.getElementById('secondaryToast');
            var toastBody = document.getElementById('secondary-toast-body');
            toastBody.textContent = message;
            var toast = new bootstrap.Toast(toastElement, {
                delay: 2000
            });
            toast.show();
        }

        // Function to check if the account already exists
        function checkAccountExists(callback) {
            var phone = document.getElementById('phone').value.trim();
            var email = document.getElementById('email').value.trim();

            $.ajax({
                url: '{{ route('check.account') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    phone: phone,
                    email: email
                },
                success: function(response) {
                    callback(response.phone_exists, response.email_exists);
                },
                error: function(xhr) {
                    console.error('AJAX request failed');
                    callback(false, false);
                }
            });
        }

        // Function to validate all form fields
        function validateAllFields(form) {
            var isValid = true;
            Object.keys(form).forEach(function(key) {
                var field = form[key];
                field.validations.forEach(function(validation) {
                    if (!validation.func(field.element.value)) {
                        field.error.textContent = validation.message;
                        isValid = false;
                    } else {
                        field.error.textContent = '';
                    }
                });
            });
            return isValid;
        }

        // Unified submit function for the register form
        function submitRegisterForm(event) {
            event.preventDefault();

            if (validateAllFields(formRegister)) {
                checkAccountExists(function(phoneExists, emailExists) {
                    if (phoneExists) {
                        showSecondaryToast('Số điện thoại này đã tồn tại.');
                    } else if (emailExists) {
                        showSecondaryToast('Email này đã tồn tại.');
                    } else {
                        document.getElementById('registerForm').submit();
                    }
                });
            }
        }

        // Document ready function to show modal alert if session exists
        $(document).ready(function() {
            @if (session('modal'))
                showAlert('{{ session('modal') }}');
            @endif
        });
    </script>

</body>

</html>
