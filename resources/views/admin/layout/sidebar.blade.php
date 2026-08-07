
<div class="sidebar no-print" data-background-color="dark">
    <div class="sidebar-logo">
        <div
            class="logo-header d-flex align-items-center justify-content-between"
            data-background-color="white"
        >
            <a href="{{ route('admin.dashboard') }}" class="logo d-flex align-items-center">
                <img
                    src="{{ showImage(optional($config)->logo, 'images/sgovn.png') }}"
                    alt="navbar brand"
                    class="navbar-brand"
                    style="max-width: 140px; max-height: 60px; object-fit: contain;"
                />
            </a>

            <div class="nav-toggle d-flex">
                <button type="button" class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>

                <button type="button" class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>

            <button type="button" class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary" id="adminSidebarMenu">

                {{-- Dashboard --}}
                @can('dashboard.view')
                    <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-chart-bar"></i>
                            <p>Tổng quan</p>
                        </a>
                    </li>
                @endcan

                {{-- Sản phẩm --}}
                @canany([
                    'product.view',
                    'product.imei.global_view',
                    'category.view',
                    'brand.view',
                    'company.view',
                ])
                    <li
                        class="nav-item {{ request()->routeIs(
                            'admin.products.*',
                            'admin.imeis.*',
                            'admin.category.*',
                            'admin.brand.*',
                            'admin.company.*'
                        ) ? 'active' : '' }}"
                    >
                        <a
                            data-bs-toggle="collapse"
                            href="#productMenu"
                            role="button"
                            aria-expanded="{{ request()->routeIs(
                                'admin.products.*',
                                'admin.imeis.*',
                                'admin.category.*',
                                'admin.brand.*',
                                'admin.company.*'
                            ) ? 'true' : 'false' }}"
                            aria-controls="productMenu"
                        >
                            <i class="fas fa-box"></i>
                            <p>Sản phẩm</p>
                            <span class="caret"></span>
                        </a>

                        <div
                            id="productMenu"
                            class="collapse {{ request()->routeIs(
                                'admin.products.*',
                                'admin.imeis.*',
                                'admin.category.*',
                                'admin.brand.*',
                                'admin.company.*'
                            ) ? 'show' : '' }}"
                            data-bs-parent="#adminSidebarMenu"
                        >
                            <ul class="nav nav-collapse">
                                @can('product.view')
                                    <li class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.products.index') }}">
                                            <span class="sub-item">Quản lý sản phẩm</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('product.imei.global_view')
                                    <li class="{{ request()->routeIs('admin.imeis.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.imeis.index') }}">
                                            <span class="sub-item">Quản lý IMEI</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('category.view')
                                    <li class="{{ request()->routeIs('admin.category.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.category.index') }}">
                                            <span class="sub-item">Quản lý danh mục</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('brand.view')
                                    <li class="{{ request()->routeIs('admin.brand.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.brand.index') }}">
                                            <span class="sub-item">Quản lý thương hiệu</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('company.view')
                                    <li class="{{ request()->routeIs('admin.company.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.company.index') }}">
                                            <span class="sub-item">Quản lý nhà cung cấp</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Kho hàng --}}
                @canany([
                    'storage.view',
                    'import_product.view',
                    'inventory_check.view',
                    'report.inventory.view',
                ])
                    <li
                        class="nav-item {{ request()->routeIs(
                            'admin.storage.*',
                            'admin.importproduct.*',
                            'admin.check.*',
                            'admin.inventory.*'
                        ) ? 'active' : '' }}"
                    >
                        <a
                            data-bs-toggle="collapse"
                            href="#warehouseMenu"
                            role="button"
                            aria-expanded="{{ request()->routeIs(
                                'admin.storage.*',
                                'admin.importproduct.*',
                                'admin.check.*',
                                'admin.inventory.*'
                            ) ? 'true' : 'false' }}"
                            aria-controls="warehouseMenu"
                        >
                            <i class="fas fa-boxes"></i>
                            <p>Kho hàng</p>
                            <span class="caret"></span>
                        </a>

                        <div
                            id="warehouseMenu"
                            class="collapse {{ request()->routeIs(
                                'admin.storage.*',
                                'admin.importproduct.*',
                                'admin.check.*',
                                'admin.inventory.*'
                            ) ? 'show' : '' }}"
                            data-bs-parent="#adminSidebarMenu"
                        >
                            <ul class="nav nav-collapse">
                                @can('storage.view')
                                    <li class="{{ request()->routeIs('admin.storage.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.storage.index') }}">
                                            <span class="sub-item">Kho hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('import_product.view')
                                    <li class="{{ request()->routeIs('admin.importproduct.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.importproduct.index') }}">
                                            <span class="sub-item">Nhập hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('inventory_check.view')
                                    <li class="{{ request()->routeIs('admin.check.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.check.index') }}">
                                            <span class="sub-item">Phiếu kiểm kho</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('report.inventory.view')
                                    <li class="{{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.inventory.index') }}">
                                            <span class="sub-item">Tồn kho</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Báo cáo --}}
                @canany([
                    'order.view',
                    'client.view',
                    'report.profit.view',
                ])
                    <li
                        class="nav-item {{ request()->routeIs(
                            'admin.order.*',
                            'admin.client.*',
                            'admin.profit.*'
                        ) ? 'active' : '' }}"
                    >
                        <a
                            data-bs-toggle="collapse"
                            href="#reportMenu"
                            role="button"
                            aria-expanded="{{ request()->routeIs(
                                'admin.order.*',
                                'admin.client.*',
                                'admin.profit.*'
                            ) ? 'true' : 'false' }}"
                            aria-controls="reportMenu"
                        >
                            <i class="fas fa-file-alt"></i>
                            <p>Báo cáo</p>
                            <span class="caret"></span>
                        </a>

                        <div
                            id="reportMenu"
                            class="collapse {{ request()->routeIs(
                                'admin.order.*',
                                'admin.client.*',
                                'admin.profit.*'
                            ) ? 'show' : '' }}"
                            data-bs-parent="#adminSidebarMenu"
                        >
                            <ul class="nav nav-collapse">
                                @can('order.view')
                                    <li class="{{ request()->routeIs('admin.order.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.order.index') }}">
                                            <span class="sub-item">Đơn hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('client.view')
                                    <li class="{{ request()->routeIs('admin.client.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.client.index') }}">
                                            <span class="sub-item">Khách hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('report.profit.view')
                                    <li class="{{ request()->routeIs('admin.profit.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.profit.index') }}">
                                            <span class="sub-item">Lợi nhuận</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Thống kê ngày --}}
                @canany([
                    'report.order.view',
                    'report.import.view',
                ])
                    <li
                        class="nav-item {{ request()->routeIs(
                            'admin.report.orders.*',
                            'admin.report.imports.*'
                        ) ? 'active' : '' }}"
                    >
                        <a
                            data-bs-toggle="collapse"
                            href="#dailyStatisticsMenu"
                            role="button"
                            aria-expanded="{{ request()->routeIs(
                                'admin.report.orders.*',
                                'admin.report.imports.*'
                            ) ? 'true' : 'false' }}"
                            aria-controls="dailyStatisticsMenu"
                        >
                            <i class="fas fa-chart-line"></i>
                            <p>Thống kê ngày</p>
                            <span class="caret"></span>
                        </a>

                        <div
                            id="dailyStatisticsMenu"
                            class="collapse {{ request()->routeIs(
                                'admin.report.orders.*',
                                'admin.report.imports.*'
                            ) ? 'show' : '' }}"
                            data-bs-parent="#adminSidebarMenu"
                        >
                            <ul class="nav nav-collapse">
                                @can('report.order.view')
                                    <li class="{{ request()->routeIs('admin.report.orders.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.report.orders.getDailyOrder') }}">
                                            <span class="sub-item">Bán hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('report.import.view')
                                    <li class="{{ request()->routeIs('admin.report.imports.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.report.imports.getDailyImport') }}">
                                            <span class="sub-item">Nhập hàng</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Kế toán --}}
                @canany([
                    'cash_transaction.view',
                    'bank_transaction.view',
                    'debt.customer.view',
                    'debt.supplier.view',
                    'debt.beginning.view',
                    'account.view',
                    'journal_entry.view',
                ])
                    <li
                        class="nav-item {{ request()->routeIs(
                            'admin.transactions.cash.*',
                            'admin.transactions.bank.*',
                            'admin.debts.customer*',
                            'admin.debts.supplier*',
                            'admin.debts.beginning*',
                            'admin.accounts.*',
                            'admin.journal-entries.*'
                        ) ? 'active' : '' }}"
                    >
                        <a
                            data-bs-toggle="collapse"
                            href="#accountingMenu"
                            role="button"
                            aria-expanded="{{ request()->routeIs(
                                'admin.transactions.cash.*',
                                'admin.transactions.bank.*',
                                'admin.debts.customer*',
                                'admin.debts.supplier*',
                                'admin.debts.beginning*',
                                'admin.accounts.*',
                                'admin.journal-entries.*'
                            ) ? 'true' : 'false' }}"
                            aria-controls="accountingMenu"
                        >
                            <i class="fa-solid fa-receipt"></i>
                            <p>Kế toán</p>
                            <span class="caret"></span>
                        </a>

                        <div
                            id="accountingMenu"
                            class="collapse {{ request()->routeIs(
                                'admin.transactions.cash.*',
                                'admin.transactions.bank.*',
                                'admin.debts.customer*',
                                'admin.debts.supplier*',
                                'admin.debts.beginning*',
                                'admin.accounts.*',
                                'admin.journal-entries.*'
                            ) ? 'show' : '' }}"
                            data-bs-parent="#adminSidebarMenu"
                        >
                            <ul class="nav nav-collapse">
                                @can('cash_transaction.view')
                                    <li class="{{ request()->routeIs('admin.transactions.cash.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.transactions.cash.index') }}">
                                            <span class="sub-item">Thu chi tiền mặt</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('bank_transaction.view')
                                    <li class="{{ request()->routeIs('admin.transactions.bank.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.transactions.bank.index') }}">
                                            <span class="sub-item">Thu chi ngân hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('debt.customer.view')
                                    <li class="{{ request()->routeIs('admin.debts.customer*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.debts.customer') }}">
                                            <span class="sub-item">Công nợ khách hàng</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('debt.supplier.view')
                                    <li class="{{ request()->routeIs('admin.debts.supplier*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.debts.supplier') }}">
                                            <span class="sub-item">Công nợ nhà cung cấp</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('debt.beginning.view')
                                    <li class="{{ request()->routeIs('admin.debts.beginning*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.debts.beginning') }}">
                                            <span class="sub-item">Nhập công nợ đầu kỳ</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('account.view')
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
                                @endcan

                                @can('journal_entry.view')
                                    <li class="{{ request()->routeIs('admin.journal-entries.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.journal-entries.index') }}">
                                            <span class="sub-item">Bút toán</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Cấu hình chung --}}
                @canany([
                    'branch.view',
                    'branch.create',
                    'user.view',
                    'employee.view',
                    'role.view',
                    'config.view',
                ])
                    <li
                        class="nav-item {{ request()->routeIs(
                            'admin.users.*',
                            'admin.employees.*',
                            'admin.role.*',
                            'admin.config.*'
                        ) ? 'active' : '' }}"
                    >
                        <a
                            data-bs-toggle="collapse"
                            href="#configurationMenu"
                            role="button"
                            aria-expanded="{{ request()->routeIs(
                                'admin.users.*',
                                'admin.employees.*',
                                'admin.role.*',
                                'admin.config.*'
                            ) ? 'true' : 'false' }}"
                            aria-controls="configurationMenu"
                        >
                            <i class="fas fa-cog"></i>
                            <p>Cấu hình chung</p>
                            <span class="caret"></span>
                        </a>

                        <div
                            id="configurationMenu"
                            class="collapse {{ request()->routeIs(
                                'admin.users.*',
                                'admin.employees.*',
                                'admin.role.*',
                                'admin.config.*'
                            ) ? 'show' : '' }}"
                            data-bs-parent="#adminSidebarMenu"
                        >
                            <ul class="nav nav-collapse">
                                @if (Auth::check() && (int) Auth::user()->role_id === 1)
                                    @canany(['branch.view', 'branch.create', 'user.view'])
                                        <li class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                            <a href="{{ url('/admin/users') }}">
                                                <span class="sub-item">Tạo chi nhánh cửa hàng</span>
                                            </a>
                                        </li>
                                    @endcanany
                                @endif

                                @can('employee.view')
                                    <li class="{{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.employees.index') }}">
                                            <span class="sub-item">Quản lý nhân viên</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('role.view')
                                    <li class="{{ request()->routeIs('admin.role.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.role.index') }}">
                                            <span class="sub-item">Quản lý vai trò</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('config.view')
                                    <li class="{{ request()->routeIs('admin.config.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.config.form') }}">
                                            <span class="sub-item">Thông tin chung</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

            </ul>
        </div>
    </div>
</div>
