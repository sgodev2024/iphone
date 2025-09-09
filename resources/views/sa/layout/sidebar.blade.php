<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="{{ route('admin.dashboard') }}" class="logo">
                <img src="{{ asset('assets/img/kaiadmin/logo_light.svg') }}" alt="navbar brand" class="navbar-brand"
                    height="20" />
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
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item active">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Thành phần quản lý</h4>
                </li>

                {{-- <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarsanpham">
                        <i class="fas fa-box-open"></i>
                        <p>Sản phẩm</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="sidebarsanpham">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('admin.product.store') }}">
                                    <span class="sub-item">Danh sách</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.product.addForm') }}">
                                    <span class="sub-item">Thêm sản phẩm</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarthuonghieu">
                        <i class="fas fa-tags"></i>
                        <p>Danh mục</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="sidebarthuonghieu">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('admin.category.index') }}">
                                    <span class="sub-item">Danh sách</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.category.add') }}">
                                    <span class="sub-item">Thêm danh mục</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarnhanvien">
                        <i class="fas fa-user-tie"></i>
                        <p>Nhân viên</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="sidebarnhanvien">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('admin.staff.store') }}">
                                    <span class="sub-item">Danh sách</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.staff.addForm') }}">
                                    <span class="sub-item">Thêm nhân viên</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarbrand">
                        <i class="fas fa-trademark"></i>
                        <p>Thương hiệu</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="sidebarbrand">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('admin.brand.store') }}">
                                    <span class="sub-item">Danh sách</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.brand.addForm') }}">
                                    <span class="sub-item">Thêm thương hiệu</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li> --}}
                <li class="nav-item">
                    <a href="{{ route('sa.store.index') }}">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Cửa hàng</p>
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a href="{{ route('admin.check.index') }}">
                        <i class="fas fa-clipboard-check"></i>
                        <p>Phiếu kiểm kho</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.client.index') }}">
                        <i class="fas fa-users"></i>
                        <p>Khách hàng</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.config.detail') }}">
                        <i class="fas fa-cogs"></i>
                        <p>Cấu hình</p>
                    </a>
                </li> --}}
            </ul>
        </div>
    </div>
</div>
