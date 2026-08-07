<div class="main-header no-print">
    <div class="main-header-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="../index.html" class="logo">
                <img src="../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20">
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->
    </div>
    <!-- Navbar Header -->
    <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
        <div class="container-fluid">
            {{-- <div style="flex: 2; align-items: baseline; display: flex; margin-right: 20px">
                <marquee id="demoMarquee" scrollamount="7" style="color: red">
                    <span>Thông báo: Đây là phiên bản demo của phần mềm quản lý bán hàng AICRM. Quý khách hàng có nhu
                        cầu trải nghiệm phần mềm đăng ký <a href="http://aicrm.vn/dang-ky" id="marqueeLink">tại
                            đây</a></span>
                </marquee>
            </div> --}}

            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" target="_blank" href="{{ route('admin.transaction.payment') }}"
                        aria-expanded="false">
                        <i style="font-size: 18px; padding: 0px 5px; color: rgb(138, 135, 135)"
                            class="fa-solid fa-wallet"></i> Ví: {{session('authUser')->wallet}} đ

                    </a>
                </li>
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" target="_blank" href="{{ route('admin.product.addForm') }}"
                        aria-expanded="false">
                        <i style="font-size: 18px; padding: 0px 5px; color: rgb(138, 135, 135)"
                            class="fa-solid fa-plus"></i> Thêm sản phẩm

                    </a>
                </li>
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" target="_blank" href="{{ route('staff.index') }}"
                        aria-expanded="false">
                        <i style="font-size: 16px; padding: 0px 5px; color: rgb(138, 135, 135)"
                            class="fas fa-cart-plus"></i> Trang bán hàng
                    </a>

                </li>
                <!-- Notifications -->
                <li class="nav-item topbar-icon dropdown hidden-caret">
                    <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-bell"></i>
                        @if ($notifications->count() > 0)
                            <span class="notification">{{ $notifications->count() ?? '0' }}</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                        <li>
                            <div class="dropdown-title">
                                You have {{ $notifications->count() }} new notification
                            </div>
                        </li>
                        <li>
                            <div class="scroll-wrapper notif-scroll scrollbar-outer" style="position: relative;">
                                <div class="notif-scroll scrollbar-outer scroll-content"
                                    style="height: auto; margin-bottom: 0px; margin-right: 0px; max-height: 0px;">
                                    <div class="notif-center">
                                        @foreach ($notifications as $item)
                                            @php
                                                $createdAt = $item->created_at;
                                                $timeElapsed = Carbon\Carbon::parse($createdAt)
                                                    ->locale('vi')
                                                    ->diffForHumans();
                                            @endphp
                                            <a href="{{ route('admin.order.detail', ['id' => $item->id]) }}"
                                                class="notification-item mark-as-read" data-id="{{ $item->id }}">
                                                <div class="notif-icon notif-primary">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </div>
                                                <div class="notif-content">
                                                    <span class="block"><b>{{ $item->client->name }} </b>vừa mua
                                                        hàng</span>
                                                    <span class="time">{{ $timeElapsed }}</span>
                                                </div>
                                            </a>
                                        @endforeach

                                    </div>
                                </div>
                                <div class="scroll-element scroll-x">
                                    <div class="scroll-element_outer">
                                        <div class="scroll-element_size"></div>
                                        <div class="scroll-element_track"></div>
                                        <div class="scroll-bar"></div>
                                    </div>
                                </div>
                                <div class="scroll-element scroll-y">
                                    <div class="scroll-element_outer">
                                        <div class="scroll-element_size"></div>
                                        <div class="scroll-element_track"></div>
                                        <div class="scroll-bar"></div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>

                <!-- User Profile -->
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                        aria-expanded="false">
                        <div class="avatar-sm">
                            <img src="{{ isset(session('authUser')->user_info->img_url) && !empty(session('authUser')->user_info->img_url) ? asset(session('authUser')->user_info->img_url) : asset('images/avatar2.jpg') }}"
                                alt="image profile" class="avatar-img rounded-circle">
                        </div>
                        <span class="profile-username">
                            <span class="op-7">Hi,</span>
                            <span class="fw-bold">{{ session('authUser')->name }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-user animated fadeIn">
                        <div class="scroll-wrapper dropdown-user-scroll scrollbar-outer" style="position: relative;">
                            <div class="dropdown-user-scroll scrollbar-outer scroll-content"
                                style="height: auto; margin-bottom: 0px; margin-right: 0px; max-height: 0px;">
                                <li>
                                    <div class="user-box">
                                        <div class="avatar-lg">
                                            <img src="{{ isset(session('authUser')->user_info->img_url) && !empty(session('authUser')->user_info->img_url) ? asset(session('authUser')->user_info->img_url) : asset('images/avatar2.jpg') }}"
                                                alt="image profile" class="avatar-img rounded-circle">
                                        </div>
                                        <div class="u-text">
                                            <h4>{{ session('authUser')->name }}</h4>
                                            <p class="text-muted">{{ session('authUser')->email }}</p>
                                            <div style="display: flex">
                                                <a href="{{ route('admin.detail', ['id' => session('authUser')->id]) }}"
                                                    class="btn btn-xs btn-secondary btn-sm p-1">Trang cá nhân</a>
                                                <a href="#" class="btn btn-xs btn-sm p-1"
                                                    style="background: red; color: #ffff; margin-left: 10px"
                                                    onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">Đăng
                                                    xuất</a>
                                                <form id="logoutForm" action="{{ route('admin.logout') }}"
                                                    method="POST" style="display: none;">
                                                    @csrf
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </div>
                            <div class="scroll-element scroll-x">
                                <div class="scroll-element_outer">
                                    <div class="scroll-element_size"></div>
                                    <div class="scroll-element_track"></div>
                                    <div class="scroll-bar"></div>
                                </div>
                            </div>
                            <div class="scroll-element scroll-y">
                                <div class="scroll-element_outer">
                                    <div class="scroll-element_size"></div>
                                    <div class="scroll-element_track"></div>
                                    <div class="scroll-bar"></div>
                                </div>
                            </div>
                        </div>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <!-- End Navbar -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Marquee control
        var marquee = document.getElementById('demoMarquee');
        if (marquee) {
            marquee.addEventListener('mouseenter', function() {
                marquee.stop();
            });
            marquee.addEventListener('mouseleave', function() {
                marquee.start();
            });
        }
    });
</script>
