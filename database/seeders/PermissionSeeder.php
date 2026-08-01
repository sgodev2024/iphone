<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Dashboard
            [
                'module' => 'Dashboard',
                'permission_key' => 'dashboard.view',
                'description' => 'Xem Dashboard',
            ],

            // Category
            [
                'module' => 'Category',
                'permission_key' => 'category.view',
                'description' => 'Xem danh sách danh mục',
            ],
            [
                'module' => 'Category',
                'permission_key' => 'category.create',
                'description' => 'Thêm danh mục',
            ],
            [
                'module' => 'Category',
                'permission_key' => 'category.update',
                'description' => 'Cập nhật danh mục',
            ],
            [
                'module' => 'Category',
                'permission_key' => 'category.delete',
                'description' => 'Xóa danh mục',
            ],

            // Client
            [
                'module' => 'Client',
                'permission_key' => 'client.view',
                'description' => 'Xem danh sách khách hàng',
            ],
            [
                'module' => 'Client',
                'permission_key' => 'client.search',
                'description' => 'Tìm kiếm khách hàng',
            ],
            [
                'module' => 'Client',
                'permission_key' => 'client.update',
                'description' => 'Cập nhật khách hàng',
            ],
            [
                'module' => 'Client',
                'permission_key' => 'client.delete',
                'description' => 'Ngừng hoạt động khách hàng',
            ],
            [
                'module' => 'Client',
                'permission_key' => 'client.export',
                'description' => 'Xuất Excel khách hàng',
            ],

            // Client Group
            [
                'module' => 'Client Group',
                'permission_key' => 'client_group.view',
                'description' => 'Xem nhóm khách hàng',
            ],

            // Company
            [
                'module' => 'Company',
                'permission_key' => 'company.view',
                'description' => 'Xem nhà cung cấp',
            ],
            [
                'module' => 'Company',
                'permission_key' => 'company.create',
                'description' => 'Thêm nhà cung cấp',
            ],
            [
                'module' => 'Company',
                'permission_key' => 'company.update',
                'description' => 'Cập nhật nhà cung cấp',
            ],

            // Configuration
            [
                'module' => 'Configuration',
                'permission_key' => 'config.view',
                'description' => 'Xem cấu hình cửa hàng',
            ],
            [
                'module' => 'Configuration',
                'permission_key' => 'config.update',
                'description' => 'Cập nhật cấu hình cửa hàng',
            ],

            // Inventory Check
            [
                'module' => 'Inventory Check',
                'permission_key' => 'inventory_check.view',
                'description' => 'Xem danh sách kiểm kê',
            ],
            [
                'module' => 'Inventory Check',
                'permission_key' => 'inventory_check.filter',
                'description' => 'Lọc phiếu kiểm kê',
            ],
            [
                'module' => 'Inventory Check',
                'permission_key' => 'inventory_check.detail',
                'description' => 'Xem chi tiết kiểm kê',
            ],

            // Daily Report
            [
                'module' => 'Daily Report',
                'permission_key' => 'report.order.view',
                'description' => 'Xem báo cáo bán hàng ngày',
            ],
            [
                'module' => 'Daily Report',
                'permission_key' => 'report.order.export',
                'description' => 'Xuất Excel báo cáo bán hàng',
            ],
            [
                'module' => 'Daily Report',
                'permission_key' => 'report.import.view',
                'description' => 'Xem báo cáo nhập hàng',
            ],
            [
                'module' => 'Daily Report',
                'permission_key' => 'report.import.export',
                'description' => 'Xuất Excel báo cáo nhập hàng',
            ],

            // Debt Client
            [
                'module' => 'Debt Client',
                'permission_key' => 'debt_client.view',
                'description' => 'Xem công nợ khách hàng',
            ],
            [
                'module' => 'Debt Client',
                'permission_key' => 'debt_client.detail',
                'description' => 'Xem chi tiết công nợ khách hàng',
            ],

            // Debt Supplier
            [
                'module' => 'Debt Supplier',
                'permission_key' => 'debt_supplier.view',
                'description' => 'Xem công nợ nhà cung cấp',
            ],
            [
                'module' => 'Debt Supplier',
                'permission_key' => 'debt_supplier.detail',
                'description' => 'Xem chi tiết công nợ nhà cung cấp',
            ],
            // Debt
            [
                'module' => 'Debt',
                'permission_key' => 'debt.customer.view',
                'description' => 'Báo cáo công nợ khách hàng',
            ],
            [
                'module' => 'Debt',
                'permission_key' => 'debt.supplier.view',
                'description' => 'Báo cáo công nợ nhà cung cấp',
            ],
            [
                'module' => 'Debt',
                'permission_key' => 'debt.beginning.view',
                'description' => 'Mở form công nợ đầu kỳ',
            ],
            [
                'module' => 'Debt',
                'permission_key' => 'debt.beginning.create',
                'description' => 'Tạo công nợ đầu kỳ',
            ],

            // Employee
            [
                'module' => 'Employee',
                'permission_key' => 'employee.view',
                'description' => 'Xem nhân viên',
            ],
            [
                'module' => 'Employee',
                'permission_key' => 'employee.create',
                'description' => 'Thêm nhân viên',
            ],
            [
                'module' => 'Employee',
                'permission_key' => 'employee.update',
                'description' => 'Cập nhật nhân viên',
            ],

            // Expense
            [
                'module' => 'Expense',
                'permission_key' => 'expense.view',
                'description' => 'Xem phiếu chi',
            ],
            [
                'module' => 'Expense',
                'permission_key' => 'expense.create',
                'description' => 'Tạo phiếu chi',
            ],
            [
                'module' => 'Expense',
                'permission_key' => 'expense.detail',
                'description' => 'Xem chi tiết phiếu chi',
            ],
            [
                'module' => 'Expense',
                'permission_key' => 'expense.debt.lookup',
                'description' => 'Tra cứu công nợ nhà cung cấp',
            ],

            // Import Product
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.barcode.view',
                'description' => 'Xem danh sách barcode phiếu nhập',
            ],
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.barcode.print',
                'description' => 'In barcode sản phẩm',
            ],
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.create',
                'description' => 'Tạo phiếu nhập hàng',
            ],

            // Journal Entry
            [
                'module' => 'Journal Entry',
                'permission_key' => 'journal_entry.view',
                'description' => 'Xem danh sách bút toán nhật ký',
            ],
            [
                'module' => 'Journal Entry',
                'permission_key' => 'journal_entry.delete',
                'description' => 'Xóa phiếu nhật ký',
            ],

            // Notification
            [
                'module' => 'Notification',
                'permission_key' => 'notification.update',
                'description' => 'Đánh dấu thông báo đã đọc',
            ],

            // Order
            [
                'module' => 'Order',
                'permission_key' => 'order.view',
                'description' => 'Xem danh sách đơn hàng',
            ],
            [
                'module' => 'Order',
                'permission_key' => 'order.detail',
                'description' => 'Xem chi tiết đơn hàng',
            ],

            // Product
            [
                'module' => 'Product',
                'permission_key' => 'product.view',
                'description' => 'Xem danh sách sản phẩm',
            ],
            [
                'module' => 'Product',
                'permission_key' => 'product.create',
                'description' => 'Thêm sản phẩm',
            ],
            [
                'module' => 'Product',
                'permission_key' => 'product.update',
                'description' => 'Cập nhật sản phẩm',
            ],
            [
                'module' => 'Product',
                'permission_key' => 'product.search_sale',
                'description' => 'Tìm kiếm sản phẩm bán hàng',
            ],
            [
                'module' => 'Product',
                'permission_key' => 'product.import',
                'description' => 'Import sản phẩm',
            ],
            [
                'module' => 'Product',
                'permission_key' => 'product.export',
                'description' => 'Xuất Excel sản phẩm',
            ],
            [
                'module' => 'Product',
                'permission_key' => 'product.delete',
                'description' => 'Xóa sản phẩm',
            ],

            // Product IMEI
            [
                'module' => 'Product IMEI',
                'permission_key' => 'product.imei.global_view',
                'description' => 'Xem toàn bộ IMEI',
            ],
            [
                'module' => 'Product IMEI',
                'permission_key' => 'product.imei.view',
                'description' => 'Xem IMEI sản phẩm',
            ],
            [
                'module' => 'Product IMEI',
                'permission_key' => 'product.imei.delete',
                'description' => 'Xóa IMEI',
            ],

            // Import Coupon
            [
                'module' => 'Import Coupon',
                'permission_key' => 'import_coupon.create',
                'description' => 'Xác nhận phiếu nhập',
            ],

            // Import Barcode
            [
                'module' => 'Import Barcode',
                'permission_key' => 'import_barcode.view',
                'description' => 'Xem danh sách barcode phiếu nhập',
            ],
            [
                'module' => 'Import Barcode',
                'permission_key' => 'import_barcode.print',
                'description' => 'In barcode phiếu nhập',
            ],

            // Receipt
            [
                'module' => 'Receipt',
                'permission_key' => 'receipt.view',
                'description' => 'Xem danh sách phiếu thu',
            ],
            [
                'module' => 'Receipt',
                'permission_key' => 'receipt.create',
                'description' => 'Tạo phiếu thu',
            ],
            [
                'module' => 'Receipt',
                'permission_key' => 'receipt.detail',
                'description' => 'Xem chi tiết phiếu thu',
            ],
            [
                'module' => 'Receipt',
                'permission_key' => 'receipt.debt',
                'description' => 'Tra cứu công nợ khách hàng',
            ],

            // Report Inventory
            [
                'module' => 'Report Inventory',
                'permission_key' => 'report.inventory.view',
                'description' => 'Xem báo cáo xuất nhập tồn',
            ],
            [
                'module' => 'Report Inventory',
                'permission_key' => 'report.inventory.filter',
                'description' => 'Lọc báo cáo kho',
            ],
            [
                'module' => 'Report Inventory',
                'permission_key' => 'report.inventory.low_stock',
                'description' => 'Xem sản phẩm sắp hết hàng',
            ],

            // Report Profit
            [
                'module' => 'Report Profit',
                'permission_key' => 'report.profit.view',
                'description' => 'Xem báo cáo lợi nhuận',
            ],
            [
                'module' => 'Report Profit',
                'permission_key' => 'report.profit.filter',
                'description' => 'Lọc báo cáo lợi nhuận',
            ],
            [
                'module' => 'Report Profit',
                'permission_key' => 'report.profit.statistics',
                'description' => 'Thống kê lợi nhuận sản phẩm',
            ],
            [
                'module' => 'Report Profit',
                'permission_key' => 'report.profit.export_pdf',
                'description' => 'Xuất PDF báo cáo lợi nhuận',
            ],

            // Warehouse Report
            [
                'module' => 'Warehouse Report',
                'permission_key' => 'warehouse_report.view',
                'description' => 'Xem báo cáo kho',
            ],
            [
                'module' => 'Warehouse Report',
                'permission_key' => 'warehouse_report.print',
                'description' => 'In báo cáo kho',
            ],

            // Storage
            [
                'module' => 'Storage',
                'permission_key' => 'storage.view',
                'description' => 'Xem danh sách kho hàng',
            ],
            [
                'module' => 'Storage',
                'permission_key' => 'storage.detail',
                'description' => 'Xem chi tiết kho hàng',
            ],
            [
                'module' => 'Storage',
                'permission_key' => 'storage.create',
                'description' => 'Thêm kho hàng',
            ],
            [
                'module' => 'Storage',
                'permission_key' => 'storage.update',
                'description' => 'Cập nhật kho hàng',
            ],
            [
                'module' => 'Storage',
                'permission_key' => 'storage.products',
                'description' => 'Xem sản phẩm trong kho',
            ],

            // Supplier
            [
                'module' => 'Supplier',
                'permission_key' => 'supplier.view',
                'description' => 'Xem danh sách nhà cung cấp',
            ],
            [
                'module' => 'Supplier',
                'permission_key' => 'supplier.search',
                'description' => 'Tìm kiếm nhà cung cấp',
            ],
            [
                'module' => 'Supplier',
                'permission_key' => 'supplier.create',
                'description' => 'Thêm nhà cung cấp',
            ],
            [
                'module' => 'Supplier',
                'permission_key' => 'supplier.update',
                'description' => 'Cập nhật nhà cung cấp',
            ],
            [
                'module' => 'Supplier',
                'permission_key' => 'supplier.delete',
                'description' => 'Xóa nhà cung cấp',
            ],

            // Support
            [
                'module' => 'Support',
                'permission_key' => 'support.view',
                'description' => 'Xem trang hỗ trợ',
            ],
            [
                'module' => 'Support',
                'permission_key' => 'support.feedback',
                'description' => 'Gửi phản hồi',
            ],
            // Transaction
            [
                'module' => 'Transaction',
                'permission_key' => 'transaction.view',
                'description' => 'Xem danh sách giao dịch',
            ],
            [
                'module' => 'Transaction',
                'permission_key' => 'transaction.search',
                'description' => 'Tìm kiếm giao dịch',
            ],
            [
                'module' => 'Transaction',
                'permission_key' => 'transaction.payment',
                'description' => 'Thanh toán giao dịch',
            ],
            [
                'module' => 'Transaction',
                'permission_key' => 'transaction.create',
                'description' => 'Tạo giao dịch',
            ],
            [
                'module' => 'Transaction',
                'permission_key' => 'transaction.export_pdf',
                'description' => 'Xuất hóa đơn PDF',
            ],
            [
                'module' => 'Transaction',
                'permission_key' => 'transaction.generate_qr',
                'description' => 'Tạo mã QR thanh toán',
            ],

            // User
            [
                'module' => 'User',
                'permission_key' => 'user.view',
                'description' => 'Xem danh sách tài khoản',
            ],
            [
                'module' => 'User',
                'permission_key' => 'user.create',
                'description' => 'Thêm tài khoản',
            ],
            [
                'module' => 'User',
                'permission_key' => 'user.search',
                'description' => 'Tìm kiếm tài khoản',
            ],
            [
                'module' => 'User',
                'permission_key' => 'user.update',
                'description' => 'Cập nhật tài khoản',
            ],
            [
                'module' => 'User',
                'permission_key' => 'user.profile_update',
                'description' => 'Cập nhật tài khoản quản trị',
            ],

            // Client
            [
                'module' => 'Sign Up',
                'permission_key' => 'signup.view',
                'description' => 'Xem trang đăng ký tài khoản dùng thử',
            ],
            [
                'module' => 'Sign Up',
                'permission_key' => 'signup.create',
                'description' => 'Đăng ký tài khoản dùng thử',
            ],
            [
                'module' => 'Sign Up',
                'permission_key' => 'signup.check_account',
                'description' => 'Kiểm tra số điện thoại và email đã tồn tại',
            ],

            // Super Admin - Store
            [
                'module' => 'Store',
                'permission_key' => 'store.view',
                'description' => 'Xem danh sách cửa hàng',
            ],
            [
                'module' => 'Store',
                'permission_key' => 'store.search',
                'description' => 'Tìm kiếm cửa hàng theo số điện thoại',
            ],
            [
                'module' => 'Store',
                'permission_key' => 'store.detail',
                'description' => 'Xem chi tiết cửa hàng',
            ],
            [
                'module' => 'Store',
                'permission_key' => 'store.delete',
                'description' => 'Xóa cửa hàng',
            ],

            // Super Admin
            [
                'module' => 'Super Admin',
                'permission_key' => 'superadmin.profile.view',
                'description' => 'Xem thông tin Super Admin',
            ],
            [
                'module' => 'Super Admin',
                'permission_key' => 'superadmin.profile.update',
                'description' => 'Cập nhật thông tin Super Admin',
            ],
            [
                'module' => 'Super Admin',
                'permission_key' => 'superadmin.login.view',
                'description' => 'Xem trang đăng nhập Super Admin',
            ],
            [
                'module' => 'Super Admin',
                'permission_key' => 'superadmin.login',
                'description' => 'Đăng nhập Super Admin',
            ],
            [
                'module' => 'Super Admin',
                'permission_key' => 'superadmin.logout',
                'description' => 'Đăng xuất Super Admin',
            ],

            [
                'module' => 'Branch',
                'permission_key' => 'branch.view',
                'description' => 'Xem danh sách chi nhánh',
            ],
            [
                'module' => 'Branch',
                'permission_key' => 'branch.create',
                'description' => 'Thêm chi nhánh',
            ],
            [
                'module' => 'Branch',
                'permission_key' => 'branch.update',
                'description' => 'Cập nhật chi nhánh',
            ],
            [
                'module' => 'Branch',
                'permission_key' => 'branch.delete',
                'description' => 'Xóa chi nhánh',
            ],
            [
                'module' => 'Branch',
                'permission_key' => 'branch.status',
                'description' => 'Thay đổi trạng thái chi nhánh',
            ],

            /*
                |--------------------------------------------------------------------------
                | Brand
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Brand',
                'permission_key' => 'brand.view',
                'description' => 'Xem danh sách thương hiệu',
            ],
            [
                'module' => 'Brand',
                'permission_key' => 'brand.create',
                'description' => 'Thêm thương hiệu',
            ],
            [
                'module' => 'Brand',
                'permission_key' => 'brand.update',
                'description' => 'Cập nhật thương hiệu',
            ],

            /*
                |--------------------------------------------------------------------------
                | Role
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Role',
                'permission_key' => 'role.view',
                'description' => 'Xem danh sách vai trò',
            ],
            [
                'module' => 'Role',
                'permission_key' => 'role.create',
                'description' => 'Thêm vai trò',
            ],
            [
                'module' => 'Role',
                'permission_key' => 'role.update',
                'description' => 'Cập nhật vai trò',
            ],
            [
                'module' => 'Role',
                'permission_key' => 'role.delete',
                'description' => 'Xóa vai trò',
            ],
            [
                'module' => 'Role',
                'permission_key' => 'role.permission',
                'description' => 'Phân quyền cho vai trò',
            ],

            /*
                |--------------------------------------------------------------------------
                | Bulk Action
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Bulk',
                'permission_key' => 'bulk.action',
                'description' => 'Thực hiện thao tác hàng loạt',
            ],

            /*
                |--------------------------------------------------------------------------
                | Multiple
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Multiple',
                'permission_key' => 'multiple.delete',
                'description' => 'Xóa nhiều bản ghi',
            ],

            /*
                |--------------------------------------------------------------------------
                | Import Product
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.view',
                'description' => 'Xem danh sách nhập hàng',
            ],
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.detail',
                'description' => 'Xem chi tiết phiếu nhập',
            ],
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.import',
                'description' => 'Import dữ liệu nhập hàng',
            ],
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.update',
                'description' => 'Cập nhật dữ liệu nhập hàng',
            ],
            [
                'module' => 'Import Product',
                'permission_key' => 'import_product.delete',
                'description' => 'Xóa dữ liệu nhập hàng',
            ],

            /*
                |--------------------------------------------------------------------------
                | Account
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Account',
                'permission_key' => 'account.view',
                'description' => 'Xem danh sách tài khoản kế toán',
            ],
            [
                'module' => 'Account',
                'permission_key' => 'account.create',
                'description' => 'Thêm tài khoản kế toán',
            ],
            [
                'module' => 'Account',
                'permission_key' => 'account.update',
                'description' => 'Cập nhật tài khoản kế toán',
            ],
            [
                'module' => 'Account',
                'permission_key' => 'account.delete',
                'description' => 'Xóa tài khoản kế toán',
            ],
            [
                'module' => 'Account',
                'permission_key' => 'account.search',
                'description' => 'Tìm kiếm tài khoản kế toán',
            ],

            /*
                |--------------------------------------------------------------------------
                | Cash Transaction
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Cash Transaction',
                'permission_key' => 'cash_transaction.view',
                'description' => 'Xem giao dịch tiền mặt',
            ],
            [
                'module' => 'Cash Transaction',
                'permission_key' => 'cash_transaction.create',
                'description' => 'Tạo giao dịch tiền mặt',
            ],
            [
                'module' => 'Cash Transaction',
                'permission_key' => 'cash_transaction.update',
                'description' => 'Cập nhật giao dịch tiền mặt',
            ],
            [
                'module' => 'Cash Transaction',
                'permission_key' => 'cash_transaction.search',
                'description' => 'Tìm kiếm giao dịch tiền mặt',
            ],

            /*
                |--------------------------------------------------------------------------
                | Bank Transaction
                |--------------------------------------------------------------------------
                */
            [
                'module' => 'Bank Transaction',
                'permission_key' => 'bank_transaction.view',
                'description' => 'Xem giao dịch ngân hàng',
            ],
            [
                'module' => 'Bank Transaction',
                'permission_key' => 'bank_transaction.create',
                'description' => 'Tạo giao dịch ngân hàng',
            ],


        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'permission_key' => $permission['permission_key'],
                ],
                $permission
            );
        }
    }
}
