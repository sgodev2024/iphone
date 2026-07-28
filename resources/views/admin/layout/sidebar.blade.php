<div class="sidebar no-print" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header d-flex align-items-center justify-content-between" data-background-color="white">
            <a href="{{ route('admin.dashboard') }}" class="logo d-flex align-items-center">
                <img src="{{ showImage(optional($config)->logo, 'images/sgovn.png') }}" alt="navbar brand"
                    class="navbar-brand" style="max-width: 140px; max-height: 60px; object-fit: contain;" />
            </a>
            <div class="nav-toggle d-flex">
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
                <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-chart-bar"></i>
                        <p>Tổng quan</p>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->routeIs('admin.products.index', 'admin.products.create', 'admin.products.edit', 'admin.products.imeis.*', 'admin.imeis.*', 'admin.category.index', 'admin.category.create', 'admin.brand.index', 'admin.brand.create', 'admin.brand.edit', 'admin.company.index', 'admin.company.create', 'admin.company.edit') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#product">
                        <i class="fas fa-box"></i>
                        <p>Sản phẩm</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.products.index', 'admin.products.create', 'admin.products.edit', 'admin.products.imeis.*', 'admin.imeis.*', 'admin.category.index', 'admin.category.create', 'admin.category.detail', 'admin.brand.index', 'admin.brand.create', 'admin.brand.edit', 'admin.company.index', 'admin.company.create', 'admin.company.edit') ? 'show' : '' }}"
                        id="product">
                        <ul class="nav nav-collapse">
                            <li
                                class=" {{ request()->routeIs('admin.products.index', 'admin.products.imeis.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.products.index') }}">
                                    <span class="sub-item">Quản lý sản phẩm</span>
                                </a>
                            </li>
                            <li class=" {{ request()->routeIs('admin.imeis.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.imeis.index') }}">
                                    <span class="sub-item">Quản lý IMEI</span>
                                </a>
                            </li>
                            <li class=" {{ request()->routeIs('admin.category.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.category.index') }}">
                                    <span class="sub-item">Quản lý danh mục</span>
                                </a>
                            </li>
                            <li class=" {{ request()->routeIs('admin.brand.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.brand.index') }}">
                                    <span class="sub-item">Quản lý thương hiệu</span>
                                </a>
                            </li>
                            <li class=" {{ request()->routeIs('admin.company.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.company.index') }}">
                                    <span class="sub-item">Quản lý nhà cung cấp</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>

                <li
                    class="nav-item {{ request()->routeIs('admin.storage.index', 'admin.importproduct.index', 'admin.check.index', 'admin.inventory.index') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#warehouse">
                        <i class="fas fa-boxes"></i>
                        <p>Kho hàng</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.storage.index', 'admin.importproduct.index', 'admin.check.index', 'admin.inventory.index') ? 'show' : '' }}"
                        id="warehouse">
                        <ul class="nav nav-collapse">
                            <li class="{{ request()->routeIs('admin.storage.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.storage.index') }}">
                                    <span class="sub-item">Kho hàng</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.importproduct.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.importproduct.index') }}">
                                    <span class="sub-item">Nhập hàng</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.check.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.check.index') }}">
                                    <span class="sub-item">Phiếu kiểm kho</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.inventory.index') }}">
                                    <span class="sub-item">Tồn kho</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- <li
                    class="nav-item {{ request()->routeIs('admin.quanlythuchi.receipts.index', 'admin.quanlythuchi.expense.index') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#sidebarthuchi">
                        <i class="fas fa-coins"></i>
                        <p>Quản lý thu chi</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.quanlythuchi.receipts.index', 'admin.quanlythuchi.expense.index') ? 'show' : '' }}"
                        id="sidebarthuchi">
                        <ul class="nav nav-collapse">
                            <li class="{{ request()->routeIs('admin.quanlythuchi.receipts.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.quanlythuchi.receipts.index') }}">
                                    <span class="sub-item">Phiếu thu</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.quanlythuchi.expense.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.quanlythuchi.expense.index') }}">
                                    <span class="sub-item">Phiếu chi</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li> --}}

                <li
                    class="nav-item {{ request()->routeIs(
                        'admin.order.index',
                        'admin.client.index',
                        'admin.profit.index',
                        'admin.debts.client',
                        'admin.debts.supplier',
                    )
                        ? 'active'
                        : '' }}">
                    <a data-bs-toggle="collapse" href="#sidebarbaocao">
                        <i class="fas fa-file-alt"></i>
                        <p>Báo cáo</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.order.index', 'admin.client.index', 'admin.profit.index') ? 'show' : '' }}"
                        id="sidebarbaocao">
                        <ul class="nav nav-collapse">
                            <li class="{{ request()->routeIs('admin.order.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.order.index') }}">
                                    <span class="sub-item">Đơn hàng</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.client.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.client.index') }}">
                                    <span class="sub-item">Khách hàng</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.profit.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.profit.index') }}">
                                    <span class="sub-item">Lợi nhuận</span>
                                </a>
                            </li>
                            {{-- <li
                                class="{{ request()->routeIs('admin.debts.client', 'admin.debts.supplier') ? 'active' : '' }}">
                                <a data-bs-toggle="collapse" href="#sidebarcongno">
                                    <span class="sub-item">Công nợ</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse {{ request()->routeIs('admin.debts.client', 'admin.debts.supplier') ? 'show' : '' }}"
                                    id="sidebarcongno">
                                    <ul class="nav nav-collapse">
                                        <li class="{{ request()->routeIs('admin.debts.client') ? 'active' : '' }}">
                                            <a href="{{ route('admin.debts.client') }}">
                                                <span class="sub-item">Khách hàng</span>
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('admin.debts.supplier') ? 'active' : '' }}">
                                            <a href="{{ route('admin.debts.supplier') }}">
                                                <span class="sub-item">Nhà cung cấp</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li> --}}
                        </ul>
                    </div>
                </li>

                <li
                    class="nav-item {{ request()->routeIs('admin.report.orders.getDailyOrder', 'admin.report.imports.getDailyImport') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#sidebarthongke">
                        <i class="fas fa-chart-line"></i>
                        <span class="sub-item">Thống kê ngày</span>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.report.orders.getDailyOrder', 'admin.report.imports.getDailyImport') ? 'show' : '' }}"
                        id="sidebarthongke">
                        <ul class="nav nav-collapse">
                            <li class="{{ request()->routeIs('admin.report.orders.getDailyOrder') ? 'active' : '' }}">
                                <a href="{{ route('admin.report.orders.getDailyOrder') }}">
                                    <span class="sub-item">Bán hàng</span>
                                </a>
                            </li>
                            <li
                                class="{{ request()->routeIs('admin.report.imports.getDailyImport') ? 'active' : '' }}">
                                <a href="{{ route('admin.report.imports.getDailyImport') }}">
                                    <span class="sub-item">Nhập hàng</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item {{ request()->routeIs('admin.accounts.index') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#receipt">
                        <i class="fa-solid fa-receipt"></i>
                        <span class="sub-item">Kế toán</span>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.accounts.index', 'admin.transactions.cash.index', 'admin.transactions.bank.index', 'admin.accounts.balance', 'admin.debts.customer', 'admin.debts.supplier', 'admin.debts.beginning', 'admin.journal-entries.index') ? 'show' : '' }}"
                        id="receipt">
                        <ul class="nav nav-collapse">
                            <li class="{{ request()->routeIs('admin.transactions.cash.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.transactions.cash.index') }}">
                                    <span class="sub-item">Thu chi tiền mặt</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.transactions.bank.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.transactions.bank.index') }}">
                                    <span class="sub-item">Thu chi ngân hàng</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.debts.customer') ? 'active' : '' }}">
                                <a href="{{ route('admin.debts.customer') }}">
                                    <span class="sub-item">Công nợ khách hàng</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.debts.supplier') ? 'active' : '' }}">
                                <a href="{{ route('admin.debts.supplier') }}">
                                    <span class="sub-item">Công nợ nhà cung cấp</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.debts.beginning') ? 'active' : '' }}">
                                <a href="{{ route('admin.debts.beginning') }}">
                                    <span class="sub-item">Nhập công nợ đầu kỳ</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.accounts.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.accounts.index') }}">
                                    <span class="sub-item">Tài khoản kế toán</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.accounts.balance') ? 'active' : '' }}">
                                <a href="{{ route('admin.accounts.balance') }}">
                                    <span class="sub-item">Tổng hợp theo tài khoản</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.journal-entries.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.journal-entries.index') }}">
                                    <span class="sub-item">Bút toán</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li
                    class="nav-item {{ request()->routeIs('admin.users.index', 'admin.employees.index', 'admin.config.form') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#config">
                        <i class="fas fa-cog"></i>
                        <p>Cấu hình chung</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.users.index', 'admin.employees.index', 'admin.config.form') ? 'show' : '' }}"
                        id="config">
                        <ul class="nav nav-collapse">
                            @if (Auth::user()->role_id === 1)
                                <li class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                                    <a href="{{ url('/admin/users') }}">
                                        <span class="sub-item">Tạo chi nhánh cửa hàng</span>
                                    </a>
                                </li>
                            @endif
                            <li class="{{ request()->routeIs('admin.employees.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.employees.index') }}">
                                    <span class="sub-item">Quản lý nhân viên</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.config.form') ? 'active' : '' }}">
                                <a href="{{ route('admin.config.form') }}">
                                    <span class="sub-item">Thông tin chung</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".nav-item > a").click(function(e) {
                // Nếu menu đang mở, thì không làm gì
                if ($(this).next(".collapse").hasClass("show")) {
                    return;
                }

                // Đóng tất cả các menu khác trước khi mở menu mới
                $(".collapse").not($(this).next(".collapse")).removeClass("show");

                // Bỏ class 'active' trên tất cả menu chính
                $(".nav-item").removeClass("active");

                // Thêm class 'active' cho menu vừa được click
                $(this).parent().addClass("active");
            });
        });
    </script>
</div>
