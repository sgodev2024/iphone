<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CashTransactionController;
use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\CheckInventoryController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\CustomerDebtPaymentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\DailyReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DebtClientController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\DebtNccController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ImportCouponController;
use App\Http\Controllers\Admin\ImportProductController;
use App\Http\Controllers\Admin\ImportBarcodeController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImeiController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReportdebtController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkController;
use App\Http\Controllers\Client\SignUpController;
use App\Http\Controllers\MultipleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Staff\BarcodeController as StaffBarcodeController;
use App\Http\Controllers\Staff\CheckInventoryController as StaffCheckController;
use App\Http\Controllers\Staff\ClientController as StaffClientController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\ProductController as StaffProductController;
use App\Http\Controllers\Staff\WareHomeController;
use App\Http\Controllers\SuperAdmin\StoreController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Middleware\CheckLoginSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::post('/check-account', [SignUpController::class, 'checkAccount'])
    ->middleware('permission:signup.check_account')
    ->name('check.account');

Route::middleware('guest')->group(function () {

    Route::controller(AuthController::class)
        ->name('auth.')
        ->group(function () {

            Route::get('login', 'login')->name('login');

            Route::post('login', 'authenticate')
                ->name('authenticate');
        });
});

Route::get('/', function () {
    return redirect()->route('auth.login');
});

Route::get('/ban-hang/product', [ProductController::class, 'searchForSale'])
    ->middleware('permission:product.search_sale')
    ->name('sale.products.search');

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])
            ->name('logout');


        Route::middleware(['role:store'])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | DASHBOARD
            |--------------------------------------------------------------------------
            */

            Route::get('/', [DashboardController::class, 'index'])
                ->middleware('permission:dashboard.view')
                ->name('dashboard');

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION
            |--------------------------------------------------------------------------
            */

            Route::prefix('transaction')
                ->name('transaction.')
                ->group(function () {

                    Route::get('', [TransactionController::class, 'index'])
                        ->middleware('permission:transaction.view')
                        ->name('index');

                    Route::get('search', [TransactionController::class, 'search'])
                        ->middleware('permission:transaction.search')
                        ->name('search');

                    Route::get('payment', [TransactionController::class, 'payment'])
                        ->middleware('permission:transaction.payment')
                        ->name('payment');

                    Route::post('store', [TransactionController::class, 'store'])
                        ->middleware('permission:transaction.create')
                        ->name('store');

                    Route::get('export-pdf/{id}', [TransactionController::class, 'exportPDF'])
                        ->middleware('permission:transaction.export_pdf')
                        ->name('export_pdf');

                    Route::get('generateQR', [TransactionController::class, 'generateQrCode'])
                        ->middleware('permission:transaction.generate_qr')
                        ->name('generate');
                });

            /*
            |--------------------------------------------------------------------------
            | PRODUCT
            |--------------------------------------------------------------------------
            */

            Route::prefix('products')
                ->controller(ProductController::class)
                ->name('products.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:product.view')
                        ->name('index');

                    Route::get('create', 'create')
                        ->middleware('permission:product.create')
                        ->name('create');

                    Route::post('/', 'store')
                        ->middleware('permission:product.create')
                        ->name('store');

                    Route::prefix('{product}/imeis')
                        ->controller(ProductImeiController::class)
                        ->name('imeis.')
                        ->group(function () {

                            Route::get('/', 'index')
                                ->middleware('permission:product.imei.view')
                                ->name('index');
                        });

                    Route::get('{id}/edit', 'edit')
                        ->middleware('permission:product.update')
                        ->name('edit');

                    Route::put('{id}', 'update')
                        ->middleware('permission:product.update')
                        ->name('update');

                    Route::post('import', 'import')
                        ->middleware('permission:product.import')
                        ->name('import');

                    Route::get('export', 'export')
                        ->middleware('permission:product.export')
                        ->name('export');
                });

            Route::get('imeis', [ProductImeiController::class, 'globalIndex'])
                ->middleware('permission:product.imei.global_view')
                ->name('imeis.index');

            Route::delete('imeis/{productImei}', [ProductImeiController::class, 'destroy'])
                ->middleware('permission:product.imei.delete')
                ->name('imeis.destroy');

            /*
            |--------------------------------------------------------------------------
            | USER
            |--------------------------------------------------------------------------
            */

            Route::prefix('users')
                ->controller(UserController::class)
                ->name('users.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:user.view')
                        ->name('index');

                    Route::get('create', 'create')
                        ->middleware('permission:user.create')
                        ->name('create');

                    Route::post('/', 'store')
                        ->middleware('permission:user.create')
                        ->name('store');

                    Route::get('{id}/edit', 'edit')
                        ->middleware('permission:user.update')
                        ->name('edit');

                    Route::put('{id}', 'update')
                        ->middleware('permission:user.update')
                        ->name('update');
                });

            /*
            |--------------------------------------------------------------------------
            | COMPANY
            |--------------------------------------------------------------------------
            */

            Route::prefix('company')
                ->controller(CompanyController::class)
                ->name('company.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:company.view')
                        ->name('index');

                    Route::get('create', 'create')
                        ->middleware('permission:company.create')
                        ->name('create');

                    Route::post('/', 'store')
                        ->middleware('permission:company.create')
                        ->name('store');

                    Route::get('{id}/edit', 'edit')
                        ->middleware('permission:company.update')
                        ->name('edit');

                    Route::put('{id}', 'update')
                        ->middleware('permission:company.update')
                        ->name('update');
                });
                            /*
            |--------------------------------------------------------------------------
            | REPORT PROFIT
            |--------------------------------------------------------------------------
            */

            Route::prefix('profit')
            ->name('profit.')
            ->group(function () {

                Route::get('', [ReportController::class, 'profitIndex'])
                    ->middleware('permission:report.profit.view')
                    ->name('index');

                Route::post('/profit-report', [ReportController::class, 'getProfitReportByFilterNew'])
                    ->middleware('permission:report.profit.filter')
                    ->name('getProfitReportByFilter');

                Route::post('/profit-report-all', [ReportController::class, 'getProfitReport'])
                    ->middleware('permission:report.profit.statistics')
                    ->name('getProfitReport');

                Route::post('/profit-report-pdf', [ReportController::class, 'getProfitReportByFilterPDF'])
                    ->middleware('permission:report.profit.export_pdf')
                    ->name('getProfitReportByFilterPDF');
            });

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD API
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard/day', [DashboardController::class, 'StatisticsByDay'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard.day');

        Route::get('/dashboard/month', [DashboardController::class, 'StatisticsByMonth'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard.month');

        Route::get('/dashboard/year', [DashboardController::class, 'StatisticsByYear'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard.year');

        /*
        |--------------------------------------------------------------------------
        | PROFILE
        |--------------------------------------------------------------------------
        */

        Route::get('profile', [AdminController::class, 'profile'])
            ->middleware('permission:superadmin.profile.view')
            ->name('profile');

        Route::post('profile', [AdminController::class, 'updateProfile'])
            ->middleware('permission:superadmin.profile.update')
            ->name('update');

        Route::post('/changePassword', [AdminController::class, 'changePassword'])
            ->middleware('permission:superadmin.profile.update')
            ->name('changePassword');

        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        Route::prefix('category')
            ->controller(CategorieController::class)
            ->name('category.')
            ->group(function () {

                Route::get('/', 'index')
                    ->middleware('permission:category.view')
                    ->name('index');

                Route::post('/', 'store')
                    ->middleware('permission:category.create')
                    ->name('store');

                Route::get('{id}', 'show')
                    ->middleware('permission:category.view')
                    ->name('show');

                Route::put('{id}', 'update')
                    ->middleware('permission:category.update')
                    ->name('update');

                Route::delete('delete/{id}', 'destroy')
                    ->middleware('permission:category.delete')
                    ->name('destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE
        |--------------------------------------------------------------------------
        */

        Route::prefix('employees')
            ->controller(EmployeeController::class)
            ->name('employees.')
            ->group(function () {

                Route::get('/', 'index')
                    ->middleware('permission:employee.view')
                    ->name('index');

                Route::get('create', 'create')
                    ->middleware('permission:employee.create')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:employee.create')
                    ->name('store');

                Route::get('{id}/edit', 'edit')
                    ->middleware('permission:employee.update')
                    ->name('edit');

                Route::put('{id}', 'update')
                    ->middleware('permission:employee.update')
                    ->name('update');
            });

        /*
        |--------------------------------------------------------------------------
        | BRANCH
        |--------------------------------------------------------------------------
        */

        Route::prefix('branchs')
            ->controller(BranchController::class)
            ->name('branchs.')
            ->group(function () {

                Route::get('/', 'index')
                    ->middleware('permission:branch.view')
                    ->name('index');

                Route::get('create', 'create')
                    ->middleware('permission:branch.create')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:branch.create')
                    ->name('store');

                Route::get('{id}/show', 'show')
                    ->middleware('permission:branch.view')
                    ->name('show');

                Route::put('{id}', 'update')
                    ->middleware('permission:branch.update')
                    ->name('update');

                Route::delete('/', 'destroy')
                    ->middleware('permission:branch.delete')
                    ->name('destroy');

                Route::patch('change-status', 'changeStatus')
                    ->middleware('permission:branch.status')
                    ->name('status.update');
            });

        /*
        |--------------------------------------------------------------------------
        | BRAND
        |--------------------------------------------------------------------------
        */

        Route::prefix('brand')
            ->controller(BrandController::class)
            ->name('brand.')
            ->group(function () {

                Route::get('', 'index')
                    ->middleware('permission:brand.view')
                    ->name('index');

                Route::get('create', 'create')
                    ->middleware('permission:brand.create')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:brand.create')
                    ->name('store');

                Route::get('{id}/edit', 'edit')
                    ->middleware('permission:brand.update')
                    ->name('edit');

                Route::put('{id}', 'update')
                    ->middleware('permission:brand.update')
                    ->name('update');
            });

        /*
        |--------------------------------------------------------------------------
        | CLIENT
        |--------------------------------------------------------------------------
        */

        Route::prefix('client')
            ->name('client.')
            ->group(function () {

                Route::get('/', [ClientController::class, 'index'])
                    ->middleware('permission:client.view')
                    ->name('index');

                Route::get('/detail/{id}', [ClientController::class, 'edit'])
                    ->middleware('permission:client.update')
                    ->name('detail');

                Route::put('/update/{id}', [ClientController::class, 'update'])
                    ->middleware('permission:client.update')
                    ->name('update');

                Route::delete('/delete/{id}', [ClientController::class, 'delete'])
                    ->middleware('permission:client.delete')
                    ->name('delete');

                Route::get('/filter', [ClientController::class, 'findClient'])
                    ->middleware('permission:client.search')
                    ->name('filter');

                Route::get('/clientgroup', [ClientController::class, 'clientgroup'])
                    ->middleware('permission:client_group.view')
                    ->name('clientgroup.index');

                Route::get('export', [ClientController::class, 'export'])
                    ->middleware('permission:client.export')
                    ->name('export');
            });

        /*
        |--------------------------------------------------------------------------
        | SUPPLIER
        |--------------------------------------------------------------------------
        */

        Route::prefix('supplier')
            ->name('supplier.')
            ->group(function () {

                Route::get('/{company_id}', [SupplierController::class, 'index'])
                    ->middleware('permission:supplier.view')
                    ->name('index');

                Route::get('/findByPhone', [SupplierController::class, 'findByPhone'])
                    ->middleware('permission:supplier.search')
                    ->name('findByPhone');

                Route::get('/add/{company_id}', [SupplierController::class, 'add'])
                    ->middleware('permission:supplier.create')
                    ->name('add');

                Route::post('/store', [SupplierController::class, 'store'])
                    ->middleware('permission:supplier.create')
                    ->name('store');

                Route::get('detail/{id}', [SupplierController::class, 'edit'])
                    ->middleware('permission:supplier.update')
                    ->name('detail');

                Route::post('update/{id}', [SupplierController::class, 'update'])
                    ->middleware('permission:supplier.update')
                    ->name('update');

                Route::delete('delete/{id}', [SupplierController::class, 'delete'])
                    ->middleware('permission:supplier.delete')
                    ->name('delete');
            });
                        /*
            |--------------------------------------------------------------------------
            | ORDER
            |--------------------------------------------------------------------------
            */

            Route::prefix('order')
                ->name('order.')
                ->group(function () {

                    Route::get('/', [OrderController::class, 'index'])
                        ->middleware('permission:order.view')
                        ->name('index');

                    Route::get('{id}', [OrderController::class, 'show'])
                        ->middleware('permission:order.detail')
                        ->name('show');
                });

            /*
            |--------------------------------------------------------------------------
            | CONFIGURATION
            |--------------------------------------------------------------------------
            */

            Route::prefix('config')
                ->controller(ConfigController::class)
                ->name('config.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:config.view')
                        ->name('form');

                    Route::post('/', 'save')
                        ->middleware('permission:config.update')
                        ->name('save');
                });

            /*
            |--------------------------------------------------------------------------
            | SUPPORT
            |--------------------------------------------------------------------------
            */

            Route::prefix('support')
                ->name('support.')
                ->group(function () {

                    Route::get('/', [SupportController::class, 'contact'])
                        ->middleware('permission:support.view')
                        ->name('lienhe');

                    Route::post('/', [SupportController::class, 'feedback'])
                        ->middleware('permission:support.feedback')
                        ->name('feedback');
                });

            /*
            |--------------------------------------------------------------------------
            | RECEIPT & EXPENSE
            |--------------------------------------------------------------------------
            */

            Route::prefix('quanlythuchi')
                ->name('quanlythuchi.')
                ->group(function () {

                    /*
                    |--------------------------------------------------------------
                    | RECEIPT
                    |--------------------------------------------------------------
                    */

                    Route::prefix('receipts')
                        ->name('receipts.')
                        ->group(function () {

                            Route::get('/', [ReceiptController::class, 'index'])
                                ->middleware('permission:receipt.view')
                                ->name('index');

                            Route::get('/detail/{id}', [ReceiptController::class, 'detail'])
                                ->middleware('permission:receipt.detail')
                                ->name('detail');

                            Route::get('/add', [CustomerDebtPaymentController::class, 'legacyReceiptRedirect'])
                                ->middleware('permission:receipt.create')
                                ->name('add');

                            Route::post('/add', [CustomerDebtPaymentController::class, 'legacyWriteDisabled'])
                                ->middleware('permission:receipt.create')
                                ->name('addSubmit');

                            Route::post('/debt', [CustomerDebtPaymentController::class, 'legacyWriteDisabled'])
                                ->middleware('permission:receipt.debt')
                                ->name('debt');
                        });

                    /*
                    |--------------------------------------------------------------
                    | EXPENSE
                    |--------------------------------------------------------------
                    */

                    Route::prefix('expense')
                        ->name('expense.')
                        ->group(function () {

                            Route::get('/', [ExpenseController::class, 'index'])
                                ->middleware('permission:expense.view')
                                ->name('index');

                            Route::get('/detail/{id}', [ExpenseController::class, 'detail'])
                                ->middleware('permission:expense.detail')
                                ->name('detail');

                            Route::get('/add', [ExpenseController::class, 'add'])
                                ->middleware('permission:expense.create')
                                ->name('add');

                            Route::post('/add', [ExpenseController::class, 'addSubmit'])
                                ->middleware('permission:expense.create')
                                ->name('addSubmit');

                            Route::post('/debt', [ExpenseController::class, 'debt'])
                                ->middleware('permission:expense.debt.lookup')
                                ->name('debt');
                        });
                });

            Route::get('debts/customer/{clientId}/payment-options', [CustomerDebtPaymentController::class, 'options'])
                ->middleware('permission:receipt.create')
                ->name('debts.customer.payment-options');

            Route::post('debts/customer/payments', [CustomerDebtPaymentController::class, 'store'])
                ->middleware('permission:receipt.create')
                ->name('debts.customer.payments.store');

            /*
            |--------------------------------------------------------------------------
            | REPORT
            |--------------------------------------------------------------------------
            */

            Route::prefix('report')
                ->name('report.')
                ->group(function () {

                    /*
                    |--------------------------------------------------------------
                    | DEBT REPORT
                    |--------------------------------------------------------------
                    */

                    Route::prefix('debt')
                        ->name('debt.')
                        ->group(function () {

                            Route::get('/', [ReportdebtController::class, 'index'])
                                ->middleware('permission:warehouse_report.view')
                                ->name('index');

                            Route::get('/print', [ReportdebtController::class, 'print'])
                                ->middleware('permission:warehouse_report.print')
                                ->name('print');
                        });

                    /*
                    |--------------------------------------------------------------
                    | DAILY ORDER REPORT
                    |--------------------------------------------------------------
                    */

                    Route::prefix('orders')
                        ->name('orders.')
                        ->group(function () {

                            Route::get('', [DailyReportController::class, 'getDailyOrder'])
                                ->middleware('permission:report.order.view')
                                ->name('getDailyOrder');

                            Route::get('get-daily-order-data', [DailyReportController::class, 'getDailyOrderData'])
                                ->middleware('permission:report.order.export')
                                ->name('getDailyOrderData');
                        });

                    /*
                    |--------------------------------------------------------------
                    | DAILY IMPORT REPORT
                    |--------------------------------------------------------------
                    */

                    Route::prefix('imports')
                        ->name('imports.')
                        ->group(function () {

                            Route::get('', [DailyReportController::class, 'getDailyImport'])
                                ->middleware('permission:report.import.view')
                                ->name('getDailyImport');

                            Route::get('get-daily-import-data', [DailyReportController::class, 'getDailyImportData'])
                                ->middleware('permission:report.import.export')
                                ->name('getDailyImportData');
                        });
                });

            /*
            |--------------------------------------------------------------------------
            | ROLE
            |--------------------------------------------------------------------------
            */

            Route::prefix('roles')
                ->name('role.')
                ->group(function () {

                    Route::get('/', [RoleController::class, 'index'])
                        ->middleware('permission:role.view')
                        ->name('index');

                    Route::get('/create', [RoleController::class, 'create'])
                        ->middleware('permission:role.create')
                        ->name('create');

                    Route::post('/', [RoleController::class, 'store'])
                        ->middleware('permission:role.create')
                        ->name('store');

                    Route::get('/{role}/edit', [RoleController::class, 'edit'])
                        ->middleware('permission:role.update')
                        ->name('edit');

                    Route::put('/{role}', [RoleController::class, 'update'])
                        ->middleware('permission:role.update')
                        ->name('update');

                    Route::delete('/{role}', [RoleController::class, 'destroy'])
                        ->middleware('permission:role.delete')
                        ->name('destroy');

                    Route::get('/{role}/permissions', [RoleController::class, 'permissions'])
                        ->middleware('permission:role.permission')
                        ->name('permissions');

                    Route::post('/{role}/permissions', [RoleController::class, 'savePermissions'])
                        ->middleware('permission:role.permission')
                        ->name('permissions.save');
                });

            /*
            |--------------------------------------------------------------------------
            | MULTIPLE DELETE
            |--------------------------------------------------------------------------
            */


            /*
            |--------------------------------------------------------------------------
            | BULK ACTION
            |--------------------------------------------------------------------------
            */

            Route::post('/bulk/{type}', [BulkController::class, 'bulk'])
                ->middleware('permission:bulk.action')
                ->name('bulk');
        });

        /*
        |--------------------------------------------------------------------------
        | WAREHOUSE (ROLE 4)
        |--------------------------------------------------------------------------
        */

        Route::middleware(['role:4'])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | INVENTORY CHECK
            |--------------------------------------------------------------------------
            */

            Route::prefix('checkInventory')
                ->name('check.')
                ->group(function () {

                    Route::get('/', [CheckInventoryController::class, 'index'])
                        ->middleware('permission:inventory_check.view')
                        ->name('index');

                    Route::get('/filter', [CheckInventoryController::class, 'filterCheck'])
                        ->middleware('permission:inventory_check.filter')
                        ->name('filter');

                    Route::get('/detail/{id}', [CheckInventoryController::class, 'detail'])
                        ->middleware('permission:inventory_check.detail')
                        ->name('detail');
                });

            /*
            |--------------------------------------------------------------------------
            | INVENTORY REPORT
            |--------------------------------------------------------------------------
            */

            Route::prefix('inventory')
                ->name('inventory.')
                ->group(function () {

                    Route::get('', [ReportController::class, 'index'])
                        ->middleware('permission:report.inventory.view')
                        ->name('index');

                    Route::post('report', [ReportController::class, 'getReportByStorage'])
                        ->middleware('permission:report.inventory.filter')
                        ->name('getReportByStorage');

                    Route::get('low-stock', [ReportController::class, 'getProductsWithSmallQuanity'])
                        ->middleware('permission:report.inventory.low_stock')
                        ->name('lowStock');
                });

            /*
            |--------------------------------------------------------------------------
            | IMPORT PRODUCT
            |--------------------------------------------------------------------------
            */

            Route::prefix('importproduct')
                ->name('importproduct.')
                ->group(function () {

                    Route::get('/', [ImportProductController::class, 'index'])
                        ->middleware('permission:import_product.view')
                        ->name('index');

                    Route::get('/add', [ImportProductController::class, 'add'])
                        ->middleware('permission:import_product.create')
                        ->name('add');

                    Route::post('/bulk-delete', [ImportProductController::class, 'bulkDelete'])
                        ->middleware('permission:import_product.delete')
                        ->name('bulk-delete');

                    Route::get('/import', [ImportProductController::class, 'listImport'])
                        ->middleware('permission:import_product.import')
                        ->name('import');

                    Route::post('/import/add', [ImportProductController::class, 'importadd'])
                        ->middleware('permission:import_product.import')
                        ->name('import.add');

                    Route::post('/import/update', [ImportProductController::class, 'importupdate'])
                        ->middleware('permission:import_product.update')
                        ->name('import.update');

                    Route::post('/import/update/price', [ImportProductController::class, 'importupdateprice'])
                        ->middleware('permission:import_product.update')
                        ->name('import.update.price');

                    Route::get('/import/delete', [ImportProductController::class, 'importdelete'])
                        ->middleware('permission:import_product.delete')
                        ->name('import.delete');

                    Route::post('/import/addCategory', [ImportProductController::class, 'addCategory'])
                        ->middleware('permission:import_product.create')
                        ->name('import.addCategory');

                    Route::post('/importCoupon', [ImportCouponController::class, 'add'])
                        ->middleware('permission:import_coupon.create')
                        ->name('importCoupon.add');

                    Route::get('/detail/{id}', [ImportProductController::class, 'importdetail'])
                        ->middleware('permission:import_product.detail')
                        ->name('importCoupon.detail');

                    Route::get('/detail/{id}/barcodes', [ImportBarcodeController::class, 'index'])
                        ->middleware('permission:import_barcode.view')
                        ->name('barcodes.index');

                    Route::post('/detail/{id}/barcodes/print', [ImportBarcodeController::class, 'print'])
                        ->middleware('permission:import_barcode.print')
                        ->name('barcodes.print');
                });

            /*
            |--------------------------------------------------------------------------
            | STORAGE
            |--------------------------------------------------------------------------
            */

            Route::prefix('storage')
                ->controller(StorageController::class)
                ->name('storage.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:storage.view')
                        ->name('index');

                    Route::post('/', 'store')
                        ->middleware('permission:storage.create')
                        ->name('store');

                    Route::get('{id}', 'show')
                        ->middleware('permission:storage.detail')
                        ->name('show');

                    Route::put('{id}', 'update')
                        ->middleware('permission:storage.update')
                        ->name('update');

                    Route::get('/products/{id}', 'detail')
                        ->middleware('permission:storage.products')
                        ->name('products');

                    Route::get('{storage}/products/{product}/imeis', [StorageController::class, 'productImeis'])
                        ->middleware('permission:product.imei.view')
                        ->name('products.imeis');

                    Route::get('{storage}/inventory', [StorageController::class, 'inventory'])
                        ->middleware('permission:storage.products')
                        ->name('inventory');
                });
        });
                /*
        |--------------------------------------------------------------------------
        | ACCOUNTING (ROLE 3)
        |--------------------------------------------------------------------------
        */

        Route::middleware(['role:staff'])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | DEBT
            |--------------------------------------------------------------------------
            */

            Route::prefix('debts')
                ->controller(DebtController::class)
                ->name('debts.')
                ->group(function () {

                    Route::get('customer', 'customer')
                        ->middleware('permission:debt.customer.view')
                        ->name('customer');

                    Route::get('supplier', 'supplier')
                        ->middleware('permission:debt.supplier.view')
                        ->name('supplier');

                    Route::get('beginning', 'create')
                        ->middleware('permission:debt.beginning.view')
                        ->name('beginning');

                    Route::post('beginning', 'store')
                        ->middleware('permission:debt.beginning.create')
                        ->name('store');
                });

            /*
            |--------------------------------------------------------------------------
            | JOURNAL ENTRY
            |--------------------------------------------------------------------------
            */

            Route::prefix('journal-entries')
                ->controller(JournalEntryController::class)
                ->name('journal-entries.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:journal_entry.view')
                        ->name('index');

                    Route::delete('destroy', 'destroy')
                        ->middleware('permission:journal_entry.delete')
                        ->name('destroy');
                });

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT
            |--------------------------------------------------------------------------
            */

            Route::prefix('accounts')
                ->controller(AccountController::class)
                ->name('accounts.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:account.view')
                        ->name('index');

                    Route::get('balance', 'balance')
                        ->middleware('permission:account.view')
                        ->name('balance');

                    Route::post('/', 'store')
                        ->middleware('permission:account.create')
                        ->name('store');

                    Route::put('/', 'update')
                        ->middleware('permission:account.update')
                        ->name('update');

                    Route::delete('/', 'destroy')
                        ->middleware('permission:account.delete')
                        ->name('destroy');

                    Route::get('ajax/list', 'list')
                        ->middleware('permission:account.view')
                        ->name('list');

                    Route::get('ajax/search', 'search')
                        ->middleware('permission:account.search')
                        ->name('search');
                });

            /*
            |--------------------------------------------------------------------------
            | CASH TRANSACTION
            |--------------------------------------------------------------------------
            */

            Route::prefix('transactions/cash')
                ->controller(CashTransactionController::class)
                ->name('transactions.cash.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:cash_transaction.view')
                        ->name('index');

                    Route::get('save', 'save')
                        ->middleware('permission:cash_transaction.create')
                        ->name('save');

                    Route::post('store', 'store')
                        ->middleware('permission:cash_transaction.create')
                        ->name('store');

                    Route::put('update', 'update')
                        ->middleware('permission:cash_transaction.update')
                        ->name('update');

                    Route::get('search', 'search')
                        ->middleware('permission:cash_transaction.search')
                        ->name('search');

                    Route::get('ajax/list', 'list')
                        ->middleware('permission:cash_transaction.view')
                        ->name('list');
                });

            /*
            |--------------------------------------------------------------------------
            | BANK TRANSACTION
            |--------------------------------------------------------------------------
            */

            Route::prefix('transactions/bank')
                ->controller(BankTransactionController::class)
                ->name('transactions.bank.')
                ->group(function () {

                    Route::get('/', 'index')
                        ->middleware('permission:bank_transaction.view')
                        ->name('index');

                    Route::get('save', 'save')
                        ->middleware('permission:bank_transaction.create')
                        ->name('save');

                    Route::post('store', 'store')
                        ->middleware('permission:bank_transaction.create')
                        ->name('store');

                    Route::get('ajax/list', 'list')
                        ->middleware('permission:bank_transaction.view')
                        ->name('list');
                });
        });

    });

// bán hàng
Route::middleware('role:staff')->prefix('ban-hang')->name('staff.')->group(function () {
    Route::post('storage/select', [StaffProductController::class, 'selectSaleStorage'])->name('storage.select');
    Route::get('product/search', [StaffProductController::class, 'search'])->name('product.search');
    Route::post('barcode/resolve', [StaffBarcodeController::class, 'resolve'])->name('barcode.resolve');
    Route::get('get-clients', [StaffProductController::class, 'getClients']);
    Route::get('', [StaffProductController::class, 'index'])->name('index');
    Route::post('/cart/add', [StaffProductController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update', [StaffProductController::class, 'updateCart'])->name('cart.update');
    Route::post('/cart/update_price', [StaffProductController::class, 'updatePriceCart'])->name('cart.update.price');
    Route::post('/cart/remove', [StaffProductController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('clients/add', [StaffClientController::class, 'addClient'])->name('client.add');
    Route::post('pay', [CustomerDebtPaymentController::class, 'legacyPosDisabled'])->name('pay');
    Route::get('cart', [StaffClientController::class, 'cart'])->name('cart.data');
    Route::get('order', [StaffOrderController::class, 'index'])->name('order');
    Route::get('order/fetch', [StaffOrderController::class, 'orderFetch'])->name('orderFetch');
    Route::get('product', [StaffProductController::class, 'product'])->name('product.get');

        Route::get('checkInventory', [StaffCheckController::class, 'index'])
            ->name('Inventory.get');

        Route::get('checkInventory/add', [StaffCheckController::class, 'add'])
            ->name('Inventory.add');

        Route::post('checkInventory/add', [StaffCheckController::class, 'submitadd'])
            ->name('Inventory.add.submit');

        Route::get('warehome', [WareHomeController::class, 'index'])
            ->name('warehome.get');

        Route::post('warehome/add', [WareHomeController::class, 'add'])
            ->name('warehome.add');

        Route::post('warehome/update', [WareHomeController::class, 'update'])
            ->name('warehome.update');

        Route::get('warehome/delete', [WareHomeController::class, 'delete'])
            ->name('warehome.delete');

        Route::post('warehome/addByCategory', [WareHomeController::class, 'addByCategory'])
            ->name('warehome.addByCategory');

        Route::get('warehome/check', [WareHomeController::class, 'checkwerehouse'])
            ->name('warehome.check');

        Route::post('order', [StaffOrderController::class, 'store']);
    });

/* ==========================================================
| SUPER ADMIN
| ========================================================== */

Route::get('super-dang-nhap', [SuperAdminController::class, 'loginForm'])
    ->middleware('permission:superadmin.login.view')
    ->name('super.dang.nhap');

Route::post('super-dang-nhap', [SuperAdminController::class, 'login'])
    ->middleware('permission:superadmin.login')
    ->name('super.login.submit');

Route::middleware(CheckLoginSuperAdmin::class)
    ->prefix('super-admin')
    ->name('super.')
    ->group(function () {

        Route::get('/detail/{id}', [SuperAdminController::class, 'getSuperAdminInfor'])
            ->middleware('permission:superadmin.profile.view')
            ->name('detail');

        Route::post('/update/{id}', [SuperAdminController::class, 'updateSuperAdminInfo'])
            ->middleware('permission:superadmin.profile.update')
            ->name('update');

        Route::post('logout', [SuperAdminController::class, 'logout'])
            ->middleware('permission:superadmin.logout')
            ->name('logout');

        Route::prefix('store')
            ->name('store.')
            ->group(function () {

                Route::get('/index', [StoreController::class, 'index'])
                    ->middleware('permission:store.view')
                    ->name('index');

                Route::get('/detail/{id}', [StoreController::class, 'detail'])
                    ->middleware('permission:store.detail')
                    ->name('detail');

                Route::get('/findByPhone', [StoreController::class, 'findByPhone'])
                    ->middleware('permission:store.search')
                    ->name('findByPhone');

                Route::get('/delete/{id}', [StoreController::class, 'delete'])
                    ->middleware('permission:store.delete')
                    ->name('delete');
            });
    });
