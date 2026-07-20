# DANH Má»¤C CHá»¨C NÄ‚NG ÄÃƒ TRIá»‚N KHAI

**Thá»i Ä‘iá»ƒm kiá»ƒm tra:** 2026-07-16 16:57:10 +07:00  
**Branch:** `viet_dev`  
**Commit:** `565229a`  
**PHP CLI:** `8.3.16`  
**Laravel:** `10.49.0`  
**Database Ä‘Ã£ kiá»ƒm tra read-only:** `ai_crm_2026`  
**Pháº¡m vi báº£n sáº¡ch:** chá»‰ giá»¯ cÃ¡c pháº§n Ä‘Ã£ cÃ³ route/controller/view/model/database hoáº·c Ä‘Ã£ cÃ³ dá»¯ liá»‡u thá»±c táº¿.  
**LÆ°u Ã½:** chÆ°a sá»­a source code, chÆ°a sá»­a database, chÆ°a cháº¡y migrate/seed.

## 1. Chá»©c nÄƒng Ä‘Ã£ triá»ƒn khai

| STT | Module | Khu vá»±c | NgÆ°á»i dÃ¹ng | Route chÃ­nh | Controller | Model/Báº£ng dá»¯ liá»‡u | View | Má»©c Ä‘á»™ tin cáº­y | Báº±ng chá»©ng Ä‘Ã£ xÃ¡c minh |
|---:|---|---|---|---|---|---|---|---|---|
| 1 | ÄÄƒng nháº­p vÃ  phÃ¢n quyá»n | DÃ¹ng chung | Admin, Staff, SuperAdmin | `login`, `logout`, `admin/*`, `ban-hang/*`, `super-admin/*` | `LoginController`, `SuperAdminController` | `users`, `super_admins`, `roles` | `Auth.*`, `superadmin.formlogin.index` | Cao | CÃ³ middleware `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/CheckLogin.php`, `app/Http/Middleware/CheckLoginSuperAdmin.php`; DB cÃ³ `users` 2 dÃ²ng, `super_admins` 2 dÃ²ng. |
| 2 | Dashboard cÆ¡ báº£n | Admin | Admin | `GET admin` | `Admin\DashboardController@index` | `orders`, `order_details`, `products`, `clients` | `resources/views/welcome.blade.php` | Cao | Route `GET admin` tá»“n táº¡i; controller tráº£ view dashboard; DB cÃ³ `orders` 11 dÃ²ng, `order_details` 11 dÃ²ng, `clients` 3 dÃ²ng. |
| 3 | CRUD sáº£n pháº©m | Admin | Admin | `admin/products*` | `Admin\ProductController@index`, `create`, `store`, `edit`, `update`, `show` | `products`, `categories`, `brands`, `product_images` | `resources/views/admin/product/*` | Cao | `app/Http/Controllers/Admin/ProductController.php` cÃ³ cÃ¡c method CRUD; DB `products` cÃ³ 1 dÃ²ng. |
| 4 | Quáº£n lÃ½ thÆ°Æ¡ng hiá»‡u | Admin | Admin | `admin/brand*` | `Admin\BrandController` | `brands`, `products` | `resources/views/admin/brand/*` | Cao | Route brand tá»“n táº¡i; controller vÃ  view admin brand cÃ³ trong source; báº£ng `brands` tá»“n táº¡i trong DB. |
| 5 | Quáº£n lÃ½ kho | Admin | Admin, kho | `admin/storage*` | `Admin\StorageController` | `storages`, `product_storage` | `resources/views/admin/storage/*` | Cao | DB `storages` cÃ³ 5 dÃ²ng, `product_storage` cÃ³ 1 dÃ²ng; cÃ³ service `app/Services/ProductStorageService.php`. |
| 6 | Nháº­p hÃ ng | Admin | Admin, kho | `admin/importproduct*`, `admin/importCoupon*` | `Admin\ImportProductController`, `Admin\importCouponController` | `import`, `import_coupon`, `import_detail`, `product_storage` | `resources/views/admin/Importproduct/*` | Cao | DB `import_coupon` cÃ³ 9 dÃ²ng, `import_detail` cÃ³ 7 dÃ²ng; `app/Http/Controllers/Admin/importCouponController.php` cÃ³ xá»­ lÃ½ táº¡o phiáº¿u nháº­p. |
| 7 | Kiá»ƒm kÃª kho | Admin, Staff | Admin, Staff | `admin/checkInventory*`, `ban-hang/checkInventory*`, `ban-hang/warehome*` | `Admin\CheckInventoryController`, `Staff\CheckInventoryController`, `Staff\WareHomeController` | `check_inventory`, `check_detail`, `warehouse` | `admin.check.*`, `Themes.pages.checkInventory.*`, `Themes.pages.warehome.*` | Cao | Route/controller/view tá»“n táº¡i; báº£ng `check_inventory`, `check_detail`, `warehouse` tá»“n táº¡i trong DB. |
| 8 | BÃ¡n hÃ ng táº¡i quáº§y - táº¡o Ä‘Æ¡n | Staff | Staff | `ban-hang`, `ban-hang/product`, `ban-hang/order`, `ban-hang/get-clients` | `Staff\ProductController`, `Staff\OrderController@store` | `products`, `clients`, `orders`, `order_details`, `transactions`, `transaction_entries` | `resources/views/Themes/pages/layout_staff/index.blade.php` | Cao | `app/Http/Controllers/Staff/OrderController.php:89-289` cÃ³ validate, táº¡o order, order detail vÃ  bÃºt toÃ¡n; DB cÃ³ `orders` 11 dÃ²ng. |
| 9 | Quáº£n lÃ½ Ä‘Æ¡n hÃ ng | Admin, Staff | Admin, Staff | `admin/order*`, `ban-hang/order/fetch` | `Admin\OrderController`, `Staff\OrderController` | `orders`, `order_details` | `resources/views/admin/order/*`, staff POS view | Cao | DB `orders` cÃ³ 11 dÃ²ng, `order_details` cÃ³ 11 dÃ²ng; model `app/Models/OrderDetail.php` tá»“n táº¡i. |
| 10 | Quáº£n lÃ½ khÃ¡ch hÃ ng | Admin, Staff | Admin, Staff | `admin/client*`, `ban-hang/get-clients`, `ban-hang/clients/add` | `Admin\ClientController`, staff endpoint | `clients`, `client_group` | `resources/views/admin/client/*`, staff POS modal | Cao | DB `clients` cÃ³ 3 dÃ²ng; route admin/staff cho khÃ¡ch hÃ ng tá»“n táº¡i. |
| 11 | NhÃ³m khÃ¡ch hÃ ng | Admin | Admin | `admin/client/clientgroup` | `Admin\ClientGroupController` | `client_group`, `clients` | `resources/views/admin/client/clientgroup.blade.php` | Trung bÃ¬nh | Route vÃ  view nhÃ³m khÃ¡ch hÃ ng tá»“n táº¡i; DB cÃ³ báº£ng `client_group`. |
| 12 | NhÃ  cung cáº¥p vÃ  cÃ´ng ty | Admin | Admin, kho | `admin/company*`, `admin/supplier*` | `Admin\CompanyController`, `Admin\SupplierController` | `companies`, `suppliers`, `supplier_debts`, `supplier_debts_detail` | `resources/views/admin/company/*`, `resources/views/admin/supplier/*` | Trung bÃ¬nh | Route/controller/view tá»“n táº¡i; cÃ¡c báº£ng supplier/company/debt tá»“n táº¡i trong DB. |
| 13 | Phiáº¿u thu | Admin, káº¿ toÃ¡n | Admin, káº¿ toÃ¡n | `admin/quanlythuchi/receipts*` | `Admin\ReceiptController` | `receipts`, `receipts_detail`, `customer_debts*` | `resources/views/admin/receipt/*` | Cao | Route/controller/view tá»“n táº¡i; DB cÃ³ báº£ng `receipts`, `receipts_detail`. |
| 14 | Phiáº¿u chi | Admin, káº¿ toÃ¡n | Admin, káº¿ toÃ¡n | `admin/quanlythuchi/expense*` | `Admin\ExpenseController` | `expense`, `expense_detail`, `supplier_debts*` | `resources/views/admin/expense/*` | Trung bÃ¬nh | Route/controller/view tá»“n táº¡i; DB cÃ³ báº£ng `expense`, `expense_detail`. |
| 15 | CÃ´ng ná»£ khÃ¡ch hÃ ng/NCC | Admin, káº¿ toÃ¡n | Admin, káº¿ toÃ¡n | `admin/debts/customer`, `admin/debts/supplier`, `admin/debts/beginning` | `Admin\DebtController` | `transactions`, `transaction_entries`, `clients`, `suppliers` | `resources/views/admin/debt/*` | Trung bÃ¬nh | Controller dÃ¹ng bÃºt toÃ¡n `transactions`/`transaction_entries`; route cÃ´ng ná»£ Ä‘ang active. |
| 16 | TÃ i khoáº£n káº¿ toÃ¡n | Admin, káº¿ toÃ¡n | Admin, káº¿ toÃ¡n | `admin/accounts*` | `Admin\AccountController` | `accounts` | `resources/views/admin/account/*` | Trung bÃ¬nh | `app/Http/Controllers/Admin/AccountController.php` cÃ³ CRUD, search vÃ  balance tree; DB cÃ³ báº£ng `accounts`. |
| 17 | Cash/bank/journal entries | Admin, káº¿ toÃ¡n | Admin, káº¿ toÃ¡n | `admin/transactions/cash*`, `admin/transactions/bank*`, `admin/journal-entries*` | `CashTransactionController`, `BankTransactionController`, `JournalEntryController` | `transactions`, `transaction_entries`, `accounts`, `banks` | `admin.cash-bank.*`, `admin.journal-entries.*` | Trung bÃ¬nh | Route/controller/view cho cash, bank vÃ  journal entries tá»“n táº¡i. |
| 18 | NhÃ¢n viÃªn vÃ  tÃ i khoáº£n ngÆ°á»i dÃ¹ng | Admin | Admin | `admin/employees*`, `admin/users*` | `Admin\EmployeeController`, `Admin\UserController` | `users`, `roles`, `user_info` | `admin.employee.*`, `admin.user.*` | Cao | DB `users` cÃ³ 2 dÃ²ng; route employee/user tá»“n táº¡i; DB `user_info` cÃ³ dá»¯ liá»‡u. |
| 19 | Cáº¥u hÃ¬nh há»‡ thá»‘ng | Admin | Admin | `admin/config` | `Admin\ConfigController` | `config`, `banks` | `admin.config.*` | Tháº¥p | Route GET/POST `admin/config` tá»“n táº¡i; DB cÃ³ báº£ng `config`, `banks`. |
| 20 | SuperAdmin Ä‘Äƒng nháº­p vÃ  há»“ sÆ¡ | SuperAdmin | SuperAdmin | `super-dang-nhap`, `super-admin/profile`, `super-admin/logout` | `SuperAdmin\SuperAdminController` | `super_admins` | `superadmin.*` | Cao | `app/Http/Controllers/SuperAdmin/SuperAdminController.php` cÃ³ login/logout/profile; DB `super_admins` cÃ³ 2 dÃ²ng. |
| 21 | Quáº£n lÃ½ store SuperAdmin | SuperAdmin | SuperAdmin | `super-admin/store/*` | `SuperAdmin\StoreController` | `companies`, `suppliers`, `users` | `superadmin.store.*` | Trung bÃ¬nh | Route index/detail/find/delete store tá»“n táº¡i; controller `app/Http/Controllers/SuperAdmin/StoreController.php` tá»“n táº¡i. |

## 2. TÃ³m táº¯t pháº§n giá»¯ láº¡i

| NhÃ³m | Sá»‘ module | Ghi chÃº |
|---|---:|---|
| ÄÃ£ xÃ¡c minh source nhÆ°ng cáº§n QA nghiá»‡p vá»¥ | 10 | CÃ³ route/controller/view/model, nhÆ°ng chÆ°a thao tÃ¡c end-to-end trÃªn UI. |
| Má»›i xÃ¡c Ä‘á»‹nh qua route/cáº¥u trÃºc | 1 | Cáº¥u hÃ¬nh há»‡ thá»‘ng cáº§n kiá»ƒm tra sÃ¢u hÆ¡n khi cÃ³ yÃªu cáº§u. |

## 3. Nháº­n xÃ©t ngáº¯n

Báº£n sáº¡ch nÃ y chá»‰ giá»¯ láº¡i cÃ¡c pháº§n Ä‘Ã£ cÃ³ ná»n triá»ƒn khai rÃµ rÃ ng: route Ä‘ang active, controller/view/model tÆ°Æ¡ng á»©ng vÃ , vá»›i nhiá»u module, cÃ³ dá»¯ liá»‡u thá»±c táº¿ trong database `ai_crm_2026`. TÃ i liá»‡u táº­p trung vÃ o pháº§n cÃ³ thá»ƒ bÃ n giao hoáº·c tiáº¿p tá»¥c QA.

