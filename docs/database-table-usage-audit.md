# Database table usage audit

NgÃ y audit: 2026-07-16  
Project: Laravel 10.49.0  
Database kiá»ƒm tra trá»±c tiáº¿p: `ai_crm_2026`  
LÆ°u Ã½: khÃ´ng káº¿t luáº­n báº£ng Ä‘ang sá»­ dá»¥ng chá»‰ vÃ¬ cÃ³ migration. CÃ¡c káº¿t luáº­n dÆ°á»›i Ä‘Ã¢y dá»±a trÃªn source, route/view/ajax, model, FK, dá»¯ liá»‡u tháº­t vÃ  truy váº¥n LEFT JOIN.

## TÃ³m táº¯t theo báº£ng

| TÃªn báº£ng | CÃ³ dá»¯ liá»‡u | CÃ³ Model | ÄÆ°á»£c code nghiá»‡p vá»¥ sá»­ dá»¥ng | CÃ³ quan há»‡ FK | Chá»©c nÄƒng liÃªn quan | Káº¿t luáº­n | Má»©c Ä‘á»™ cháº¯c cháº¯n | Báº±ng chá»©ng |
|---|---:|---|---|---|---|---|---|---|
| `customers` | CÃ³, 3 dÃ²ng | CÃ³: `Customer`, khÃ´ng khai bÃ¡o `$table` | KhÃ´ng tháº¥y dÃ¹ng tháº­t; chá»‰ cÃ³ `use App\Models\Customer` khÃ´ng kÃ¨m `Customer::...` | KhÃ´ng cÃ³ FK inbound/outbound phÃ¡t hiá»‡n trong DB | KhÃ´ng tháº¥y route/UI dÃ¹ng báº£ng `customers`; cÃ¡c mÃ n "khÃ¡ch hÃ ng" Ä‘ang dÃ¹ng `clients` | Legacy nhÆ°ng cÃ²n dá»¯ liá»‡u | Cao | [app/Models/Customer.php](/d:/iphone/app/Models/Customer.php:8) `class Customer extends Model`; `rg Customer::` khÃ´ng cÃ³ káº¿t quáº£; [app/Http/Controllers/Admin/DebtController.php](/d:/iphone/app/Http/Controllers/Admin/DebtController.php:7), [CashTransactionController.php](/d:/iphone/app/Http/Controllers/Admin/CashTransactionController.php:11), [BankTransactionController.php](/d:/iphone/app/Http/Controllers/Admin/BankTransactionController.php:7) chá»‰ import model; SQL: `select count(*) from customers` = 3. |
| `clients` | CÃ³, 3 dÃ²ng | CÃ³: `Client`, cÃ³ `$table = 'clients'` | CÃ³, dÃ¹ng trong admin, bÃ¡n hÃ ng, Ä‘Æ¡n hÃ ng, cÃ´ng ná»£, bÃ¡o cÃ¡o, export | CÃ³ FK DB: `orders.client_id`, `customer_debts.client_id` tham chiáº¿u `clients.id`; `clients.user_id` tham chiáº¿u `users.id`; `receipts.client_id` lÃ  tham chiáº¿u má»m | Quáº£n lÃ½ khÃ¡ch hÃ ng, tÃ¬m khÃ¡ch táº¡i POS, thÃªm khÃ¡ch, Ä‘Æ¡n hÃ ng, cÃ´ng ná»£ | Äang sá»­ dá»¥ng | Ráº¥t cao | [app/Models/Client.php](/d:/iphone/app/Models/Client.php:11) `$table = 'clients'`; [routes/web.php](/d:/iphone/routes/web.php:207) prefix `admin.client`; [routes/web.php](/d:/iphone/routes/web.php:392) `get-clients`; [app/Http/Controllers/Admin/ClientController.php](/d:/iphone/app/Http/Controllers/Admin/ClientController.php:38) `Client::query()`; [app/Http/Controllers/Staff/ProductController.php](/d:/iphone/app/Http/Controllers/Staff/ProductController.php:94) `Client::query()`; [app/Models/Order.php](/d:/iphone/app/Models/Order.php:56) `belongsTo(Client::class)`; SQL: `clients_linked_orders` = 3, `receipts_client_missing` = 2. |
| `carts` | KhÃ´ng, 0 dÃ²ng | CÃ³: `Cart`, cÃ³ `$table = 'carts'` | CÃ³, dÃ¹ng trá»±c tiáº¿p á»Ÿ POS/bÃ¡n hÃ ng; dá»¯ liá»‡u cÃ³ tÃ­nh táº¡m theo user | CÃ³ FK DB: `carts.user_id -> users.id`, `carts.product_id -> products.id`; báº£ng `cart_detail` Ä‘Ã£ bá»‹ drop vÃ  hiá»‡n khÃ´ng tá»“n táº¡i | Giá» hÃ ng bÃ¡n hÃ ng, thÃªm/sá»­a/xÃ³a cart, thanh toÃ¡n rá»“i xÃ³a cart | Äang sá»­ dá»¥ng | Ráº¥t cao | [app/Models/Cart.php](/d:/iphone/app/Models/Cart.php:12) `$table = 'carts'`; [routes/web.php](/d:/iphone/routes/web.php:394) `staff.cart.add`, [routes/web.php](/d:/iphone/routes/web.php:397) `staff.cart.remove`; [app/Http/Controllers/Staff/ProductController.php](/d:/iphone/app/Http/Controllers/Staff/ProductController.php:116) `Cart::where(...)`; [app/Http/Controllers/Staff/ClientController.php](/d:/iphone/app/Http/Controllers/Staff/ClientController.php:245) `Cart::where(...)->delete()`; SQL: `select count(*) from carts` = 0, orphan cart = 0. |
| `user_info` | CÃ³, 2 dÃ²ng | CÃ³: `UserInfo`, cÃ³ `$table = 'user_info'` | CÃ³ giÃ¡n tiáº¿p qua accessor cá»§a `User` vÃ  view header/profile | Migration khai bÃ¡o FK `user_info.user_id -> users.id`; DB hiá»‡n cÃ³ 1 dÃ²ng orphan theo LEFT JOIN | Avatar/thÃ´ng tin phá»¥ user trong session header/profile | Äang sá»­ dá»¥ng giÃ¡n tiáº¿p | Cao | [app/Models/UserInfo.php](/d:/iphone/app/Models/UserInfo.php:11) `$table = 'user_info'`; [app/Models/User.php](/d:/iphone/app/Models/User.php:44) `getUserInfoAttribute()`, [app/Models/User.php](/d:/iphone/app/Models/User.php:46) `UserInfo::where('user_id', ...)`; [resources/views/admin/layout/header.blade.php](/d:/iphone/resources/views/admin/layout/header.blade.php:124) dÃ¹ng `session('authUser')->user_info->img_url`; SQL: `user_info_user_missing` = 1. |
| `role_permission` | KhÃ´ng, 0 dÃ²ng | CÃ³: `RolePermission`, cÃ³ `$table = 'role_permission'` | KhÃ´ng tháº¥y nghiá»‡p vá»¥ quyá»n dÃ¹ng báº£ng nÃ y; middleware dÃ¹ng `users.role_id` trá»±c tiáº¿p | Migration khai bÃ¡o `role_id -> roles.id`; DB cÃ³ FK nhÆ°ng khÃ´ng cÃ³ dá»¯ liá»‡u | Role middleware (`role:1`, `role:3`, `role:4`) khÃ´ng dÃ¹ng permissions | CÃ³ kháº£ nÄƒng thá»«a | Cao | [app/Models/RolePermission.php](/d:/iphone/app/Models/RolePermission.php:12) `$table = 'role_permission'`; [app/Models/Roles.php](/d:/iphone/app/Models/Roles.php:22) `hasMany(RolePermission::class)`; [routes/web.php](/d:/iphone/routes/web.php:102) `middleware(['role:1'])`; [app/Http/Middleware/RoleMiddleware.php](/d:/iphone/app/Http/Middleware/RoleMiddleware.php:24) kiá»ƒm tra `role_id`; khÃ´ng tháº¥y `Gate`, `Policy`, `can()` nghiá»‡p vá»¥; SQL: `select count(*) from role_permission` = 0. |
| `warehouse` | CÃ³, 1 dÃ²ng | CÃ³: `warehome`, cÃ³ `$table = 'warehouse'` | CÃ³, dÃ¹ng trá»±c tiáº¿p trong module kiá»ƒm kho táº¡m | Migration cÃ³ `product_id -> products.id`; `user_id` Ä‘Æ°á»£c thÃªm sau; DB cÃ³ 1 dÃ²ng `product_id` khÃ´ng khá»›p `products.id` | Staff kiá»ƒm kho: thÃªm sáº£n pháº©m kiá»ƒm, cáº­p nháº­t thá»±c táº¿, xÃ³a, submit phiáº¿u kiá»ƒm | Äang sá»­ dá»¥ng | Cao | [app/Models/warehome.php](/d:/iphone/app/Models/warehome.php:11) `$table = 'warehouse'`; [routes/web.php](/d:/iphone/routes/web.php:409) `staff.warehome.get`; [app/Http/Controllers/Staff/WareHomeController.php](/d:/iphone/app/Http/Controllers/Staff/WareHomeController.php:38) `warehome::where(...)`; [app/Http/Controllers/Staff/CheckInventoryController.php](/d:/iphone/app/Http/Controllers/Staff/CheckInventoryController.php:78) `warehome::truncate()`; SQL: `warehouse_product_missing` = 1. |
| `products_code` | CÃ³, 5 dÃ²ng | KhÃ´ng tháº¥y Model | KhÃ´ng tháº¥y source nghiá»‡p vá»¥ dÃ¹ng; khÃ´ng tháº¥y migration/Seeder/Factory trong repo | KhÃ´ng tháº¥y FK DB; cá»™t tháº­t lÃ  `id_product`, `masp`; 5/5 dÃ²ng khÃ´ng ná»‘i Ä‘Æ°á»£c sang `products.id` | KhÃ´ng tháº¥y route/UI/API; tÃ¬m thÃªm `product_code`, `barcode`, `sku`, `masp` khÃ´ng ra nghiá»‡p vá»¥ liÃªn quan | Legacy nhÆ°ng cÃ²n dá»¯ liá»‡u | Cao | `rg products_code/id_product/masp/product_code/barcode/sku` chá»‰ ra nhÃ£n barcode cá»§a order detail, khÃ´ng tháº¥y báº£ng; DB columns: `id`, `id_product`, `masp`; SQL: `select count(*) from products_code` = 5; `products_code_product_missing` = 5. |
| `company_product` | CÃ³, 1 dÃ²ng | CÃ³: `CompanyProduct`, cÃ³ `$table = 'company_product'` | CÃ³, Ä‘Æ°á»£c cáº­p nháº­t khi lÆ°u phiáº¿u nháº­p; Ä‘Æ°á»£c khai bÃ¡o pivot giá»¯a company-product | CÃ³ FK DB: `company_id -> companies.id`, `product_id -> products.id`; khÃ´ng orphan | LiÃªn káº¿t nhÃ  cung cáº¥p/cÃ´ng ty vá»›i sáº£n pháº©m khi nháº­p hÃ ng | Äang sá»­ dá»¥ng | Cao | [app/Models/CompanyProduct.php](/d:/iphone/app/Models/CompanyProduct.php:12) `$table = 'company_product'`; [app/Models/Company.php](/d:/iphone/app/Models/Company.php:51) `belongsToMany(Product::class, 'company_product')`; [app/Models/Product.php](/d:/iphone/app/Models/Product.php:63) `belongsToMany(Company::class, 'company_product')`; [app/Services/CompanyProductService.php](/d:/iphone/app/Services/CompanyProductService.php:26) `updateCompanyProduct`; [app/Http/Controllers/Admin/importCouponController.php](/d:/iphone/app/Http/Controllers/Admin/importCouponController.php:154) gá»i service; SQL orphan = 0. |
| `import` | KhÃ´ng, 0 dÃ²ng | CÃ³: `Import`, cÃ³ `$table = 'import'` | CÃ³, nhÆ°ng lÃ  báº£ng táº¡m cho mÃ n nháº­p hÃ ng; phiáº¿u nháº­p tháº­t náº±m á»Ÿ `import_coupon`/`import_detail` | Migration táº¡o báº£ng `import` khÃ´ng khai bÃ¡o FK; báº£ng nÃ y tham chiáº¿u má»m `product_id -> products.id`; khÃ´ng báº£ng nÃ o FK tá»›i `import` | Ajax thÃªm/sá»­a/xÃ³a dÃ²ng nháº­p hÃ ng trÆ°á»›c khi lÆ°u phiáº¿u; sau khi lÆ°u thÃ¬ `Import::truncate()` | Báº£ng táº¡m | Ráº¥t cao | [app/Models/Import.php](/d:/iphone/app/Models/Import.php:11) `$table = 'import'`; [app/Http/Controllers/Admin/ImportProductController.php](/d:/iphone/app/Http/Controllers/Admin/ImportProductController.php:61) `Import::where('product_id', ...)`; [app/Http/Controllers/Admin/importCouponController.php](/d:/iphone/app/Http/Controllers/Admin/importCouponController.php:130) láº¥y `Import::where('quantity', '>', 0)`; [app/Http/Controllers/Admin/importCouponController.php](/d:/iphone/app/Http/Controllers/Admin/importCouponController.php:157) `Import::truncate()`; [database/migrations/2024_07_16_140303_create_import_detail_table.php](/d:/iphone/database/migrations/2024_07_16_140303_create_import_detail_table.php:16) `import_detail.import_id` constrained tá»›i `import_coupon`, khÃ´ng pháº£i `import`; SQL: `select count(*) from import` = 0. |
| `commissions` | KhÃ´ng tá»“n táº¡i | KhÃ´ng tháº¥y Model | KhÃ´ng tháº¥y dÃ¹ng ngoÃ i migration cÅ© cá»§a `users.commission_id` | KhÃ´ng kiá»ƒm Ä‘Æ°á»£c vÃ¬ báº£ng khÃ´ng tá»“n táº¡i; migration cÅ© tá»«ng foreign tá»›i `commissions` | KhÃ´ng tháº¥y chá»©c nÄƒng hoa há»“ng Ä‘ang dÃ¹ng báº£ng nÃ y | CÃ³ kháº£ nÄƒng thá»«a | Cao | `Schema::hasTable('commissions') = false`; [database/migrations/2024_06_27_091638_create_users_table.php](/d:/iphone/database/migrations/2024_06_27_091638_create_users_table.php:23) `commission_id`; [database/migrations/2024_07_12_083923_add_columns_to_user_table.php](/d:/iphone/database/migrations/2024_07_12_083923_add_columns_to_user_table.php:25) drop `commission_id`; khÃ´ng tháº¥y `Commission` model/controller/service. |
| `personal_access_tokens` | KhÃ´ng, 0 dÃ²ng | KhÃ´ng cÃ³ app model riÃªng; model vendor cá»§a Laravel Sanctum | CÃ³ giÃ¡n tiáº¿p náº¿u dÃ¹ng Sanctum/API token; `User` vÃ  `SuperAdmin` dÃ¹ng `HasApiTokens` | KhÃ´ng cÃ³ FK DB; polymorphic `tokenable_type/tokenable_id` | API route `auth:sanctum`, cáº¥u hÃ¬nh Sanctum | Äang sá»­ dá»¥ng giÃ¡n tiáº¿p | Trung bÃ¬nh cao | [routes/api.php](/d:/iphone/routes/api.php:17) `auth:sanctum`; [app/Models/User.php](/d:/iphone/app/Models/User.php:8) `HasApiTokens`; [app/Models/SuperAdmin.php](/d:/iphone/app/Models/SuperAdmin.php:8) `HasApiTokens`; [config/sanctum.php](/d:/iphone/config/sanctum.php:3) `Laravel\Sanctum\Sanctum`; SQL: `select count(*) from personal_access_tokens` = 0. |
| `check_inventory` | KhÃ´ng, 0 dÃ²ng | CÃ³: `CheckInventory`, cÃ³ `$table = 'check_inventory'` | CÃ³, dÃ¹ng trá»±c tiáº¿p trong admin/staff kiá»ƒm kho | CÃ³ FK DB: `check_inventory.user_id -> users.id`; `check_detail.check_inventory_id -> check_inventory.id`; khÃ´ng orphan | Phiáº¿u kiá»ƒm kho, chi tiáº¿t phiáº¿u, danh sÃ¡ch/filter/detail | Äang sá»­ dá»¥ng | Ráº¥t cao | [app/Models/CheckInventory.php](/d:/iphone/app/Models/CheckInventory.php:12) `$table = 'check_inventory'`; [routes/web.php](/d:/iphone/routes/web.php:288) admin check routes; [routes/web.php](/d:/iphone/routes/web.php:405) staff check routes; [app/Services/CheckInventoryService.php](/d:/iphone/app/Services/CheckInventoryService.php:95) táº¡o phiáº¿u; [app/Http/Controllers/Staff/CheckInventoryController.php](/d:/iphone/app/Http/Controllers/Staff/CheckInventoryController.php:70) táº¡o `CheckDetail`; SQL: `check_detail_inventory_missing` = 0. |

## LEFT JOIN kiá»ƒm tra dá»¯ liá»‡u má»“ cÃ´i

CÃ¡c cÃ¢u kiá»ƒm tra Ä‘Ã£ cháº¡y trÃªn DB `ai_crm_2026`:

```sql
select count(*) from clients c left join users u on u.id = c.user_id where c.user_id is not null and u.id is null; -- 0
select count(*) from orders o left join clients c on c.id = o.client_id where o.client_id is not null and c.id is null; -- 0
select count(*) from customer_debts d left join clients c on c.id = d.client_id where d.client_id is not null and c.id is null; -- 0
select count(*) from receipts r left join clients c on c.id = r.client_id where r.client_id is not null and c.id is null; -- 2
select count(*) from carts c left join users u on u.id = c.user_id where c.user_id is not null and u.id is null; -- 0
select count(*) from carts c left join products p on p.id = c.product_id where c.product_id is not null and p.id is null; -- 0
select count(*) from user_info ui left join users u on u.id = ui.user_id where ui.user_id is not null and u.id is null; -- 1
select count(*) from role_permission rp left join roles r on r.id = rp.role_id where rp.role_id is not null and r.id is null; -- 0
select count(*) from warehouse w left join users u on u.id = w.user_id where w.user_id is not null and u.id is null; -- 0
select count(*) from warehouse w left join products p on p.id = w.product_id where w.product_id is not null and p.id is null; -- 1
select count(*) from products_code pc left join products p on p.id = pc.id_product where pc.id_product is not null and p.id is null; -- 5
select count(*) from company_product cp left join companies c on c.id = cp.company_id where cp.company_id is not null and c.id is null; -- 0
select count(*) from company_product cp left join products p on p.id = cp.product_id where cp.product_id is not null and p.id is null; -- 0
select count(*) from `import` i left join products p on p.id = i.product_id where i.product_id is not null and p.id is null; -- 0
select count(*) from import_detail d left join `import` i on i.id = d.import_id where d.import_id is not null and i.id is null; -- 7
select count(*) from check_inventory ci left join users u on u.id = ci.user_id where ci.user_id is not null and u.id is null; -- 0
select count(*) from check_detail cd left join check_inventory ci on ci.id = cd.check_inventory_id where cd.check_inventory_id is not null and ci.id is null; -- 0
```

LÆ°u Ã½ riÃªng cho `import_detail`: 7 dÃ²ng "má»“ cÃ´i" náº¿u ná»‘i sang báº£ng `import` lÃ  Ä‘Ãºng ká»³ vá»ng vÃ¬ migration vÃ  model Ä‘ang ná»‘i `import_detail.import_id` tá»›i `import_coupon.id`, khÃ´ng pháº£i báº£ng táº¡m `import`.

## Danh sÃ¡ch phÃ¢n loáº¡i

### 1. Báº£ng cháº¯c cháº¯n Ä‘ang sá»­ dá»¥ng

- `clients`
- `carts`
- `warehouse`
- `company_product`
- `import`
- `check_inventory`

### 2. Báº£ng khÃ´ng Ä‘Æ°á»£c source sá»­ dá»¥ng nhÆ°ng cÃ²n dá»¯ liá»‡u

- `customers` - 3 dÃ²ng, chá»‰ tháº¥y model/import khÃ´ng tháº¥y nghiá»‡p vá»¥ dÃ¹ng.
- `products_code` - 5 dÃ²ng, khÃ´ng tháº¥y source/migration/route dÃ¹ng; 5/5 dÃ²ng khÃ´ng ná»‘i Ä‘Æ°á»£c sang `products`.

### 3. Báº£ng khÃ´ng Ä‘Æ°á»£c source sá»­ dá»¥ng vÃ  khÃ´ng cÃ³ dá»¯ liá»‡u

- `role_permission` - 0 dÃ²ng, cÃ³ model/migration nhÆ°ng middleware dÃ¹ng `users.role_id` trá»±c tiáº¿p.
- `commissions` - báº£ng khÃ´ng tá»“n táº¡i trong DB hiá»‡n táº¡i; chá»‰ cÃ²n dáº¥u váº¿t migration cÅ©.

### 4. Báº£ng chÆ°a thá»ƒ káº¿t luáº­n

- `personal_access_tokens` - khÃ´ng cÃ³ dá»¯ liá»‡u, nhÆ°ng cÃ³ Sanctum trong `routes/api.php`, `config/sanctum.php`, `HasApiTokens`. Náº¿u API token khÃ´ng bao giá» dÃ¹ng thÃ¬ cÃ³ thá»ƒ khÃ´ng cáº§n, nhÆ°ng khÃ´ng nÃªn xÃ³a khi chÆ°a xÃ¡c nháº­n chiáº¿n lÆ°á»£c auth.
- `user_info` - Ä‘ang Ä‘Æ°á»£c dÃ¹ng giÃ¡n tiáº¿p, nhÆ°ng cÃ³ 1 dÃ²ng má»“ cÃ´i cáº§n xá»­ lÃ½ dá»¯ liá»‡u trÆ°á»›c khi dá»n.

## Äá» xuáº¥t quy trÃ¬nh an toÃ n

| TÃªn báº£ng | Äá» xuáº¥t |
|---|---|
| `customers` | ÄÃ¡nh dáº¥u legacy; backup rá»“i Ä‘á»•i tÃªn báº£ng trong mÃ´i trÆ°á»ng test; theo dÃµi lá»—i 7 ngÃ y; náº¿u khÃ´ng lá»—i vÃ  khÃ´ng cáº§n dá»¯ liá»‡u lá»‹ch sá»­ thÃ¬ viáº¿t migration xÃ³a sau. |
| `clients` | Giá»¯ nguyÃªn; khÃ´ng Ä‘Æ°á»£c xÃ³a. TrÆ°á»›c khi sá»­a dá»¯ liá»‡u, xá»­ lÃ½ 2 dÃ²ng `receipts.client_id` khÃ´ng ná»‘i Ä‘Æ°á»£c sang `clients`. |
| `carts` | Giá»¯ nguyÃªn; khÃ´ng Ä‘Æ°á»£c xÃ³a dÃ¹ hiá»‡n 0 dÃ²ng vÃ¬ lÃ  báº£ng runtime/táº¡m theo user. |
| `user_info` | Giá»¯ nguyÃªn; xá»­ lÃ½ 1 dÃ²ng orphan; khÃ´ng xÃ³a báº£ng. |
| `role_permission` | Backup rá»“i Ä‘á»•i tÃªn báº£ng trong mÃ´i trÆ°á»ng test; theo dÃµi lá»—i 7 ngÃ y; náº¿u khÃ´ng lá»—i thÃ¬ viáº¿t migration xÃ³a sau. |
| `warehouse` | Giá»¯ nguyÃªn vÃ¬ module kiá»ƒm kho dÃ¹ng trá»±c tiáº¿p; xá»­ lÃ½ 1 dÃ²ng orphan `product_id`; khÃ´ng xÃ³a báº£ng. |
| `products_code` | ÄÃ¡nh dáº¥u legacy; backup dá»¯ liá»‡u; Ä‘á»•i tÃªn báº£ng trong test; theo dÃµi lá»—i 7 ngÃ y; náº¿u khÃ´ng lá»—i thÃ¬ viáº¿t migration xÃ³a sau. |
| `company_product` | Giá»¯ nguyÃªn; khÃ´ng Ä‘Æ°á»£c xÃ³a. |
| `import` | Giá»¯ nguyÃªn hoáº·c ghi chÃº rÃµ lÃ  báº£ng táº¡m; khÃ´ng xÃ³a náº¿u cÃ²n dÃ¹ng mÃ n nháº­p hÃ ng hiá»‡n táº¡i. |
| `commissions` | VÃ¬ báº£ng khÃ´ng tá»“n táº¡i, khÃ´ng cÃ³ thao tÃ¡c DROP. NÃªn dá»n migration/schema cÅ© trong má»™t nhÃ¡nh riÃªng náº¿u cáº§n chuáº©n hÃ³a lá»‹ch sá»­ migration. |
| `personal_access_tokens` | Giá»¯ nguyÃªn; khÃ´ng Ä‘Æ°á»£c xÃ³a khi chÆ°a xÃ¡c nháº­n bá» Sanctum/API token. |
| `check_inventory` | Giá»¯ nguyÃªn; khÃ´ng Ä‘Æ°á»£c xÃ³a. |

