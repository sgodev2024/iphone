<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Notify Plugin -->
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/notify/0.4.2/notify.min.js"></script> --}}
    <!-- Bootstrap CSS for better styling (optional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.22.4/sweetalert2.min.css">

    {{-- <link rel="stylesheet" href="{{asset('css/staff.css')}}"> --}}
    <script src="{{ asset('validator/validator.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">

    @stack('style')
</head>
<style>
    /* Container của submenu */
    .submenu {
        display: none;
        position: absolute;
        right: 10px;
        top: 50px;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
        z-index: 1000;
    }

    /* Thiết lập cho các mục trong submenu */
    .submenu ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    /* Thiết lập cho các liên kết trong submenu */
    .submenu ul li {
        padding: 12px 16px;
        text-align: left;
    }

    .submenu ul li a {
        text-decoration: none;
        color: #333;
        display: block;
    }

    /* Hiệu ứng hover cho các mục trong submenu */
    .submenu ul li:hover {
        background-color: #f0f0f0;
    }

    /* Hiệu ứng hover cho biểu tượng home */
    .home-icon:hover+.submenu,
    .submenu:hover {
        display: block;
    }

    /* Định dạng cho biểu tượng home */
    .home-icon {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    /* Định dạng cho biểu tượng người dùng */
    .home-icon i {
        color: #333;
    }

    @media (max-width: 768px) {
        body {
            margin: 0;
            padding-top: 20px;
            overflow-x: hidden;
            width: 100vw;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 10px 10px;
        }

        #row1 {
            margin-top: 20px;
        }

        .product-item1 img {
            display: none;
        }

        .product-item1 .product-name {
            display: contents;
        }
    }

    @media (max-width: 100%) {
        .header {
            width: 100vw;
            margin-bottom: 10px;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const homeIcon = document.getElementById('homeIcon');
        const submenu = document.getElementById('submenu');

        homeIcon.addEventListener('click', function(event) {
            event.preventDefault();
            submenu.style.display = (submenu.style.display === 'none' || submenu.style.display === '') ?
                'block' : 'none';
        });

        // Đóng menu con khi nhấp ra ngoài
        document.addEventListener('click', function(event) {
            if (!homeIcon.contains(event.target) && !submenu.contains(event.target)) {
                submenu.style.display = 'none';
            }
        });

    });
</script>


<!-- Bao gồm thư viện SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- Bao gồm thư viện SweetAlert2 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

@if (session('action'))
    <script>
        $(document).ready(function() {
            Swal.fire({
                icon: 'success',
                title: 'Thông báo',
                text: '{{ session('action') }}',
                position: 'center',
                showConfirmButton: false, // Ẩn nút xác nhận
                timer: 3000 // Tự động đóng sau 3 giây
            });
        })
    </script>
@else
    @if (session('fail'))
        <script>
            $(document).ready(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Thông báo',
                    text: '{{ session('fail') }}',
                    position: 'center',
                    showConfirmButton: false, // Ẩn nút xác nhận
                    timer: 3000 // Tự động đóng sau 3 giây
                });
            })
        </script>
    @endif
@endif

<body style="overflow-x: hidden; ">
    <header class="header bg-primary py-2" id="header">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Left side: Search bar -->
                <div class="text-center">
                    <a href="{{ route('staff.index') }}">
                        <img src="{{ showImage(optional($config)->logo, 'images/aicrm1.png') }}" alt="logo"
                            style="max-width: 120px; height: auto; object-fit: contain;" />
                    </a>
                </div>
                <!-- Right side: Icons -->
                <div class="">
                    <a href="#" class="home-icon" id="homeIcon" style="font-size: 20px;">
                        <i style="color: white;" class="fas fa-user-tag"></i>
                    </a>
                    <div id="submenu" class="submenu">
                        <ul>
                            <li><a href="{{ route('staff.Inventory.get') }}">Kiểm kho</a></li>
                            <li><a style="padding: 0px" class="dropdown-item" href="{{ route('staff.order') }}">Lịch sử
                                    mua hàng</a></li>
                            <li>
                                <form id="logoutForm" action="{{ route('admin.logout') }}" method="POST"
                                    style="display: none;">
                                    @csrf
                                </form>
                                <a style="padding: 0px" class="dropdown-item" href="#"
                                    onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
                                    Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </header>
