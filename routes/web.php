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
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Staff\ClientController as StaffClientController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\ProductController as StaffProductController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\DailyReportController;
use App\Http\Controllers\Admin\DebtClientController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\DebtNccController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\importCouponController;
use App\Http\Controllers\Admin\ImportProductController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReportdebtController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Client\SignUpController;
use App\Http\Controllers\Staff\CheckInventoryController as staffcheckController;
use App\Http\Controllers\Staff\WareHomeController;
use App\Http\Controllers\SuperAdmin\StoreController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\BulkController;
use App\Http\Controllers\MultipleController;
use App\Http\Controllers\SuperAdmin\CampaignController;
use App\Http\Controllers\SuperAdmin\ZnsMessageController;
use App\Http\Controllers\SuperAdmin\ZaloController;
use App\Http\Middleware\CheckLogin;
use App\Http\Middleware\CheckLoginSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::post('/check-account', [SignUpController::class, 'checkAccount'])->name('check.account');

Route::middleware('guest')->group(function () {
    // Route::get('/dang-ky', [SignUpController::class, 'index'])->name('register.index');
    // Route::post('/register_account', [SignUpController::class, 'store'])->name('register.signup');
    // Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify-otp');
    // Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify_otp_confirm');

    Route::controller(AuthController::class)
        ->name('auth.')
        ->group(function () {
            Route::get('login', 'login')->name('login');
            Route::post('login', 'authenticate')->name('authenticate');
        });
});

// Route::middleware(['auth'])->group(function () {
//     Route::get('/home', function () {
//         return view('home');
//     })->name('home');
//     Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
//     Route::get('/payment', [PaymentController::class, 'showPaymentForm'])->name('payment.form');
//     Route::post('/payment', [PaymentController::class, 'processPayment'])->name('payment.process');
//     Route::get('/payment-success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
//     Route::post('/payment-notify', [PaymentController::class, 'paymentNotify'])->name('payment.notify');
// });

// Route::get('forget-password', function () {
//     return view('auth.forget-password');
// })->name('forget-password');

// Route::get('/product', function () {
//     return view('Themes.pages.product.index');
// })->name('product');

// Route::get('/employee', function () {
//     return view('Themes.pages.employee.index');
// })->name('employee');

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::post('bulk/{type}', [BulkController::class, 'bulk'])->name('bulk');

        Route::middleware(['role:1'])->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::prefix('transaction')->name('transaction.')->group(function () {
                Route::get('', [TransactionController::class, 'index'])->name('index');
                Route::get('search', [TransactionController::class, 'search'])->name('search');
                Route::get('payment', [TransactionController::class, 'payment'])->name('payment');
                Route::post('store', [TransactionController::class, 'store'])->name('store');
                Route::get('export-pdf/{id}', [TransactionController::class, 'exportPDF'])->name('export_pdf');
                Route::get('generateQR', [TransactionController::class, 'generateQrCode'])->name('generate');
            });

            Route::prefix('products')
                ->controller(ProductController::class)
                ->name('products.')
                ->group(function () {
                    Route::get('/',  'index')->name('index');
                    Route::get('create',  'create')->name('create');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}/edit',  'edit')->name('edit');
                    Route::put('{id}',  'update')->name('update');
                    Route::post('import',  'import')->name('import');
                    Route::get('export',  'export')->name('export');
                });

            Route::prefix('users')
                ->controller(UserController::class)
                ->name('users.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('{id}/edit', 'edit')->name('edit');
                    Route::put('{id}', 'update')->name('update');
                });

            Route::prefix('company')
                ->controller(CompanyController::class)
                ->name('company.')
                ->group(function () {
                    Route::get("/",  'index')->name('index');
                    Route::get('create',  'create')->name('create');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}/edit',  'edit')->name('edit');
                    Route::put('{id}',  'update')->name('update');
                });

            Route::prefix('profit')->name('profit.')->group(function () {
                Route::get('', [ReportController::class, 'profitIndex'])->name('index');
                Route::post('/profit-report', [ReportController::class, 'getProfitReportByFilterNew'])->name('getProfitReportByFilter');
                Route::post('/profit-report-all', [ReportController::class, 'getProfitReport'])->name('getProfitReport');
                Route::post('/profit-report-pdf', [ReportController::class, 'getProfitReportByFilterPDF'])->name('getProfitReportByFilterPDF');
            });
            Route::get('/dashboard/day', [DashboardController::class, 'StatisticsByDay'])->name('dashboard.day');
            Route::get('/dashboard/month', [DashboardController::class, 'StatisticsByMonth'])->name('dashboard.month');
            Route::get('/dashboard/year', [DashboardController::class, 'StatisticsByYear'])->name('dashboard.year');
            Route::get('profile', [AdminController::class, 'profile'])->name('profile');
            Route::post('profile', [AdminController::class, 'updateProfile'])->name('update');
            Route::post('/changePassword', [AdminController::class, 'changePassword'])->name('changePassword');

            Route::prefix('category')
                ->controller(CategorieController::class)
                ->name('category.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}',  'show')->name('show');
                    Route::put('{id}',  'update')->name('update');
                    Route::delete('delete/{id}',  'destroy')->name('destroy');
                });

            Route::prefix('employees')
                ->controller(EmployeeController::class)
                ->name('employees.')
                ->group(function () {
                    Route::get('/',  'index')->name('index');
                    Route::get('create',  'create')->name('create');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}/edit',  'edit')->name('edit');
                    Route::put('{id}',  'update')->name('update');
                });

            Route::prefix('branchs')
                ->controller(BranchController::class)
                ->name('branchs.')
                ->group(function () {
                    Route::get('/',  'index')->name('index');
                    Route::get('create',  'create')->name('create');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}/show',  'show')->name('show');
                    Route::put('{id}',  'update')->name('update');
                    Route::delete('/',  'destroy')->name('destroy');
                    Route::patch('change-status',  'changeStatus')->name('status.update');
                });

            Route::prefix('brand')
                ->controller(BrandController::class)
                ->name('brand.')
                ->group(function () {
                    Route::get('',  'index')->name('index');
                    Route::get('create',  'create')->name('create');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}/edit',  'edit')->name('edit');
                    Route::put('{id}',  'update')->name('update');
                });

            Route::prefix('client')->name('client.')->group(function () {
                Route::get('/', [ClientController::class, 'index'])->name('index');
                Route::get('/detail/{id}', [ClientController::class, 'edit'])->name('detail');
                Route::put('/update/{id}', [ClientController::class, 'update'])->name('update');
                Route::delete('/delete/{id}', [ClientController::class, 'delete'])->name('delete');
                Route::get('/filter', [ClientController::class, 'findClient'])->name('filter');
                Route::get('/clientgroup', [ClientController::class, 'clientgroup'])->name('clientgroup.index');
                Route::get('export', [ClientController::class, 'export'])->name('export');
            });


            Route::prefix('supplier')->name('supplier.')->group(function () {
                Route::get("/{company_id}", [SupplierController::class, 'index'])->name('index');
                Route::get('/findByPhone', [SupplierController::class, 'findByPhone'])->name('findByPhone');
                Route::get('/add/{company_id}', [SupplierController::class, 'add'])->name('add');
                Route::post('/store', [SupplierController::class, 'store'])->name('store');
                Route::get('detail/{id}', [SupplierController::class, 'edit'])->name('detail');
                Route::post('update/{id}', [SupplierController::class, 'update'])->name('update');
                Route::delete('delete/{id}', [SupplierController::class, 'delete'])->name('delete');
            });
            Route::prefix('order')->name('order.')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('index');
                Route::get('{id}', [OrderController::class, 'show'])->name('show');
            });

            Route::prefix('config')
                ->controller(ConfigController::class)
                ->name('config.')
                ->group(function () {
                    Route::get('/',  'index')->name('form');
                    Route::post('/',  'save')->name('save');
                });


            Route::prefix('support')->name('support.')->group(function () {
                Route::get('/', [SupportController::class, 'contact'])->name('lienhe');
                Route::post('/', [SupportController::class, 'feedback'])->name('feedback');
            });



            Route::prefix('quanlythuchi')->name('quanlythuchi.')->group(function () {
                Route::prefix('receipts')->name('receipts.')->group(function () { // phiếu thu
                    Route::get('/', [ReceiptController::class, 'index'])->name('index');
                    Route::get('/detail/{id}', [ReceiptController::class, 'detail'])->name('detail');
                    Route::get('/add', [ReceiptController::class, 'add'])->name('add');
                    Route::post('/add', [ReceiptController::class, 'addSubmit'])->name('addSubmit');
                    Route::post('/debt', [ReceiptController::class, 'debt'])->name('debt');
                });
                Route::prefix('expense')->name('expense.')->group(function () { // phiếu chi
                    Route::get('/', [ExpenseController::class, 'index'])->name('index');
                    Route::get('/detail/{id}', [ExpenseController::class, 'detail'])->name('detail');
                    Route::get('/add', [ExpenseController::class, 'add'])->name('add');
                    Route::post('/add', [ExpenseController::class, 'addSubmit'])->name('addSubmit');
                    Route::post('/debt', [ExpenseController::class, 'debt'])->name('debt');
                });
            });

            Route::prefix('report')->name('report.')->group(function () {
                Route::prefix('debt')->name('debt.')->group(function () {
                    Route::get('/', [ReportdebtController::class, 'index'])->name('index');
                    Route::get('/print', [ReportdebtController::class, 'print'])->name('print');
                });
                Route::prefix('orders')->name('orders.')->group(function () {
                    Route::get('', [DailyReportController::class, 'getDailyOrder'])->name('getDailyOrder');
                    Route::get('get-daily-order-data', [DailyReportController::class, 'getDailyOrderData'])->name('getDailyOrderData');
                });
                Route::prefix('imports')->name('imports.')->group(function () {
                    Route::get('', [DailyReportController::class, 'getDailyImport'])->name('getDailyImport');
                    Route::get('get-daily-import-data', [DailyReportController::class, 'getDailyImportData'])->name('getDailyImportData');
                });
            });
            Route::post('/delete-multiple', [MultipleController::class, 'deleteMultiple'])->name('delete-multiple');
        });





        // kho
        Route::middleware(['role:4'])->group(function () {
            Route::prefix('checkInventory')->name('check.')->group(function () {
                Route::get('/', [CheckInventoryController::class, 'index'])->name('index');
                Route::get('/filter', [CheckInventoryController::class, 'filterCheck'])->name('filter');
                Route::get('/detail/{id}', [CheckInventoryController::class, 'detail'])->name('detail');
            });
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('', [ReportController::class, 'index'])->name('index');
                Route::post('report', [ReportController::class, 'getReportByStorage'])->name('getReportByStorage');
                Route::get('exportPdf', [ReportController::class, 'exportPdf'])->name('exportPdf');
            });

            Route::prefix('importproduct')->name('importproduct.')->group(function () {
                Route::get('/', [ImportProductController::class, 'index'])->name('index');
                Route::get('/add', [ImportProductController::class, 'add'])->name('add');
                Route::get('/import', [ImportProductController::class, 'listImport'])->name('import');
                Route::post('/import/add', [ImportProductController::class, 'importadd'])->name('import.add');
                Route::post('/import/update', [ImportProductController::class, 'importupdate'])->name('import.update');
                Route::post('/import/update/price', [ImportProductController::class, 'importupdateprice'])->name('import.update.price');
                Route::get('/import/delete', [ImportProductController::class, 'importdelete'])->name('import.delete');
                Route::post('/import/addCategory', [ImportProductController::class, 'addCategory'])->name('import.addCategory');
                
                // tạo phiếu
                Route::post('/importCoupon', [importCouponController::class, 'add'])->name('importCoupon.add');
                Route::get('/detail/{id}', [ImportProductController::class, 'importdetail'])->name('importCoupon.detail');
            });

            Route::prefix('storage')
                ->controller(StorageController::class)
                ->name('storage.')
                ->group(function () {
                    Route::get('/',  'index')->name('index');
                    Route::post('/',  'store')->name('store');
                    Route::get('{id}',  'show')->name('show');
                    Route::put('{id}',  'update')->name('update');
                    Route::get('/products/{id}',  'detail')->name('products');
                });
        });
        //end kho

        // kế toán
        Route::middleware(['role:3'])->group(function () {
            // Route::prefix('debts')->name('debts.')->group(function () {
            //     Route::get('/client', [DebtClientController::class, 'index'])->name('client');
            //     Route::get('/client/detail/{id}', [DebtClientController::class, 'detail'])->name('client.detail');
            //     Route::get('/supplier', [DebtNccController::class, 'index'])->name('supplier');
            //     Route::get('/supplier/detail/{id}', [DebtNccController::class, 'detail'])->name('supplier.detail');
            // });

            Route::prefix('debts')->controller(DebtController::class)->name('debts.')->group(function () {
                Route::get('customer', 'customer')->name('customer');
                Route::get('supplier', 'supplier')->name('supplier');
                Route::get('beginning', 'create')->name('beginning');
                Route::post('beginning', 'store');
            });

            Route::prefix('journal-entries')
                ->controller(JournalEntryController::class)
                ->name('journal-entries.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::delete('destroy', 'destroy')->name('destroy');
                });

            Route::prefix('accounts')
                ->controller(AccountController::class)
                ->name('accounts.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('balance', 'balance')->name('balance');
                    Route::post('/', 'store')->name('store');
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                    Route::get('ajax/list', 'list')->name('list');
                    Route::get('ajax/search', 'search')->name('search');
                });

            Route::prefix('transactions/cash')
                ->controller(CashTransactionController::class)
                ->name('transactions.cash.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('save', 'save')->name('save');
                    Route::post('store', 'store')->name('store');
                    Route::put('update', 'update')->name('update');
                    Route::get('search', 'search')->name('search');
                    Route::get('ajax/list', 'list')->name('list');
                });

            Route::prefix('transactions/bank')
                ->controller(BankTransactionController::class)
                ->name('transactions.bank.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('save', 'save')->name('save');
                    Route::post('store', 'store')->name('store');
                    Route::get('ajax/list', 'list')->name('list');
                });
        });
        // end kế toán
    });

// bán hàng
Route::middleware([CheckLogin::class, 'role:3'])->prefix('ban-hang')->name('staff.')->group(function () {
    Route::get('product/search', [StaffProductController::class, 'search'])->name('product.search');
    Route::get('get-clients', [StaffProductController::class, 'getClients']);
    Route::get('', [StaffProductController::class, 'index'])->name('index');
    Route::post('/cart/add', [StaffProductController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update', [StaffProductController::class, 'updateCart'])->name('cart.update');
    Route::post('/cart/update_price', [StaffProductController::class, 'updatePriceCart'])->name('cart.update.price');
    Route::post('/cart/remove', [StaffProductController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('clients/add', [StaffClientController::class, 'addClient'])->name('client.add');
    Route::post('pay', [StaffClientController::class, 'pay'])->name('pay');
    Route::get('cart', [StaffClientController::class, 'cart'])->name('cart.data');
    Route::get('order', [StaffOrderController::class, 'index'])->name('order');
    Route::get('order/fetch', [StaffOrderController::class, 'orderFetch'])->name('orderFetch');
    Route::get('product', [StaffProductController::class, 'product'])->name('product.get');

    Route::get('checkInventory', [staffcheckController::class, 'index'])->name('Inventory.get');
    Route::get('checkInventory/add', [staffcheckController::class, 'add'])->name('Inventory.add');
    Route::post('checkInventory/add', [staffcheckController::class, 'submitadd'])->name('Inventory.add.submit');
    // warehome
    Route::get('warehome', [WareHomeController::class, 'index'])->name('warehome.get');
    Route::post('warehome/add', [WareHomeController::class, 'add'])->name('warehome.add');
    Route::post('warehome/update', [WareHomeController::class, 'update'])->name('warehome.update');
    Route::get('warehome/delete', [WareHomeController::class, 'delete'])->name('warehome.delete');
    Route::post('warehome/addByCategory', [WareHomeController::class, 'addByCategory'])->name('warehome.addByCategory');
    Route::get('warehome/check', [WareHomeController::class, 'checkwerehouse'])->name('warehome.check');

    Route::post('order', [StaffOrderController::class, 'store']);
});

Route::get('super-dang-nhap', [SuperAdminController::class, 'loginForm'])->name('super.dang.nhap');
Route::post('super-dang-nhap', [SuperAdminController::class, 'login'])->name('super.login.submit');
Route::middleware(CheckLoginSuperAdmin::class)->prefix('super-admin')->name('super.')->group(function () {
    Route::prefix('campaign')->name('campaign.')->group(function () {
        Route::get('add', [CampaignController::class, 'add'])->name('add');
        Route::get('', [CampaignController::class, 'index'])->name('index');
        Route::get('fetch', [CampaignController::class, 'fetch'])->name('fetch');
        Route::post('store', [CampaignController::class, 'store'])->name('store');
        Route::get('detail/{id}', [CampaignController::class, 'edit'])->name('detail');
        Route::post('update/{id}', [CampaignController::class, 'update'])->name('update');
        Route::delete('delete/{id}', [CampaignController::class, 'delete'])->name('delete');
        Route::post('update-status/{id}', [CampaignController::class, 'updateStatus'])->name('updateStatus');
    });
    Route::prefix('zalo')->name('zalo.')->group(function () {
        Route::get('zns', [ZaloController::class, 'index'])->name('zns');
        Route::get('/get-active-oa-name', [ZaloController::class, 'getActiveOaName'])->name('getActiveOaName');
        Route::post('/update-oa-status/{oaId}', [ZaloController::class, 'updateOaStatus'])->name('updateOaStatus');
        Route::post('/refresh-access-token', [ZaloController::class, 'refreshAccessToken'])->name('refreshAccessToken');
    });
    Route::prefix('message')->name('message.')->group(function () {
        Route::get('', [ZnsMessageController::class, 'znsMessage'])->name('znsMessage');
        Route::get('/quota', [ZnsMessageController::class, 'znsQuota'])->name('znsQuota');
        Route::get('template', [ZnsMessageController::class, 'templateIndex'])->name('znsTemplate');
        Route::get('refresh', [ZnsMessageController::class, 'refreshTemplates'])->name('znsTemplateRefresh');
        Route::get('detail', [ZnsMessageController::class, 'getTemplateDetail'])->name('znsTemplateDetail');
        Route::get('test', [ZnsMessageController::class, 'test'])->name('test');
    });
    Route::get('/detail/{id}', [SuperAdminController::class, 'getSuperAdminInfor'])->name('detail');
    Route::post('/update/{id}', [SuperAdminController::class, 'updateSuperAdminInfo'])->name('update');
    Route::post('logout', [SuperAdminController::class, 'logout'])->name('logout');
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/index', [StoreController::class, 'index'])->name('index');
        Route::get('/detail/{id}', [StoreController::class, 'detail'])->name('detail');
        Route::get('/findByPhone', [StoreController::class, 'findByPhone'])->name('findByPhone');
        Route::get('/delete/{id}', [StoreController::class, 'delete'])->name('delete');
    });
});
