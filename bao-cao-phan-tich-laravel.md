# Bao cao phan tich du an Laravel

> Pham vi phan tich: doc truc tiep source trong `D:\iphone`, gom `routes/web.php`, controller, model, migration, helper va Blade view. Du an chay Laravel 10, PHP ^8.1 theo `composer.json`.

## 1. Tong quan du an

Day la website quan ly ban hang, kho hang, cong no va bao cao noi bo. Cac module chinh gom quan tri san pham, danh muc, thuong hieu, nha cung cap, khach hang, kho, nhap hang, ban hang tai quay, don hang, thu chi, cong no, bao cao loi nhuan va bao cao xuat nhap ton.


Nhom nguoi dung chinh:

- Admin/chu cua hang: truy cap `/admin`, quan ly san pham, nhan vien, kho, don hang, bao cao, cau hinh. Route admin duoc bao ve bang `auth` va cac nhom role trong `routes/web.php:94-387`.
- Nhan vien ban hang: truy cap `/ban-hang`, tim san pham, lap gio hang, tao khach hang, thanh toan va xem lich su don. Route ban hang duoc dinh nghia tai `routes/web.php:390-417`.
- Nhan vien kho/ke toan: duoc phan quyen qua middleware `role:4` cho kho va `role:3` cho ke toan trong `routes/web.php:287-385`.
- Super admin: dang nhap rieng tai `/super-dang-nhap`, sau do vao `/super-admin/*`, middleware rieng `CheckLoginSuperAdmin` trong `routes/web.php:419-455`.

Luong hoat dong tong quat:

1. Nguoi dung vao `/`, he thong redirect den route `auth.login` (`routes/web.php:91-93`).
2. Dang nhap bang `AuthController@authenticate`; tuy `role_id`, he thong tra JSON thanh cong kem duong dan `/admin` hoac `/ban-hang` (`app/Http/Controllers/AuthController.php:22-55`).
3. Admin tao danh muc, thuong hieu, san pham, kho va nhap hang.
4. Nhan vien ban hang tim san pham qua Ajax, them vao gio tren giao dien, nhap thong tin khach, tao don hang qua `/ban-hang/order`.
5. Don hang tao `orders`, `order_details`, giam ton kho san pham va tao but toan trong `transactions`, `transaction_entries` (`app/Http/Controllers/Staff/OrderController.php:89-289`).
6. Admin/ke toan xem dashboard, lich su don, cong no, thu chi, bao cao doanh thu, bao cao ton kho.

## 2. Cau truc thu muc Laravel

- `app/Http/Controllers`: chua controller xu ly request. Du an chia theo namespace `Admin`, `Staff`, `SuperAdmin`, vi du `Admin\ProductController`, `Staff\OrderController`, `SuperAdmin\SuperAdminController`.
- `app/Models`: chua Eloquent model dai dien bang du lieu, vi du `Product`, `Order`, `Client`, `Storage`, `Transaction`.
- `app/Services`: chua lop service cho nghiep vu trung gian, vi du `ProductStorageService`, `ClientService`, `OrderService`, `ProfitService`.
- `app/Http/Middleware`: chua middleware dang nhap va phan quyen: `Authenticate`, `RoleMiddleware`, `CheckLogin`, `CheckLoginSuperAdmin`.
- `routes`: route web chinh nam gan nhu toan bo trong `routes/web.php`; `routes/api.php` chi co route mac dinh `/api/user`.
- `resources/views`: chua Blade view. Admin dung `resources/views/admin/*`; nhan vien ban hang dung `resources/views/Themes/layout_staff/*` va `resources/views/Themes/pages/*`; super admin dung `resources/views/superadmin/*`.
- `public`: chua asset cong khai, anh fallback, file build va file co the truy cap truc tiep.
- `storage`: noi Laravel luu file upload qua disk `public`. Helper `showImage()` doc tu `Storage::disk('public')` va fallback `assets/img/default-image.jpg` (`app/Helpers/helper.php:10-21`).

Controller, model, view lien ket theo mo hinh MVC. Route goi controller; controller lay model/service; sau do tra view Blade hoac JSON. Vi du `admin/products` goi `Admin\ProductController@index`, controller truy van `Product` va tra `admin.product.index` hoac render partial `admin.product.table` khi Ajax (`app/Http/Controllers/Admin/ProductController.php:20-39`).

## 3. Phan tich route he thong

Route dang nhap/dang xuat:

- `GET /login` -> `AuthController@login`, tra view `auth.login` (`routes/web.php:61-65`, `AuthController.php:17-20`).
- `POST /login` -> `AuthController@authenticate`, validate bang `LoginRequest`, dung `Auth::attempt`, chan user `inactive`/`locked`, redirect role 1/2 ve `/admin`, role 3 ve `/ban-hang` (`AuthController.php:22-55`).
- `POST /admin/logout` -> `AuthController@logout`, logout, invalidate session, regenerate token (`AuthController.php:59-65`).

Route admin:

- Group `/admin` dung middleware `auth`, name prefix `admin.` (`routes/web.php:94-97`).
- Role admin/chuan quan tri `role:1` gom dashboard, san pham, nguoi dung, cong ty/nha cung cap, bao cao loi nhuan, danh muc, thuong hieu, khach hang, don hang, cau hinh, ho tro, thu/chi, report (`routes/web.php:102-280`).
- Role kho `role:4` gom kiem kho, bao cao ton kho, nhap hang, kho hang (`routes/web.php:287-324`).
- Role ke toan `role:3` gom cong no, so cai, tai khoan ke toan, giao dich tien mat/ngan hang (`routes/web.php:328-385`).

Route san pham:

- `GET /admin/products` -> `Admin\ProductController@index`, tra danh sach san pham; neu Ajax tra HTML partial trong JSON (`ProductController.php:20-39`).
- `GET /admin/products/create` -> form tao san pham (`ProductController.php:42-49`).
- `POST /admin/products` -> tao san pham, upload `thumbnail`, gan `user_id`, sinh ma `SP...` (`ProductController.php:51-67`).
- `GET /admin/products/{id}/edit`, `PUT /admin/products/{id}` -> sua san pham, thay thumbnail thi xoa anh cu (`ProductController.php:70-101`).
- `POST /admin/products/import` duoc khai route nhung method `import()` dang rong (`ProductController.php:104`).
- `GET /admin/products/export` xuat Excel bang `PhpSpreadsheet` (`ProductController.php:106+`).

Route kho hang:

- `admin/checkInventory/*` -> `Admin\CheckInventoryController`, xem va loc phieu kiem kho.
- `admin/inventory` -> `Admin\ReportController@index`, bao cao xuat nhap ton; `admin/inventory/report` tra JSON theo kho; `admin/inventory/exportPdf` xuat PDF (`routes/web.php:293-297`, `ReportController.php:35-84`).
- `admin/importproduct/*` -> `Admin\ImportProductController` va `importCouponController`, them gio nhap, cap nhat so luong/gia, tao phieu nhap (`routes/web.php:299-312`).
- `admin/storage/*` -> `Admin\StorageController`, CRUD kho va xem san pham trong kho (`routes/web.php:314-323`).

Route don hang:

- Admin: `GET /admin/order` va `GET /admin/order/{id}` -> `Admin\OrderController@index/show` (`routes/web.php:227-230`).
- Staff: `GET /ban-hang/order` -> lich su mua hang; Ajax tra partial table (`Staff\OrderController.php:21-60`).
- Staff: `POST /ban-hang/order` -> tao don, tao chi tiet don, giam ton kho, tao giao dich ke toan (`Staff\OrderController.php:89-289`).

Route khach hang:

- Admin: `/admin/client`, `/admin/client/detail/{id}`, `/admin/client/update/{id}`, `/admin/client/delete/{id}`, `/admin/client/filter`, `/admin/client/export` -> `Admin\ClientController` (`routes/web.php:207-215`).
- Staff: `POST /ban-hang/clients/add` -> tao khach hang bang Ajax (`Staff\ClientController.php:58-91`).
- Staff: `GET /ban-hang/get-clients` -> tim khach hang theo ten/SDT, tra JSON (`Staff\ProductController.php:87-101`).

Route ban hang `/ban-hang`:

- `GET /ban-hang` -> `Staff\ProductController@index`, load man hinh ban hang, xoa gio hang cu cua user khi vao trang (`Staff\ProductController.php:30-49`).
- `GET /ban-hang/product` -> tra JSON danh sach san pham theo `user_id/manager_id`, dung cho autocomplete (`Staff\ProductController.php:59-85`).
- `GET /ban-hang/product/search` -> tim trong `product_storage` theo kho cua user, tra JSON san pham con ton (`Staff\ProductController.php:241-271`).
- `POST /ban-hang/cart/add/update/remove/update_price` -> thao tac gio hang server-side cu (`Staff\ProductController.php:104-238`, `274-308`).
- View hien tai quan ly gio hang client-side va gui don qua `/ban-hang/order` (`resources/views/Themes/pages/layout_staff/index.blade.php:849-989`).

Route bao cao, thong ke, ke toan:

- Dashboard theo ngay/thang/nam: `admin/dashboard/day`, `admin/dashboard/month`, `admin/dashboard/year`.
- Loi nhuan: `admin/profit`, `admin/profit/profit-report`, `admin/profit/profit-report-all`, `admin/profit/profit-report-pdf`.
- Bao cao cong no: `admin/report/debt`, `admin/report/debt/print`.
- Bao cao don/nhap hang theo ngay: `admin/report/orders`, `admin/report/orders/get-daily-order-data`, `admin/report/imports`, `admin/report/imports/get-daily-import-data`.
- Ke toan: `admin/accounts`, `admin/journal-entries`, `admin/transactions/cash`, `admin/transactions/bank`.

Route super-admin:

- `GET/POST /super-dang-nhap` -> `SuperAdminController@loginForm/login`.
- `/super-admin/message/*` -> quota/template/message.
- `/super-admin/store/*` -> quan ly store.

## 4. Chuc nang Admin

Dashboard:

`Admin\DashboardController@index` lay khoang ngay mac dinh tu dau thang den cuoi thang, tinh cac nhom chi so: doanh thu hom nay/hom qua, so don hom nay/hom qua, tong doanh thu va bien loi nhuan gop, ton kho, gia tri don hang trung binh, top san pham ban chay, san pham sap het hang, don moi nhat va khach moi (`DashboardController.php:23-47`). View duoc tra la `welcome.blade.php`, khong phai `admin.dashboard`.

Quan ly san pham:

Admin co CRUD san pham, upload thumbnail, loc Ajax, xuat Excel. Form dung `ProductRequest` de validate ten, gia ban, gia nhap, don vi, so luong, danh muc, thuong hieu, status, thumbnail (`app/Http/Requests/Product/ProductRequest.php:22-37`). Ngoai san pham, menu admin co danh muc (`CategorieController`), thuong hieu (`BrandController`) va nha cung cap/cong ty (`CompanyController`, `SupplierController`).

Quan ly kho:

Module kho gom danh sach kho `storages`, bang trung gian `product_storage`, nhap hang `import_coupon/import_detail`, kiem kho `check_inventory/check_detail` va bao cao xuat nhap ton. `ProductStorageService@updateProductStorage` cong so luong vao `product_storage`; `updateProductAmount` tru so luong khi ban (`app/Services/ProductStorageService.php:30-75`). Bao cao ton kho tinh theo lan nhap gan nhat, so luong da ban sau lan nhap va gia tri hien tai (`ProductStorageService.php:77-159`).

Quan ly don hang:

Admin xem danh sach va chi tiet don qua `Admin\OrderController`. Staff tao don qua `Staff\OrderController@store`, controller validate gio hang, tinh lai subtotal/discount/grand o server, tao `orders`, tao `order_details`, tru `products.quantity`, va sinh giao dich ke toan theo phuong thuc thanh toan (`Staff\OrderController.php:91-282`).

Quan ly khach hang, nha cung cap, nhan vien, tai khoan:

- Khach hang: `Admin\ClientController@index` loc theo ten/email/phone va render partial table neu Ajax (`ClientController.php:33-61`), co sua, xoa, export Excel.
- Nha cung cap: `SupplierController` quan ly nguoi dai dien theo `company_id`, tim theo phone, them/sua/xoa (`SupplierController.php:24-150`).
- Nhan vien/tai khoan: `EmployeeController`, `UserController`, `AccountController` phuc vu nhan vien va tai khoan ke toan.
- Thu chi/cong no: `ReceiptController`, `ExpenseController`, `DebtController`, `CashTransactionController`, `BankTransactionController`.

Bao cao/thong ke:

Bao cao ton kho dung `ReportController@index/getReportByStorage`; bao cao loi nhuan dung `profitIndex`, `getProfitReportByFilterNew`, `getProfitReportByFilterPDF`; bao cao ngay ve don/nhap dung `DailyReportController`. Muc dich la giup admin/ke toan theo doi doanh thu, loi nhuan, ton kho, san pham ban chay va cong no.

## 5. Phan tich trang ban hang `/ban-hang`

Trang `/ban-hang` dung cho nhan vien ban hang tai quay. Controller `Staff\ProductController@index` lay `Config` kem bank, nhom khach hang, gio hang cua user, sau do xoa cart cu va render `Themes.pages.layout_staff.index` (`Staff\ProductController.php:30-49`).

Luong ban hang:

1. Nhan vien vao `/ban-hang`.
2. Tim san pham qua o tim kiem. View goi Ajax `GET /ban-hang/product` voi `searchText` (`resources/views/Themes/pages/layout_staff/index.blade.php:849-867`).
3. Chon san pham de them vao gio hang JavaScript client-side. Gio hang tinh so luong, tam tinh, giam gia va tong thanh toan tren browser.
4. Tim khach hang qua `GET /ban-hang/get-clients` (`layout_staff/index.blade.php:869-888`).
5. Neu chua co khach, modal them khach goi `POST /ban-hang/clients/add` (`layout_staff/index.blade.php:890-927`).
6. Khi thanh toan, view gui payload `items`, `subtotal`, `discountType`, `discountInput`, `grand`, `customer` den `POST /ban-hang/order` (`layout_staff/index.blade.php:929-989`).
7. Server validate va tinh lai tien de tranh sua gia tren client; neu khop thi tao don (`Staff\OrderController.php:91-176`).

Tai sao `/ban-hang/product` tra JSON:

Day la endpoint noi bo phuc vu giao dien dong/Ajax, khong phai trang HTML. View can danh sach san pham theo chuoi tim kiem de render popup ket qua ngay tren man hinh ban hang. Vi vay controller `product()` tra `response()->json($products)` (`Staff\ProductController.php:59-85`), con giao dien HTML van nam trong `Themes.pages.layout_staff.index`.

Vai tro Ajax/API noi bo:

- Tim san pham va khach hang khong reload trang.
- Them khach hang trong modal, nhan JSON tra ve de dien vao form don.
- Tao don hang va hien toast thanh cong/thong bao loi.
- Lich su don `/ban-hang/order` cung dung Ajax de tai partial table theo ngay/tim kiem (`resources/views/Themes/pages/order/index.blade.php:119-138`).

## 6. Phan tich co so du lieu

Bang chinh theo migration/model:

- `users`: tai khoan nguoi dung noi bo, co `role_id`, `storage_id`, `manager_id`, `status`, `wallet`, thong tin dia chi. Model `User` co quan he `role`, `storage`, `transaction` (`app/Models/User.php:18-99`).
- `roles`, `role_permission`: vai tro va quyen, dung middleware `role`.
- `products`: san pham, gia ban/gia nhap, don vi, so luong, danh muc, thuong hieu, thumbnail/code/status. Model `Product` co `category`, `brand`, `storages`, `productImages` (`app/Models/Product.php:14-76`).
- `categories`: danh muc san pham, model `Categories`.
- `brands`: thuong hieu, logo, mo ta, status, user.
- `clients`: khach hang, code sinh tu model, thong tin lien he (`app/Models/Client.php:11-30`).
- `suppliers`, `companies`: nha cung cap/cong ty va nguoi dai dien.
- `orders`: don hang, lien ket `user_id`, `client_id`, tong tien, status, thong tin khach, payment.
- `order_details`: chi tiet don hang, lien ket `order_id`, `product_id`, so luong/gia/san pham tai thoi diem ban.
- `storages`: kho hang; `product_storage`: ton kho theo san pham va kho, unique `product_id + storage_id` (`database/migrations/2024_07_29_095318_create_product_storage_table.php:14-20`).
- `import_coupon`, `import_detail`, `import`: phieu nhap, chi tiet nhap va gio nhap tam.
- `check_inventory`, `check_detail`, `warehouse`: kiem kho va bang tam kiem kho.
- `transactions`, `transaction_entries`, `accounts`: giao dich ke toan va but toan no/co.
- `receipts`, `expense`, `customer_debts`, `supplier_debts`: thu chi va cong no.
- `config`, `banks`: cau hinh cua hang, logo, tai khoan ngan hang.

Quan he du lieu quan trong:

- San pham - danh muc: `products.category_id` foreign key den `categories.id` (`database/migrations/2024_06_27_092924_create_products_table.php:24-25`); model `Product::category()` belongsTo `Categories` (`Product.php:56-59`).
- San pham - thuong hieu: migration them `products.brands_id` den `brands.id` (`2024_06_27_143608_add_brands_id_to_products_table.php:16-19`), nhung model fillable lai dung `brand_id` va relation `brand()` mac dinh khoa `brand_id` (`Product.php:14-18`, `48-50`). Day la diem bat nhat can sua.
- Don hang - chi tiet don: `Order::orderDetails()` hasMany `OrderDetail` (`Order.php:44-47`), `OrderDetail::order()` belongsTo `Order` (`OrderDetail.php:26-29`).
- Khach hang - don hang: `Order::client()` belongsTo `Client` (`Order.php:54-57`), migration them `orders.client_id` va FK set null khi xoa client (`2024_07_01_154616_add_client_id_to_orders_table.php:14-18`).
- Kho - san pham: `Product::storages()` va `Storage::products()` belongsToMany qua `product_storage`, pivot co `quantity` (`Product.php:71-76`, `Storage.php:18-23`).
- Giao dich - but toan: `Transaction::entries()` hasMany `TransactionEntry` (`Transaction.php:21-24`); `Staff\OrderController@store` tao `transactions` va `transaction_entries` theo thanh toan tien mat/chuyen khoan/cong no (`Staff\OrderController.php:212-275`).

## 7. Phan tich Model

Model san pham:

- `Product` dai dien bang `products`.
- Fillable: `user_id`, `category_id`, `brand_id`, `code`, `name`, `price`, `price_buy`, `thumbnail`, `product_unit`, `quantity`, `description`, `is_featured`, `status` (`Product.php:14-28`).
- Cast: `is_featured`, `status` ve boolean (`Product.php:30-33`).
- Quan he: `brand`, `carts`, `category`, `company`, `productImages`, `storages`.
- Diem can chu y: accessor `getBrandsAttribute()` doc `$this->attributes['brands_id']` trong khi fillable/relation dung `brand_id` (`Product.php:43-50`).

Model don hang:

- `Order` dai dien `orders`, fillable gom khach hang, tong tien, giam gia, phuong thuc thanh toan, status, note, `created_by` (`Order.php:16-32`).
- `Order::orderDetails()` hasMany `OrderDetail`, `Order::user()` belongsTo `User`, `Order::client()` belongsTo `Client`, `Order::creator()` belongsTo `User` qua `created_by` (`Order.php:44-62`).
- `OrderDetail` dai dien `order_details`, fillable `order_id`, `storage_id`, `product_id`, `p_name`, `p_price`, `p_quantity` (`OrderDetail.php:11-12`).

Model khach hang:

- `Client` dai dien `clients`, fillable `user_id`, `name`, `phone`, `zip_code`, `address`, `dob`, `email`, `gender`.
- Khi tao moi, model gan `code = generateCode('clients', 'KH')` (`Client.php:23-29`).

Model kho:

- `Storage` dai dien `storages`, fillable `user_id`, `name`, `location`; quan he many-to-many voi `Product` qua `product_storage` (`Storage.php:12-23`).
- `ProductStorage` dai dien `product_storage`, fillable `product_id`, `storage_id`, `quantity`, belongsTo `Product` va `Storage` (`ProductStorage.php:12-28`).

Model nhap hang/kiem kho:

- `ImportCoupon` dai dien `import_coupon`, tu sinh `coupon_code` dang `MP000001`, hasMany `ImportDetail` (`ImportCoupon.php:11-70`).
- `ImportDetail` dai dien `import_detail`, belongsTo `Product`, `ImportCoupon`, `Supplier` (`ImportDetail.php:12-35`).
- `CheckInventory` dai dien `check_inventory`, sinh `test_code` dang `KH000001`, hasMany `CheckDetail`, belongsTo `User` (`CheckInventory.php:12-37`).

Model ke toan:

- `Transaction` fillable `user_id`, `transaction_date`, `description`, `reference_number`, `type`, `document_type`, `attachment`, `created_by`; hasMany `TransactionEntry`; cast `transaction_date` thanh date (`Transaction.php:10-56`).
- `Account` co quan he cha-con va creator theo ket qua `rg` trong `app/Models/Account.php`.

## 8. Phan tich Controller

`AuthController`:

- `login()` tra view `auth.login`.
- `authenticate()` dung `LoginRequest`, `Auth::attempt`, kiem tra status inactive/locked, tra JSON thanh cong/lá»—i va redirect theo role (`AuthController.php:17-55`).
- `logout()` xoa session va quay ve login (`AuthController.php:59-65`).

`DashboardController`:

- Tinh doanh thu, don hang, tong doanh thu, loi nhuan gop, ton kho, AOV, top san pham, don moi va khach moi (`DashboardController.php:23-47`).
- Dung query builder/DB raw truc tiep tren `orders`, `order_details`, `products`, `clients`.
- Tra view `welcome`, la dashboard admin thuc te.

`ProductController`:

- Xu ly giao dien admin san pham: index/create/edit.
- Xu ly Ajax danh sach: khi request Ajax, render `admin.product.table` va tra JSON (`ProductController.php:20-37`).
- Xu ly upload anh thumbnail bang `uploadImages('thumbnail', 'products')` (`ProductController.php:57-59`, `88-90`).
- `import()` dang rong, trong khi route `admin.products.import` da ton tai (`ProductController.php:104`).

`OrderController`:

- `Admin\OrderController` phuc vu danh sach/chi tiet don admin.
- `Staff\OrderController@index` phuc vu lich su mua hang, neu Ajax tra HTML partial (`Staff\OrderController.php:21-60`).
- `Staff\OrderController@store` la luong tao don moi: validate payload, tinh lai tien, tao order, tao order detail, tru ton kho, tao giao dich ke toan (`Staff\OrderController.php:89-289`).

`ClientController`:

- Admin: tim/liet ke/sua/xoa/export khach hang (`Admin\ClientController.php:33-141`).
- Staff: `addClient()` validate va tao khach hang trong popup ban hang, tra JSON (`Staff\ClientController.php:58-91`).

`StorageController` va `ProductStorageService`:

- `StorageController` quan ly kho va danh sach san pham trong kho.
- `ProductStorageService` chua logic cap nhat ton kho va bao cao ton (`ProductStorageService.php:30-159`).

`ReportController`:

- `index()` lay kho dau tien, goi `inventoryReport`, tra view `admin.inventory.index` (`ReportController.php:35-58`).
- `getReportByStorage()` tra JSON bao cao ton theo kho (`ReportController.php:60-84`).
- `profitIndex()` va cac ham `getProfitReport...` tra JSON/PDF bao cao loi nhuan (`ReportController.php:154-428`).

`TransactionController`, `CashTransactionController`, `BankTransactionController`, `DebtController`:

- Xu ly giao dich, but toan va cong no. `DebtController@customer/supplier` tinh so du dau ky, phat sinh no/co, so du cuoi ky bang `transaction_entries` va `transactions` (`DebtController.php:19-178`).

Controller Staff va SuperAdmin:

- `Staff\ProductController` tra view ban hang, JSON san pham/khach hang, thao tac cart cu.
- `Staff\OrderController` tao don hang va giao dich.
- `SuperAdmin\SuperAdminController` dang nhap bang service rieng, luu session `authSuper`, logout flush session (`SuperAdminController.php:61-83`).

Controller xu ly giao dien vs JSON/Ajax:

- Giao dien HTML: `DashboardController@index`, `ProductController@index/create/edit`, `ReportController@index/profitIndex`, `Staff\ProductController@index`, `Staff\OrderController@index`.
- JSON/Ajax: `ProductController@index` khi Ajax, `Staff\ProductController@product/getClients/search`, `Staff\OrderController@store/orderFetch`, `ReportController@getReportByStorage/getProfitReport...`, `ClientController@index` khi Ajax.

## 9. Phan tich giao dien

Layout admin:

- Layout chinh `resources/views/admin/layout/index.blade.php` gom meta CSRF, include style, sidebar, header, `@yield('content')`, footer, script (`admin/layout/index.blade.php:1-57`).
- Header co nut sang trang ban hang, notification don hang, avatar user, profile va logout form co CSRF (`admin/layout/header.blade.php:50-155`).
- Sidebar co logo cau hinh, menu tong quan, san pham, kho hang, don hang, khach hang, bao cao, ke toan... Menu active theo `request()->routeIs` (`admin/layout/sidebar.blade.php:1+`).
- Dashboard `welcome.blade.php` hien card metric, top selling, latest orders, low stock, va Ajax doi thong ke ngay/thang/nam (`welcome.blade.php:388-464`).

Giao dien ban hang:

- Layout staff `Themes/layout_staff/app.blade.php` include header, `@yield('content')`, footer.
- Header staff co logo, menu kiem kho, lich su mua hang, logout (`Themes/layout_staff/header.blade.php:177-214`).
- Footer staff chua modal them khach hang va validate client-side (`Themes/layout_staff/footer.blade.php:25-168`).
- Man hinh ban hang `Themes/pages/layout_staff/index.blade.php` co tim san pham, gio hang, thong tin khach, phuong thuc thanh toan, tao QR VietQR va gui order Ajax (`layout_staff/index.blade.php:849-989`).

To chuc Blade:

- Admin: `resources/views/admin/<module>/index|form|table|detail.blade.php`.
- Staff: `resources/views/Themes/pages/layout_staff/index.blade.php`, `Themes/pages/order/*`, `Themes/pages/Inventory/*`.
- Super admin: `resources/views/superadmin/*`.
- Co cac thu muc `resources/views/emails/admin/*` va `resources/views/Themes/admin/*` co ve la ban copy/legacy cua admin view.

Uu diem giao dien:

- Tach layout/header/sidebar/footer kha ro.
- Nhieu danh sach dung Ajax partial de tim kiem khong reload toan trang.
- Dashboard co cac chi so phu hop nghiep vu ban hang.
- Man ban hang giau tinh tuong tac, co modal them khach va QR thanh toan.

Han che giao dien:

- Co nhieu view trung lap giua `admin`, `Themes/admin`, `emails/admin`, de gay nham lan khi bao tri.
- Mot so route trong view legacy khong khop route hien tai, vi du `admin.brand.addForm`, `admin.storage.findByName`, `admin.staff.edit` xuat hien trong view legacy/old.
- Chu tieng Viet trong mot so file/terminal hien thi mojibake khi encoding khong dung; can dam bao UTF-8.
- Staff layout nhung CSS/JS inline rat dai, kho tai su dung va kho test.

## 10. Bao mat va phan quyen

Dang nhap:

- Login dung Laravel Auth qua `Auth::attempt`.
- `LoginRequest` validate email bat buoc, email ton tai trong `users`, password required (`LoginRequest.php:22-27`).
- Sau login, user inactive/locked bi logout va tra loi (`AuthController.php:33-41`).

Middleware:

- `auth` alias tro den `Authenticate`, user chua dang nhap redirect `auth.login` (`app/Http/Middleware/Authenticate.php:13-16`).
- `role` tro den `RoleMiddleware` trong `Kernel.php:69-73`.
- `RoleMiddleware` cho role 1/2 di tat ca route co middleware role; role khac phai nam trong danh sach cho phep (`RoleMiddleware.php:16-35`).
- `/ban-hang` dung them `CheckLogin`, chi cho role 1,2,3 (`CheckLogin.php:21-32`).
- Super admin dung session rieng `authSuper` va `CheckLoginSuperAdmin` (`CheckLoginSuperAdmin.php:16-22`).

CSRF va validate:

- Group `web` co `VerifyCsrfToken` (`Kernel.php:31-39`).
- Form Blade co `@csrf`, vi du login (`resources/views/auth/login.blade.php:318-319`) va logout admin (`admin/layout/header.blade.php:152-155`).
- Ajax thuong gui token qua header/meta hoac data `_token`, vi du staff ban hang (`layout_staff/index.blade.php:856-858`, `968-970`).
- Cac request quan trong co validate: `LoginRequest`, `ProductRequest`, `Staff\OrderController@store`, `Staff\ClientController@addClient`, `DebtController@store`.

Diem tot:

- Co session auth, CSRF, middleware phan role.
- Server tinh lai tong tien don hang, khong tin hoan toan vao client (`Staff\OrderController.php:124-176`).
- Co transaction DB khi tao don va ghi but toan (`Staff\OrderController.php:182-289`).

Diem can cai thien:

- Phan quyen dang dua vao so role hard-code `1`, `2`, `3`, `4`; nen chuan hoa bang enum/constant hoac permission.
- `CheckLoginSuperAdmin` chi kiem tra session `authSuper`, tach khoi guard Laravel; nen can than fixation/session hardening.
- Mot so endpoint GET co tac dong xoa/thay doi trong legacy, vi du `ban-hang/warehome/delete` la GET; nen doi sang DELETE/POST.
- Upload update san pham neu co file moi nhung rule update `thumbnail` chi la `nullable`, chua ep `image|mimes|max` khi update (`ProductRequest.php:36`).

## 11. Xu ly anh/file

Anh/logo/san pham:

- Helper `uploadImages()` luu file vao `storage/app/public/<directory>` qua `Storage::disk('public')->put`, encode WebP bang Intervention Image (`helper.php:123-162`).
- `showImage()` doc tu disk public, neu khong co thi fallback `assets/img/default-image.jpg` (`helper.php:10-21`).
- Product thumbnail luu trong `products` folder (`ProductController.php:57-59`).
- Brand logo luu trong `brands` folder (`BrandController` co goi `uploadImages('logo', 'brands')` theo ket qua `rg`).
- Config logo luu trong `logo` folder (`ConfigController` goi `uploadImages('logo', 'logo')`).
- Avatar admin luu trong `avatar` folder (`AdminController` goi `uploadImages('img_url', 'avatar')`).

Import/export:

- Product export dung `PhpSpreadsheet` (`Admin\ProductController@export`).
- Client export dung `Maatwebsite\Excel` (`Admin\ClientController@export`).
- Bao cao loi nhuan/ton kho co PDF qua `barryvdh/laravel-dompdf`.
- Nhap hang admin dung Ajax tren bang `import` tam, sau do tao `import_coupon` va `import_detail`.

Luu y khi clone:

- Can chay `php artisan storage:link`; neu khong, anh trong `storage/app/public` se khong hien thi.
- Can migrate/seed dung thu tu; migration hien co loi co the lam fresh migrate fail.
- Neu thieu file public asset hoac storage link, logo/san pham se fallback default hoac 404.
- Neu route cache cu, can `php artisan route:clear`.

## 12. Uu diem cua he thong

He thong co do phu nghiep vu rong, dap ung nhieu chuc nang quan ly ban hang thuc te: san pham, danh muc, thuong hieu, nha cung cap, khach hang, kho, nhap hang, ban hang tai quay, don hang, thu chi, cong no, bao cao ton kho va bao cao loi nhuan. Viec chia controller theo `Admin`, `Staff`, `SuperAdmin` giup nhan dien nhanh pham vi chuc nang.

He thong co su dung Eloquent model, service layer, FormRequest va middleware phan quyen. Mot so nghiep vu quan trong da co kiem tra server-side, dac biet la tao don hang: server validate san pham, so luong, tong tien va giam gia truoc khi ghi database.

Giao dien admin co layout rieng, sidebar nhieu module, dashboard co so lieu tong quan. Giao dien ban hang co trai nghiem nhanh, nhieu thao tac Ajax, phu hop quy trinh ban tai quay.


## 13. Han che va bat hop ly trong code

1. Migration tao bang `users` hai lan.
   - File: `database/migrations/2024_06_27_090928_create_users_table.php:14` va `database/migrations/2024_06_27_091638_create_users_table.php:14`.
   - Van de: fresh migrate co the fail vi bang `users` da ton tai.
   - Goi y: xoa/gop migration rong dau tien hoac doi thanh `Schema::table` neu can.

2. Migration `users` tham chieu bang `commissions` nhung repo khong thay migration tao bang nay.
   - File: `database/migrations/2024_06_27_091638_create_users_table.php:23-24`.
   - Van de: migrate moi co the fail foreign key.
   - Goi y: tao migration `commissions` truoc, hoac bo FK neu chuc nang khong dung.

3. Thuong hieu san pham khong thong nhat `brand_id` va `brands_id`.
   - File: `Product.php:14-18` dung `brand_id`; `Product.php:43-45` doc `brands_id`; migration tao `brands_id` (`2024_06_27_143608_add_brands_id_to_products_table.php:16-19`); `ProductService` co ghi `brands_id`.
   - Van de: form moi co the gui `brand_id` nhung database co `brands_id`; relation `brand()` mac dinh `brand_id` co the khong lay dung.
   - Goi y: chon mot ten cot, nen dung `brand_id`; tao migration doi cot, sua fillable/relation/service/view dong bo.

4. Migration `company_product` sai chinh ta method.
   - File: `database/migrations/2024_09_05_104610_create_company_product_table.php:19`.
   - Van de: `onDelte('cascade')` sai, dung la `onDelete('cascade')`.
   - Goi y: sua migration truoc khi migrate moi.

5. Controller tao don dung cot khong thay migration tao.
   - File: `Staff\OrderController.php:185-208` ghi `discount_value`, `discount_type`, `payment_method`, `created_by`, `p_name`, `p_price`, `p_quantity`.
   - Van de: `rg` trong `database/migrations` khong thay migration tao cac cot nay. Neu DB moi chi tu migration, tao don co nguy co loi SQL.
   - Goi y: them migration bo sung cac cot dang duoc model/controller su dung, hoac sua controller ve cac cot that su ton tai.

6. Ban hang moi chi tru `products.quantity`, chua tru `product_storage`.
   - File: `Staff\OrderController.php:202-211`.
   - Van de: du an co bang ton kho theo kho `product_storage`, nhung luong `/ban-hang/order` khong ghi `storage_id` vao order detail va khong goi `ProductStorageService@updateProductAmount`. Bao cao ton kho theo kho co the sai.
   - Goi y: lay `Auth::user()->storage_id`, validate ton trong `product_storage`, ghi `storage_id`, tru pivot `product_storage.quantity` trong cung DB transaction.

7. Endpoint tim san pham ban hang khong thong nhat.
   - File: `Staff\ProductController@product` tra san pham theo `user_id` (`Staff\ProductController.php:75-84`); `search()` lai tra san pham theo kho va so luong > 0 (`Staff\ProductController.php:241-271`).
   - Van de: view hien tai goi `/ban-hang/product`, co the hien san pham khong co trong kho nhan vien.
   - Goi y: hop nhat endpoint tim san pham theo `product_storage` va `storage_id`.

8. `Admin\ProductController@import()` dang rong.
   - File: `app/Http/Controllers/Admin/ProductController.php:104`.
   - Van de: route `POST admin/products/import` co ton tai nhung khong xu ly.
   - Goi y: neu can import Excel san pham, implement hoac an nut/route de tranh gay nham.

9. Helper `formatPrice` co doan khai bao trung/chet.
   - File: `app/Helpers/helper.php:31-47` va `app/Helpers/helper.php:166-172`.
   - Van de: function dau tien da ton tai nen block `if (!function_exists('formatPrice'))` phia duoi khong bao gio chay; code gay nham.
   - Goi y: chi giu mot ham `formatPrice` dung logic.

10. View trung lap/legacy nhieu.
    - File/thu muc: `resources/views/admin`, `resources/views/Themes/admin`, `resources/views/emails/admin`.
    - Van de: de sua nham view, mot so route trong view legacy khong ton tai theo `route:list`.
    - Goi y: xac dinh view dang dung, xoa/doi ten view legacy hoac dua vao thu muc archive.

## 14. De xuat cai thien

- Them trang chu/landing noi bo ro rang hoac trang chon vai tro thay vi redirect thang `/login`; neu van la he thong noi bo, co the giu redirect nhung can ghi ro trong tai lieu.
- Chuan hoa schema: sua migration trung `users`, them bang/cot thieu, sua `onDelte`, thong nhat `brand_id`.
- Chuan hoa ton kho: moi nghiep vu ban/nhap/kiem kho phai cap nhat `product_storage` theo kho, dong thoi neu van can `products.quantity` thi quy dinh no la tong ton hay fallback.
- Hoan thien import san pham hoac xoa route/nut import chua dung.
- Tach JS/CSS inline trong Blade sang file rieng de de bao tri.
- Gop/cat bo view legacy, giu mot source of truth cho admin.
- Nang cap phan quyen tu role id hard-code sang constant/enum/permission, vi du `Role::ADMIN`, `Role::STAFF`, `Role::ACCOUNTANT`, `Role::WAREHOUSE`.
- Them validate cho upload update: neu co file moi van phai la image, mimes hop le, max size.
- Them test cho luong tao don: validate tong tien, tru ton kho theo kho, tao transaction, tao order detail.
- Viet `README` cai dat du an: copy `.env`, composer install, npm install/build, migrate/seed, storage link, queue worker, tai khoan demo.

## 15. Ket luan

Du an la mot he thong Laravel quan ly ban hang va kho hang co pham vi nghiep vu kha day du, phu hop de dua vao bao cao thuc tap/do an. He thong da dap ung cac chuc nang cot loi nhu quan ly san pham, khach hang, nha cung cap, kho, nhap hang, ban hang tai quay, don hang, cong no va bao cao co ban. Diem manh nam o viec co phan quyen theo vai tro, co giao dien admin/staff rieng, co xu ly Ajax cho cac tac vu nhanh va co cac module bao cao/ke toan phuc vu quan tri.

Tuy nhien, de san sang van hanh va de bao cao co tinh thuyet phuc hon, can neu ro cac han che ky thuat hien tai: migration chua dong bo, ten cot thuong hieu chua thong nhat, luong ban hang chua cap nhat ton kho theo kho, va mot so route/chuc nang import con dang dang do. Neu khac phuc cac diem nay, he thong se co tinh on dinh cao hon, de cai dat tren moi moi truong moi va phu hop hon voi nghiep vu quan ly ban hang/kho hang/cong no/thong ke trong thuc te.

---

# Ban tom tat ngan de dua vao bao cao


Ve cau truc, du an tuan theo mo hinh MVC cua Laravel. File route chinh la `routes/web.php`, trong do cac route admin duoc bao ve bang middleware `auth` va `role`, route ban hang dung `CheckLogin` va `role:3`, route super-admin dung session rieng `authSuper`. Controller duoc chia theo namespace `Admin`, `Staff`, `SuperAdmin`; model nam trong `app/Models`; view nam trong `resources/views/admin`, `resources/views/Themes` va `resources/views/superadmin`. Du an co them service layer trong `app/Services` de tach mot phan nghiep vu nhu san pham, kho, khach hang, bao cao.

Chuc nang admin gom dashboard thong ke doanh thu, don hang, ton kho, top san pham ban chay, san pham sap het hang va don moi. Module san pham cho phep them, sua, upload anh, xuat Excel; module kho gom kho hang, nhap hang, kiem kho va bao cao xuat nhap ton; module don hang cho phep xem lich su va chi tiet don; module khach hang/nha cung cap/nhan vien ho tro quan ly thong tin doi tac va nguoi dung noi bo. Phan ke toan co quan ly tai khoan, giao dich tien mat/ngan hang, thu chi va cong no khach hang/nha cung cap.

Trang ban hang `/ban-hang` la man hinh thao tac nhanh cho nhan vien. Giao dien dung Ajax de tim san pham qua `/ban-hang/product`, tim khach hang qua `/ban-hang/get-clients`, them khach hang qua `/ban-hang/clients/add`, sau do gui don hang den `/ban-hang/order`. Controller tao don validate lai du lieu tren server, tinh lai tong tien, giam gia, tao `orders`, `order_details`, tru ton kho san pham va tao giao dich ke toan theo phuong thuc thanh toan.

Co so du lieu gom cac bang quan trong: `users`, `roles`, `products`, `categories`, `brands`, `clients`, `suppliers`, `companies`, `orders`, `order_details`, `storages`, `product_storage`, `import_coupon`, `import_detail`, `check_inventory`, `transactions`, `transaction_entries`, `accounts`, `receipts`, `expense`, `customer_debts`, `supplier_debts`. Quan he chinh gom san pham thuoc danh muc/thuong hieu, don hang co nhieu chi tiet don, khach hang co nhieu don, san pham thuoc nhieu kho qua `product_storage`, va giao dich co nhieu but toan.

Ve bao mat, he thong co dang nhap bang Laravel Auth, CSRF trong web middleware, validate bang FormRequest/Validator va phan quyen bang middleware role. Diem tot la server khong tin hoan toan vao tong tien tu client khi tao don, ma tinh lai truoc khi ghi database. Tuy nhien, phan quyen dang hard-code theo `role_id`, super-admin dung session rieng, va mot so endpoint legacy nen duoc chuan hoa lai ve method POST/PUT/DELETE phu hop.

Uu diem cua he thong la do phu chuc nang rong, co tach admin/staff/super-admin, co dashboard, co Ajax giup thao tac nhanh, co upload anh, export Excel/PDF va co module ke toan/cong no. Han che lon nhat la schema migration chua dong bo: co migration tao `users` hai lan, foreign key den `commissions` nhung khong thay migration bang nay, ten cot thuong hieu `brand_id/brands_id` khong thong nhat, va controller tao don dang su dung mot so cot chua thay migration tao. Ngoai ra, luong ban hang moi dang tru `products.quantity` nhung chua cap nhat `product_storage` theo kho, co the lam bao cao ton kho sai.

Huong cai thien de xuat la chuan hoa migration va ten cot, hoan thien luong ton kho theo kho, bo view/route legacy, hoan thien import san pham, tach JS/CSS khoi Blade, bo role hard-code, them validate upload khi update va viet tai lieu cai dat day du. Nhin chung, du an da dap ung duoc cac nghiep vu quan ly ban hang, kho hang, cong no va thong ke co ban, phu hop lam nen tang cho bao cao thuc tap/do an neu trinh bay kem cac han che va huong nang cap neu tren.
