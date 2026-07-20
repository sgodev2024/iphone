# 1. THÃ”NG TIN CHUNG

| Ná»™i dung | ThÃ´ng tin |
| --- | --- |
| TÃªn dá»± Ã¡n | iphone |
| NgÆ°á»i thá»±c hiá»‡n | Nguyá»…n Äá»©c Viá»‡t |
| NgÆ°á»i phá»¥ trÃ¡ch/bÃ n giao | [Cáº¦N Bá»” SUNG] - ChÆ°a xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c tá»« mÃ£ nguá»“n hiá»‡n táº¡i. |
| Thá»i gian nghiÃªn cá»©u | [Cáº¦N Bá»” SUNG] |
| Loáº¡i há»‡ thá»‘ng | Há»‡ thá»‘ng web quáº£n lÃ½ bÃ¡n hÃ ng, kho, khÃ¡ch hÃ ng, nhÃ  cung cáº¥p, thu chi/cÃ´ng ná»£ vÃ  váº­n hÃ nh ná»™i bá»™. |
| CÃ´ng nghá»‡ backend | PHP, Laravel Framework, Laravel Sanctum, Eloquent ORM, há»‡ thá»‘ng route/controller/service/model theo cáº¥u trÃºc Laravel. |
| CÃ´ng nghá»‡ frontend | Laravel Blade, Vite, JavaScript, CSS, Bootstrap; giao diá»‡n quáº£n trá»‹ sá»­ dá»¥ng asset theme Kaiadmin vÃ  cÃ¡c thÆ° viá»‡n JavaScript há»— trá»£. |
| PhiÃªn báº£n PHP | ^8.1 |
| PhiÃªn báº£n Laravel | v10.49.0 theo `composer.lock`; constraint trong `composer.json` lÃ  ^10.10. |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u | MySQL lÃ  káº¿t ná»‘i máº·c Ä‘á»‹nh trong `.env.example` vÃ  `config/database.php`. |
| ThÆ° viá»‡n giao diá»‡n chÃ­nh | Bootstrap, Kaiadmin assets, Font Awesome, SweetAlert/SweetAlert2, CKEditor/CKFinder, Bootstrap Notify, Toastr. |
| MÃ´i trÆ°á»ng triá»ƒn khai hiá»‡n táº¡i | [Cáº¦N Bá»” SUNG] - ChÆ°a xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c mÃ´i trÆ°á»ng triá»ƒn khai thá»±c táº¿ tá»« mÃ£ nguá»“n hiá»‡n táº¡i. Ghi nháº­n `.env.example` cáº¥u hÃ¬nh mÃ´i trÆ°á»ng `local`; `README.dev.md` cÃ³ hÆ°á»›ng dáº«n cháº¡y queue worker local/server báº±ng `php artisan queue:work`, screen hoáº·c Supervisor. |
| Quáº£n lÃ½ mÃ£ nguá»“n | Git; branch hiá»‡n táº¡i `viet_dev`, remote `origin` trá» tá»›i GitHub. |
| Repository hoáº·c tÃ i liá»‡u liÃªn quan | Repository GitHub: `https://github.com/sgodev2024/iphone.git`; tÃ i liá»‡u trong repo gá»“m `README.md`, `README.dev.md`, `CHANGELOG.md` vÃ  `bao-cao-phan-tich-laravel.md`. |
| TÃ¬nh tráº¡ng tÃ i liá»‡u bÃ n giao | CÃ³ má»™t sá»‘ tÃ i liá»‡u/hÆ°á»›ng dáº«n ná»™i bá»™ nhÆ°ng chÆ°a tháº¥y tÃ i liá»‡u bÃ n giao Ä‘áº§y Ä‘á»§ vá» nghiá»‡p vá»¥, kiáº¿n trÃºc, triá»ƒn khai vÃ  váº­n hÃ nh. CÃ¡c pháº§n chÆ°a cÃ³ cáº§n Ä‘Æ°á»£c bá»• sung. |

# 2. Má»¤C TIÃŠU VÃ€ PHáº M VI NGHIÃŠN Cá»¨U

## 2.1. Má»¥c tiÃªu nghiÃªn cá»©u

Viá»‡c nghiÃªn cá»©u dá»± Ã¡n nháº±m há»— trá»£ quÃ¡ trÃ¬nh tiáº¿p nháº­n má»™t há»‡ thá»‘ng Laravel Ä‘Ã£ Ä‘Æ°á»£c phÃ¡t triá»ƒn trÆ°á»›c Ä‘Ã³, phá»¥c vá»¥ báº£o trÃ¬, sá»­a lá»—i vÃ  phÃ¡t triá»ƒn chá»©c nÄƒng má»›i. Pháº¡m vi nghiÃªn cá»©u táº­p trung vÃ o viá»‡c hiá»ƒu nghiá»‡p vá»¥ chÃ­nh, cáº¥u trÃºc mÃ£ nguá»“n, mÃ´ hÃ¬nh dá»¯ liá»‡u, cÃ¡ch tá»• chá»©c giao diá»‡n, cÆ¡ cháº¿ phÃ¢n quyá»n, phá»¥ thuá»™c ká»¹ thuáº­t vÃ  cÃ¡c Ä‘iá»u kiá»‡n váº­n hÃ nh cáº§n thiáº¿t. Káº¿t quáº£ pháº§n nÃ y chá»‰ dÃ¹ng Ä‘á»ƒ xÃ¡c Ä‘á»‹nh pháº¡m vi Ä‘Ã£ kiá»ƒm tra vÃ  cÃ¡c giá»›i háº¡n hiá»‡n cÃ³, chÆ°a Ä‘Æ°a ra káº¿t luáº­n cuá»‘i cÃ¹ng vá» cháº¥t lÆ°á»£ng dá»± Ã¡n.

2. Náº¯m cáº¥u trÃºc tá»•ng thá»ƒ cá»§a mÃ£ nguá»“n Laravel, bao gá»“m route, controller, model, service, request, middleware, view, config vÃ  helper.
3. XÃ¡c Ä‘á»‹nh cÃ¡ch tá»• chá»©c cÃ¡c module Admin, Staff/POS, SuperAdmin vÃ  cÃ¡c thÃ nh pháº§n dÃ¹ng chung trong há»‡ thá»‘ng.
4. Äá»‘i chiáº¿u cáº¥u trÃºc cÆ¡ sá»Ÿ dá»¯ liá»‡u qua migration, seeder, factory, model vÃ  tÃ i liá»‡u audit cÃ³ sáºµn trong repository.
5. Nháº­n diá»‡n cÃ¡c chá»©c nÄƒng Ä‘Ã£ cÃ³ route, controller, view, model hoáº·c service Ä‘á»ƒ lÃ m cÆ¡ sá»Ÿ kiá»ƒm tra tiáº¿p á»Ÿ cÃ¡c pháº§n sau.
7. XÃ¡c Ä‘á»‹nh cÃ¡c Ä‘iá»u kiá»‡n cÃ²n thiáº¿u Ä‘á»ƒ kiá»ƒm thá»­ váº­n hÃ nh thá»±c táº¿, gá»“m tÃ i khoáº£n, dá»¯ liá»‡u, mÃ´i trÆ°á»ng, quyá»n truy cáº­p vÃ  dá»‹ch vá»¥ sandbox/live.
8. LÃ m cÆ¡ sá»Ÿ láº­p káº¿ hoáº¡ch tiáº¿p nháº­n, kiá»ƒm thá»­ bá»• sung, chuáº©n hÃ³a triá»ƒn khai vÃ  phÃ¡t triá»ƒn dá»± Ã¡n trong giai Ä‘oáº¡n tiáº¿p theo.

## 2.2. Pháº¡m vi nghiÃªn cá»©u

Pháº¡m vi nghiÃªn cá»©u hiá»‡n táº¡i Ä‘Æ°á»£c thá»±c hiá»‡n báº±ng cÃ¡ch Ä‘á»c mÃ£ nguá»“n, cáº¥u hÃ¬nh, tÃ i liá»‡u cÃ³ trong repository vÃ  cáº¥u trÃºc Git. KhÃ´ng cháº¡y migration, seeder, lá»‡nh xÃ³a dá»¯ liá»‡u hoáº·c thao tÃ¡c cÃ³ kháº£ nÄƒng lÃ m thay Ä‘á»•i dá»¯ liá»‡u. CÃ¡c thÃ nh pháº§n Ä‘Æ°á»£c ghi nháº­n theo ba má»©c: Ä‘Ã£ xem Ä‘Æ°á»£c cáº¥u trÃºc vÃ  mÃ£ nguá»“n nhÆ°ng chÆ°a cháº¡y thá»­, chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n xÃ¡c minh váº­n hÃ nh, hoáº·c chÆ°a cÃ³ nguá»“n xÃ¡c minh phÃ¹ há»£p táº¡i thá»i Ä‘iá»ƒm nghiÃªn cá»©u.

| NhÃ³m nghiÃªn cá»©u | Ná»™i dung Ä‘Ã£ kiá»ƒm tra | Má»©c Ä‘á»™ kiá»ƒm tra | Nguá»“n xÃ¡c minh | Ghi chÃº |
| --- | --- | --- | --- | --- |
| Backend Laravel | Routes web/API, controllers Admin/Staff/SuperAdmin, models, services, Form Requests, middleware, jobs, events/listeners, mail, exports, helpers, exception handler, config. | Má»™t pháº§n | `routes`, `app`, `composer.json`, `config` | ÄÃ£ kiá»ƒm tra tÄ©nh mÃ£ nguá»“n; chÆ°a cháº¡y thá»­ toÃ n bá»™ luá»“ng nghiá»‡p vá»¥. |
| Giao diá»‡n quáº£n trá»‹ | Blade layout, sidebar/header/footer, cÃ¡c mÃ n sáº£n pháº©m, kho, nháº­p hÃ ng, khÃ¡ch hÃ ng, Ä‘Æ¡n hÃ ng, bÃ¡o cÃ¡o, thu chi, cÃ´ng ná»£, káº¿ toÃ¡n, nhÃ¢n viÃªn, cáº¥u hÃ¬nh. | Má»™t pháº§n | `resources/views/admin`, `resources/views/Themes/admin`, `public/assets` | ChÆ°a kiá»ƒm tra hiá»ƒn thá»‹ thá»±c táº¿ trÃªn trÃ¬nh duyá»‡t vÃ  thiáº¿t bá»‹. |
| Giao diá»‡n ngÆ°á»i dÃ¹ng | MÃ n Staff/POS, bÃ¡n hÃ ng, Ä‘Æ¡n hÃ ng, kiá»ƒm kÃª, warehome; giao diá»‡n Ä‘Äƒng nháº­p; má»™t sá»‘ view cÅ© trong `Themes`, `sa`, `emails/admin`. | Má»™t pháº§n | `resources/views/Themes/pages`, `resources/views/auth`, `resources/views/sa`, `resources/views/emails` | ChÆ°a thao tÃ¡c end-to-end vá»›i tÃ i khoáº£n tháº­t. |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u | 111 migration, 16 seeder, 13 factory, 52 model, quan há»‡ Eloquent, tÃ i liá»‡u `database-table-usage-audit.md`. | Má»™t pháº§n | `database`, `app/Models`, `docs/database-table-usage-audit.md` | KhÃ´ng truy cáº­p hoáº·c thay Ä‘á»•i database production trong láº§n nghiÃªn cá»©u nÃ y. |
| PhÃ¢n quyá»n | Login/logout, middleware `auth`, `role`, `CheckLogin`, `CheckLoginSuperAdmin`, `AuthServiceProvider`, route group theo role. | Má»™t pháº§n | `routes/web.php`, `app/Http/Kernel.php`, `app/Http/Middleware`, `app/Providers/AuthServiceProvider.php` | ChÆ°a cÃ³ Policy/Gate nghiá»‡p vá»¥; chÆ°a kiá»ƒm thá»­ quyá»n báº±ng tÃ i khoáº£n thá»±c táº¿. |
| API ná»™i bá»™ | `routes/api.php` vá»›i route `/api/user` dÃ¹ng `auth:sanctum`; helper response vÃ  má»™t sá»‘ endpoint Ajax trong web routes. | Má»™t pháº§n | `routes/api.php`, `routes/web.php`, `app/Http/Responses`, `app/Helpers` | ChÆ°a tháº¥y API nghiá»‡p vá»¥ riÃªng ngoÃ i route Sanctum máº·c Ä‘á»‹nh. |
| Triá»ƒn khai há»‡ thá»‘ng | README, README.dev, Composer, NPM/Vite, queue worker, `.styleci.yml`, cáº¥u hÃ¬nh Laravel cache/session/logging/filesystem. | Má»™t pháº§n | `README.md`, `README.dev.md`, `composer.json`, `package.json`, `vite.config.js`, `.env.example`, `config`, `.styleci.yml` | KhÃ´ng tÃ¬m tháº¥y Dockerfile, docker-compose, cáº¥u hÃ¬nh web server hoáº·c CI/CD Ä‘áº§y Ä‘á»§. |
| Kiá»ƒm thá»­ | `phpunit.xml`, 5 file test, cáº¥u hÃ¬nh test suite Unit/Feature, test redirect login vÃ  test helper Ä‘Æ°á»ng dáº«n áº£nh upload. | Má»™t pháº§n | `tests`, `phpunit.xml` | ChÆ°a cháº¡y test Ä‘á»ƒ trÃ¡nh phÃ¡t sinh thay Ä‘á»•i phá»¥; chÆ°a cÃ³ coverage cho pháº§n lá»›n nghiá»‡p vá»¥. |


## 2.3. Ná»™i dung chÆ°a kiá»ƒm tra

CÃ¡c ná»™i dung dÆ°á»›i Ä‘Ã¢y Ä‘Æ°á»£c ghi nháº­n lÃ  `[CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH]` táº¡i thá»i Ä‘iá»ƒm nghiÃªn cá»©u. LÃ½ do chá»§ yáº¿u lÃ  chÆ°a cÃ³ quyá»n truy cáº­p mÃ´i trÆ°á»ng váº­n hÃ nh, tÃ i khoáº£n kiá»ƒm thá»­, dá»¯ liá»‡u thá»±c táº¿ Ä‘á»§ Ä‘áº¡i diá»‡n, thÃ´ng tin dá»‹ch vá»¥ bÃªn thá»© ba hoáº·c chÆ°a thá»±c hiá»‡n thao tÃ¡c cháº¡y thá»­ cÃ³ kiá»ƒm soÃ¡t. Nhá»¯ng ná»™i dung nÃ y khÃ´ng Ä‘Æ°á»£c xem lÃ  káº¿t luáº­n lá»—i, mÃ  lÃ  giá»›i háº¡n cáº§n bá»• sung trÆ°á»›c khi Ä‘Ã¡nh giÃ¡ á»Ÿ má»©c váº­n hÃ nh thá»±c táº¿.

| STT | Ná»™i dung chÆ°a kiá»ƒm tra | NguyÃªn nhÃ¢n | áº¢nh hÆ°á»Ÿng Ä‘áº¿n káº¿t quáº£ Ä‘Ã¡nh giÃ¡ | Äiá»u kiá»‡n cáº§n bá»• sung |
| --: | --- | --- | --- | --- |
| 1 | MÃ´i trÆ°á»ng production, staging vÃ  server triá»ƒn khai thá»±c táº¿ | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] ChÆ°a cÃ³ quyá»n truy cáº­p server hoáº·c tÃ i liá»‡u triá»ƒn khai Ä‘áº§y Ä‘á»§ | ChÆ°a thá»ƒ xÃ¡c minh cáº¥u hÃ¬nh web server, domain, SSL, queue worker, scheduler, logging vÃ  runtime tháº­t | TÃ i khoáº£n server, sÆ¡ Ä‘á»“ triá»ƒn khai, cáº¥u hÃ¬nh web server vÃ  hÆ°á»›ng dáº«n váº­n hÃ nh |
| 2 | Database production hoáº·c database váº­n hÃ nh hiá»‡n táº¡i | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] Trong láº§n nghiÃªn cá»©u nÃ y khÃ´ng truy cáº­p database tháº­t vÃ  khÃ´ng cháº¡y lá»‡nh thay Ä‘á»•i dá»¯ liá»‡u | ChÆ°a thá»ƒ xÃ¡c nháº­n dá»¯ liá»‡u thá»±c táº¿, migration status hiá»‡n hÃ nh, orphan data hoáº·c hiá»‡u nÄƒng truy váº¥n | Quyá»n Ä‘á»c database phÃ¹ há»£p, báº£n dump áº©n danh hoáº·c mÃ´i trÆ°á»ng staging |
| 3 | ÄÄƒng nháº­p vÃ  phÃ¢n quyá»n báº±ng tÃ i khoáº£n thá»±c táº¿ | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] ChÆ°a cÃ³ tÃ i khoáº£n Admin, Staff vÃ  SuperAdmin Ä‘áº§y Ä‘á»§ | ChÆ°a thá»ƒ xÃ¡c minh luá»“ng quyá»n theo vai trÃ², kháº£ nÄƒng truy cáº­p tá»«ng module vÃ  dá»¯ liá»‡u theo ngÆ°á»i dÃ¹ng | Bá»™ tÃ i khoáº£n kiá»ƒm thá»­ cho tá»«ng vai trÃ² vÃ  ma tráº­n quyá»n mong muá»‘n |
| 4 | Luá»“ng nghiá»‡p vá»¥ end-to-end | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] ChÆ°a thao tÃ¡c trÃªn UI tá»« Ä‘Äƒng nháº­p, nháº­p hÃ ng, bÃ¡n hÃ ng, trá»« tá»“n, bÃ¡o cÃ¡o Ä‘áº¿n cÃ´ng ná»£ | ChÆ°a thá»ƒ káº¿t luáº­n chá»©c nÄƒng hoáº¡t Ä‘á»™ng á»•n Ä‘á»‹nh khi váº­n hÃ nh liÃªn tá»¥c | MÃ´i trÆ°á»ng test, dá»¯ liá»‡u máº«u, ká»‹ch báº£n kiá»ƒm thá»­ vÃ  tiÃªu chÃ­ nghiá»‡m thu |
| 6 | Thanh toÃ¡n trá»±c tuyáº¿n | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] Route thanh toÃ¡n MoMo trong `routes/web.php` Ä‘ang á»Ÿ dáº¡ng comment vÃ  chÆ°a cÃ³ Ä‘iá»u kiá»‡n kiá»ƒm thá»­ gateway | ChÆ°a thá»ƒ xÃ¡c nháº­n tráº¡ng thÃ¡i tÃ­ch há»£p thanh toÃ¡n, callback/IPN hoáº·c Ä‘á»‘i soÃ¡t giao dá»‹ch | TÃ i khoáº£n sandbox, thÃ´ng sá»‘ gateway vÃ  route/view Ä‘Æ°á»£c báº­t trong mÃ´i trÆ°á»ng kiá»ƒm thá»­ |
| 8 | CI/CD, Docker, backup vÃ  khÃ´i phá»¥c | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] Chá»‰ tháº¥y `.styleci.yml`; chÆ°a tháº¥y Dockerfile, docker-compose, pipeline hoáº·c tÃ i liá»‡u backup Ä‘áº§y Ä‘á»§ | ChÆ°a thá»ƒ Ä‘Ã¡nh giÃ¡ quy trÃ¬nh build, deploy tá»± Ä‘á»™ng, rollback vÃ  phá»¥c há»“i dá»¯ liá»‡u | File pipeline, tÃ i liá»‡u deploy, chÃ­nh sÃ¡ch backup/restore vÃ  quyá»n kiá»ƒm tra mÃ´i trÆ°á»ng |
| 9 | Kiá»ƒm thá»­ táº£i, báº£o máº­t chuyÃªn sÃ¢u vÃ  tÆ°Æ¡ng thÃ­ch trÃ¬nh duyá»‡t/thiáº¿t bá»‹ | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] ChÆ°a cÃ³ dá»¯ liá»‡u lá»›n, cÃ´ng cá»¥ test, pháº¡m vi báº£o máº­t hoáº·c danh sÃ¡ch thiáº¿t bá»‹ má»¥c tiÃªu | ChÆ°a thá»ƒ Ä‘Æ°a ra káº¿t luáº­n vá» hiá»‡u nÄƒng, báº£o máº­t á»©ng dá»¥ng hoáº·c tráº£i nghiá»‡m Ä‘a thiáº¿t bá»‹ | Káº¿ hoáº¡ch performance/security test, dá»¯ liá»‡u Ä‘áº¡i diá»‡n vÃ  danh sÃ¡ch trÃ¬nh duyá»‡t/thiáº¿t bá»‹ |
| 10 | TÃ i liá»‡u nghiá»‡p vá»¥ vÃ  tiÃªu chÃ­ nghiá»‡m thu Ä‘áº§y Ä‘á»§ | [CHÆ¯A Äá»¦ ÄIá»€U KIá»†N XÃC MINH] Repository cÃ³ tÃ i liá»‡u phÃ¢n tÃ­ch, nhÆ°ng chÆ°a tháº¥y tÃ i liá»‡u yÃªu cáº§u nghiá»‡p vá»¥ chÃ­nh thá»©c cho tá»«ng chá»©c nÄƒng | ChÆ°a thá»ƒ Ä‘á»‘i chiáº¿u Ä‘áº§y Ä‘á»§ tÃ­nh Ä‘Ãºng Ä‘áº¯n cá»§a luá»“ng nghiá»‡p vá»¥ vá»›i yÃªu cáº§u ban Ä‘áº§u | TÃ i liá»‡u BRD/SRS, checklist nghiá»‡m thu vÃ  xÃ¡c nháº­n tá»« ngÆ°á»i phá»¥ trÃ¡ch nghiá»‡p vá»¥ |

Pháº¡m vi Ä‘Ã¡nh giÃ¡ hiá»‡n táº¡i Ä‘Æ°á»£c xÃ¢y dá»±ng dá»±a trÃªn mÃ£ nguá»“n, tÃ i liá»‡u ná»™i bá»™ trong repository, cáº¥u hÃ¬nh máº«u vÃ  quyá»n truy cáº­p Ä‘ang cÃ³ táº¡i thá»i Ä‘iá»ƒm nghiÃªn cá»©u. Vá»›i cÃ¡c ná»™i dung chÆ°a cÃ³ tÃ i khoáº£n, dá»¯ liá»‡u váº­n hÃ nh, mÃ´i trÆ°á»ng server hoáº·c dá»‹ch vá»¥ bÃªn thá»© ba tÆ°Æ¡ng á»©ng, bÃ¡o cÃ¡o chá»‰ ghi nháº­n Ä‘Æ°á»£c dáº¥u hiá»‡u triá»ƒn khai trong mÃ£ nguá»“n vÃ  tÃ i liá»‡u, chÆ°a thá»ƒ xÃ¡c minh Ä‘áº§y Ä‘á»§ khi cháº¡y thá»±c táº¿. Khi cÃ³ thÃªm mÃ´i trÆ°á»ng kiá»ƒm thá»­, dá»¯ liá»‡u Ä‘áº¡i diá»‡n, tÃ i khoáº£n phÃ¹ há»£p vÃ  tiÃªu chÃ­ nghiá»‡m thu, cÃ¡c nháº­n Ä‘á»‹nh liÃªn quan cÃ³ thá»ƒ Ä‘Æ°á»£c cáº­p nháº­t Ä‘á»ƒ pháº£n Ã¡nh chÃ­nh xÃ¡c hÆ¡n tráº¡ng thÃ¡i váº­n hÃ nh cá»§a há»‡ thá»‘ng.

## Nguá»“n xÃ¡c minh

| Nháº­n Ä‘á»‹nh | File, thÆ° má»¥c hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| Pháº¡m vi backend | `app`, `app/Http/Controllers`, `app/Services`, `app/Models`, `app/Http/Requests`, `app/Http/Middleware`, `app/Jobs`, `app/Events`, `app/Listeners`, `app/Mail`, `app/Exports`, `app/Helpers`, `app/Exceptions`, `routes`, `config` |
| Pháº¡m vi frontend | `resources/views`, `resources/js`, `resources/css`, `public/assets`, `public/js`, `public/validator`, `package.json`, `package-lock.json`, `vite.config.js` |
| Pháº¡m vi database | `database/migrations`, `database/seeders`, `database/factories`, `app/Models`, `docs/database-table-usage-audit.md` |
| PhÃ¢n quyá»n | `routes/web.php`, `routes/api.php`, `app/Http/Kernel.php`, `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/CheckLogin.php`, `app/Http/Middleware/CheckLoginSuperAdmin.php`, `app/Providers/AuthServiceProvider.php` |
| Triá»ƒn khai | `README.md`, `README.dev.md`, `composer.json`, `composer.lock`, `package.json`, `vite.config.js`, `.env.example`, `.styleci.yml`, `config`, Git branch/remote/log |
| Kiá»ƒm thá»­ | `tests`, `phpunit.xml`; chÆ°a cháº¡y test trong láº§n cáº­p nháº­t nÃ y |
| TÃ i liá»‡u chá»©c nÄƒng | `docs/feature-completion-clean.md`, `docs/feature-completion-audit.md`, `bao-cao-phan-tich-laravel.md`, `CHANGELOG.md` |

# 3. Tá»”NG QUAN Há»† THá»NG HIá»†N Táº I

## 3.1. Má»¥c Ä‘Ã­ch cá»§a há»‡ thá»‘ng


Há»‡ thá»‘ng giáº£i quyáº¿t bÃ i toÃ¡n váº­n hÃ nh cá»­a hÃ ng báº±ng cÃ¡ch cho phÃ©p ngÆ°á»i dÃ¹ng ná»™i bá»™ cáº¥u hÃ¬nh thÃ´ng tin chung, táº¡o sáº£n pháº©m, danh má»¥c, thÆ°Æ¡ng hiá»‡u, nhÃ  cung cáº¥p, kho hÃ ng, nháº­p hÃ ng, kiá»ƒm kÃª, bÃ¡n hÃ ng táº¡i quáº§y, ghi nháº­n Ä‘Æ¡n hÃ ng vÃ  theo dÃµi dá»¯ liá»‡u káº¿ toÃ¡n. KhÃ¡ch hÃ ng vÃ  nhÃ  cung cáº¥p khÃ´ng Ä‘Æ°á»£c xÃ¡c minh lÃ  nhÃ³m Ä‘Äƒng nháº­p trá»±c tiáº¿p trong há»‡ thá»‘ng hiá»‡n táº¡i, nhÆ°ng lÃ  Ä‘á»‘i tÆ°á»£ng dá»¯ liá»‡u chÃ­nh trong cÃ¡c luá»“ng bÃ¡n hÃ ng, cÃ´ng ná»£, thu chi vÃ  bÃ¡o cÃ¡o.


## 3.2. CÃ¡c nhÃ³m ngÆ°á»i dÃ¹ng

CÃ¡c nhÃ³m dÆ°á»›i Ä‘Ã¢y Ä‘Æ°á»£c xÃ¡c Ä‘á»‹nh tá»« route, middleware, controller, model, migration, view sidebar vÃ  tÃ i liá»‡u audit trong repository. Há»‡ thá»‘ng cÃ³ báº£ng `roles` vÃ  `role_permission`, tuy nhiÃªn luá»“ng phÃ¢n quyá»n Ä‘ang Ä‘Æ°á»£c xÃ¡c minh chá»§ yáº¿u qua `users.role_id`, route middleware `role:*`, `CheckLogin` vÃ  session riÃªng cá»§a SuperAdmin.

| STT | NhÃ³m ngÆ°á»i dÃ¹ng | Vai trÃ² trong há»‡ thá»‘ng | Khu vá»±c truy cáº­p | Chá»©c nÄƒng chÃ­nh | CÆ¡ cháº¿ xÃ¡c Ä‘á»‹nh quyá»n | Nguá»“n xÃ¡c minh |
| --: | --- | --- | --- | --- | --- | --- |
| 1 | Quáº£n trá»‹ viÃªn/chá»§ cá»­a hÃ ng | TÃ i khoáº£n quáº£n trá»‹ cáº¥p cao trong báº£ng `users`, Ä‘Æ°á»£c Ä‘iá»u hÆ°á»›ng vÃ o khu vá»±c admin sau Ä‘Äƒng nháº­p | `/admin`, dashboard, menu admin, cÃ¡c route Ä‘Æ°á»£c báº£o vá»‡ bá»Ÿi `auth` vÃ  `role:*` | Quáº£n lÃ½ sáº£n pháº©m, danh má»¥c, thÆ°Æ¡ng hiá»‡u, cÃ´ng ty/nhÃ  cung cáº¥p, khÃ¡ch hÃ ng, kho, nháº­p hÃ ng, Ä‘Æ¡n hÃ ng, bÃ¡o cÃ¡o, káº¿ toÃ¡n, cáº¥u hÃ¬nh vÃ  tÃ i khoáº£n quáº£n trá»‹ cáº¥p dÆ°á»›i | `AuthController@authenticate` Ä‘iá»u hÆ°á»›ng `role_id` 1 vá» `/admin`; `RoleMiddleware` cho `role_id` 1 Ä‘i qua cÃ¡c nhÃ³m route role; sidebar cÃ³ menu táº¡o chi nhÃ¡nh chá»‰ hiá»ƒn thá»‹ vá»›i `role_id === 1` | `routes/web.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Middleware/RoleMiddleware.php`, `resources/views/admin/layout/sidebar.blade.php`, `app/Models/User.php` |
| 2 | Quáº£n trá»‹ cá»­a hÃ ng/chi nhÃ¡nh | TÃ i khoáº£n quáº£n trá»‹ cáº¥p dÆ°á»›i do admin táº¡o, gáº¯n `manager_id` vÃ  `role_id` 2 | `/admin` vÃ  cÃ¡c route admin Ä‘Æ°á»£c middleware cho phÃ©p | Quáº£n lÃ½ dá»¯ liá»‡u váº­n hÃ nh theo pháº¡m vi tÃ i khoáº£n, gá»“m sáº£n pháº©m, nhÃ¢n viÃªn, khÃ¡ch hÃ ng, Ä‘Æ¡n hÃ ng, kho, bÃ¡o cÃ¡o vÃ  cáº¥u hÃ¬nh quan sÃ¡t Ä‘Æ°á»£c trong route/controller | `Admin\UserController@store` táº¡o user vá»›i `role_id = 2`; `RoleMiddleware` cho `role_id` 2 Ä‘i qua cÃ¡c nhÃ³m route role; dá»¯ liá»‡u thÆ°á»ng lá»c theo `manager_id` hoáº·c `user_id` trong controller/service | `app/Http/Controllers/Admin/UserController.php`, `app/Services/StoreService.php`, `app/Services/AdminService.php`, `routes/web.php`, `app/Http/Middleware/RoleMiddleware.php` |
| 3 | NhÃ¢n viÃªn bÃ¡n hÃ ng | TÃ i khoáº£n nhÃ¢n viÃªn trong báº£ng `users`, do admin táº¡o vá»›i `role_id` 3 | `/ban-hang`; má»™t sá»‘ route `/admin` nhÃ³m káº¿ toÃ¡n Ä‘ang gáº¯n `role:3` | TÃ¬m sáº£n pháº©m, tÃ¬m hoáº·c thÃªm khÃ¡ch hÃ ng, láº­p Ä‘Æ¡n bÃ¡n hÃ ng, xem lá»‹ch sá»­ Ä‘Æ¡n, táº¡o phiáº¿u kiá»ƒm kho tá»« khu vá»±c staff; theo route cÃ²n cÃ³ quyá»n vÃ o nhÃ³m cÃ´ng ná»£, tÃ i khoáº£n káº¿ toÃ¡n, tiá»n máº·t/ngÃ¢n hÃ ng | `Admin\EmployeeController@store` táº¡o user `role_id = 3`; `AuthController@authenticate` Ä‘iá»u hÆ°á»›ng `role_id` 3 vá» `/ban-hang`; `CheckLogin` cho role 1, 2, 3 vÃ o staff; route `/ban-hang` dÃ¹ng `role:3` | `app/Http/Controllers/Admin/EmployeeController.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Middleware/CheckLogin.php`, `routes/web.php`, `resources/views/Themes/layout_staff/header.blade.php` |
| 4 | NhÃ¢n viÃªn kho | CÃ³ nhÃ³m route kho gáº¯n `role:4`, nhÆ°ng chÆ°a xÃ¡c minh Ä‘Æ°á»£c luá»“ng táº¡o tÃ i khoáº£n hoáº·c Ä‘Äƒng nháº­p Ä‘áº§y Ä‘á»§ cho role nÃ y | `/admin/checkInventory`, `/admin/inventory`, `/admin/importproduct`, `/admin/storage` theo khai bÃ¡o route | Xem phiáº¿u kiá»ƒm kho, bÃ¡o cÃ¡o tá»“n kho, nháº­p hÃ ng, quáº£n lÃ½ kho hÃ ng náº¿u truy cáº­p Ä‘Æ°á»£c qua middleware | `routes/web.php` khai bÃ¡o `Route::middleware(['role:4'])`; chÆ°a tháº¥y seeder/controller táº¡o `role_id` 4 vÃ  `AuthController@authenticate` chÆ°a cÃ³ nhÃ¡nh Ä‘iá»u hÆ°á»›ng role 4 | `routes/web.php`, `app/Http/Middleware/RoleMiddleware.php`, `database/migrations/*roles*`, `app/Http/Controllers/AuthController.php` |
| 6 | NgÆ°á»i dÃ¹ng chÆ°a Ä‘Äƒng nháº­p | NgÆ°á»i truy cáº­p chÆ°a cÃ³ phiÃªn Ä‘Äƒng nháº­p | `/`, `/login`, `/super-dang-nhap`, `/check-account`; API `/api/user` yÃªu cáº§u Sanctum nÃªn khÃ´ng má»Ÿ tá»± do | Truy cáº­p form Ä‘Äƒng nháº­p hoáº·c kiá»ƒm tra tÃ i khoáº£n qua endpoint `check-account`; chÆ°a xÃ¡c minh luá»“ng Ä‘Äƒng kÃ½ public vÃ¬ cÃ¡c route Ä‘Äƒng kÃ½/OTP trong `routes/web.php` Ä‘ang comment | Middleware `guest` báº£o vá»‡ route login; route `/` redirect vá» `auth.login`; `CheckLoginSuperAdmin` redirect vá» form login SuperAdmin náº¿u thiáº¿u session | `routes/web.php`, `routes/api.php`, `resources/views/auth/login.blade.php`, `resources/views/superadmin/formlogin/index.blade.php` |

## 3.3. CÃ¡c chá»©c nÄƒng chÃ­nh

### Báº£ng chá»©c nÄƒng chi tiáº¿t

| STT | NhÃ³m chá»©c nÄƒng | Chá»©c nÄƒng | NgÆ°á»i sá»­ dá»¥ng | MÃ´ táº£ hoáº¡t Ä‘á»™ng | Tráº¡ng thÃ¡i hiá»‡n táº¡i | ThÃ nh pháº§n liÃªn quan | Nguá»“n xÃ¡c minh |
| --: | --- | --- | --- | --- | --- | --- | --- |
| 1 | Quáº£n trá»‹ há»‡ thá»‘ng | ÄÄƒng nháº­p, Ä‘Äƒng xuáº¥t vÃ  Ä‘iá»u hÆ°á»›ng theo vai trÃ² | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng, nhÃ¢n viÃªn bÃ¡n hÃ ng | NgÆ°á»i dÃ¹ng nháº­p email/máº­t kháº©u, há»‡ thá»‘ng kiá»ƒm tra tÃ i khoáº£n, tráº¡ng thÃ¡i vÃ  `role_id`, sau Ä‘Ã³ tráº£ Ä‘Æ°á»ng dáº«n `/admin` hoáº·c `/ban-hang`; Ä‘Äƒng xuáº¥t lÃ m má»›i session vÃ  quay vá» login | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `AuthController`, `LoginRequest`, middleware `auth`, `role`, `CheckLogin`, view `auth.login` | `routes/web.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Middleware` |
| 2 | Quáº£n trá»‹ há»‡ thá»‘ng | ÄÄƒng nháº­p vÃ  quáº£n lÃ½ phiÃªn SuperAdmin | SuperAdmin | SuperAdmin Ä‘Äƒng nháº­p táº¡i trang riÃªng, há»‡ thá»‘ng xÃ¡c thá»±c qua service vÃ  lÆ°u session `authSuper`; cÃ¡c route `/super-admin` kiá»ƒm tra session trÆ°á»›c khi cho truy cáº­p | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `SuperAdminController`, `SupperAdminService`, `CheckLoginSuperAdmin`, `SuperAdmin` model, view `superadmin.formlogin.index` | `routes/web.php`, `app/Http/Controllers/SuperAdmin/SuperAdminController.php`, `app/Services/SupperAdminService.php`, `app/Http/Middleware/CheckLoginSuperAdmin.php` |
| 3 | Quáº£n trá»‹ há»‡ thá»‘ng | Quáº£n lÃ½ tÃ i khoáº£n quáº£n trá»‹ cá»­a hÃ ng/chi nhÃ¡nh | Quáº£n trá»‹ viÃªn/chá»§ cá»­a hÃ ng | Quáº£n trá»‹ viÃªn táº¡o, xem, tÃ¬m kiáº¿m vÃ  cáº­p nháº­t tÃ i khoáº£n `role_id` 2; dá»¯ liá»‡u tÃ i khoáº£n gáº¯n `manager_id`, cÃ³ tráº¡ng thÃ¡i vÃ  cÃ³ thá»ƒ gá»­i email thÃ´ng tin tÃ i khoáº£n | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `Admin\UserController`, `users`, `roles`, `storages`, view `admin.employee.*`, mail `SendMailInfo` | `routes/web.php`, `app/Http/Controllers/Admin/UserController.php`, `app/Models/User.php`, `resources/views/admin/employee` |
| 4 | Quáº£n trá»‹ há»‡ thá»‘ng | Quáº£n lÃ½ nhÃ¢n viÃªn bÃ¡n hÃ ng | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng | NgÆ°á»i quáº£n trá»‹ táº¡o vÃ  cáº­p nháº­t nhÃ¢n viÃªn `role_id` 3, gáº¯n `manager_id`, kho, tráº¡ng thÃ¡i vÃ  thÃ´ng tin Ä‘Äƒng nháº­p; nhÃ¢n viÃªn dÃ¹ng tÃ i khoáº£n nÃ y Ä‘á»ƒ vÃ o khu vá»±c bÃ¡n hÃ ng | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `Admin\EmployeeController`, `users`, `storages`, view `admin.employee.*`, mail `SendMailInfo` | `routes/web.php`, `app/Http/Controllers/Admin/EmployeeController.php`, `resources/views/admin/employee` |
| 5 | Quáº£n trá»‹ há»‡ thá»‘ng | Cáº¥u hÃ¬nh thÃ´ng tin chung | Quáº£n trá»‹ viÃªn | NgÆ°á»i quáº£n trá»‹ má»Ÿ form cáº¥u hÃ¬nh vÃ  lÆ°u thÃ´ng tin cá»­a hÃ ng, logo, ngÃ¢n hÃ ng hoáº·c dá»¯ liá»‡u cáº¥u hÃ¬nh Ä‘ang Ä‘Æ°á»£c view/header/sidebar sá»­ dá»¥ng | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `ConfigController`, `Config`, `Bank`, view `admin.configuration.config`, helper upload áº£nh | `routes/web.php`, `app/Http/Controllers/Admin/ConfigController.php`, `app/Models/Config.php`, `resources/views/admin/configuration/config.blade.php` |
| 6 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Quáº£n lÃ½ sáº£n pháº©m | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng | NgÆ°á»i quáº£n trá»‹ táº¡o, cáº­p nháº­t, tÃ¬m kiáº¿m, phÃ¢n loáº¡i, táº£i áº£nh thumbnail vÃ  xuáº¥t danh sÃ¡ch sáº£n pháº©m; dá»¯ liá»‡u sáº£n pháº©m gáº¯n danh má»¥c, thÆ°Æ¡ng hiá»‡u, giÃ¡, sá»‘ lÆ°á»£ng, Ä‘Æ¡n vá»‹ vÃ  tráº¡ng thÃ¡i | CÃ³ má»™t pháº§n | `ProductController`, `ProductRequest`, `Product`, `Categories`, `Brand`, view `admin.product.*`, export Excel; route import sáº£n pháº©m cÃ³ khai bÃ¡o nhÆ°ng method xá»­ lÃ½ chÆ°a cÃ³ ná»™i dung | `routes/web.php`, `app/Http/Controllers/Admin/ProductController.php`, `app/Http/Requests/Product/ProductRequest.php`, `app/Models/Product.php`, `resources/views/admin/product` |
| 7 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Quáº£n lÃ½ danh má»¥c vÃ  thÆ°Æ¡ng hiá»‡u | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng | NgÆ°á»i quáº£n trá»‹ táº¡o, cáº­p nháº­t, tÃ¬m kiáº¿m danh má»¥c vÃ  thÆ°Æ¡ng hiá»‡u, táº£i logo thÆ°Æ¡ng hiá»‡u, dÃ¹ng cÃ¡c dá»¯ liá»‡u nÃ y Ä‘á»ƒ phÃ¢n nhÃ³m sáº£n pháº©m | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `CategorieController`, `BrandController`, `Categories`, `Brand`, view `admin.category.*`, `admin.brand.*` | `routes/web.php`, `app/Http/Controllers/Admin/CategorieController.php`, `app/Http/Controllers/Admin/BrandController.php`, `resources/views/admin/category`, `resources/views/admin/brand` |
| 8 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Quáº£n lÃ½ cÃ´ng ty vÃ  nhÃ  cung cáº¥p | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng, nhÃ³m kho | NgÆ°á»i dÃ¹ng quáº£n lÃ½ danh sÃ¡ch cÃ´ng ty/nhÃ  cung cáº¥p, tÃ¬m theo sá»‘ Ä‘iá»‡n thoáº¡i, thÃªm/sá»­a/xÃ³a ngÆ°á»i Ä‘áº¡i diá»‡n hoáº·c nhÃ  cung cáº¥p theo cÃ´ng ty; dá»¯ liá»‡u nÃ y Ä‘Æ°á»£c dÃ¹ng khi nháº­p hÃ ng vÃ  cÃ´ng ná»£ | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `CompanyController`, `SupplierController`, `Company`, `Supplier`, `supplier_debts`, view `admin.company.*`, `admin.supplier.*` | `routes/web.php`, `app/Http/Controllers/Admin/CompanyController.php`, `app/Http/Controllers/Admin/SupplierController.php`, `app/Models/Company.php`, `app/Models/Supplier.php` |
| 9 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Quáº£n lÃ½ kho hÃ ng vÃ  tá»“n theo kho | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng, nhÃ³m kho | NgÆ°á»i dÃ¹ng táº¡o, cáº­p nháº­t, tÃ¬m kiáº¿m kho vÃ  xem sáº£n pháº©m thuá»™c kho; tá»“n kho theo sáº£n pháº©m/kho Ä‘Æ°á»£c lÆ°u qua báº£ng trung gian `product_storage` | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `StorageController`, `StorageService`, `ProductStorageService`, `Storage`, `ProductStorage`, view `admin.storage.*` | `routes/web.php`, `app/Http/Controllers/Admin/StorageController.php`, `app/Services/ProductStorageService.php`, `app/Models/Storage.php`, `app/Models/ProductStorage.php` |
| 10 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Nháº­p hÃ ng vÃ  táº¡o phiáº¿u nháº­p | Quáº£n trá»‹ viÃªn, nhÃ³m kho | NgÆ°á»i dÃ¹ng chá»n sáº£n pháº©m, danh má»¥c, nhÃ  cung cáº¥p/cÃ´ng ty vÃ  kho; há»‡ thá»‘ng ghi dá»¯ liá»‡u táº¡m vÃ o báº£ng `import`, táº¡o phiáº¿u `import_coupon`, chi tiáº¿t `import_detail`, cáº­p nháº­t `products.quantity`, `product_storage`, quan há»‡ `company_product` vÃ  bÃºt toÃ¡n káº¿ toÃ¡n liÃªn quan | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `ImportProductController`, `importCouponController`, `Import`, `ImportCoupon`, `ImportDetail`, `ProductStorageService`, `CompanyProductService`, view `admin.Importproduct.*` | `routes/web.php`, `app/Http/Controllers/Admin/ImportProductController.php`, `app/Http/Controllers/Admin/importCouponController.php`, `app/Models/ImportCoupon.php`, `database/migrations/*import*` |
| 11 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Kiá»ƒm kÃª kho | NhÃ¢n viÃªn bÃ¡n hÃ ng, nhÃ³m kho, quáº£n trá»‹ viÃªn | NhÃ¢n viÃªn chá»n sáº£n pháº©m kiá»ƒm kÃª vÃ o báº£ng táº¡m `warehouse`, nháº­p sá»‘ lÆ°á»£ng thá»±c táº¿, há»‡ thá»‘ng tÃ­nh chÃªnh lá»‡ch vÃ  khi gá»­i sáº½ táº¡o phiáº¿u `check_inventory` cÃ¹ng chi tiáº¿t `check_detail`; admin cÃ³ mÃ n xem, lá»c vÃ  xem chi tiáº¿t phiáº¿u | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `Staff\WareHomeController`, `Staff\CheckInventoryController`, `Admin\CheckInventoryController`, `warehome`, `CheckInventory`, `CheckDetail`, view `Themes.pages.Inventory.*`, `admin.check.*` | `routes/web.php`, `app/Http/Controllers/Staff/WareHomeController.php`, `app/Http/Controllers/Staff/CheckInventoryController.php`, `app/Http/Controllers/Admin/CheckInventoryController.php`, `database/migrations/*check*`, `database/migrations/*warehouse*` |
| 12 | PhÃ­a ngÆ°á»i dÃ¹ng ná»™i bá»™ | BÃ¡n hÃ ng táº¡i quáº§y/POS | NhÃ¢n viÃªn bÃ¡n hÃ ng | NhÃ¢n viÃªn vÃ o `/ban-hang`, tÃ¬m sáº£n pháº©m vÃ  khÃ¡ch hÃ ng báº±ng Ajax, thÃªm khÃ¡ch hÃ ng náº¿u cáº§n, láº­p giá» hÃ ng trÃªn giao diá»‡n, gá»­i Ä‘Æ¡n Ä‘áº¿n server; há»‡ thá»‘ng kiá»ƒm tra sáº£n pháº©m, tá»“n tá»•ng, tá»•ng tiá»n, giáº£m giÃ¡, táº¡o Ä‘Æ¡n, chi tiáº¿t Ä‘Æ¡n vÃ  bÃºt toÃ¡n theo phÆ°Æ¡ng thá»©c thanh toÃ¡n | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `Staff\ProductController`, `Staff\ClientController`, `Staff\OrderController`, `Product`, `Client`, `Order`, `OrderDetail`, `Transaction`, `TransactionEntry`, view `Themes.pages.layout_staff.index` | `routes/web.php`, `app/Http/Controllers/Staff/ProductController.php`, `app/Http/Controllers/Staff/ClientController.php`, `app/Http/Controllers/Staff/OrderController.php`, `resources/views/Themes/pages/layout_staff/index.blade.php` |
| 13 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Quáº£n lÃ½ Ä‘Æ¡n hÃ ng | Quáº£n trá»‹ viÃªn, nhÃ¢n viÃªn bÃ¡n hÃ ng | Admin xem danh sÃ¡ch vÃ  chi tiáº¿t Ä‘Æ¡n; nhÃ¢n viÃªn xem lá»‹ch sá»­ Ä‘Æ¡n, lá»c theo ngÃ y hoáº·c mÃ£/khÃ¡ch hÃ ng; dá»¯ liá»‡u Ä‘Æ¡n láº¥y tá»« `orders` vÃ  `order_details` | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `Admin\OrderController`, `Staff\OrderController`, `Order`, `OrderDetail`, view `admin.order.*`, `Themes.pages.order.*` | `routes/web.php`, `app/Http/Controllers/Admin/OrderController.php`, `app/Http/Controllers/Staff/OrderController.php`, `app/Models/Order.php`, `app/Models/OrderDetail.php` |
| 14 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Quáº£n lÃ½ khÃ¡ch hÃ ng vÃ  nhÃ³m khÃ¡ch hÃ ng | Quáº£n trá»‹ viÃªn, nhÃ¢n viÃªn bÃ¡n hÃ ng | Admin tÃ¬m kiáº¿m, xem, sá»­a, xÃ³a vÃ  xuáº¥t danh sÃ¡ch khÃ¡ch hÃ ng; nhÃ¢n viÃªn tÃ¬m khÃ¡ch hÃ ng hoáº·c thÃªm khÃ¡ch má»›i trong luá»“ng bÃ¡n hÃ ng; nhÃ³m khÃ¡ch hÃ ng Ä‘Æ°á»£c hiá»ƒn thá»‹ trong khu vá»±c quáº£n trá»‹ | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `Admin\ClientController`, `Staff\ClientController`, `ClientService`, `ClientGroupService`, `Client`, `ClientGroup`, export `ClientsExport`, view `admin.client.*` | `routes/web.php`, `app/Http/Controllers/Admin/ClientController.php`, `app/Http/Controllers/Staff/ClientController.php`, `app/Services/ClientService.php`, `app/Models/Client.php` |
| 15 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Phiáº¿u thu vÃ  phiáº¿u chi | Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route | NgÆ°á»i dÃ¹ng láº­p phiáº¿u thu cho khÃ¡ch hoáº·c phiáº¿u chi cho nhÃ  cung cáº¥p, ghi chi tiáº¿t phiáº¿u vÃ  cáº­p nháº­t dá»¯ liá»‡u cÃ´ng ná»£ liÃªn quan trong cÃ¡c báº£ng thu/chi vÃ  debt detail | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `ReceiptController`, `ExpenseController`, `Receipts`, `ReceiptDetail`, `Expense`, `ExpenseDetail`, `ClientDebtsDetail`, `SupplierDebtsDetail`, view `admin.quanlythuchi.*` | `routes/web.php`, `app/Http/Controllers/Admin/ReceiptController.php`, `app/Http/Controllers/Admin/ExpenseController.php`, `database/migrations/*receipt*`, `database/migrations/*expense*` |
| 16 | Quáº£n lÃ½ nghiá»‡p vá»¥ | CÃ´ng ná»£ khÃ¡ch hÃ ng, cÃ´ng ná»£ nhÃ  cung cáº¥p vÃ  cÃ´ng ná»£ Ä‘áº§u ká»³ | Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route | NgÆ°á»i dÃ¹ng xem cÃ´ng ná»£ khÃ¡ch hÃ ng/nhÃ  cung cáº¥p theo ká»³, lá»c theo Ä‘á»‘i tÆ°á»£ng vÃ  táº¡o cÃ´ng ná»£ Ä‘áº§u ká»³; há»‡ thá»‘ng tá»•ng há»£p phÃ¡t sinh tá»« `transactions` vÃ  `transaction_entries` gáº¯n vá»›i `clients` hoáº·c `suppliers` | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `DebtController`, `Transaction`, `TransactionEntry`, `Client`, `Supplier`, view `admin.debt.*` | `routes/web.php`, `app/Http/Controllers/Admin/DebtController.php`, `app/Models/Transaction.php`, `app/Models/TransactionEntry.php`, `resources/views/admin/debt` |
| 17 | Quáº£n lÃ½ nghiá»‡p vá»¥ | TÃ i khoáº£n káº¿ toÃ¡n | Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route | NgÆ°á»i dÃ¹ng táº¡o, cáº­p nháº­t, xÃ³a cÃ³ kiá»ƒm soÃ¡t, tÃ¬m kiáº¿m tÃ i khoáº£n káº¿ toÃ¡n vÃ  xem báº£ng tá»•ng há»£p sá»‘ dÆ° theo cÃ¢y tÃ i khoáº£n | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `AccountController`, `Account`, view `admin.account.*`, `transactions`, `transaction_entries` | `routes/web.php`, `app/Http/Controllers/Admin/AccountController.php`, `app/Models/Account.php`, `resources/views/admin/account` |
| 18 | Quáº£n lÃ½ nghiá»‡p vá»¥ | Giao dá»‹ch tiá»n máº·t, ngÃ¢n hÃ ng vÃ  bÃºt toÃ¡n | Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route | NgÆ°á»i dÃ¹ng láº­p phiáº¿u tiá»n máº·t/ngÃ¢n hÃ ng, chá»n Ä‘á»‘i tÆ°á»£ng khÃ¡ch hÃ ng hoáº·c nhÃ  cung cáº¥p, chá»n tÃ i khoáº£n tiá»n, nháº­p sá»‘ tiá»n, Ä‘Ã­nh kÃ¨m chá»©ng tá»« vÃ  há»‡ thá»‘ng táº¡o cÃ¡c dÃ²ng bÃºt toÃ¡n ná»£/cÃ³; mÃ n bÃºt toÃ¡n hiá»ƒn thá»‹ danh sÃ¡ch giao dá»‹ch káº¿ toÃ¡n | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `CashTransactionController`, `BankTransactionController`, `JournalEntryController`, `Transaction`, `TransactionEntry`, `Account`, view `admin.cash-bank.*`, `admin.journal-entries.*` | `routes/web.php`, `app/Http/Controllers/Admin/CashTransactionController.php`, `app/Http/Controllers/Admin/BankTransactionController.php`, `app/Http/Controllers/Admin/JournalEntryController.php` |
| 19 | BÃ¡o cÃ¡o vÃ  thá»‘ng kÃª | Dashboard quáº£n trá»‹ | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng | Dashboard tá»•ng há»£p sá»‘ liá»‡u bÃ¡n hÃ ng, Ä‘Æ¡n hÃ ng, doanh thu, tá»“n kho, sáº£n pháº©m bÃ¡n cháº¡y, sáº£n pháº©m sáº¯p háº¿t hÃ ng, Ä‘Æ¡n má»›i vÃ  khÃ¡ch má»›i; cÃ³ cÃ¡c route Ajax theo ngÃ y/thÃ¡ng/nÄƒm Ä‘Æ°á»£c khai bÃ¡o | CÃ³ má»™t pháº§n | `DashboardController`, `DashboardService`, `OrderService`, `Order`, `OrderDetail`, `Product`, `Client`, view `welcome.blade.php` | `routes/web.php`, `app/Http/Controllers/Admin/DashboardController.php`, `app/Services/DashboardService.php`, `resources/views/welcome.blade.php` |
| 20 | BÃ¡o cÃ¡o vÃ  thá»‘ng kÃª | BÃ¡o cÃ¡o tá»“n kho, lá»£i nhuáº­n, Ä‘Æ¡n hÃ ng/ngÃ y nháº­p hÃ ng/ngÃ y vÃ  cÃ´ng ná»£ | Quáº£n trá»‹ viÃªn, nhÃ³m kho, káº¿ toÃ¡n theo route | NgÆ°á»i dÃ¹ng xem bÃ¡o cÃ¡o tá»“n kho theo kho, lá»£i nhuáº­n theo bá»™ lá»c, bÃ¡o cÃ¡o Ä‘Æ¡n hÃ ng/nháº­p hÃ ng theo ngÃ y, bÃ¡o cÃ¡o cÃ´ng ná»£ vÃ  in/xuáº¥t má»™t sá»‘ biá»ƒu máº«u PDF | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `ReportController`, `ReportdebtController`, `DailyReportController`, `ProfitService`, `DailyReportService`, `ProductStorageService`, view `admin.inventory.*`, `admin.profit.*`, `admin.report.*` | `routes/web.php`, `app/Http/Controllers/Admin/ReportController.php`, `app/Http/Controllers/Admin/DailyReportController.php`, `app/Http/Controllers/Admin/ReportdebtController.php`, `app/Services/ProfitService.php` |
| 21 | Quáº£n trá»‹ há»‡ thá»‘ng | Quáº£n lÃ½ store á»Ÿ SuperAdmin | SuperAdmin | SuperAdmin xem danh sÃ¡ch store/tÃ i khoáº£n chá»§, tÃ¬m theo sá»‘ Ä‘iá»‡n thoáº¡i, xem chi tiáº¿t vÃ  xÃ³a store theo route hiá»‡n cÃ³ | ÄÃ£ cÃ³ luá»“ng xá»­ lÃ½ | `StoreController`, `StoreService`, `SignUpService`, `User`, view `superadmin.store.*` | `routes/web.php`, `app/Http/Controllers/SuperAdmin/StoreController.php`, `app/Services/StoreService.php`, `resources/views/superadmin/store` |
| 24 | Chá»©c nÄƒng tÃ­ch há»£p | Email, file, Excel, PDF vÃ  QR | Quáº£n trá»‹ viÃªn, nhÃ¢n viÃªn, SuperAdmin | Há»‡ thá»‘ng gá»­i email thÃ´ng tin tÃ i khoáº£n, xá»­ lÃ½ upload áº£nh/file, xuáº¥t Excel khÃ¡ch hÃ ng/sáº£n pháº©m, xuáº¥t PDF má»™t sá»‘ bÃ¡o cÃ¡o/chá»©ng tá»« vÃ  táº¡o QR trong luá»“ng giao dá»‹ch | CÃ³ má»™t pháº§n | `app/Mail`, `app/Exports`, helper upload áº£nh, `ProductController@export`, `ClientController@export`, `TransactionController@generateQrCode`, DomPDF, Maatwebsite Excel, PhpSpreadsheet | `composer.json`, `app/Mail`, `app/Exports`, `app/Helpers/helper.php`, `app/Http/Controllers/Admin/ProductController.php`, `app/Http/Controllers/Admin/ClientController.php`, `app/Http/Controllers/Admin/TransactionController.php` |
| 25 | Chá»©c nÄƒng tÃ­ch há»£p | API/Ajax ná»™i bá»™ | NgÆ°á»i dÃ¹ng Ä‘Ã£ Ä‘Äƒng nháº­p, giao diá»‡n admin/staff/superadmin | `routes/api.php` cÃ³ route `/api/user` qua `auth:sanctum`; nhiá»u mÃ n web dÃ¹ng Ajax Ä‘á»ƒ tÃ¬m kiáº¿m, render báº£ng, láº¥y sáº£n pháº©m/khÃ¡ch hÃ ng, táº¡o Ä‘Æ¡n, lá»c bÃ¡o cÃ¡o vÃ  cáº­p nháº­t tráº¡ng thÃ¡i | CÃ³ má»™t pháº§n | `routes/api.php`, Laravel Sanctum, cÃ¡c route web tráº£ JSON, `ApiResponse`, helper response | `routes/api.php`, `routes/web.php`, `app/Http/Responses/ApiResponse.php`, `app/Http/Controllers/Staff/ProductController.php`, `app/Http/Controllers/Staff/OrderController.php`, `app/Http/Controllers/Admin/ReportController.php` |

### Báº£ng tá»•ng há»£p module

| Module | Sá»‘ chá»©c nÄƒng Ä‘Æ°á»£c xÃ¡c Ä‘á»‹nh | Vai trÃ² sá»­ dá»¥ng chÃ­nh | Má»¥c Ä‘Ã­ch cá»§a module |
| --- | --: | --- | --- |
| Quáº£n trá»‹ há»‡ thá»‘ng | 6 | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng, SuperAdmin | Quáº£n lÃ½ phiÃªn Ä‘Äƒng nháº­p, tÃ i khoáº£n váº­n hÃ nh, cáº¥u hÃ¬nh vÃ  store cáº¥p há»‡ thá»‘ng |
| Sáº£n pháº©m, Ä‘á»‘i tÃ¡c vÃ  dá»¯ liá»‡u ná»n | 4 | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng | Táº¡o dá»¯ liá»‡u ná»n cho bÃ¡n hÃ ng, nháº­p hÃ ng, khÃ¡ch hÃ ng vÃ  nhÃ  cung cáº¥p |
| Kho, nháº­p hÃ ng vÃ  kiá»ƒm kÃª | 3 | Quáº£n trá»‹ viÃªn, nhÃ³m kho, nhÃ¢n viÃªn bÃ¡n hÃ ng | Ghi nháº­n hÃ ng nháº­p, tá»“n theo kho vÃ  káº¿t quáº£ kiá»ƒm kÃª |
| BÃ¡n hÃ ng vÃ  chÄƒm sÃ³c khÃ¡ch hÃ ng táº¡i quáº§y | 3 | NhÃ¢n viÃªn bÃ¡n hÃ ng, quáº£n trá»‹ viÃªn | TÃ¬m sáº£n pháº©m/khÃ¡ch hÃ ng, táº¡o Ä‘Æ¡n hÃ ng vÃ  theo dÃµi lá»‹ch sá»­ bÃ¡n |
| Thu chi, cÃ´ng ná»£ vÃ  káº¿ toÃ¡n | 4 | Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route | Ghi nháº­n phiáº¿u thu/chi, cÃ´ng ná»£, tÃ i khoáº£n káº¿ toÃ¡n, giao dá»‹ch vÃ  bÃºt toÃ¡n |
| BÃ¡o cÃ¡o vÃ  thá»‘ng kÃª | 2 | Quáº£n trá»‹ viÃªn, nhÃ³m kho, káº¿ toÃ¡n theo route | Tá»•ng há»£p sá»‘ liá»‡u dashboard, tá»“n kho, lá»£i nhuáº­n, ngÃ y bÃ¡n/nháº­p vÃ  cÃ´ng ná»£ |

## 3.4. Quy trÃ¬nh nghiá»‡p vá»¥ chÃ­nh

### Quy trÃ¬nh 1: Thiáº¿t láº­p ngÆ°á»i dÃ¹ng váº­n hÃ nh vÃ  truy cáº­p theo vai trÃ²

**Má»¥c Ä‘Ã­ch:**
Thiáº¿t láº­p tÃ i khoáº£n ná»™i bá»™ vÃ  Ä‘Æ°a ngÆ°á»i dÃ¹ng vÃ o Ä‘Ãºng khu vá»±c thao tÃ¡c theo vai trÃ² Ä‘ang Ä‘Æ°á»£c lÆ°u trong há»‡ thá»‘ng.

**Vai trÃ² tham gia:**
Quáº£n trá»‹ viÃªn/chá»§ cá»­a hÃ ng, quáº£n trá»‹ cá»­a hÃ ng/chi nhÃ¡nh, nhÃ¢n viÃªn bÃ¡n hÃ ng, SuperAdmin.

**Äiá»u kiá»‡n báº¯t Ä‘áº§u:**
NgÆ°á»i dÃ¹ng cÃ³ tÃ i khoáº£n trong `users` hoáº·c `super_admins`; vá»›i tÃ i khoáº£n admin/staff, route login public Ä‘ang hoáº¡t Ä‘á»™ng.

**Luá»“ng thá»±c hiá»‡n:**

1. Quáº£n trá»‹ viÃªn vÃ o khu vá»±c tÃ i khoáº£n quáº£n trá»‹ hoáº·c nhÃ¢n viÃªn Ä‘á»ƒ táº¡o user má»›i, nháº­p tÃªn, email, sá»‘ Ä‘iá»‡n thoáº¡i, máº­t kháº©u, tráº¡ng thÃ¡i vÃ  kho náº¿u cÃ³.
2. Há»‡ thá»‘ng lÆ°u tÃ i khoáº£n vÃ o báº£ng `users`, gáº¯n `role_id`, `manager_id` vÃ  gá»­i email thÃ´ng tin tÃ i khoáº£n qua mail class tÆ°Æ¡ng á»©ng.
3. NgÆ°á»i dÃ¹ng Ä‘Äƒng nháº­p táº¡i `/login`; `AuthController@authenticate` kiá»ƒm tra thÃ´ng tin Ä‘Äƒng nháº­p, tráº¡ng thÃ¡i tÃ i khoáº£n vÃ  `role_id`.
4. Náº¿u `role_id` lÃ  1 hoáº·c 2, há»‡ thá»‘ng tráº£ Ä‘Æ°á»ng dáº«n `/admin`; náº¿u `role_id` lÃ  3, há»‡ thá»‘ng tráº£ Ä‘Æ°á»ng dáº«n `/ban-hang`.
5. CÃ¡c route sau Ä‘Äƒng nháº­p tiáº¿p tá»¥c Ä‘Æ°á»£c kiá»ƒm soÃ¡t báº±ng middleware `auth`, `role`, `CheckLogin` hoáº·c session `authSuper` vá»›i SuperAdmin.

**Thay Ä‘á»•i tráº¡ng thÃ¡i dá»¯ liá»‡u:**

`ChÆ°a cÃ³ tÃ i khoáº£n -> TÃ i khoáº£n users/super_admins Ä‘Æ°á»£c táº¡o -> PhiÃªn Ä‘Äƒng nháº­p há»£p lá»‡ -> Truy cáº­p khu vá»±c theo vai trÃ²`

**Káº¿t quáº£:**
NgÆ°á»i dÃ¹ng ná»™i bá»™ cÃ³ thá»ƒ thao tÃ¡c trong khu vá»±c Ä‘Æ°á»£c xÃ¡c Ä‘á»‹nh bá»Ÿi route vÃ  middleware; SuperAdmin cÃ³ phiÃªn riÃªng trong khu vá»±c `/super-admin`.

**Dá»¯ liá»‡u vÃ  thÃ nh pháº§n liÃªn quan:**

* Báº£ng database: `users`, `roles`, `super_admins`, `user_info`, `storages`
* Route: `/login`, `/admin/users`, `/admin/employees`, `/admin/logout`, `/super-dang-nhap`, `/super-admin/logout`
* Controller: `AuthController`, `Admin\UserController`, `Admin\EmployeeController`, `SuperAdmin\SuperAdminController`
* Service hoáº·c Repository: `SupperAdminService`, `AdminService`, `UserService`
* View hoáº·c API: `auth.login`, `admin.employee.*`, `superadmin.formlogin.index`
* Notification hoáº·c Job: `SendMailInfo` mail; chÆ°a tháº¥y job ná»n riÃªng cho táº¡o tÃ i khoáº£n

**Nguá»“n xÃ¡c minh:**
`routes/web.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/Admin/UserController.php`, `app/Http/Controllers/Admin/EmployeeController.php`, `app/Http/Controllers/SuperAdmin/SuperAdminController.php`, `app/Http/Middleware`, `app/Mail/SendMailInfo.php`, `app/Models/User.php`, `app/Models/SuperAdmin.php`.

### Quy trÃ¬nh 2: Nháº­p hÃ ng vÃ  cáº­p nháº­t tá»“n kho

**Má»¥c Ä‘Ã­ch:**
Ghi nháº­n hÃ ng nháº­p tá»« cÃ´ng ty/nhÃ  cung cáº¥p, táº¡o phiáº¿u nháº­p, cáº­p nháº­t tá»“n kho theo sáº£n pháº©m/kho vÃ  táº¡o dá»¯ liá»‡u káº¿ toÃ¡n liÃªn quan.

**Vai trÃ² tham gia:**
Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng, nhÃ³m kho theo route.

**Äiá»u kiá»‡n báº¯t Ä‘áº§u:**
NgÆ°á»i dÃ¹ng Ä‘Ã£ Ä‘Äƒng nháº­p, cÃ³ sáº£n pháº©m, cÃ´ng ty/nhÃ  cung cáº¥p vÃ  kho Ä‘á»ƒ chá»n trÃªn form nháº­p hÃ ng.

**Luá»“ng thá»±c hiá»‡n:**

1. NgÆ°á»i dÃ¹ng má»Ÿ mÃ n nháº­p hÃ ng táº¡i `/admin/importproduct/add`.
2. Há»‡ thá»‘ng táº£i danh sÃ¡ch sáº£n pháº©m, danh má»¥c, cÃ´ng ty/nhÃ  cung cáº¥p vÃ  kho Ä‘á»ƒ hiá»ƒn thá»‹ trÃªn form.
3. NgÆ°á»i dÃ¹ng thÃªm tá»«ng sáº£n pháº©m hoáº·c thÃªm theo danh má»¥c; há»‡ thá»‘ng ghi cÃ¡c dÃ²ng táº¡m vÃ o báº£ng `import`, cáº­p nháº­t sá»‘ lÆ°á»£ng, giÃ¡ vÃ  tá»•ng tiá»n qua cÃ¡c route Ajax.
4. Khi táº¡o phiáº¿u, controller nháº­n nhÃ  cung cáº¥p/cÃ´ng ty, tá»•ng tiá»n, sá»‘ tiá»n Ä‘Ã£ thanh toÃ¡n vÃ  kho nháº­p.
5. Há»‡ thá»‘ng táº¡o hoáº·c cáº­p nháº­t cÃ´ng ná»£/phiáº¿u chi liÃªn quan náº¿u cÃ³ pháº§n chÆ°a thanh toÃ¡n hoáº·c Ä‘Ã£ thanh toÃ¡n.
6. Há»‡ thá»‘ng táº¡o `import_coupon`, táº¡o cÃ¡c dÃ²ng `import_detail`, tÄƒng `products.quantity`, tÄƒng `product_storage.quantity` theo kho vÃ  cáº­p nháº­t liÃªn káº¿t `company_product`.
7. Sau khi lÆ°u phiáº¿u, dá»¯ liá»‡u táº¡m trong báº£ng `import` Ä‘Æ°á»£c xÃ³a vÃ  há»‡ thá»‘ng táº¡o bÃºt toÃ¡n nháº­p hÃ ng náº¿u tÃ¬m Ä‘Æ°á»£c tÃ i khoáº£n káº¿ toÃ¡n liÃªn quan.

**Thay Ä‘á»•i tráº¡ng thÃ¡i dá»¯ liá»‡u:**

`DÃ²ng nháº­p táº¡m -> Phiáº¿u nháº­p Ä‘Ã£ táº¡o -> Chi tiáº¿t nháº­p Ä‘Ã£ táº¡o -> Tá»“n sáº£n pháº©m/kho tÄƒng -> BÃºt toÃ¡n nháº­p hÃ ng Ä‘Æ°á»£c ghi nháº­n`

**Káº¿t quáº£:**
Phiáº¿u nháº­p cÃ³ thá»ƒ xem láº¡i á»Ÿ danh sÃ¡ch/chi tiáº¿t nháº­p hÃ ng; tá»“n kho vÃ  cÃ¡c báº£ng káº¿ toÃ¡n/cÃ´ng ná»£ liÃªn quan cÃ³ dá»¯ liá»‡u phÃ¡t sinh theo phiáº¿u.

**Dá»¯ liá»‡u vÃ  thÃ nh pháº§n liÃªn quan:**

* Báº£ng database: `import`, `import_coupon`, `import_detail`, `products`, `product_storage`, `company_product`, `companies`, `supplier_debts`, `expense`, `transactions`, `transaction_entries`
* Route: `/admin/importproduct`, `/admin/importproduct/add`, `/admin/importproduct/import/*`, `/admin/importproduct/importCoupon`
* Controller: `Admin\ImportProductController`, `Admin\importCouponController`
* Service hoáº·c Repository: `ImportProductService`, `ProductStorageService`, `CompanyProductService`, `DebtNccService`, `ExpenseService`
* View hoáº·c API: `admin.Importproduct.index`, `admin.Importproduct.add`, `admin.Importproduct.detail`, Ajax import routes
* Notification hoáº·c Job: ChÆ°a xÃ¡c minh job/notification cho luá»“ng nháº­p hÃ ng

**Nguá»“n xÃ¡c minh:**
`routes/web.php`, `app/Http/Controllers/Admin/ImportProductController.php`, `app/Http/Controllers/Admin/importCouponController.php`, `app/Services/ProductStorageService.php`, `app/Models/Import.php`, `app/Models/ImportCoupon.php`, `app/Models/ImportDetail.php`, `resources/views/admin/Importproduct`.

### Quy trÃ¬nh 3: BÃ¡n hÃ ng táº¡i quáº§y vÃ  ghi nháº­n Ä‘Æ¡n hÃ ng

**Má»¥c Ä‘Ã­ch:**
Cho phÃ©p nhÃ¢n viÃªn bÃ¡n hÃ ng tÃ¬m sáº£n pháº©m, chá»n hoáº·c thÃªm khÃ¡ch hÃ ng, táº¡o Ä‘Æ¡n, ghi nháº­n phÆ°Æ¡ng thá»©c thanh toÃ¡n vÃ  táº¡o dá»¯ liá»‡u káº¿ toÃ¡n phÃ¡t sinh.

**Vai trÃ² tham gia:**
NhÃ¢n viÃªn bÃ¡n hÃ ng, quáº£n trá»‹ viÃªn/quáº£n trá»‹ cá»­a hÃ ng khi truy cáº­p Ä‘Æ°á»£c khu vá»±c bÃ¡n hÃ ng.

**Äiá»u kiá»‡n báº¯t Ä‘áº§u:**
NgÆ°á»i dÃ¹ng Ä‘Ã£ Ä‘Äƒng nháº­p, cÃ³ sáº£n pháº©m Ä‘ang hoáº¡t Ä‘á»™ng, cÃ³ khÃ¡ch hÃ ng hoáº·c thÃ´ng tin khÃ¡ch hÃ ng má»›i Ä‘á»ƒ nháº­p trÃªn giao diá»‡n.

**Luá»“ng thá»±c hiá»‡n:**

1. NhÃ¢n viÃªn vÃ o `/ban-hang`; há»‡ thá»‘ng táº£i cáº¥u hÃ¬nh, nhÃ³m khÃ¡ch hÃ ng vÃ  mÃ n POS.
2. NhÃ¢n viÃªn tÃ¬m sáº£n pháº©m qua endpoint Ajax, chá»n sáº£n pháº©m, sá»‘ lÆ°á»£ng vÃ  giÃ¡ trá»‹ giáº£m giÃ¡ trÃªn giao diá»‡n.
3. NhÃ¢n viÃªn tÃ¬m khÃ¡ch hÃ ng hiá»‡n cÃ³ hoáº·c thÃªm khÃ¡ch hÃ ng má»›i báº±ng modal.
4. Khi thanh toÃ¡n, giao diá»‡n gá»­i danh sÃ¡ch sáº£n pháº©m, tá»•ng tiá»n, giáº£m giÃ¡, thÃ´ng tin khÃ¡ch hÃ ng vÃ  phÆ°Æ¡ng thá»©c thanh toÃ¡n Ä‘áº¿n `POST /ban-hang/order`.
5. Server kiá»ƒm tra sáº£n pháº©m tá»“n táº¡i, tráº¡ng thÃ¡i sáº£n pháº©m, tá»“n tá»•ng, dá»¯ liá»‡u khÃ¡ch hÃ ng vÃ  tá»± tÃ­nh láº¡i tá»•ng tiá»n.
6. Há»‡ thá»‘ng táº¡o báº£n ghi `orders`, táº¡o nhiá»u dÃ²ng `order_details`, giáº£m `products.quantity` vÃ  táº¡o `transactions` cÃ¹ng `transaction_entries` theo `cash`, `bank_transfer` hoáº·c `debt`.
7. NhÃ¢n viÃªn xem láº¡i lá»‹ch sá»­ Ä‘Æ¡n á»Ÿ `/ban-hang/order`; admin xem danh sÃ¡ch/chi tiáº¿t á»Ÿ `/admin/order`.

**Thay Ä‘á»•i tráº¡ng thÃ¡i dá»¯ liá»‡u:**

`Giá» hÃ ng trÃªn giao diá»‡n -> ÄÆ¡n hÃ ng Ä‘Ã£ táº¡o -> Chi tiáº¿t Ä‘Æ¡n hÃ ng Ä‘Ã£ táº¡o -> Tá»“n tá»•ng sáº£n pháº©m giáº£m -> BÃºt toÃ¡n thanh toÃ¡n/cÃ´ng ná»£ Ä‘Æ°á»£c ghi nháº­n`

**Káº¿t quáº£:**
ÄÆ¡n hÃ ng Ä‘Æ°á»£c lÆ°u, cÃ³ chi tiáº¿t sáº£n pháº©m, thÃ´ng tin khÃ¡ch hÃ ng, tá»•ng tiá»n, phÆ°Æ¡ng thá»©c thanh toÃ¡n vÃ  dá»¯ liá»‡u káº¿ toÃ¡n liÃªn quan.

**Dá»¯ liá»‡u vÃ  thÃ nh pháº§n liÃªn quan:**

* Báº£ng database: `products`, `clients`, `orders`, `order_details`, `transactions`, `transaction_entries`, `accounts`
* Route: `/ban-hang`, `/ban-hang/product`, `/ban-hang/get-clients`, `/ban-hang/clients/add`, `/ban-hang/order`, `/admin/order`
* Controller: `Staff\ProductController`, `Staff\ClientController`, `Staff\OrderController`, `Admin\OrderController`
* Service hoáº·c Repository: `ProductService`, `ClientService`, `ClientGroupService`
* View hoáº·c API: `Themes.pages.layout_staff.index`, `Themes.pages.order.index`, `admin.order.*`, Ajax staff routes
* Notification hoáº·c Job: ChÆ°a xÃ¡c minh notification/job trong luá»“ng bÃ¡n hÃ ng

**Nguá»“n xÃ¡c minh:**
`routes/web.php`, `app/Http/Controllers/Staff/ProductController.php`, `app/Http/Controllers/Staff/ClientController.php`, `app/Http/Controllers/Staff/OrderController.php`, `app/Http/Controllers/Admin/OrderController.php`, `app/Models/Order.php`, `app/Models/OrderDetail.php`, `resources/views/Themes/pages/layout_staff/index.blade.php`.

### Quy trÃ¬nh 4: Kiá»ƒm kÃª kho

**Má»¥c Ä‘Ã­ch:**
Ghi nháº­n káº¿t quáº£ kiá»ƒm kÃª thá»±c táº¿, so sÃ¡nh vá»›i sá»‘ lÆ°á»£ng há»‡ thá»‘ng vÃ  lÆ°u phiáº¿u kiá»ƒm kho Ä‘á»ƒ admin hoáº·c nhÃ³m kho theo dÃµi.

**Vai trÃ² tham gia:**
NhÃ¢n viÃªn bÃ¡n hÃ ng, nhÃ³m kho theo route, quáº£n trá»‹ viÃªn.

**Äiá»u kiá»‡n báº¯t Ä‘áº§u:**
NgÆ°á»i dÃ¹ng Ä‘Äƒng nháº­p vÃ  cÃ³ danh sÃ¡ch sáº£n pháº©m Ä‘á»ƒ chá»n vÃ o mÃ n kiá»ƒm kÃª.

**Luá»“ng thá»±c hiá»‡n:**

1. NgÆ°á»i dÃ¹ng má»Ÿ danh sÃ¡ch kiá»ƒm kho hoáº·c form thÃªm kiá»ƒm kho trong khu vá»±c staff.
2. NgÆ°á»i dÃ¹ng thÃªm sáº£n pháº©m vÃ o báº£ng táº¡m `warehouse` theo tá»«ng sáº£n pháº©m hoáº·c theo danh má»¥c.
3. NgÆ°á»i dÃ¹ng nháº­p sá»‘ lÆ°á»£ng thá»±c táº¿; há»‡ thá»‘ng tÃ­nh chÃªnh lá»‡ch sá»‘ lÆ°á»£ng vÃ  giÃ¡ trá»‹ chÃªnh lá»‡ch dá»±a trÃªn sá»‘ lÆ°á»£ng Ä‘ang lÆ°u á»Ÿ sáº£n pháº©m.
4. TrÆ°á»›c khi gá»­i phiáº¿u, há»‡ thá»‘ng kiá»ƒm tra báº£ng táº¡m cÃ³ dá»¯ liá»‡u vÃ  cÃ³ Ã­t nháº¥t má»™t dÃ²ng thá»±c táº¿.
5. Khi gá»­i, há»‡ thá»‘ng táº¡o báº£n ghi `check_inventory` vá»›i ngÆ°á»i táº¡o, ghi chÃº vÃ  mÃ£ kho; sau Ä‘Ã³ táº¡o cÃ¡c dÃ²ng `check_detail` cho nhá»¯ng sáº£n pháº©m cÃ³ sá»‘ lÆ°á»£ng thá»±c táº¿.
6. Báº£ng táº¡m `warehouse` Ä‘Æ°á»£c xÃ³a sau khi táº¡o phiáº¿u; admin cÃ³ thá»ƒ xem danh sÃ¡ch, lá»c vÃ  xem chi tiáº¿t phiáº¿u kiá»ƒm kho.

**Thay Ä‘á»•i tráº¡ng thÃ¡i dá»¯ liá»‡u:**

`DÃ²ng kiá»ƒm kÃª táº¡m -> Sá»‘ lÆ°á»£ng thá»±c táº¿ Ä‘Æ°á»£c nháº­p -> Phiáº¿u kiá»ƒm kho Ä‘Æ°á»£c táº¡o -> Chi tiáº¿t kiá»ƒm kho Ä‘Æ°á»£c lÆ°u`

**Káº¿t quáº£:**
Há»‡ thá»‘ng cÃ³ phiáº¿u kiá»ƒm kÃª vÃ  chi tiáº¿t chÃªnh lá»‡ch Ä‘á»ƒ phá»¥c vá»¥ theo dÃµi tá»“n kho.

**Dá»¯ liá»‡u vÃ  thÃ nh pháº§n liÃªn quan:**

* Báº£ng database: `warehouse`, `check_inventory`, `check_detail`, `products`, `users`
* Route: `/ban-hang/checkInventory`, `/ban-hang/checkInventory/add`, `/ban-hang/warehome/*`, `/admin/checkInventory/*`
* Controller: `Staff\WareHomeController`, `Staff\CheckInventoryController`, `Admin\CheckInventoryController`
* Service hoáº·c Repository: `CheckInventoryService`, `ProductService`, `CategoryService`
* View hoáº·c API: `Themes.pages.Inventory.*`, `admin.check.*`, Ajax warehome routes
* Notification hoáº·c Job: ChÆ°a xÃ¡c minh notification/job cho kiá»ƒm kÃª

**Nguá»“n xÃ¡c minh:**
`routes/web.php`, `app/Http/Controllers/Staff/WareHomeController.php`, `app/Http/Controllers/Staff/CheckInventoryController.php`, `app/Http/Controllers/Admin/CheckInventoryController.php`, `app/Services/CheckInventoryService.php`, `app/Models/warehome.php`, `app/Models/CheckInventory.php`, `app/Models/CheckDetail.php`.

### Quy trÃ¬nh 5: CÃ´ng ná»£, thu chi vÃ  káº¿ toÃ¡n

**Má»¥c Ä‘Ã­ch:**
Ghi nháº­n phÃ¡t sinh tiá»n máº·t/ngÃ¢n hÃ ng, cÃ´ng ná»£ khÃ¡ch hÃ ng/nhÃ  cung cáº¥p, sá»‘ dÆ° tÃ i khoáº£n vÃ  dá»¯ liá»‡u bÃºt toÃ¡n phá»¥c vá»¥ bÃ¡o cÃ¡o káº¿ toÃ¡n ná»™i bá»™.

**Vai trÃ² tham gia:**
Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route, nhÃ¢n viÃªn cÃ³ `role_id` 3 náº¿u truy cáº­p nhÃ³m route káº¿ toÃ¡n.

**Äiá»u kiá»‡n báº¯t Ä‘áº§u:**
CÃ³ tÃ i khoáº£n káº¿ toÃ¡n, khÃ¡ch hÃ ng hoáº·c nhÃ  cung cáº¥p, vÃ  ngÆ°á»i dÃ¹ng Ä‘Ã£ Ä‘Äƒng nháº­p vÃ o khu vá»±c route tÆ°Æ¡ng á»©ng.

**Luá»“ng thá»±c hiá»‡n:**

1. NgÆ°á»i dÃ¹ng xem hoáº·c táº¡o tÃ i khoáº£n káº¿ toÃ¡n trong mÃ n `admin/accounts`, dá»¯ liá»‡u Ä‘Æ°á»£c tá»• chá»©c theo cÃ¢y tÃ i khoáº£n.
2. NgÆ°á»i dÃ¹ng táº¡o giao dá»‹ch tiá»n máº·t hoáº·c ngÃ¢n hÃ ng, chá»n Ä‘á»‘i tÆ°á»£ng lÃ  khÃ¡ch hÃ ng hoáº·c nhÃ  cung cáº¥p, chá»n tÃ i khoáº£n tiá»n, loáº¡i giao dá»‹ch, sá»‘ tiá»n, ngÃ y giao dá»‹ch vÃ  chá»©ng tá»« Ä‘Ã­nh kÃ¨m náº¿u cÃ³.
3. Há»‡ thá»‘ng táº¡o báº£n ghi `transactions` vÃ  cÃ¡c dÃ²ng `transaction_entries` ná»£/cÃ³ tÆ°Æ¡ng á»©ng vá»›i tÃ i khoáº£n tiá»n vÃ  tÃ i khoáº£n cÃ´ng ná»£.
4. NgÆ°á»i dÃ¹ng cÃ³ thá»ƒ táº¡o cÃ´ng ná»£ Ä‘áº§u ká»³, há»‡ thá»‘ng ghi giao dá»‹ch loáº¡i `other` vÃ  entry gáº¯n Ä‘á»‘i tÆ°á»£ng `Client` hoáº·c `Supplier`.
5. MÃ n cÃ´ng ná»£ khÃ¡ch hÃ ng/nhÃ  cung cáº¥p tá»•ng há»£p sá»‘ dÆ° Ä‘áº§u ká»³, phÃ¡t sinh trong ká»³ vÃ  sá»‘ dÆ° cuá»‘i ká»³ tá»« `transactions` vÃ  `transaction_entries`.
6. CÃ¡c mÃ n phiáº¿u thu/phiáº¿u chi riÃªng ghi dá»¯ liá»‡u vÃ o `receipts`, `receipt_details`, `expense`, `expense_detail` vÃ  cáº­p nháº­t báº£ng cÃ´ng ná»£ legacy tÆ°Æ¡ng á»©ng.

**Thay Ä‘á»•i tráº¡ng thÃ¡i dá»¯ liá»‡u:**

`ThÃ´ng tin giao dá»‹ch/phiáº¿u -> Transaction Ä‘Æ°á»£c táº¡o -> Transaction entries ná»£/cÃ³ Ä‘Æ°á»£c táº¡o -> Sá»‘ dÆ°/cÃ´ng ná»£ Ä‘Æ°á»£c tá»•ng há»£p theo ká»³`

**Káº¿t quáº£:**
Há»‡ thá»‘ng cÃ³ dá»¯ liá»‡u káº¿ toÃ¡n phá»¥c vá»¥ bÃ¡o cÃ¡o cÃ´ng ná»£, báº£ng cÃ¢n Ä‘á»‘i tÃ i khoáº£n, giao dá»‹ch tiá»n máº·t/ngÃ¢n hÃ ng vÃ  lá»‹ch sá»­ phiáº¿u thu/chi.

**Dá»¯ liá»‡u vÃ  thÃ nh pháº§n liÃªn quan:**

* Báº£ng database: `accounts`, `transactions`, `transaction_entries`, `clients`, `suppliers`, `receipts`, `receipts_detail`, `expense`, `expense_detail`, `customer_debts`, `supplier_debts`
* Route: `/admin/accounts`, `/admin/accounts/balance`, `/admin/transactions/cash`, `/admin/transactions/bank`, `/admin/journal-entries`, `/admin/debts/*`, `/admin/quanlythuchi/*`
* Controller: `AccountController`, `CashTransactionController`, `BankTransactionController`, `JournalEntryController`, `DebtController`, `ReceiptController`, `ExpenseController`
* Service hoáº·c Repository: `ReceiptsService`, `ExpenseService`, `DebtKHService`, `DebtNccService`
* View hoáº·c API: `admin.account.*`, `admin.cash-bank.*`, `admin.journal-entries.*`, `admin.debt.*`, `admin.quanlythuchi.*`
* Notification hoáº·c Job: ChÆ°a xÃ¡c minh notification/job cho káº¿ toÃ¡n vÃ  cÃ´ng ná»£

**Nguá»“n xÃ¡c minh:**
`routes/web.php`, `app/Http/Controllers/Admin/AccountController.php`, `app/Http/Controllers/Admin/CashTransactionController.php`, `app/Http/Controllers/Admin/BankTransactionController.php`, `app/Http/Controllers/Admin/DebtController.php`, `app/Http/Controllers/Admin/ReceiptController.php`, `app/Http/Controllers/Admin/ExpenseController.php`, `app/Models/Transaction.php`, `app/Models/TransactionEntry.php`, `app/Models/Account.php`.

### Báº£ng tá»•ng há»£p quy trÃ¬nh

| STT | Quy trÃ¬nh | NgÆ°á»i báº¯t Ä‘áº§u | Vai trÃ² tham gia | Dá»¯ liá»‡u chÃ­nh | Káº¿t quáº£ cuá»‘i cÃ¹ng |
| --: | --- | --- | --- | --- | --- |
| 1 | Thiáº¿t láº­p ngÆ°á»i dÃ¹ng váº­n hÃ nh vÃ  truy cáº­p theo vai trÃ² | Quáº£n trá»‹ viÃªn hoáº·c SuperAdmin | Quáº£n trá»‹ viÃªn, quáº£n trá»‹ cá»­a hÃ ng, nhÃ¢n viÃªn bÃ¡n hÃ ng, SuperAdmin | `users`, `roles`, `super_admins`, session Ä‘Äƒng nháº­p | NgÆ°á»i dÃ¹ng Ä‘Æ°á»£c táº¡o tÃ i khoáº£n vÃ  truy cáº­p Ä‘Ãºng khu vá»±c theo vai trÃ² |
| 2 | Nháº­p hÃ ng vÃ  cáº­p nháº­t tá»“n kho | Quáº£n trá»‹ viÃªn hoáº·c nhÃ³m kho | Quáº£n trá»‹ viÃªn, nhÃ³m kho | `import`, `import_coupon`, `import_detail`, `products`, `product_storage`, `transactions` | Phiáº¿u nháº­p, chi tiáº¿t nháº­p, tá»“n kho vÃ  bÃºt toÃ¡n nháº­p hÃ ng Ä‘Æ°á»£c ghi nháº­n |
| 3 | BÃ¡n hÃ ng táº¡i quáº§y vÃ  ghi nháº­n Ä‘Æ¡n hÃ ng | NhÃ¢n viÃªn bÃ¡n hÃ ng | NhÃ¢n viÃªn bÃ¡n hÃ ng, quáº£n trá»‹ viÃªn theo quyá»n truy cáº­p | `products`, `clients`, `orders`, `order_details`, `transactions`, `transaction_entries` | ÄÆ¡n hÃ ng, chi tiáº¿t Ä‘Æ¡n, thanh toÃ¡n/cÃ´ng ná»£ vÃ  bÃºt toÃ¡n Ä‘Æ°á»£c lÆ°u |
| 4 | Kiá»ƒm kÃª kho | NhÃ¢n viÃªn bÃ¡n hÃ ng hoáº·c nhÃ³m kho | NhÃ¢n viÃªn bÃ¡n hÃ ng, nhÃ³m kho, quáº£n trá»‹ viÃªn | `warehouse`, `check_inventory`, `check_detail`, `products` | Phiáº¿u kiá»ƒm kho vÃ  chi tiáº¿t chÃªnh lá»‡ch Ä‘Æ°á»£c lÆ°u |
| 5 | CÃ´ng ná»£, thu chi vÃ  káº¿ toÃ¡n | Quáº£n trá»‹ viÃªn hoáº·c káº¿ toÃ¡n theo route | Quáº£n trá»‹ viÃªn, káº¿ toÃ¡n theo route | `accounts`, `transactions`, `transaction_entries`, `receipts`, `expense`, `customer_debts`, `supplier_debts` | Dá»¯ liá»‡u cÃ´ng ná»£, thu chi, giao dá»‹ch vÃ  sá»‘ dÆ° tÃ i khoáº£n Ä‘Æ°á»£c tá»•ng há»£p |

## 3.5. SÆ¡ Ä‘á»“ tÆ°Æ¡ng tÃ¡c tá»•ng quÃ¡t

```mermaid
flowchart LR
    Guest["NgÆ°á»i chÆ°a Ä‘Äƒng nháº­p"] --> Login["ÄÄƒng nháº­p há»‡ thá»‘ng"]
    Login --> Admin["Khu vá»±c Admin"]
    Login --> POS["Khu vá»±c bÃ¡n hÃ ng"]
    SuperLogin["ÄÄƒng nháº­p SuperAdmin"] --> SuperPortal["Khu vá»±c SuperAdmin"]

    Owner["Quáº£n trá»‹ viÃªn/chá»§ cá»­a hÃ ng"] --> Admin
    BranchAdmin["Quáº£n trá»‹ cá»­a hÃ ng/chi nhÃ¡nh"] --> Admin
    Staff["NhÃ¢n viÃªn bÃ¡n hÃ ng"] --> POS
    Warehouse["NhÃ³m kho theo route"] --> Admin
    SuperAdmin["SuperAdmin"] --> SuperPortal

    Admin --> ProductFlow["Sáº£n pháº©m, kho, nháº­p hÃ ng, khÃ¡ch hÃ ng, káº¿ toÃ¡n, bÃ¡o cÃ¡o"]
    POS --> SaleFlow["TÃ¬m sáº£n pháº©m, chá»n khÃ¡ch hÃ ng, táº¡o Ä‘Æ¡n, ghi nháº­n thanh toÃ¡n"]

    ProductFlow --> DB["CÆ¡ sá»Ÿ dá»¯ liá»‡u Laravel/MySQL"]
    SaleFlow --> DB
```


## 3.6. Nguá»“n xÃ¡c minh

| Ná»™i dung | File, thÆ° má»¥c hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| Má»¥c Ä‘Ã­ch há»‡ thá»‘ng | `routes/web.php`, `resources/views/admin/layout/sidebar.blade.php`, `resources/views/Themes/layout_staff/header.blade.php`, `resources/views/superadmin/layout/sidebar.blade.php`, `docs/feature-completion-clean.md`, `bao-cao-phan-tich-laravel.md` |
| NhÃ³m ngÆ°á»i dÃ¹ng | `app/Models/User.php`, `app/Models/Roles.php`, `app/Models/SuperAdmin.php`, `database/migrations/*users*`, `database/migrations/*roles*`, `database/migrations/*super_admins*`, `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/CheckLogin.php`, `app/Http/Middleware/CheckLoginSuperAdmin.php`, `app/Http/Controllers/AuthController.php` |
| Chá»©c nÄƒng há»‡ thá»‘ng | `routes/web.php`, `routes/api.php`, `app/Http/Controllers/Admin`, `app/Http/Controllers/Staff`, `app/Http/Controllers/SuperAdmin`, `app/Services`, `app/Models`, `resources/views` |
| Quy trÃ¬nh nghiá»‡p vá»¥ | `app/Http/Controllers/Staff/OrderController.php`, `app/Http/Controllers/Admin/importCouponController.php`, `app/Http/Controllers/Admin/ImportProductController.php`, `app/Http/Controllers/Staff/CheckInventoryController.php`, `app/Http/Controllers/Staff/WareHomeController.php`, `app/Http/Controllers/Admin/DebtController.php`, `app/Http/Controllers/Admin/CashTransactionController.php`, `app/Http/Controllers/Admin/BankTransactionController.php` |
| Giao diá»‡n theo vai trÃ² | `resources/views/admin`, `resources/views/Themes/layout_staff`, `resources/views/Themes/pages`, `resources/views/superadmin`, `resources/views/auth` |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u vÃ  quan há»‡ Eloquent | `database/migrations`, `database/seeders`, `app/Models/Product.php`, `app/Models/Order.php`, `app/Models/OrderDetail.php`, `app/Models/Client.php`, `app/Models/ImportCoupon.php`, `app/Models/Transaction.php`, `docs/database-table-usage-audit.md` |
| Kiá»ƒm thá»­ liÃªn quan | `tests`, `phpunit.xml`, Ä‘áº·c biá»‡t `tests/Unit/UploadedImageUrlTest.php`; chÆ°a cháº¡y test trong láº§n cáº­p nháº­t section 3 |

# 4. KIáº¾N TRÃšC VÃ€ CÃ”NG NGHá»† Cá»¦A Dá»° ÃN

## 4.1. CÃ´ng nghá»‡ sá»­ dá»¥ng

### 4.1.1. Backend

Dá»± Ã¡n sá»­ dá»¥ng PHP vá»›i framework Laravel. YÃªu cáº§u PHP Ä‘Æ°á»£c khai bÃ¡o lÃ  `^8.1` trong `composer.json`; phiÃªn báº£n Laravel xÃ¡c minh tá»« `composer.lock` lÃ  `v10.49.0`. á»¨ng dá»¥ng khá»Ÿi táº¡o theo cáº¥u trÃºc Laravel truyá»n thá»‘ng thÃ´ng qua `artisan`, `bootstrap/app.php`, `App\Http\Kernel`, `App\Console\Kernel` vÃ  `App\Exceptions\Handler`.

CÆ¡ cháº¿ ORM chÃ­nh lÃ  Eloquent. CÃ¡c Model trong `app/Models` khai bÃ¡o `$fillable`, `$casts`, accessor thÃ´ng qua `$appends`, quan há»‡ `belongsTo`, `hasOne`, `hasMany`, `belongsToMany`, `hasManyThrough` vÃ  má»™t sá»‘ hook `boot()` Ä‘á»ƒ sinh mÃ£ hoáº·c gÃ¡n dá»¯ liá»‡u khi táº¡o báº£n ghi.

Route backend táº­p trung chá»§ yáº¿u á»Ÿ `routes/web.php`; `routes/api.php` hiá»‡n chá»‰ cÃ³ route `/api/user` dÃ¹ng middleware `auth:sanctum`. Web request Ä‘i qua middleware group `web`, sau Ä‘Ã³ vÃ o cÃ¡c group route theo khu vá»±c `admin`, `ban-hang` vÃ  `super-admin`. CÃ¡c request Ä‘Æ°á»£c xá»­ lÃ½ bá»Ÿi Controller trong `app/Http/Controllers`, má»™t pháº§n gá»i Service trong `app/Services`, má»™t pháº§n truy váº¥n Model hoáº·c Query Builder trá»±c tiáº¿p.

Validation Ä‘Æ°á»£c thá»±c hiá»‡n báº±ng cáº£ Form Request (`LoginRequest`, `CategoryRequest`, `CompanyRequest`, `ProductRequest`) vÃ  validate trá»±c tiáº¿p trong Controller báº±ng `$request->validate()` hoáº·c `Validator::make()`. PhÃ¢n quyá»n chá»§ yáº¿u dá»±a trÃªn session guard `web`, cá»™t `role_id`, middleware `role`, `CheckLogin` vÃ  `CheckLoginSuperAdmin`. ChÆ°a phÃ¡t hiá»‡n `app/Policies` hoáº·c Ä‘Äƒng kÃ½ Gate nghiá»‡p vá»¥ riÃªng trong mÃ£ nguá»“n hiá»‡n táº¡i.


Mail Ä‘Æ°á»£c cáº¥u hÃ¬nh qua `config/mail.php`; mÃ£ nguá»“n cÃ³ cÃ¡c lá»›p Mail trong `app/Mail`, view email trong `resources/views/emails`, vÃ  cÃ¡c Ä‘iá»ƒm gá»i `Mail::to()`/`Mail::send()` trong Controller hoáº·c Listener.

### 4.1.2. Frontend

Giao diá»‡n sá»­ dá»¥ng Laravel Blade. CÃ¡c view Ä‘Æ°á»£c tá»• chá»©c trong `resources/views` theo khu vá»±c `admin`, `superadmin`, `sa`, `Themes`, `auth`, `emails`, `components` vÃ  `vendor/pagination`. Layout chÃ­nh Ä‘Æ°á»£c xÃ¡c minh á»Ÿ `resources/views/admin/layout/index.blade.php`, `resources/views/superadmin/layout/index.blade.php`, `resources/views/sa/layout/index.blade.php` vÃ  `resources/views/Themes/layout_staff/app.blade.php`.

Frontend cÃ³ cáº¥u hÃ¬nh Vite trong `vite.config.js` vá»›i input `resources/css/app.css` vÃ  `resources/js/app.js`; `package.json` khai bÃ¡o `vite`, `laravel-vite-plugin`, `axios`, `ckeditor5`, `sweetalert2`. Tuy nhiÃªn, trong pháº¡m vi kiá»ƒm tra hiá»‡n táº¡i chÆ°a phÃ¡t hiá»‡n `@vite` trong Blade, nÃªn Vite Ä‘Æ°á»£c ghi nháº­n lÃ  cÃ³ cáº¥u hÃ¬nh build nhÆ°ng chÆ°a xÃ¡c minh lÃ  luá»“ng náº¡p giao diá»‡n chÃ­nh.

CÃ¡c layout Blade Ä‘ang náº¡p trá»±c tiáº¿p nhiá»u asset tá»« `public/assets`, `public/global`, `public/css`, `public/js`, `public/validator` vÃ  má»™t sá»‘ CDN. CÃ¡c thÆ° viá»‡n giao diá»‡n xÃ¡c minh Ä‘ang Ä‘Æ°á»£c náº¡p hoáº·c gá»i gá»“m Bootstrap, jQuery, Font Awesome, Kaiadmin theme, DataTables, Chart.js, jquery sparkline, jsVectorMap, Bootstrap Notify, SweetAlert/SweetAlert2, Toastr ná»™i bá»™ `datgin`, CKEditor CDN trong má»™t sá»‘ form vÃ  daterangepicker trong view dashboard/welcome.

Vue, React vÃ  Alpine.js khÃ´ng Ä‘Æ°á»£c phÃ¡t hiá»‡n trong `package.json` hoáº·c luá»“ng view Ä‘Ã£ kiá»ƒm tra. JavaScript chá»§ yáº¿u lÃ  jQuery, JavaScript thuáº§n trong Blade vÃ  Axios Ä‘Æ°á»£c khai bÃ¡o trong `resources/js/bootstrap.js`.

### 4.1.3. CÆ¡ sá»Ÿ dá»¯ liá»‡u

CÆ¡ sá»Ÿ dá»¯ liá»‡u máº·c Ä‘á»‹nh lÃ  MySQL theo `config/database.php` vÃ  `.env.example` (`DB_CONNECTION=mysql`). Laravel váº«n giá»¯ cáº¥u hÃ¬nh chuáº©n cho `sqlite`, `pgsql`, `sqlsrv` vÃ  Redis, nhÆ°ng chÆ°a phÃ¡t hiá»‡n luá»“ng nghiá»‡p vá»¥ dÃ¹ng nhiá»u database connection trong mÃ£ nguá»“n Ä‘Ã£ kiá»ƒm tra.



### 4.1.4. XÃ¡c thá»±c vÃ  phÃ¢n quyá»n

XÃ¡c thá»±c web sá»­ dá»¥ng guard `web` vá»›i driver `session` vÃ  provider Eloquent `App\Models\User` theo `config/auth.php`. Model `User` dÃ¹ng `HasApiTokens`, `HasFactory`, `Notifiable`; cá»™t `role_id` Ä‘Æ°á»£c dÃ¹ng Ä‘á»ƒ phÃ¢n biá»‡t vai trÃ². `AuthController` xá»­ lÃ½ Ä‘Äƒng nháº­p qua `LoginRequest`, gá»i `Auth::attempt()`, kiá»ƒm tra tráº¡ng thÃ¡i tÃ i khoáº£n vÃ  chuyá»ƒn hÆ°á»›ng theo `role_id`: nhÃ³m `1, 2` vÃ o `/admin`, nhÃ³m `3` vÃ o `/ban-hang`.

Laravel Sanctum Ä‘Æ°á»£c cÃ i Ä‘áº·t vÃ  cáº¥u hÃ¬nh trong `config/sanctum.php`; route `/api/user` trong `routes/api.php` dÃ¹ng `auth:sanctum`. ChÆ°a xÃ¡c minh Ä‘Æ°á»£c API nghiá»‡p vá»¥ hoÃ n chá»‰nh sá»­ dá»¥ng token Sanctum ngoÃ i route máº«u nÃ y.

Khu vá»±c Admin sá»­ dá»¥ng `Route::middleware(['auth'])->prefix('admin')`, sau Ä‘Ã³ chia tiáº¿p theo middleware `role:1`, `role:4`, `role:3`. Khu vá»±c bÃ¡n hÃ ng dÃ¹ng `CheckLogin::class` káº¿t há»£p `role:3`. Khu vá»±c Super Admin dÃ¹ng Ä‘Äƒng nháº­p riÃªng qua `SuperAdminController`, lÆ°u session `authSuper` vÃ  kiá»ƒm tra báº±ng `CheckLoginSuperAdmin`. ChÆ°a phÃ¡t hiá»‡n guard riÃªng trong `config/auth.php`; cÆ¡ cháº¿ Super Admin dÃ¹ng session tÃ¹y chá»‰nh bÃªn cáº¡nh `Auth::login()`.

### 4.1.5. LÆ°u trá»¯ vÃ  xá»­ lÃ½ file

Cáº¥u hÃ¬nh filesystem máº·c Ä‘á»‹nh lÃ  `local`; disk `public` trá» tá»›i `storage/app/public` vÃ  URL `/storage` theo `config/filesystems.php`. Trong thÆ° má»¥c `public` hiá»‡n cÃ³ `public/storage` dáº¡ng Junction trá» Ä‘áº¿n `D:\iphone\storage\app\public`, tÆ°Æ¡ng á»©ng cÆ¡ cháº¿ `storage:link`.

Upload vÃ  xá»­ lÃ½ áº£nh Ä‘Æ°á»£c xÃ¡c minh qua helper `uploadImages()` trong `app/Helpers/helper.php`: nháº­n file tá»« request, dÃ¹ng Intervention Image, lÆ°u áº£nh WebP qua `Storage::disk('public')`. Helper `showImage()` vÃ  `deleteImage()` cÅ©ng dÃ¹ng disk `public`. CÃ¡c luá»“ng upload gá»“m thumbnail sáº£n pháº©m (`ProductController`), logo cáº¥u hÃ¬nh cá»­a hÃ ng (`ConfigController`), logo thÆ°Æ¡ng hiá»‡u (`BrandController`), avatar (`AdminController`) vÃ  attachment giao dá»‹ch tiá»n máº·t/ngÃ¢n hÃ ng (`CashTransactionController`, `BankTransactionController`) báº±ng `storeAs(..., 'public')`.

CKFinder Ä‘Æ°á»£c cÃ i Ä‘áº·t, cÃ³ `config/ckfinder.php`, asset trong `public/js/ckfinder` vÃ  backend local máº·c Ä‘á»‹nh trá» tá»›i `storage/app/public`. Cáº¥u hÃ¬nh S3 tá»“n táº¡i trong `config/filesystems.php`, nhÆ°ng chÆ°a xÃ¡c minh Ä‘Æ°á»£c luá»“ng sá»­ dá»¥ng S3 trong mÃ£ nguá»“n hiá»‡n táº¡i.

### 4.1.6. Dá»‹ch vá»¥ vÃ  thÆ° viá»‡n bÃªn thá»© ba



QR/VietQR Ä‘Æ°á»£c xá»­ lÃ½ trong `TransactionController::generateQrCode()` báº±ng URL áº£nh VietQR; package `endroid/qr-code` cÃ³ khai bÃ¡o trong Composer nhÆ°ng chÆ°a xÃ¡c minh nÆ¡i gá»i package trá»±c tiáº¿p. `kavenegar/laravel` cÃ³ trong Composer vÃ  cÃ³ `use Kavenegar` trong `Staff\ClientController`, nhÆ°ng chÆ°a phÃ¡t hiá»‡n lá»i gá»i SMS rÃµ rÃ ng. `PaymentController` cÃ³ mÃ£ xá»­ lÃ½ MoMo test báº±ng cURL/HTTP, nhÆ°ng route payment trong `routes/web.php` Ä‘ang comment nÃªn tráº¡ng thÃ¡i lÃ  cÃ³ mÃ£ nhÆ°ng chÆ°a xÃ¡c minh luá»“ng Ä‘ang sá»­ dá»¥ng.

### 4.1.7. CÃ´ng cá»¥ phÃ¡t triá»ƒn vÃ  triá»ƒn khai

Dá»± Ã¡n sá»­ dá»¥ng Composer cho backend vÃ  NPM cho frontend, cÃ³ `composer.json`, `composer.lock`, `package.json`, `package-lock.json`. Vite Ä‘Æ°á»£c cáº¥u hÃ¬nh nhÆ°ng chÆ°a xÃ¡c minh Ä‘Æ°á»£c view náº¡p `@vite`. PHPUnit Ä‘Æ°á»£c cáº¥u hÃ¬nh trong `phpunit.xml`; tests hiá»‡n cÃ³ `Feature\ExampleTest`, `Unit\ExampleTest` vÃ  `Unit\UploadedImageUrlTest`.

README.dev.md ghi nháº­n cÃ¡c lá»‡nh cÃ i package, hÆ°á»›ng dáº«n `php artisan queue:work`, cháº¡y worker báº±ng `screen`/`tmux` vÃ  vÃ­ dá»¥ cáº¥u hÃ¬nh Supervisor. ChÆ°a phÃ¡t hiá»‡n Dockerfile, `docker-compose.yml`, file CI/CD, file Supervisor riÃªng, cáº¥u hÃ¬nh Nginx/Apache hoáº·c script deploy Ä‘á»™c láº­p trong thÆ° má»¥c gá»‘c hiá»‡n táº¡i. Git Ä‘ang tá»“n táº¡i vÃ¬ repo cÃ³ thÆ° má»¥c `.git`.

#### Báº£ng tá»•ng há»£p cÃ´ng nghá»‡

| NhÃ³m | CÃ´ng nghá»‡ hoáº·c cÃ´ng cá»¥ | PhiÃªn báº£n | Má»¥c Ä‘Ã­ch sá»­ dá»¥ng | Tráº¡ng thÃ¡i xÃ¡c minh | Nguá»“n xÃ¡c minh |
| --- | --- | --- | --- | --- | --- |
| Backend | PHP | `^8.1` | NgÃ´n ngá»¯ server | Äang sá»­ dá»¥ng | `composer.json` |
| Backend | Laravel Framework | `v10.49.0` | Framework chÃ­nh | Äang sá»­ dá»¥ng | `composer.json`, `composer.lock`, `artisan`, `bootstrap/app.php` |
| Backend | Eloquent ORM | Theo Laravel `v10.49.0` | Model, quan há»‡, truy váº¥n dá»¯ liá»‡u | Äang sá»­ dá»¥ng | `app/Models`, `composer.lock` |
| Backend | Laravel Sanctum | `v3.3.3` | API token/cookie auth, route máº«u `/api/user` | CÃ³ cáº¥u hÃ¬nh nhÆ°ng chÆ°a xÃ¡c minh Ä‘Æ°á»£c luá»“ng API nghiá»‡p vá»¥ | `composer.lock`, `config/sanctum.php`, `routes/api.php`, `app/Models/User.php` |
| Backend | Laravel Mail | Theo Laravel | Gá»­i email tÃ i khoáº£n, há»— trá»£, OTP | Äang sá»­ dá»¥ng | `config/mail.php`, `app/Mail`, `app/Listeners/SendMailOtpCustomerLogin.php`, `app/Http/Controllers/Admin/UserController.php` |
| Backend | Monolog/Laravel Logging | Theo Laravel | Ghi log lá»—i vÃ  nghiá»‡p vá»¥ | Äang sá»­ dá»¥ng | `config/logging.php`, `app/Exceptions/Handler.php`, `app/Services` |
| Frontend | Laravel Blade | Theo Laravel | Render giao diá»‡n web | Äang sá»­ dá»¥ng | `resources/views`, `routes/web.php` |
| Frontend | Vite | `4.5.3` | Build frontend theo cáº¥u hÃ¬nh Laravel Vite | CÃ³ cáº¥u hÃ¬nh nhÆ°ng chÆ°a xÃ¡c minh view náº¡p `@vite` | `package-lock.json`, `vite.config.js`, `resources/js/app.js` |
| Frontend | Axios | `1.7.2` | HTTP client trong bundle Vite | CÃ³ khai bÃ¡o vÃ  import, chÆ°a tháº¥y view náº¡p bundle Vite | `package-lock.json`, `resources/js/bootstrap.js` |
| Frontend | jQuery | KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c phiÃªn báº£n cá»¥ thá»ƒ tá»« package; asset/CDN cÃ³ náº¡p | AJAX, xá»­ lÃ½ DOM trong Blade | Äang sá»­ dá»¥ng | `resources/views/admin/layout/script.blade.php`, `public/assets/js/core/jquery-3.7.1.min.js` |
| Frontend | Bootstrap | KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | CSS/JS giao diá»‡n | Äang sá»­ dá»¥ng | `resources/views/*/layout`, `public/assets/css/bootstrap.min.css`, CDN trong view auth |
| Frontend | Kaiadmin theme | KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | Theme quáº£n trá»‹ | Äang sá»­ dá»¥ng | `public/assets/css/kaiadmin.css`, `public/assets/js/kaiadmin.js`, layout Blade |
| Frontend | Font Awesome | CDN/asset; khÃ´ng xÃ¡c Ä‘á»‹nh Ä‘á»“ng nháº¥t má»™t phiÃªn báº£n | Icon giao diá»‡n | Äang sá»­ dá»¥ng | `resources/views/*/layout`, `public/assets/fonts/fontawesome` |
| Frontend | SweetAlert/SweetAlert2 | `sweetalert2` NPM `11.12.1`; CDN/asset cÃ³ náº¡p | XÃ¡c nháº­n thao tÃ¡c, thÃ´ng bÃ¡o | Äang sá»­ dá»¥ng | `package-lock.json`, `resources/views/admin/layout/script.blade.php`, `resources/views/superadmin/*` |
| Frontend | Toastr ná»™i bá»™ `datgin` | KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | ThÃ´ng bÃ¡o phiÃªn/response | Äang sá»­ dá»¥ng | `public/global/js/toastr.js`, `resources/views/admin/layout/script.blade.php` |
| Frontend | DataTables | KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | Báº£ng dá»¯ liá»‡u | Äang sá»­ dá»¥ng | `public/assets/js/plugin/datatables/datatables.min.js`, layout Blade |
| Frontend | Chart.js | KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | Biá»ƒu Ä‘á»“/dashboard | Äang sá»­ dá»¥ng | `public/assets/js/plugin/chart.js/chart.min.js`, `resources/views/welcome.blade.php` |
| Frontend | CKEditor/ckeditor5 | NPM `ckeditor5` `42.0.1`; CDN CKEditor 4 trong view | Soáº¡n tháº£o ná»™i dung | Äang sá»­ dá»¥ng qua CDN trong má»™t sá»‘ view; package NPM chÆ°a xÃ¡c minh luá»“ng náº¡p | `package-lock.json`, `resources/views/admin/branch/*.blade.php`, `resources/views/admin/client/edit.blade.php` |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u | MySQL | KhÃ´ng xÃ¡c Ä‘á»‹nh phiÃªn báº£n server tá»« mÃ£ nguá»“n | Database máº·c Ä‘á»‹nh | Äang sá»­ dá»¥ng theo cáº¥u hÃ¬nh | `config/database.php`, `.env.example` |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u | Migrations | 111 file | Quáº£n lÃ½ schema | Äang sá»­ dá»¥ng | `database/migrations` |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u | Seeders/Factories | 16 seeders, 13 factories | Dá»¯ liá»‡u máº«u/test | CÃ³ trong mÃ£ nguá»“n | `database/seeders`, `database/factories` |
| XÃ¡c thá»±c | Laravel session guard `web` | Theo Laravel | ÄÄƒng nháº­p web | Äang sá»­ dá»¥ng | `config/auth.php`, `AuthController`, `routes/web.php` |
| XÃ¡c thá»±c | Middleware role/session tÃ¹y chá»‰nh | KhÃ´ng Ã¡p dá»¥ng | Kiá»ƒm tra vai trÃ² vÃ  khu vá»±c truy cáº­p | Äang sá»­ dá»¥ng | `app/Http/Middleware/RoleMiddleware.php`, `CheckLogin.php`, `CheckLoginSuperAdmin.php` |
| LÆ°u trá»¯ | Laravel Storage local/public | Theo Laravel | LÆ°u áº£nh, logo, attachment | Äang sá»­ dá»¥ng | `config/filesystems.php`, `app/Helpers/helper.php`, `public/storage` |
| LÆ°u trá»¯ | S3 disk | KhÃ´ng xÃ¡c Ä‘á»‹nh phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | Cloud storage theo cáº¥u hÃ¬nh Laravel | CÃ³ cáº¥u hÃ¬nh nhÆ°ng chÆ°a xÃ¡c minh luá»“ng sá»­ dá»¥ng | `config/filesystems.php` |
| ThÆ° viá»‡n bÃªn thá»© ba | DomPDF | `v2.2.0` | Xuáº¥t PDF | Äang sá»­ dá»¥ng | `composer.lock`, `app/Http/Controllers/Staff/ClientController.php`, `Admin/ReportController.php`, `Admin/TransactionController.php` |
| ThÆ° viá»‡n bÃªn thá»© ba | Intervention Image | `2.7.0` | Resize/encode áº£nh upload | Äang sá»­ dá»¥ng | `composer.lock`, `app/Helpers/helper.php` |
| ThÆ° viá»‡n bÃªn thá»© ba | CKFinder | `v5.0.1` | Quáº£n lÃ½ file qua CKFinder | CÃ³ cáº¥u hÃ¬nh vÃ  asset | `composer.lock`, `config/ckfinder.php`, `public/js/ckfinder` |
| ThÆ° viá»‡n bÃªn thá»© ba | Endroid QR Code | `5.1.0` | QR code theo package | CÃ³ khai bÃ¡o nhÆ°ng chÆ°a xÃ¡c minh nÆ¡i dÃ¹ng trá»±c tiáº¿p | `composer.lock`, `composer.json` |
| ThÆ° viá»‡n bÃªn thá»© ba | Kavenegar Laravel | `v1.3.8` | SMS theo package | CÃ³ khai bÃ¡o nhÆ°ng chÆ°a xÃ¡c minh lá»i gá»i SMS rÃµ rÃ ng | `composer.lock`, `app/Http/Controllers/Staff/ClientController.php` |
| ThÆ° viá»‡n bÃªn thá»© ba | MoMo payment code | KhÃ´ng xÃ¡c Ä‘á»‹nh phiÃªn báº£n package; dÃ¹ng cURL/HTTP | Thanh toÃ¡n thá»­ nghiá»‡m | CÃ³ Controller nhÆ°ng route Ä‘ang comment, chÆ°a xÃ¡c minh Ä‘ang sá»­ dá»¥ng | `app/Http/Controllers/PaymentController.php`, `routes/web.php` |
| Triá»ƒn khai | Composer | KhÃ´ng xÃ¡c Ä‘á»‹nh phiÃªn báº£n Composer tá»« mÃ£ nguá»“n | Quáº£n lÃ½ package PHP | Äang sá»­ dá»¥ng | `composer.json`, `composer.lock` |
| Triá»ƒn khai | NPM | KhÃ´ng xÃ¡c Ä‘á»‹nh phiÃªn báº£n NPM tá»« mÃ£ nguá»“n | Quáº£n lÃ½ package frontend | Äang sá»­ dá»¥ng | `package.json`, `package-lock.json` |
| Triá»ƒn khai | PHPUnit | `10.5.53` | Kiá»ƒm thá»­ | CÃ³ cáº¥u hÃ¬nh vÃ  test máº«u | `composer.lock`, `phpunit.xml`, `tests` |
| Triá»ƒn khai | Laravel Pint | `v1.24.0` | Äá»‹nh dáº¡ng code | CÃ³ khai bÃ¡o dev dependency, chÆ°a xÃ¡c minh luá»“ng cháº¡y | `composer.lock`, `composer.json` |
| Triá»ƒn khai | Supervisor/screen | KhÃ´ng xÃ¡c Ä‘á»‹nh phiÃªn báº£n cá»¥ thá»ƒ tá»« mÃ£ nguá»“n hiá»‡n táº¡i | Gá»£i Ã½ cháº¡y queue worker | CÃ³ hÆ°á»›ng dáº«n, chÆ°a phÃ¡t hiá»‡n file cáº¥u hÃ¬nh riÃªng | `README.dev.md` |

## 4.2. Cáº¥u trÃºc mÃ£ nguá»“n

### 4.2.1. Tá»•ng quan cáº¥u trÃºc thÆ° má»¥c

| ThÆ° má»¥c hoáº·c thÃ nh pháº§n | Vai trÃ² trong dá»± Ã¡n | Ná»™i dung chÃ­nh Ä‘Æ°á»£c phÃ¡t hiá»‡n |
| --- | --- | --- |
| `app/Http/Controllers` | Tiáº¿p nháº­n request vÃ  Ä‘iá»u phá»‘i nghiá»‡p vá»¥ | Controller gá»‘c, nhÃ³m `Admin`, `Staff`, `SuperAdmin`, `Client`, `AuthController`, `BulkController`, `MultipleController`, `PaymentController` |
| `app/Http/Requests` | Form Request validation | Login, Category, Company, Product |
| `app/Http/Middleware` | Middleware xÃ¡c thá»±c vÃ  phÃ¢n quyá»n | Middleware chuáº©n Laravel vÃ  middleware tÃ¹y chá»‰nh `RoleMiddleware`, `CheckLogin`, `CheckLoginSuperAdmin`, `CheckRole`, `AuthMiddleware` |
| `app/Events` | Sá»± kiá»‡n tÃ¹y chá»‰nh | `CustomerLogin` |
| `app/Listeners` | Listener xá»­ lÃ½ sá»± kiá»‡n | `SendMailOtpCustomerLogin` gá»­i OTP email vÃ  triá»ƒn khai `ShouldQueue` |
| `app/Mail` | Mailable | `CustomerEmail`, `SendMailInfo`, `SuperAdmin`, `UserRegistered` |
| `app/Exports` | Export Excel | `ClientsExport`, `DailyReportExport`, `OrdersSheet`, `ProductsSalesSheet`, `UsersExport` |
| `app/Helpers` | HÃ m helper dÃ¹ng chung | Upload áº£nh, xÃ³a áº£nh, response JSON, transaction wrapper, sinh mÃ£ |
| `app/View/Components` | Blade component class | `Breadcrumb`, `Card` |
| `resources/views` | Giao diá»‡n Blade | Admin, SuperAdmin, Staff/POS, Auth, Email, Components, Pagination, Theme copy |
| `resources/js` | JS nguá»“n cho Vite | `app.js`, `bootstrap.js` náº¡p Axios |
| `resources/css` | CSS nguá»“n cho Vite | `app.css` |
| `public/assets` | Asset theme quáº£n trá»‹ | CSS/JS Kaiadmin, Bootstrap, plugins, fonts, áº£nh |
| `public/global` | Asset ná»™i bá»™ | `toastr.js`, `helpers.js`, `data-tables.js`, CSS toastr/table |
| `public/js/ckfinder` | Asset CKFinder | JS vÃ  sample CKFinder |
| `routes` | Äá»‹nh nghÄ©a route | `web.php`, `api.php`, `channels.php`, `console.php` |
| `database` | Migration, seeder, factory | 111 migrations, 16 seeders, 13 factories |
| `tests` | Kiá»ƒm thá»­ | Unit, Feature, test helper hiá»ƒn thá»‹ áº£nh upload |

ChÆ°a phÃ¡t hiá»‡n thÆ° má»¥c Repository riÃªng trong cáº¥u trÃºc mÃ£ nguá»“n hiá»‡n táº¡i. ChÆ°a phÃ¡t hiá»‡n `app/Policies`, `app/Notifications`, `app/Http/Resources`, `app/Actions`, `app/DTO` hoáº·c `app/Console/Commands`.

### 4.2.2. Routes

Route táº­p trung chá»§ yáº¿u trong `routes/web.php`. File nÃ y khai bÃ¡o cÃ¡c route Ä‘Äƒng nháº­p, redirect `/`, khu vá»±c `admin`, khu vá»±c bÃ¡n hÃ ng `ban-hang`, khu vá»±c `super-admin`, vÃ  route kiá»ƒm tra tÃ i khoáº£n Ä‘Äƒng kÃ½. CÃ¡c route dÃ¹ng nhiá»u `prefix`, `name`, `middleware group` vÃ  controller group; chÆ°a phÃ¡t hiá»‡n route resource dáº¡ng `Route::resource()` trong pháº¡m vi kiá»ƒm tra hiá»‡n táº¡i.

`routes/api.php` chá»‰ cÃ³ route `/user` Ä‘Æ°á»£c báº£o vá»‡ bá»Ÿi `auth:sanctum`, tráº£ vá» user hiá»‡n táº¡i. ChÆ°a xÃ¡c minh Ä‘Æ°á»£c API nghiá»‡p vá»¥ riÃªng.

| File route | Khu vá»±c hoáº·c Ä‘á»‘i tÆ°á»£ng | Middleware chÃ­nh | Controller hoáº·c module liÃªn quan |
| --- | --- | --- | --- |
| `routes/web.php` | Guest/Auth | `guest` | `AuthController`, `SignUpController` |
| `routes/web.php` | Admin `/admin` | `auth`, `role:1`, `role:4`, `role:3` | Dashboard, sáº£n pháº©m, ngÆ°á»i dÃ¹ng, cÃ´ng ty, danh má»¥c, nhÃ¢n viÃªn, chi nhÃ¡nh, thÆ°Æ¡ng hiá»‡u, khÃ¡ch hÃ ng, nhÃ  cung cáº¥p, Ä‘Æ¡n hÃ ng, cáº¥u hÃ¬nh, há»— trá»£, nháº­p hÃ ng, kho, bÃ¡o cÃ¡o, káº¿ toÃ¡n |
| `routes/web.php` | BÃ¡n hÃ ng `/ban-hang` | `CheckLogin`, `role:3` | `Staff\ProductController`, `Staff\ClientController`, `Staff\OrderController`, `Staff\CheckInventoryController`, `Staff\WareHomeController` |
| `routes/api.php` | API user hiá»‡n táº¡i | `auth:sanctum` | Closure tráº£ `$request->user()` |
| `routes/console.php` | Console command máº«u | KhÃ´ng Ã¡p dá»¥ng | Closure command `inspire` |
| `routes/channels.php` | Broadcast channels | KhÃ´ng phÃ¡t hiá»‡n channel tÃ¹y chá»‰nh Ä‘Ã¡ng ká»ƒ trong pháº¡m vi kiá»ƒm tra | Laravel broadcasting scaffold |

### 4.2.3. Controllers

Controller Ä‘Æ°á»£c chia theo vai trÃ² vÃ  khu vá»±c:

* `Admin`: quáº£n trá»‹ cá»­a hÃ ng, sáº£n pháº©m, kho, nháº­p hÃ ng, khÃ¡ch hÃ ng, nhÃ  cung cáº¥p, bÃ¡o cÃ¡o, thu chi, tÃ i khoáº£n káº¿ toÃ¡n, giao dá»‹ch.
* `Staff`: bÃ¡n hÃ ng/POS, giá» hÃ ng, Ä‘Æ¡n hÃ ng, kiá»ƒm kÃª, kho nhÃ¢n viÃªn.
* `Client`: Ä‘Äƒng kÃ½/kiá»ƒm tra tÃ i khoáº£n.
* Controller ngoÃ i namespace vai trÃ²: `AuthController`, `BulkController`, `MultipleController`, `PaymentController`.


Controller cÃ³ tráº£ vá» Blade View, redirect, JSON response, file download PDF/Excel, vÃ  HTML partial render cho AJAX.

### 4.2.4. Models

Model Eloquent Ä‘áº¡i diá»‡n cho cÃ¡c nhÃ³m dá»¯ liá»‡u chÃ­nh:

* Sáº£n pháº©m vÃ  kho: `Product`, `ProductImages`, `Categories`, `Brand`, `Storage`, `ProductStorage`, `warehome`, `CheckInventory`, `CheckDetail`.
* ÄÆ¡n hÃ ng vÃ  bÃ¡n hÃ ng: `Order`, `OrderDetail`, `Cart`.
* KhÃ¡ch hÃ ng: `Client`, `ClientGroup`, `Customer`, `ClientDebt`, `ClientDebtsDetail`.
* NhÃ  cung cáº¥p vÃ  nháº­p hÃ ng: `Supplier`, `Company`, `CompanyProduct`, `Import`, `ImportCoupon`, `ImportDetail`.
* Thu chi, cÃ´ng ná»£ vÃ  káº¿ toÃ¡n: `Account`, `Transaction`, `TransactionEntry`, `Receipts`, `ReceiptDetail`, `Expense`, `ExpenseDetail`, `SupplierDebt`, `SupplierDebtsDetail`.
* TÃ i khoáº£n vÃ  phÃ¢n quyá»n: `User`, `UserInfo`, `Roles`, `RolePermission`, `SuperAdmin`, `Wallet`, `UserWallet`.
* Dá»¯ liá»‡u Ä‘á»‹a lÃ½/cáº¥u hÃ¬nh: `City`, `Districts`, `Ward`, `Field`, `Bank`, `Config`.

Quan há»‡ Eloquent Ä‘Ã£ xÃ¡c minh gá»“m `belongsTo`, `hasOne`, `hasMany`, `belongsToMany`, `hasManyThrough`, `morphTo`. Má»™t sá»‘ Model dÃ¹ng `$appends` vÃ  accessor Ä‘á»ƒ láº¥y dá»¯ liá»‡u liÃªn quan, vÃ­ dá»¥ `User::user_info`, `Product::images`, `Order::orderdetail`, `ImportCoupon::detail/user/supplier/company`. ChÆ°a phÃ¡t hiá»‡n sá»­ dá»¥ng `SoftDeletes` trong cÃ¡c Model Ä‘Ã£ kiá»ƒm tra báº±ng tÃ¬m kiáº¿m.

### 4.2.5. Services

ThÆ° má»¥c `app/Services` tá»“n táº¡i vÃ  Ä‘ang Ä‘Æ°á»£c Controller inject qua namespace `App\Services`. CÃ¡c Service tiÃªu biá»ƒu:

| NhÃ³m Service | Service tiÃªu biá»ƒu | Vai trÃ² Ä‘Ã£ xÃ¡c minh |
| --- | --- | --- |
| NgÆ°á»i dÃ¹ng/quáº£n trá»‹ | `UserService`, `AdminService`, `SupperAdminService`, `StoreService`, `SignUpService` | ÄÄƒng nháº­p/táº¡o tÃ i khoáº£n, quáº£n trá»‹ staff/store, Ä‘Äƒng kÃ½ |
| Sáº£n pháº©m/kho | `ProductService`, `ProductStorageService`, `CategoryService`, `BrandService`, `StorageService`, `CheckInventoryService` | CRUD sáº£n pháº©m/danh má»¥c/thÆ°Æ¡ng hiá»‡u/kho, tá»“n kho, kiá»ƒm kÃª |
| KhÃ¡ch hÃ ng/nhÃ  cung cáº¥p | `ClientService`, `ClientGroupService`, `SupplierService`, `CompanyService`, `CompanyProductService` | Quáº£n lÃ½ khÃ¡ch hÃ ng, nhÃ³m khÃ¡ch, nhÃ  cung cáº¥p, cÃ´ng ty |
| BÃ¡n hÃ ng/Ä‘Æ¡n hÃ ng | `CartService`, `OrderService` | Giá» hÃ ng, Ä‘Æ¡n hÃ ng, thÃ´ng bÃ¡o Ä‘Æ¡n |
| Nháº­p hÃ ng/cÃ´ng ná»£/thu chi | `ImportProductService`, `ReceiptsService`, `ExpenseService`, `DebtKHService`, `DebtNccService`, `TransactionService` | Phiáº¿u nháº­p, thu/chi, cÃ´ng ná»£, giao dá»‹ch |
| BÃ¡o cÃ¡o | `DashboardService`, `DailyReportService`, `ProfitService` | Dashboard, bÃ¡o cÃ¡o ngÃ y, tá»“n kho/lá»£i nhuáº­n |

NgoÃ i `app/Services`, thÆ° má»¥c `app/Models/Services` cÅ©ng tá»“n táº¡i vÃ  chá»©a nhiá»u file service cÃ³ namespace `App\Services`. Luá»“ng Controller Ä‘Ã£ kiá»ƒm tra Ä‘ang `use App\Services\...`, vÃ¬ váº­y pháº§n bÃ¡o cÃ¡o ghi nháº­n `app/Services` lÃ  nÆ¡i service Ä‘ang Ä‘Æ°á»£c tham chiáº¿u chÃ­nh; `app/Models/Services` lÃ  thÃ nh pháº§n tá»“n táº¡i trong mÃ£ nguá»“n nhÆ°ng chÆ°a cáº§n tÃ¡ch thÃ nh Repository.

Má»™t sá»‘ nghiá»‡p vá»¥ váº«n xá»­ lÃ½ trá»±c tiáº¿p táº¡i Controller thÃ´ng qua Model, Query Builder hoáº·c helper, vÃ­ dá»¥ táº¡o Ä‘Æ¡n bÃ¡n hÃ ng trong `Staff\OrderController`, táº¡o phiáº¿u tiá»n máº·t/ngÃ¢n hÃ ng trong `CashTransactionController`/`BankTransactionController`, upload attachment vÃ  xuáº¥t file.

### 4.2.6. Repositories

[KHÃ”NG PHÃT HIá»†N TRONG MÃƒ NGUá»’N HIá»†N Táº I] ChÆ°a phÃ¡t hiá»‡n lá»›p Repository riÃªng, interface Repository hoáº·c binding Repository trong Service Provider. CÃ¡c truy váº¥n hiá»‡n Ä‘Æ°á»£c thá»±c hiá»‡n thÃ´ng qua Model Eloquent, Query Builder, raw SQL trong Controller, hoáº·c thÃ´ng qua Service.

### 4.2.7. Form Requests vÃ  validation

Form Request Ä‘ang cÃ³ trong:

* `app/Http/Requests/Auth/LoginRequest.php`: validate Ä‘Äƒng nháº­p.
* `app/Http/Requests/CategoryRequest.php`: validate danh má»¥c.
* `app/Http/Requests/Company/CompanyRequest.php`: validate cÃ´ng ty/nhÃ  cung cáº¥p theo form.
* `app/Http/Requests/Product/ProductRequest.php`: validate sáº£n pháº©m.
* `app/Http/Requests/ProductRequest.php`: Form Request cÅ©/khÃ¡c namespace, chÆ°a xÃ¡c minh route dÃ¹ng trá»±c tiáº¿p.

Validation trá»±c tiáº¿p trong Controller cÅ©ng xuáº¥t hiá»‡n nhiá»u, báº±ng `$request->validate()`, `Validator::make()`, `$this->validate()`. CÃ¡c module dÃ¹ng validate trá»±c tiáº¿p gá»“m tÃ i khoáº£n káº¿ toÃ¡n, chi nhÃ¡nh, thÆ°Æ¡ng hiá»‡u, nhÃ¢n viÃªn, cáº¥u hÃ¬nh, khÃ¡ch hÃ ng staff, Ä‘Æ¡n hÃ ng staff, cash/bank transaction, chiáº¿n dá»‹ch, cÃ´ng ná»£ vÃ  bulk action.

ChÆ°a phÃ¡t hiá»‡n rule tÃ¹y chá»‰nh tÃ¡ch thÃ nh class riÃªng trong pháº¡m vi mÃ£ nguá»“n Ä‘Ã£ kiá»ƒm tra.

### 4.2.8. Middleware, Policies vÃ  Gates

Middleware tÃ¹y chá»‰nh Ä‘Ã£ xÃ¡c minh:

| Middleware | CÃ¡ch sá»­ dá»¥ng hiá»‡n táº¡i | Nguá»“n xÃ¡c minh |
| --- | --- | --- |
| `RoleMiddleware` | Alias `role`, kiá»ƒm tra `auth()->check()` vÃ  `role_id`; role `1` hoáº·c `2` Ä‘i qua, cÃ¡c role khÃ¡c pháº£i náº±m trong danh sÃ¡ch route | `app/Http/Kernel.php`, `app/Http/Middleware/RoleMiddleware.php`, `routes/web.php` |
| `CheckLogin` | Kiá»ƒm tra Ä‘Äƒng nháº­p vÃ  `role_id` thuá»™c `[1,2,3]` cho khu bÃ¡n hÃ ng | `app/Http/Middleware/CheckLogin.php`, `routes/web.php` |
| `CheckLoginSuperAdmin` | Kiá»ƒm tra session `authSuper` cho khu `super-admin` | `app/Http/Middleware/CheckLoginSuperAdmin.php`, `routes/web.php` |
| `CheckRole` | Tá»“n táº¡i trong mÃ£ nguá»“n, kiá»ƒm tra `Auth::check()` vÃ  role truyá»n vÃ o | `app/Http/Middleware/CheckRole.php` |
| Middleware Laravel chuáº©n | Auth, guest, CSRF, session, throttle, bindings | `app/Http/Kernel.php` |

ChÆ°a phÃ¡t hiá»‡n thÆ° má»¥c `app/Policies`. ChÆ°a phÃ¡t hiá»‡n Ä‘Äƒng kÃ½ Gate nghiá»‡p vá»¥ trong `AuthServiceProvider`. Trong view cÃ³ kiá»ƒm tra Ä‘iá»u kiá»‡n theo `Auth::user()->role_id` vÃ  session á»Ÿ má»™t sá»‘ layout, nhÆ°ng chÆ°a phÃ¡t hiá»‡n directive Blade `@can` cho nghiá»‡p vá»¥ chÃ­nh trong pháº¡m vi tÃ¬m kiáº¿m.

### 4.2.9. Jobs vÃ  Queues

Dá»± Ã¡n cÃ³ cÃ¡c Job:

| Job | Má»¥c Ä‘Ã­ch | Nguá»“n xÃ¡c minh |
| --- | --- | --- |

Queue connection máº·c Ä‘á»‹nh lÃ  `sync` theo `.env.example` vÃ  `config/queue.php`. Dá»± Ã¡n cÃ³ migration `2024_08_28_142348_create_jobs_table.php` vÃ  README.dev.md hÆ°á»›ng dáº«n `php artisan queue:work`, cháº¡y ná»n báº±ng `screen`/`tmux` hoáº·c Supervisor. ChÆ°a phÃ¡t hiá»‡n file cáº¥u hÃ¬nh Supervisor riÃªng trong repo. ChÆ°a phÃ¡t hiá»‡n retry/timeout tÃ¹y chá»‰nh trong Job ngoÃ i cÆ¡ cháº¿ máº·c Ä‘á»‹nh cá»§a Laravel.

### 4.2.10. Events vÃ  Listeners

Event tÃ¹y chá»‰nh Ä‘Ã£ phÃ¡t hiá»‡n lÃ  `App\Events\CustomerLogin`. Listener tÆ°Æ¡ng á»©ng `App\Listeners\SendMailOtpCustomerLogin` Ä‘Æ°á»£c Ä‘Äƒng kÃ½ trong `EventServiceProvider` vÃ  triá»ƒn khai `ShouldQueue`; listener gá»­i email OTP qua `Mail::send('emails.otp_email', ...)`.

`EventServiceProvider` cÅ©ng giá»¯ mapping máº·c Ä‘á»‹nh `Registered => SendEmailVerificationNotification`. ChÆ°a phÃ¡t hiá»‡n nhiá»u event nghiá»‡p vá»¥ khÃ¡c cho Ä‘Æ¡n hÃ ng, thanh toÃ¡n, kho hoáº·c bÃ¡o cÃ¡o trong pháº¡m vi mÃ£ nguá»“n Ä‘Ã£ kiá»ƒm tra.

### 4.2.11. Views vÃ  API Resources

Blade view Ä‘Æ°á»£c chia theo khu vá»±c:

* `resources/views/admin`: giao diá»‡n quáº£n trá»‹ sáº£n pháº©m, kho, nháº­p hÃ ng, khÃ¡ch hÃ ng, cÃ´ng ty, bÃ¡o cÃ¡o, káº¿ toÃ¡n, cáº¥u hÃ¬nh, layout.
* `resources/views/sa`: má»™t nhÃ³m layout/store/profile khÃ¡c cho super admin hoáº·c admin store.
* `resources/views/Themes`: layout staff, mÃ n bÃ¡n hÃ ng, Ä‘Æ¡n hÃ ng, kiá»ƒm kÃª, cÃ¡c báº£n view theme/admin copy.
* `resources/views/auth`: Ä‘Äƒng nháº­p, quÃªn máº­t kháº©u, xÃ¡c minh OTP.
* `resources/views/emails`: email OTP, thÃ´ng tin tÃ i khoáº£n, thÃ´ng bÃ¡o Ä‘Äƒng kÃ½.
* `resources/views/components` vÃ  `app/View/Components`: component `breadcrumb`, `card`.
* `resources/views/vendor/pagination`: template phÃ¢n trang.

ChÆ°a phÃ¡t hiá»‡n `app/Http/Resources` hoáº·c `JsonResource`. API/JSON response hiá»‡n chá»§ yáº¿u tráº£ trá»±c tiáº¿p báº±ng `response()->json()`, helper `successResponse()`/`errorResponse()`, hoáº·c render HTML partial trong response AJAX.

### 4.2.12. Tests

ThÆ° má»¥c `tests` tá»“n táº¡i vá»›i Unit vÃ  Feature test. `phpunit.xml` khai bÃ¡o test suite `Unit` vÃ  `Feature`, bootstrap `vendor/autoload.php`, mÃ´i trÆ°á»ng `APP_ENV=testing`, cache/session array, mail array vÃ  queue sync. Cáº¥u hÃ¬nh SQLite in-memory Ä‘ang comment.

CÃ¡c test hiá»‡n cÃ³:

* `tests/Feature/ExampleTest.php`: kiá»ƒm tra `/` redirect tá»›i route login.
* `tests/Unit/ExampleTest.php`: test máº«u `true`.
* `tests/Unit/UploadedImageUrlTest.php`: kiá»ƒm tra má»™t sá»‘ view khÃ´ng render áº£nh upload báº±ng cÃ¡c máº«u asset cÅ©.


#### Báº£ng tá»•ng há»£p thÃ nh pháº§n kiáº¿n trÃºc

| ThÃ nh pháº§n | CÃ³ trong dá»± Ã¡n | CÃ¡ch sá»­ dá»¥ng hiá»‡n táº¡i | Module tiÃªu biá»ƒu | Nguá»“n xÃ¡c minh |
| --- | --- | --- | --- | --- |
| Routes | CÃ³ | Táº­p trung trong `web.php`, cÃ³ `api.php` route máº«u | Admin, Staff/POS, SuperAdmin, API `/user` | `routes/web.php`, `routes/api.php` |
| Controllers | CÃ³ | Chia theo namespace vai trÃ², tráº£ Blade/JSON/file | `Admin`, `Staff`, `SuperAdmin` | `app/Http/Controllers` |
| Repositories | KhÃ´ng | ChÆ°a phÃ¡t hiá»‡n Repository Pattern riÃªng | KhÃ´ng Ã¡p dá»¥ng | TÃ¬m kiáº¿m `app/Repositories`, `Repository` |
| Form Requests | CÃ³ | DÃ¹ng cho Ä‘Äƒng nháº­p, danh má»¥c, cÃ´ng ty, sáº£n pháº©m; nhiá»u Controller validate trá»±c tiáº¿p | Auth, Category, Product, Company | `app/Http/Requests`, `app/Http/Controllers` |
| Middleware | CÃ³ | Auth, guest, role, CheckLogin, CheckLoginSuperAdmin | Admin, Staff, SuperAdmin | `app/Http/Kernel.php`, `app/Http/Middleware`, `routes/web.php` |
| Policies/Gates | KhÃ´ng phÃ¡t hiá»‡n | ChÆ°a tháº¥y policy/gate nghiá»‡p vá»¥ riÃªng | KhÃ´ng Ã¡p dá»¥ng | `app/Policies` khÃ´ng tá»“n táº¡i, `AuthServiceProvider` |
| Events/Listeners | CÃ³ | `CustomerLogin` -> gá»­i OTP email; event Registered máº·c Ä‘á»‹nh | Auth/OTP | `app/Events`, `app/Listeners`, `app/Providers/EventServiceProvider.php` |
| Views/API Resources | CÃ³ view, khÃ´ng phÃ¡t hiá»‡n API Resource | Blade theo module; JSON response trá»±c tiáº¿p | Admin, POS, SuperAdmin, Email | `resources/views`, `app/Http/Resources` khÃ´ng tá»“n táº¡i |
| Tests | CÃ³ | Unit/Feature test cÆ¡ báº£n vÃ  test view upload | Redirect login, uploaded image URL | `tests`, `phpunit.xml` |

## 4.3. Luá»“ng xá»­ lÃ½ dá»¯ liá»‡u

### 4.3.1. Luá»“ng web request thÃ´ng thÆ°á»ng

Luá»“ng web Ä‘Ã£ xÃ¡c minh:

`TrÃ¬nh duyá»‡t -> routes/web.php -> middleware web/auth/role hoáº·c middleware tÃ¹y chá»‰nh -> Controller -> Form Request hoáº·c validate trá»±c tiáº¿p -> Service hoáº·c Model/Query Builder -> Eloquent/DB transaction -> Blade View, redirect, JSON response hoáº·c file download`

VÃ­ dá»¥ sáº£n pháº©m Admin:

1. NgÆ°á»i dÃ¹ng Ä‘Ã£ Ä‘Äƒng nháº­p truy cáº­p `/admin/products`.
2. Route thuá»™c group `admin`, qua middleware `auth` vÃ  `role:1`.
3. `Admin\ProductController` nháº­n request.
4. `ProductRequest` validate khi táº¡o/cáº­p nháº­t sáº£n pháº©m.
5. Controller dÃ¹ng `uploadImages()` náº¿u cÃ³ thumbnail, gÃ¡n `user_id`, sinh mÃ£ báº±ng `generateCode()`.
6. Dá»¯ liá»‡u lÆ°u vÃ o báº£ng `products` báº±ng Eloquent `Product::create()` hoáº·c `update()`.
7. Há»‡ thá»‘ng tráº£ JSON qua `successResponse()` hoáº·c Blade view/partial table cho AJAX.

### 4.3.2. Luá»“ng xá»­ lÃ½ API

`routes/api.php` hiá»‡n chá»‰ cÃ³:

`Client -> /api/user -> middleware auth:sanctum -> Closure -> tráº£ vá» $request->user()`

Dá»± Ã¡n cÃ³ cáº¥u hÃ¬nh Sanctum vÃ  Model `User` dÃ¹ng `HasApiTokens`, nhÆ°ng chÆ°a xÃ¡c minh Ä‘Æ°á»£c Ä‘áº§y Ä‘á»§ má»™t luá»“ng API nghiá»‡p vá»¥ hoÃ n chá»‰nh cÃ³ Controller API, Form Request API, Resource vÃ  response schema riÃªng. CÃ¡c JSON response nghiá»‡p vá»¥ trong há»‡ thá»‘ng hiá»‡n xuáº¥t hiá»‡n nhiá»u á»Ÿ web route/AJAX, khÃ´ng pháº£i nhÃ³m API tÃ¡ch riÃªng.

### 4.3.3. Luá»“ng xÃ¡c thá»±c vÃ  phÃ¢n quyá»n

Luá»“ng Ä‘Äƒng nháº­p web chÃ­nh:

1. NgÆ°á»i dÃ¹ng truy cáº­p `/login`, route `guest` tráº£ view `auth.login`.
2. Form gá»­i POST `/login` tá»›i `AuthController::authenticate`.
3. `LoginRequest` validate email/password.
4. `Auth::attempt()` kiá»ƒm tra tÃ i khoáº£n qua provider `users`.
5. Controller kiá»ƒm tra `status` cá»§a user.
6. Náº¿u há»£p lá»‡, session guard `web` Ä‘Æ°á»£c táº¡o; há»‡ thá»‘ng tráº£ JSON thÃ nh cÃ´ng kÃ¨m Ä‘Æ°á»ng dáº«n redirect.
7. `role_id` quyáº¿t Ä‘á»‹nh khu vá»±c chuyá»ƒn Ä‘áº¿n: role `1,2` vÃ o `/admin`, role `3` vÃ o `/ban-hang`.
8. CÃ¡c request tiáº¿p theo Ä‘i qua middleware `auth`, `role`, `CheckLogin` hoáº·c `CheckLoginSuperAdmin`.
9. Khi khÃ´ng cÃ³ quyá»n, middleware redirect login hoáº·c `abort(403)`.

Luá»“ng Super Admin riÃªng:

`GET/POST super-dang-nhap -> SuperAdminController -> SupperAdminService -> Auth::login($supper) vÃ  session authSuper -> CheckLoginSuperAdmin -> /super-admin/*`

### 4.3.4. Luá»“ng nghiá»‡p vá»¥ qua Service







**Luá»“ng bÃ¡o cÃ¡o tá»“n kho/lá»£i nhuáº­n**

`Route admin/inventory hoáº·c admin/profit -> auth + role -> ReportController -> ProductStorageService/ProfitService -> ProductStorage, ImportCoupon, ImportDetail, OrderDetail -> Blade/JSON/PDF`

Service tá»•ng há»£p dá»¯ liá»‡u tá»“n kho, nháº­p hÃ ng, bÃ¡n hÃ ng vÃ  lá»£i nhuáº­n theo kho/ká»³, sau Ä‘Ã³ Controller tráº£ view, JSON hoáº·c PDF.

### 4.3.5. Luá»“ng xá»­ lÃ½ trá»±c tiáº¿p trong Controller

VÃ­ dá»¥ luá»“ng bÃ¡n hÃ ng trong `Staff\OrderController::store`:

`Route POST /ban-hang/order -> CheckLogin + role:3 -> Staff\OrderController -> Validator::make -> Product kiá»ƒm tra tá»“n kho -> DB::beginTransaction -> Order::create -> OrderDetail::create -> Product::decrement -> Transaction/TransactionEntry theo phÆ°Æ¡ng thá»©c thanh toÃ¡n -> DB::commit -> JSON response`

Luá»“ng nÃ y dÃ¹ng trá»±c tiáº¿p Model `Product`, `Order`, `OrderDetail`, `Transaction`, `TransactionEntry`, `Account` trong Controller. Khi thanh toÃ¡n tiá»n máº·t/chuyá»ƒn khoáº£n/cÃ´ng ná»£, Controller táº¡o giao dá»‹ch káº¿ toÃ¡n tÆ°Æ¡ng á»©ng. Náº¿u lá»—i xáº£y ra trong transaction, há»‡ thá»‘ng rollback vÃ  tráº£ JSON lá»—i.

Má»™t vÃ­ dá»¥ khÃ¡c lÃ  `CashTransactionController` vÃ  `BankTransactionController`: Controller validate request, xá»­ lÃ½ attachment, táº¡o `Transaction`, táº¡o cÃ¡c dÃ²ng `TransactionEntry`, dÃ¹ng raw SQL/Query Builder Ä‘á»ƒ tá»•ng há»£p sá»‘ dÆ° vÃ  tráº£ JSON/partial table.

### 4.3.6. Luá»“ng upload vÃ  lÆ°u trá»¯ file

CÃ¡c luá»“ng upload chÃ­nh:

* Sáº£n pháº©m: `ProductController::store/update` nháº­n `thumbnail`, validate báº±ng `ProductRequest`, gá»i `uploadImages('thumbnail', 'products')`, lÆ°u Ä‘Æ°á»ng dáº«n vÃ o cá»™t `thumbnail`.
* Cáº¥u hÃ¬nh cá»­a hÃ ng: `ConfigController::save` nháº­n `logo`, gá»i `uploadImages('logo', 'logo')`, lÆ°u vÃ o báº£ng `config`, xÃ³a logo cÅ© náº¿u cÃ³ upload má»›i.
* Giao dá»‹ch tiá»n máº·t/ngÃ¢n hÃ ng: `CashTransactionController` vÃ  `BankTransactionController` nháº­n `attachment`, validate file `jpg/jpeg/png/pdf/webp`, sinh tÃªn báº±ng `Str::uuid()`, lÆ°u vÃ o `attachments/cash_transactions` trÃªn disk `public`, lÆ°u Ä‘Æ°á»ng dáº«n vÃ o `transactions.attachment`.
* Helper áº£nh: `uploadImages()` dÃ¹ng Intervention Image Ä‘á»ƒ encode WebP vÃ  `Storage::disk('public')->put()`. `deleteImage()` xÃ³a file qua disk `public`; `showImage()` tráº£ URL public hoáº·c áº£nh máº·c Ä‘á»‹nh.

Disk public trá» tá»›i `storage/app/public`; `public/storage` hiá»‡n lÃ  Junction tá»›i thÆ° má»¥c storage public. CKFinder cÅ©ng cáº¥u hÃ¬nh backend local vÃ o `storage/app/public`.

### 4.3.7. Luá»“ng queue hoáº·c tÃ¡c vá»¥ ná»n




#### SÆ¡ Ä‘á»“ Mermaid luá»“ng xá»­ lÃ½ dá»¯ liá»‡u tá»•ng quÃ¡t

```mermaid
flowchart LR
    User["NgÆ°á»i dÃ¹ng hoáº·c trÃ¬nh duyá»‡t"] --> WebRoute["routes/web.php"]
    ApiClient["API client"] --> ApiRoute["routes/api.php /user"]
    WebRoute --> Middleware["Middleware auth, role, CheckLogin, CheckLoginSuperAdmin"]
    ApiRoute --> Sanctum["auth:sanctum"]
    Middleware --> Controller["Controller theo khu vá»±c"]
    Sanctum --> JsonUser["JSON user hiá»‡n táº¡i"]
    Controller --> FormRequest["Form Request hoáº·c validate trá»±c tiáº¿p"]
    FormRequest --> Service["Service Layer"]
    FormRequest --> DirectModel["Model hoáº·c Query Builder trá»±c tiáº¿p"]
    Service --> Model["Eloquent Model"]
    DirectModel --> Model
    Model --> DB[("MySQL")]
    Service --> Queue["Job hoáº·c Listener queue"]
    Service --> Mail["Mail"]
    Controller --> Blade["Blade View"]
    Controller --> Json["JSON/AJAX Response"]
    Controller --> File["PDF/Excel/File download"]
    Controller --> Storage["Storage public/local"]
    Storage --> PublicStorage["public/storage -> storage/app/public"]
```

#### Báº£ng tá»•ng há»£p luá»“ng xá»­ lÃ½

| Loáº¡i luá»“ng | Äiá»ƒm báº¯t Ä‘áº§u | ThÃ nh pháº§n xá»­ lÃ½ chÃ­nh | NÆ¡i lÆ°u dá»¯ liá»‡u | Káº¿t quáº£ tráº£ vá» | Nguá»“n xÃ¡c minh |
| --- | --- | --- | --- | --- | --- |
| Web request | Browser gá»­i request tá»›i `routes/web.php` | Middleware, Controller, Form Request/Validator, Service hoáº·c Model | MySQL qua Eloquent/DB | Blade, redirect, JSON, file | `routes/web.php`, `app/Http/Controllers`, `app/Services` |
| API request | `/api/user` | `auth:sanctum`, Closure | KhÃ´ng ghi dá»¯ liá»‡u trong route hiá»‡n táº¡i | JSON user hiá»‡n táº¡i | `routes/api.php`, `config/sanctum.php` |
| XÃ¡c thá»±c | POST `/login`, POST `super-dang-nhap` | `LoginRequest`, `AuthController`, `SuperAdminController`, Service, middleware | Session file theo cáº¥u hÃ¬nh | Redirect/JSON, session Ä‘Äƒng nháº­p | `config/auth.php`, `config/session.php`, `AuthController`, `CheckLoginSuperAdmin` |
| Upload file | Form sáº£n pháº©m/cáº¥u hÃ¬nh/giao dá»‹ch | Form Request/validate, helper `uploadImages`, `Storage::disk('public')`, `storeAs` | `storage/app/public`, DB lÆ°u Ä‘Æ°á»ng dáº«n | URL/file hiá»ƒn thá»‹ hoáº·c attachment | `app/Helpers/helper.php`, `ProductController`, `ConfigController`, `CashTransactionController`, `BankTransactionController` |
| BÃ¡n hÃ ng | `/ban-hang`, POST `/ban-hang/order` | `Staff\ProductController`, `Staff\OrderController`, Validator, DB transaction | `orders`, `order_details`, `products`, `transactions`, `transaction_entries` | JSON hoáº·c Blade | `routes/web.php`, `app/Http/Controllers/Staff/OrderController.php` |
| BÃ¡o cÃ¡o/PDF/Excel | Admin report/export route | Controller, Service, DomPDF, Excel/PhpSpreadsheet | Äá»c MySQL, file táº¡m/output stream | PDF/Excel download, JSON, Blade | `ReportController`, `DailyReportController`, `ProductController`, `ClientController`, `app/Exports` |

## 4.4. MÃ´ hÃ¬nh kiáº¿n trÃºc tá»•ng quÃ¡t


## Nguá»“n xÃ¡c minh

| Ná»™i dung | File, thÆ° má»¥c hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| Backend vÃ  package PHP | `composer.json`, `composer.lock`, `artisan`, `bootstrap/app.php`, `app`, `config` |
| Frontend vÃ  thÆ° viá»‡n giao diá»‡n | `package.json`, `package-lock.json`, `vite.config.js`, `resources/js`, `resources/css`, `resources/views`, `public/assets`, `public/global`, `public/js/ckfinder` |
| CÆ¡ sá»Ÿ dá»¯ liá»‡u | `config/database.php`, `database/migrations`, `database/seeders`, `database/factories`, `app/Models` |
| Routes vÃ  middleware | `routes/web.php`, `routes/api.php`, `routes/console.php`, `app/Http/Kernel.php`, `app/Http/Middleware` |
| Controllers vÃ  nghiá»‡p vá»¥ | `app/Http/Controllers`, `app/Services`, `app/Helpers/helper.php` |
| XÃ¡c thá»±c vÃ  phÃ¢n quyá»n | `config/auth.php`, `config/session.php`, `config/sanctum.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/SuperAdmin/SuperAdminController.php`, `app/Http/Middleware/RoleMiddleware.php`, `CheckLogin.php`, `CheckLoginSuperAdmin.php`, `app/Models/User.php`, `app/Models/SuperAdmin.php` |
| Queue vÃ  tÃ¡c vá»¥ ná»n | `app/Jobs`, `app/Listeners`, `app/Events`, `app/Providers/EventServiceProvider.php`, `config/queue.php`, `database/migrations/2024_08_28_142348_create_jobs_table.php`, `README.dev.md` |
| Views vÃ  API | `resources/views`, `routes/api.php`, `app/Http/Responses`, `app/Http/Resources` khÃ´ng tá»“n táº¡i trong pháº¡m vi kiá»ƒm tra |
| File storage/upload | `config/filesystems.php`, `config/ckfinder.php`, `app/Helpers/helper.php`, `public/storage`, `app/Http/Controllers/Admin/ProductController.php`, `ConfigController.php`, `CashTransactionController.php`, `BankTransactionController.php` |
| Kiá»ƒm thá»­ | `tests`, `phpunit.xml` |
| Triá»ƒn khai | `README.md`, `README.dev.md`, `.git`, `composer.json`, `package.json`; chÆ°a phÃ¡t hiá»‡n Dockerfile, docker-compose, CI/CD, Nginx/Apache hoáº·c file Supervisor riÃªng |

# 5. ÄÃNH GIÃ CHá»¨C NÄ‚NG HIá»†N Táº I

## 5.1. PhÆ°Æ¡ng phÃ¡p Ä‘Ã¡nh giÃ¡

Pháº§n Ä‘Ã¡nh giÃ¡ nÃ y Ä‘Æ°á»£c thá»±c hiá»‡n theo hÆ°á»›ng kiá»ƒm tra tÄ©nh káº¿t há»£p cháº¡y cÃ¡c lá»‡nh Ä‘á»c tráº¡ng thÃ¡i an toÃ n cá»§a Laravel, khÃ´ng sá»­a mÃ£ nguá»“n, khÃ´ng thay Ä‘á»•i cáº¥u trÃºc cÆ¡ sá»Ÿ dá»¯ liá»‡u vÃ  khÃ´ng thao tÃ¡c dá»¯ liá»‡u tháº­t. Má»—i chá»©c nÄƒng Ä‘Æ°á»£c Ä‘á»‘i chiáº¿u tá»« route, middleware, controller, request validation, service, model, migration, view Blade, queue/job, cáº¥u hÃ¬nh package vÃ  test hiá»‡n cÃ³. CÃ¡c chá»©c nÄƒng cáº§n tÃ i khoáº£n tháº­t, dá»¯ liá»‡u nghiá»‡p vá»¥ tháº­t hoáº·c khÃ³a API bÃªn thá»© ba chá»‰ Ä‘Æ°á»£c xÃ¡c minh á»Ÿ má»©c mÃ£ nguá»“n vÃ  Ä‘Æ°á»£c Ä‘Ã¡nh dáº¥u riÃªng. Khi má»™t luá»“ng cÃ³ route vÃ  giao diá»‡n nhÆ°ng controller rá»—ng, thiáº¿u migration, lá»‡ch model-schema hoáº·c cÃ³ nguy cÆ¡ lÃ m sai dá»¯ liá»‡u, tráº¡ng thÃ¡i khÃ´ng Ä‘Æ°á»£c xem lÃ  á»•n Ä‘á»‹nh. Káº¿t quáº£ kiá»ƒm thá»­ tá»± Ä‘á»™ng chá»‰ pháº£n Ã¡nh bá»™ test hiá»‡n táº¡i, khÃ´ng Ä‘Æ°á»£c dÃ¹ng thay tháº¿ cho kiá»ƒm thá»­ nghiá»‡p vá»¥ Ä‘áº§y Ä‘á»§.

| TiÃªu chÃ­ kiá»ƒm tra | CÃ¡ch Ä‘á»‘i chiáº¿u |
| --- | --- |
| Route vÃ  middleware | Kiá»ƒm tra `routes/web.php`, `routes/api.php`, `app/Http/Middleware` vÃ  káº¿t quáº£ `php artisan route:list` |
| Controller vÃ  service | Äá»‘i chiáº¿u method route gá»i tá»›i controller/service tÆ°Æ¡ng á»©ng, transaction, validate, catch lá»—i vÃ  pháº£n há»“i |
| Model vÃ  database | So sÃ¡nh fillable, quan há»‡ Eloquent, migration Ä‘Ã£ cháº¡y/chÆ°a cháº¡y vÃ  tÃªn cá»™t Ä‘Æ°á»£c sá»­ dá»¥ng |
| View vÃ  thao tÃ¡c ngÆ°á»i dÃ¹ng | Kiá»ƒm tra Blade, AJAX endpoint, form, nÃºt thao tÃ¡c vÃ  dá»¯ liá»‡u gá»­i lÃªn |
| TÃ­ch há»£p vÃ  queue | Kiá»ƒm tra package, job, scheduler, queue config, log vÃ  Ä‘iá»u kiá»‡n credential |
| Kiá»ƒm thá»­ | Äá»‘i chiáº¿u `tests`, `phpunit.xml`, káº¿t quáº£ `php artisan test` vÃ  khoáº£ng trá»‘ng coverage |

## 5.2. Quy táº¯c xÃ¡c Ä‘á»‹nh tráº¡ng thÃ¡i

| Tráº¡ng thÃ¡i | Quy táº¯c Ã¡p dá»¥ng |
| --- | --- |
| Hoáº¡t Ä‘á»™ng á»•n Ä‘á»‹nh | CÃ³ route, UI hoáº·c API, controller xá»­ lÃ½ Ä‘áº§y Ä‘á»§, validation há»£p lÃ½, schema khá»›p, cÃ³ kiá»ƒm thá»­ hoáº·c cÃ³ thá»ƒ xÃ¡c minh end-to-end an toÃ n, khÃ´ng tháº¥y lá»—i dá»¯ liá»‡u nghiÃªm trá»ng trong pháº¡m vi kiá»ƒm tra |
| Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | Luá»“ng chÃ­nh cÃ³ thá»ƒ cháº¡y theo mÃ£ nguá»“n, nhÆ°ng cÃ²n thiáº¿u má»™t sá»‘ nhÃ¡nh, thiáº¿u kiá»ƒm thá»­, phá»¥ thuá»™c cáº¥u hÃ¬nh, thiáº¿u kiá»ƒm soÃ¡t quyá»n chi tiáº¿t hoáº·c cÃ²n rá»§i ro váº­n hÃ nh chÆ°a cháº·n luá»“ng chÃ­nh |
| Hoáº¡t Ä‘á»™ng má»™t pháº§n | Chá»‰ má»™t pháº§n luá»“ng cÃ³ thá»ƒ xÃ¡c minh; cÃ³ route/view/controller nhÆ°ng thiáº¿u bÆ°á»›c quan trá»ng, lá»‡ch dá»¯ liá»‡u, thiáº¿u cáº­p nháº­t tá»“n kho/káº¿ toÃ¡n, thiáº¿u transaction hoáº·c cÃ³ lá»—i khiáº¿n má»™t nhÃ¡nh nghiá»‡p vá»¥ Ä‘Ã¡ng ká»ƒ khÃ´ng tin cáº­y |
| ChÆ°a hoáº¡t Ä‘á»™ng | CÃ³ dáº¥u váº¿t chá»©c nÄƒng nhÆ°ng implementation rá»—ng, route trá» sai method, migration/schema lÃ m luá»“ng khÃ³ cháº¡y hoáº·c lá»—i Ä‘Ã£ xÃ¡c minh khiáº¿n chá»©c nÄƒng khÃ´ng sá»­ dá»¥ng Ä‘Æ°á»£c theo má»¥c tiÃªu |
| ChÆ°a cÃ³ chá»©c nÄƒng | ChÆ°a tháº¥y route/controller/view/service cho nghiá»‡p vá»¥, hoáº·c route Ä‘ang bá»‹ comment vÃ  khÃ´ng cÃ³ luá»“ng thay tháº¿ |
| ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra | Chá»©c nÄƒng phá»¥ thuá»™c tÃ i khoáº£n tháº­t, dá»¯ liá»‡u tháº­t, khÃ³a API, worker/scheduler hoáº·c mÃ´i trÆ°á»ng ngoÃ i chÆ°a cÃ³ trong pháº¡m vi kiá»ƒm tra; chá»‰ káº¿t luáº­n Ä‘Æ°á»£c vá» mÃ£ nguá»“n tÄ©nh |

## 5.3. Thang Ä‘iá»ƒm má»©c Ä‘á»™ hoÃ n thiá»‡n

Äiá»ƒm hoÃ n thiá»‡n Ä‘Æ°á»£c cháº¥m theo 10 tiÃªu chÃ­, má»—i tiÃªu chÃ­ tá»‘i Ä‘a 10 Ä‘iá»ƒm: route/entrypoint, giao diá»‡n hoáº·c API, xá»­ lÃ½ controller/service, validation, schema/model, phÃ¢n quyá»n, tÃ­nh Ä‘Ãºng dá»¯ liá»‡u nghiá»‡p vá»¥, xá»­ lÃ½ lá»—i/transaction, tÃ­ch há»£p hoáº·c file/queue náº¿u cÃ³, vÃ  kiá»ƒm thá»­. Äiá»ƒm khÃ´ng nháº±m kháº³ng Ä‘á»‹nh cháº¥t lÆ°á»£ng sáº£n pháº©m cuá»‘i cÃ¹ng; Ä‘iá»ƒm chá»‰ pháº£n Ã¡nh má»©c Ä‘á»™ cÃ³ thá»ƒ xÃ¡c minh trong mÃ£ nguá»“n hiá»‡n táº¡i.

| Khoáº£ng Ä‘iá»ƒm | Diá»…n giáº£i |
| --- | --- |
| 85-100% | Gáº§n hoÃ n chá»‰nh, Ã­t rá»§i ro rÃµ rÃ ng trong pháº¡m vi kiá»ƒm tra |
| 70-84% | Luá»“ng chÃ­nh cÃ³ thá»ƒ dÃ¹ng nhÆ°ng cÃ²n thiáº¿u kiá»ƒm thá»­, phÃ¢n quyá»n chi tiáº¿t hoáº·c xá»­ lÃ½ biÃªn |
| 45-69% | CÃ³ ná»n táº£ng nhÆ°ng thiáº¿u bÆ°á»›c nghiá»‡p vá»¥ quan trá»ng hoáº·c cÃ³ rá»§i ro dá»¯ liá»‡u cáº§n sá»­a trÆ°á»›c khi tin cáº­y |
| 1-44% | CÃ³ dáº¥u váº¿t chá»©c nÄƒng nhÆ°ng hiá»‡n chÆ°a Ä‘á»§ Ä‘á»ƒ váº­n hÃ nh Ä‘Ãºng má»¥c tiÃªu |
| 0% | ChÆ°a cÃ³ chá»©c nÄƒng hoáº·c route bá»‹ comment, khÃ´ng cÃ³ implementation |
| N/A | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra do thiáº¿u mÃ´i trÆ°á»ng, credential, dá»¯ liá»‡u hoáº·c worker |

## 5.4. Báº£ng Ä‘Ã¡nh giÃ¡ tá»•ng há»£p

Tá»•ng sá»‘ chá»©c nÄƒng/nhÃ³m chá»©c nÄƒng Ä‘Ã£ nháº­n diá»‡n: **34**.

| STT | NhÃ³m | Chá»©c nÄƒng | Tráº¡ng thÃ¡i | Äiá»ƒm | Báº±ng chá»©ng chÃ­nh | Ghi chÃº rá»§i ro |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | XÃ¡c thá»±c | ÄÄƒng nháº­p tÃ i khoáº£n admin/staff | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 78% | `routes/web.php`, `AuthController::authenticate`, `LoginRequest`, `resources/views/auth/login.blade.php` | CÃ³ kiá»ƒm tra `inactive/locked`, nhÆ°ng chÆ°a tháº¥y throttling, role 4 khÃ´ng cÃ³ Ä‘iá»u hÆ°á»›ng rÃµ, JS lá»—i dÃ¹ng `datgin.error` cÃ³ thá»ƒ lÃ m thÃ´ng bÃ¡o lá»—i khÃ´ng hiá»ƒn thá»‹ |
| 2 | XÃ¡c thá»±c | ÄÄƒng xuáº¥t | Hoáº¡t Ä‘á»™ng á»•n Ä‘á»‹nh | 90% | `AuthController::logout`, route `logout` | CÃ³ `logout`, invalidate session vÃ  regenerate token |
| 3 | XÃ¡c thá»±c | QuÃªn máº­t kháº©u/Ä‘áº·t láº¡i máº­t kháº©u | ChÆ°a cÃ³ chá»©c nÄƒng | 0% | Route `forget-password` vÃ  MoMo trong `routes/web.php` Ä‘ang bá»‹ comment | ChÆ°a tháº¥y controller/view/reset token tÆ°Æ¡ng á»©ng |
| 4 | XÃ¡c thá»±c | Äá»•i máº­t kháº©u ngÆ°á»i dÃ¹ng | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 45% | `AdminController@changePassword`, `AdminService::changePassword` | Controller phá»¥ thuá»™c `session('authUser')`, trong khi login chuáº©n dÃ¹ng `Auth::attempt`; dá»… tráº£ 401 sau Ä‘Äƒng nháº­p bÃ¬nh thÆ°á»ng |
| 5 | XÃ¡c thá»±c | KhÃ³a/má»Ÿ tráº¡ng thÃ¡i tÃ i khoáº£n | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 80% | `AuthController`, `UserController`, `EmployeeController` | Tráº¡ng thÃ¡i `active/inactive/locked` Ä‘Æ°á»£c kiá»ƒm tra khi login, nhÆ°ng chÆ°a cÃ³ luá»“ng audit hoáº·c lá»‹ch sá»­ khÃ³a |
| 6 | PhÃ¢n quyá»n | Äiá»u hÆ°á»›ng vÃ  middleware vai trÃ² | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 60% | `RoleMiddleware`, `CheckLogin`, route group `role:1`, `role:3`, `role:4` | `role_id` 2 Ä‘Æ°á»£c cho qua má»i nhÃ³m role, role 4 cÃ³ route nhÆ°ng khÃ´ng Ä‘Æ°á»£c Ä‘iá»u hÆ°á»›ng sau login; chÆ°a tháº¥y policy/gate |
| 7 | TÃ i khoáº£n | Quáº£n lÃ½ tÃ i khoáº£n quáº£n trá»‹/chi nhÃ¡nh | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 78% | `Admin/UserController`, views admin, route `admin/users` | CÃ³ list/create/update, chÆ°a tháº¥y delete riÃªng, phá»¥ thuá»™c SMTP khi gá»­i mail |
| 8 | TÃ i khoáº£n | Quáº£n lÃ½ nhÃ¢n viÃªn bÃ¡n hÃ ng | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 78% | `Admin/EmployeeController`, routes `admin/employees` | CÃ³ CRUD chÃ­nh vÃ  gÃ¡n kho, nhÆ°ng thiáº¿u test nghiá»‡p vá»¥ vÃ  chÆ°a tháº¥y delete/khÃ³a riÃªng ngoÃ i status |
| 9 | HÃ ng hÃ³a | Quáº£n lÃ½ sáº£n pháº©m CRUD | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 62% | `ProductController`, `ProductRequest`, `ProductService`, `Product` model, migrations products | List/create/update cÃ³, nhÆ°ng delete khÃ´ng rÃµ, import rá»—ng, lá»‡ch `brand_id` vÃ  `brands_id`, tráº¡ng thÃ¡i product giá»¯a request vÃ  migration khÃ´ng thá»‘ng nháº¥t |
| 10 | HÃ ng hÃ³a | Import Excel sáº£n pháº©m | ChÆ°a hoáº¡t Ä‘á»™ng | 20% | Route `admin/products/import`, `ProductController::import(Request $request) {}` | CÃ³ route nhÆ°ng method rá»—ng |
| 11 | HÃ ng hÃ³a | Quáº£n lÃ½ danh má»¥c | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 55% | `CategorieController`, route category delete | Route delete trá» `destroy` trong khi controller cÃ³ `delete`, nguy cÆ¡ lá»—i method khÃ´ng tá»“n táº¡i |
| 12 | HÃ ng hÃ³a | Quáº£n lÃ½ thÆ°Æ¡ng hiá»‡u | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 58% | `BrandController`, route brand, product form | CÃ³ list/create/update, nhÆ°ng khÃ´ng tháº¥y route xÃ³a; product dÃ¹ng cáº£ `brand_id` vÃ  `brands_id` |
| 13 | Äá»‘i tÃ¡c | Quáº£n lÃ½ khÃ¡ch hÃ ng | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 78% | `Admin/ClientController`, `Staff/ClientController`, `ClientService` | POS thÃªm khÃ¡ch cÃ³ validate, admin update/delete/export cÃ³, nhÆ°ng unique rule theo user chÆ°a cháº·t vÃ  thiáº¿u test |
| 14 | Äá»‘i tÃ¡c | CÃ´ng ty, nhÃ  cung cáº¥p, ngÆ°á»i Ä‘áº¡i diá»‡n | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 75% | `CompanyController`, `SupplierController`, `RepresentativePersonController`, requests/services | CÃ³ nghiá»‡p vá»¥ chÃ­nh, nhÆ°ng má»™t sá»‘ nhÃ¡nh thiáº¿u validate rÃµ vÃ  company khÃ´ng tháº¥y route xÃ³a |
| 15 | Kho | Quáº£n lÃ½ kho hÃ ng | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 74% | `StorageController`, `StorageService`, `ProductStorage`, migrations storage/product_storage | CÃ³ ná»n táº£ng kho vÃ  sáº£n pháº©m theo kho; cÃ²n phá»¥ thuá»™c cÃ¡c luá»“ng nháº­p/bÃ¡n Ä‘á»ƒ giá»¯ sá»‘ liá»‡u Ä‘Ãºng |
| 16 | Kho | Nháº­p hÃ ng vÃ  phiáº¿u nháº­p | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 63% | `ImportProductController`, `importCouponController`, `ImportCoupon`, `ProductStorageService` | CÃ³ táº¡o phiáº¿u vÃ  tÄƒng tá»“n, nhÆ°ng dÃ¹ng báº£ng táº¡m global `import`, `Import::truncate()`, thiáº¿u transaction rÃµ vÃ  migration `storage_id` cá»§a import_coupon Ä‘ang pending |
| 17 | Kho | Tá»“n kho theo kho | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 55% | `ProductStorageService::inventoryReport`, `ReportController`, `Staff\OrderController` | Nháº­p hÃ ng cáº­p nháº­t `product_storage`, nhÆ°ng bÃ¡n hÃ ng hiá»‡n táº¡i chá»‰ giáº£m `products.quantity`, khÃ´ng giáº£m tá»“n theo kho |
| 18 | Kho | Kiá»ƒm kÃª kho | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 52% | `Staff/CheckInventoryController`, `warehome`, `CheckInventory`, `CheckDetail` | CÃ³ táº¡o phiáº¿u kiá»ƒm kÃª tá»« báº£ng táº¡m, nhÆ°ng khÃ´ng cáº­p nháº­t tá»“n thá»±c táº¿ vÃ  cÃ³ `warehome::truncate()` global |
| 19 | BÃ¡n hÃ ng | POS tÃ¬m sáº£n pháº©m vÃ  khÃ¡ch hÃ ng | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 76% | `resources/views/Themes/pages/layout_staff/index.blade.php`, `Staff/ProductController`, `Staff/ClientController` | Giao diá»‡n POS hoáº¡t Ä‘á»™ng theo AJAX, nhÆ°ng endpoint chÃ­nh láº¥y product global, chÆ°a khÃ³a cháº·t theo tá»“n kho cá»§a nhÃ¢n viÃªn |
| 20 | BÃ¡n hÃ ng | POS táº¡o Ä‘Æ¡n/thanh toÃ¡n | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 65% | `Staff/OrderController::store`, `Order`, `OrderDetail`, `Transaction`, `TransactionEntry` | CÃ³ transaction DB vÃ  kiá»ƒm tra sá»‘ lÆ°á»£ng global, nhÆ°ng khÃ´ng ghi `storage_id` cho order detail, khÃ´ng giáº£m `product_storage`, phá»¥ thuá»™c account code káº¿ toÃ¡n cÃ³ sáºµn |
| 21 | BÃ¡n hÃ ng | Quáº£n lÃ½ Ä‘Æ¡n hÃ ng | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 72% | `Admin/OrderController`, `Staff/OrderController`, views order | CÃ³ list/detail/filter, nhÆ°ng chÆ°a tháº¥y luá»“ng há»§y/Ä‘á»•i tráº£/cáº­p nháº­t tráº¡ng thÃ¡i Ä‘áº§y Ä‘á»§ vÃ  thiáº¿u test |
| 22 | Thu chi | Phiáº¿u thu/chi legacy | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 45% | `ReceiptController`, `ExpenseController`, routes receipt/expense | Route cÃ²n tá»“n táº¡i nhÆ°ng menu cÃ³ pháº§n bá»‹ comment; cÃ³ `ClientDebtsDetail::truncate()` vÃ  `SupplierDebtsDetail::truncate()` khi háº¿t ná»£, rá»§i ro xÃ³a dá»¯ liá»‡u toÃ n cá»¥c |
| 23 | CÃ´ng ná»£ | CÃ´ng ná»£ khÃ¡ch hÃ ng/NCC vÃ  Ä‘áº§u ká»³ | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 75% | `DebtController`, `TransactionEntry`, routes debts | CÃ³ bÃ¡o cÃ¡o vÃ  ghi Ä‘áº§u ká»³, nhÆ°ng Ä‘á»™ Ä‘Ãºng phá»¥ thuá»™c bÃºt toÃ¡n tá»« POS/nháº­p hÃ ng vÃ  mapping account |
| 24 | Káº¿ toÃ¡n | Há»‡ thá»‘ng tÃ i khoáº£n káº¿ toÃ¡n | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 80% | `AccountController`, `accounts`, recursive balance query | CÃ³ CRUD, cÃ¢y tÃ i khoáº£n, kiá»ƒm tra sá»‘ dÆ°; thiáº¿u test vÃ  cÃ²n phá»¥ thuá»™c danh sÃ¡ch tÃ i khoáº£n máº·c Ä‘á»‹nh |
| 25 | Káº¿ toÃ¡n | Giao dá»‹ch tiá»n máº·t/ngÃ¢n hÃ ng | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 82% | `CashTransactionController`, `BankTransactionController`, `Transaction`, `TransactionEntry` | CÃ³ validate, attachment vÃ  DB transaction; chÆ°a cÃ³ test há»“i quy cho bÃºt toÃ¡n vÃ  sá»‘ dÆ° |
| 26 | BÃ¡o cÃ¡o | Dashboard quáº£n trá»‹ | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 76% | `DashboardController`, views dashboard | CÃ³ sá»‘ liá»‡u doanh thu, sáº£n pháº©m, khÃ¡ch hÃ ng; má»™t sá»‘ truy váº¥n chÆ°a scope rÃµ theo user/kho |
| 27 | BÃ¡o cÃ¡o | BÃ¡o cÃ¡o tá»“n kho, lá»£i nhuáº­n, bÃ¡o cÃ¡o ngÃ y | Hoáº¡t Ä‘á»™ng má»™t pháº§n | 60% | `ReportController`, `DailyReportController`, `ReportdebtController`, `ProfitService` | BÃ¡o cÃ¡o theo kho phá»¥ thuá»™c `order_details.storage_id`; migration thÃªm cá»™t nÃ y Ä‘ang pending vÃ  POS hiá»‡n chÆ°a ghi cá»™t Ä‘Ã³ |
| 28 | SuperAdmin | ÄÄƒng nháº­p SuperAdmin vÃ  quáº£n lÃ½ store | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 72% | `SuperAdminController`, `CheckLoginSuperAdmin`, `StoreController` | CÃ³ session `authSuper` vÃ  store list/detail/delete, nhÆ°ng delete dÃ¹ng GET vÃ  thiáº¿u kiá»ƒm thá»­ |
| 32 | API/AJAX | API vÃ  endpoint ná»™i bá»™ | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 78% | `routes/api.php`, AJAX routes trong `routes/web.php` | API cÃ´ng khai chá»‰ cÃ³ `/api/user` qua Sanctum; nghiá»‡p vá»¥ chá»§ yáº¿u lÃ  web/AJAX ná»™i bá»™ |
| 33 | TÃ­ch há»£p | Email, SMS, PDF, Excel, upload file | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra | N/A | `Mail`, `Excel`, DomPDF, CKFinder, helper upload, `.env` | MÃ£ nguá»“n cÃ³ package vÃ  class, nhÆ°ng cáº§n SMTP/SMS/API/file tháº­t Ä‘á»ƒ xÃ¡c minh Ä‘áº§y Ä‘á»§; test hiá»‡n chá»‰ kiá»ƒm tra chuá»—i asset áº£nh |
| 34 | Thanh toÃ¡n | Thanh toÃ¡n trá»±c tuyáº¿n MoMo | ChÆ°a cÃ³ chá»©c nÄƒng | 0% | `routes/web.php` cÃ³ route payment bá»‹ comment, `PaymentController` chÆ°a Ä‘Æ°á»£c ná»‘i route Ä‘ang hoáº¡t Ä‘á»™ng | ChÆ°a cÃ³ luá»“ng thanh toÃ¡n online Ä‘ang báº­t |

### 5.4.1. Thá»‘ng kÃª tráº¡ng thÃ¡i

| Tráº¡ng thÃ¡i | Sá»‘ lÆ°á»£ng |
| --- | ---: |
| Hoáº¡t Ä‘á»™ng á»•n Ä‘á»‹nh | 1 |
| Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ | 15 |
| Hoáº¡t Ä‘á»™ng má»™t pháº§n | 12 |
| ChÆ°a hoáº¡t Ä‘á»™ng | 1 |
| ChÆ°a cÃ³ chá»©c nÄƒng | 2 |
| ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra | 3 |
| Tá»•ng cá»™ng | 34 |

## 5.5. ÄÃ¡nh giÃ¡ chi tiáº¿t cÃ¡c chá»©c nÄƒng trá»ng yáº¿u

### 5.5.1. ÄÄƒng nháº­p, tráº¡ng thÃ¡i tÃ i khoáº£n vÃ  phÃ¢n quyá»n

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | Cho phÃ©p ngÆ°á»i dÃ¹ng Ä‘Äƒng nháº­p, Ä‘iá»u hÆ°á»›ng theo vai trÃ² vÃ  cháº·n tÃ i khoáº£n khÃ´ng há»£p lá»‡ |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | Admin, quáº£n trá»‹ chi nhÃ¡nh, nhÃ¢n viÃªn bÃ¡n hÃ ng, káº¿ toÃ¡n/kho, SuperAdmin |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | CÃ³ user há»£p lá»‡ trong báº£ng `users` hoáº·c `super_admins`, session hoáº¡t Ä‘á»™ng, middleware route Ä‘Ãºng |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | `GET/POST /login`, `AuthController::authenticate`, `LoginRequest`, `RoleMiddleware`, `CheckLogin`, `CheckLoginSuperAdmin` |
| Káº¿t quáº£ mong Ä‘á»£i | ÄÄƒng nháº­p thÃ nh cÃ´ng sáº½ Ä‘Æ°a user tá»›i Ä‘Ãºng khu vá»±c; tÃ i khoáº£n khÃ³a hoáº·c inactive bá»‹ tá»« chá»‘i; role bá»‹ giá»›i háº¡n Ä‘Ãºng chá»©c nÄƒng |
| Káº¿t quáº£ thá»±c táº¿ | Login chÃ­nh cÃ³ validate vÃ  kiá»ƒm tra tráº¡ng thÃ¡i. Tuy nhiÃªn `role_id` 2 Ä‘Æ°á»£c `RoleMiddleware` cho qua má»i role group, role 4 khÃ´ng cÃ³ Ä‘iá»u hÆ°á»›ng sau login, vÃ  má»™t sá»‘ view/controller váº«n dÃ¹ng `session('authUser')` ngoÃ i luá»“ng Auth chuáº©n |
| Tráº¡ng thÃ¡i | Hoáº¡t Ä‘á»™ng má»™t pháº§n cho phÃ¢n quyá»n tá»•ng thá»ƒ; riÃªng Ä‘Äƒng nháº­p Ä‘áº¡t má»©c hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ |
| Má»©c hoÃ n thiá»‡n | 60-78% tÃ¹y nhÃ¡nh |
| Lá»—i/rá»§i ro | NgÆ°á»i dÃ¹ng role 2 cÃ³ thá»ƒ vÆ°á»£t pháº¡m vi káº¿ toÃ¡n/kho náº¿u biáº¿t URL; Ä‘á»•i máº­t kháº©u cÃ³ thá»ƒ khÃ´ng cháº¡y sau login chuáº©n; role 4 khÃ³ vÃ o Ä‘Ãºng luá»“ng |
| áº¢nh hÆ°á»Ÿng | Sai quyá»n truy cáº­p cÃ³ thá»ƒ áº£nh hÆ°á»Ÿng dá»¯ liá»‡u kho, káº¿ toÃ¡n vÃ  bÃ¡n hÃ ng |
| Äá» xuáº¥t | RÃ  láº¡i ma tráº­n role, bá» bypass role 2 náº¿u khÃ´ng cÃ³ chá»§ Ä‘Ã­ch, thÃªm policy/gate hoáº·c permission table thá»±c thi, chuáº©n hÃ³a dÃ¹ng `Auth::user()` thay cho `session('authUser')` |
| Nguá»“n xÃ¡c minh | `routes/web.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/CheckLogin.php`, `app/Providers/AuthServiceProvider.php` |

### 5.5.2. Quáº£n lÃ½ sáº£n pháº©m, danh má»¥c vÃ  thÆ°Æ¡ng hiá»‡u

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | Quáº£n lÃ½ dá»¯ liá»‡u hÃ ng hÃ³a, phÃ¢n loáº¡i sáº£n pháº©m, thÆ°Æ¡ng hiá»‡u vÃ  áº£nh sáº£n pháº©m |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | Admin vÃ  tÃ i khoáº£n quáº£n trá»‹ cá»­a hÃ ng |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | Migration products/categories/brands khá»›p vá»›i model vÃ  form; storage public hoáº¡t Ä‘á»™ng |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | `ProductController`, `ProductRequest`, `ProductService`, `CategorieController`, `BrandController`, form add/edit sáº£n pháº©m |
| Káº¿t quáº£ mong Ä‘á»£i | CRUD sáº£n pháº©m Ä‘áº§y Ä‘á»§, import Excel hoáº¡t Ä‘á»™ng, danh má»¥c/thÆ°Æ¡ng hiá»‡u Ä‘á»“ng bá»™ vá»›i schema |
| Káº¿t quáº£ thá»±c táº¿ | List/create/update sáº£n pháº©m cÃ³ logic rÃµ vÃ  validate áº£nh. Tuy nhiÃªn `ProductController::import` rá»—ng, product request dÃ¹ng `brand_id` trong khi migration/model/view dÃ¹ng `brands_id`, route xÃ³a danh má»¥c trá» `destroy` nhÆ°ng controller cÃ³ `delete` |
| Tráº¡ng thÃ¡i | Hoáº¡t Ä‘á»™ng má»™t pháº§n |
| Má»©c hoÃ n thiá»‡n | 55-62% |
| Lá»—i/rá»§i ro | Import sáº£n pháº©m khÃ´ng cháº¡y; danh má»¥c cÃ³ nguy cÆ¡ lá»—i route; thÆ°Æ¡ng hiá»‡u sáº£n pháº©m cÃ³ thá»ƒ lÆ°u/Ä‘á»c khÃ´ng nháº¥t quÃ¡n |
| áº¢nh hÆ°á»Ÿng | Sai dá»¯ liá»‡u sáº£n pháº©m lÃ m áº£nh hÆ°á»Ÿng POS, nháº­p hÃ ng, tá»“n kho, bÃ¡o cÃ¡o lá»£i nhuáº­n vÃ  xuáº¥t Excel |
| Äá» xuáº¥t | Chuáº©n hÃ³a má»™t tÃªn cá»™t thÆ°Æ¡ng hiá»‡u, sá»­a route xÃ³a danh má»¥c, triá»ƒn khai hoáº·c gá»¡ route import rá»—ng, bá»• sung test CRUD sáº£n pháº©m vÃ  danh má»¥c |
| Nguá»“n xÃ¡c minh | `app/Http/Controllers/Admin/ProductController.php`, `app/Http/Requests/Product/ProductRequest.php`, `app/Models/Product.php`, `database/migrations/*products*`, `app/Http/Controllers/Admin/CategorieController.php`, `app/Http/Controllers/Admin/BrandController.php` |

### 5.5.3. Nháº­p hÃ ng, tá»“n kho theo kho vÃ  kiá»ƒm kÃª

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | Ghi nháº­n nháº­p hÃ ng, tÄƒng tá»“n kho tá»•ng vÃ  tá»“n kho theo tá»«ng kho, kiá»ƒm kÃª thá»±c táº¿ |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | Admin, bá»™ pháº­n kho, nhÃ¢n viÃªn Ä‘Æ°á»£c gÃ¡n kho |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | CÃ³ kho, sáº£n pháº©m, nhÃ  cung cáº¥p, phiáº¿u nháº­p, báº£ng `product_storage`, cÃ¡c migration storage Ä‘Ã£ cháº¡y |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | `ImportProductController`, `importCouponController`, `ProductStorageService`, `Staff/CheckInventoryController`, `Staff/WareHomeController` |
| Káº¿t quáº£ mong Ä‘á»£i | Nháº­p hÃ ng táº¡o phiáº¿u vÃ  cáº­p nháº­t tá»“n theo kho trong transaction; kiá»ƒm kÃª cáº­p nháº­t hoáº·c ghi nháº­n chÃªnh lá»‡ch cÃ³ kiá»ƒm soÃ¡t |
| Káº¿t quáº£ thá»±c táº¿ | Nháº­p hÃ ng cÃ³ táº¡o phiáº¿u vÃ  tÄƒng tá»“n thÃ´ng qua service, nhÆ°ng giá» nháº­p dÃ¹ng báº£ng táº¡m global `import`, sau khi lÆ°u dÃ¹ng `Import::truncate()`. Kiá»ƒm kÃª táº¡o phiáº¿u tá»« báº£ng `warehome` rá»“i `warehome::truncate()`, chÆ°a tháº¥y bÆ°á»›c cáº­p nháº­t tá»“n thá»±c táº¿ |
| Tráº¡ng thÃ¡i | Hoáº¡t Ä‘á»™ng má»™t pháº§n |
| Má»©c hoÃ n thiá»‡n | 52-63% |
| Lá»—i/rá»§i ro | Báº£ng táº¡m dÃ¹ng chung cÃ³ thá»ƒ lÃ m láº«n dá»¯ liá»‡u giá»¯a ngÆ°á»i dÃ¹ng; truncate toÃ n báº£ng cÃ³ thá»ƒ xÃ³a dá»¯ liá»‡u phiÃªn khÃ¡c; thiáº¿u transaction rÃµ á»Ÿ phiáº¿u nháº­p lá»›n |
| áº¢nh hÆ°á»Ÿng | CÃ³ nguy cÆ¡ sai tá»“n kho, sai phiáº¿u nháº­p vÃ  sai bÃ¡o cÃ¡o kho khi nhiá»u ngÆ°á»i thao tÃ¡c |
| Äá» xuáº¥t | Scope báº£ng táº¡m theo `user_id/session_id/storage_id`, bá»c lÆ°u phiáº¿u nháº­p vÃ  kiá»ƒm kÃª trong `DB::transaction`, thay truncate báº±ng xÃ³a cÃ³ Ä‘iá»u kiá»‡n, xÃ¡c Ä‘á»‹nh rÃµ kiá»ƒm kÃª cÃ³ cáº­p nháº­t tá»“n hay chá»‰ ghi nháº­n chÃªnh lá»‡ch |
| Nguá»“n xÃ¡c minh | `app/Http/Controllers/Admin/ImportProductController.php`, `app/Http/Controllers/Admin/importCouponController.php`, `app/Services/ProductStorageService.php`, `app/Http/Controllers/Staff/CheckInventoryController.php`, `app/Http/Controllers/Staff/WareHomeController.php` |

### 5.5.4. POS bÃ¡n hÃ ng vÃ  táº¡o Ä‘Æ¡n

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | BÃ¡n hÃ ng táº¡i quáº§y, tÃ¬m sáº£n pháº©m/khÃ¡ch hÃ ng, táº¡o Ä‘Æ¡n, ghi nháº­n thanh toÃ¡n vÃ  giáº£m tá»“n |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | NhÃ¢n viÃªn bÃ¡n hÃ ng, quáº£n trá»‹ viÃªn há»— trá»£ bÃ¡n hÃ ng |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | User cÃ³ kho, sáº£n pháº©m cÃ²n hÃ ng, khÃ¡ch hÃ ng há»£p lá»‡ hoáº·c khÃ¡ch láº», tÃ i khoáº£n káº¿ toÃ¡n cáº¥u hÃ¬nh Ä‘á»§ mÃ£ |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | View POS `resources/views/Themes/pages/layout_staff/index.blade.php`, `Staff/ProductController`, `Staff/OrderController::store` |
| Káº¿t quáº£ mong Ä‘á»£i | ÄÆ¡n hÃ ng lÆ°u Ä‘áº§y Ä‘á»§, giáº£m tá»“n kho Ä‘Ãºng kho, táº¡o chi tiáº¿t Ä‘Æ¡n, táº¡o bÃºt toÃ¡n Ä‘Ãºng phÆ°Æ¡ng thá»©c thanh toÃ¡n |
| Káº¿t quáº£ thá»±c táº¿ | Luá»“ng hiá»‡n táº¡i cÃ³ validate request, kiá»ƒm tra sá»‘ lÆ°á»£ng `products.quantity`, táº¡o `orders`, `order_details`, giáº£m `products.quantity` vÃ  táº¡o transaction entries. NhÆ°ng khÃ´ng cáº­p nháº­t `product_storage`, khÃ´ng gÃ¡n `storage_id` cho `order_details`, endpoint product chÃ­nh chÆ°a luÃ´n khÃ³a theo tá»“n kho cá»§a nhÃ¢n viÃªn |
| Tráº¡ng thÃ¡i | Hoáº¡t Ä‘á»™ng má»™t pháº§n |
| Má»©c hoÃ n thiá»‡n | 65% |
| Lá»—i/rá»§i ro | Tá»“n tá»•ng vÃ  tá»“n theo kho cÃ³ thá»ƒ lá»‡ch nhau; bÃ¡o cÃ¡o theo kho khÃ´ng Ä‘á»§ dá»¯ liá»‡u; náº¿u thiáº¿u mÃ£ tÃ i khoáº£n káº¿ toÃ¡n thÃ¬ Ä‘Æ¡n cÃ³ thá»ƒ rollback |
| áº¢nh hÆ°á»Ÿng | áº¢nh hÆ°á»Ÿng trá»±c tiáº¿p Ä‘áº¿n bÃ¡n hÃ ng, tá»“n kho vÃ  bÃ¡o cÃ¡o lá»£i nhuáº­n |
| Äá» xuáº¥t | Khi táº¡o Ä‘Æ¡n pháº£i giáº£m `product_storage` theo kho cá»§a user, ghi `storage_id` vÃ o `order_details`, kiá»ƒm thá»­ cÃ¡c phÆ°Æ¡ng thá»©c cash/bank/debt vÃ  chuáº©n hÃ³a endpoint tÃ¬m sáº£n pháº©m theo kho |
| Nguá»“n xÃ¡c minh | `app/Http/Controllers/Staff/OrderController.php`, `app/Http/Controllers/Staff/ProductController.php`, `app/Models/OrderDetail.php`, `app/Services/ProductStorageService.php`, POS Blade |

### 5.5.5. CÃ´ng ná»£, thu chi vÃ  káº¿ toÃ¡n

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | Ghi nháº­n cÃ´ng ná»£ khÃ¡ch hÃ ng/NCC, phiáº¿u thu/chi, giao dá»‹ch tiá»n máº·t/ngÃ¢n hÃ ng vÃ  tÃ i khoáº£n káº¿ toÃ¡n |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | Káº¿ toÃ¡n, admin, chá»§ cá»­a hÃ ng |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | CÃ³ há»‡ thá»‘ng tÃ i khoáº£n, giao dá»‹ch, transaction entries, dá»¯ liá»‡u khÃ¡ch/NCC vÃ  quyá»n káº¿ toÃ¡n |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | `DebtController`, `AccountController`, `CashTransactionController`, `BankTransactionController`, `ReceiptController`, `ExpenseController` |
| Káº¿t quáº£ mong Ä‘á»£i | Giao dá»‹ch ghi bÃºt toÃ¡n cÃ¢n Ä‘á»‘i, cÃ´ng ná»£ Ä‘Æ°á»£c cáº­p nháº­t cÃ³ lá»‹ch sá»­ chi tiáº¿t, khÃ´ng xÃ³a dá»¯ liá»‡u ngoÃ i pháº¡m vi phiáº¿u |
| Káº¿t quáº£ thá»±c táº¿ | Module tÃ i khoáº£n vÃ  giao dá»‹ch tiá»n máº·t/ngÃ¢n hÃ ng cÃ³ cáº¥u trÃºc tÆ°Æ¡ng Ä‘á»‘i rÃµ, validate vÃ  DB transaction. Tuy nhiÃªn luá»“ng receipt/expense legacy cÃ²n route vÃ  cÃ³ thao tÃ¡c truncate báº£ng chi tiáº¿t cÃ´ng ná»£ khi háº¿t ná»£ |
| Tráº¡ng thÃ¡i | Hoáº¡t Ä‘á»™ng nhÆ°ng cÃ²n háº¡n cháº¿ cho káº¿ toÃ¡n má»›i; hoáº¡t Ä‘á»™ng má»™t pháº§n cho thu chi legacy |
| Má»©c hoÃ n thiá»‡n | 45-82% |
| Lá»—i/rá»§i ro | `ClientDebtsDetail::truncate()` vÃ  `SupplierDebtsDetail::truncate()` lÃ  rá»§i ro máº¥t dá»¯ liá»‡u toÃ n cá»¥c; bÃºt toÃ¡n tá»« POS/nháº­p hÃ ng cáº§n Ä‘Æ°á»£c káº¿ toÃ¡n kiá»ƒm tra láº¡i |
| áº¢nh hÆ°á»Ÿng | CÃ³ thá»ƒ lÃ m sai lá»‹ch sá»­ cÃ´ng ná»£ vÃ  bÃ¡o cÃ¡o tÃ i chÃ­nh |
| Äá» xuáº¥t | VÃ´ hiá»‡u hÃ³a hoáº·c sá»­a legacy receipt/expense, thay truncate báº±ng cáº­p nháº­t/xÃ³a theo phiáº¿u vÃ  Ä‘á»‘i tÆ°á»£ng, thÃªm test sá»‘ dÆ° tÃ i khoáº£n vÃ  cÃ´ng ná»£ |
| Nguá»“n xÃ¡c minh | `app/Http/Controllers/Admin/ReceiptController.php`, `app/Http/Controllers/Admin/ExpenseController.php`, `app/Http/Controllers/Admin/DebtController.php`, `app/Http/Controllers/Admin/AccountController.php`, `app/Http/Controllers/Admin/CashTransactionController.php`, `app/Http/Controllers/Admin/BankTransactionController.php` |

### 5.5.6. BÃ¡o cÃ¡o vÃ  dashboard

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | Cung cáº¥p thá»‘ng kÃª doanh thu, tá»“n kho, lá»£i nhuáº­n, cÃ´ng ná»£ vÃ  bÃ¡o cÃ¡o ngÃ y |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | Admin, káº¿ toÃ¡n, quáº£n lÃ½ kho, chá»§ cá»­a hÃ ng |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | Dá»¯ liá»‡u Ä‘Æ¡n hÃ ng, chi tiáº¿t Ä‘Æ¡n, nháº­p hÃ ng, tá»“n kho theo kho vÃ  bÃºt toÃ¡n pháº£i nháº¥t quÃ¡n |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | `DashboardController`, `ReportController`, `DailyReportController`, `ReportdebtController`, `ProfitService` |
| Káº¿t quáº£ mong Ä‘á»£i | BÃ¡o cÃ¡o lá»c Ä‘Ãºng theo kho/ngÃ y/ká»³, sá»‘ liá»‡u khá»›p vá»›i Ä‘Æ¡n hÃ ng vÃ  nháº­p hÃ ng |
| Káº¿t quáº£ thá»±c táº¿ | CÃ³ nhiá»u controller vÃ  view bÃ¡o cÃ¡o. Tuy nhiÃªn bÃ¡o cÃ¡o theo kho phá»¥ thuá»™c `order_details.storage_id`, trong khi luá»“ng POS hiá»‡n táº¡i chÆ°a ghi cá»™t nÃ y; má»™t sá»‘ bÃ¡o cÃ¡o cÅ© dÃ¹ng `quantity` trong khi model hiá»‡n dÃ¹ng `p_quantity` |
| Tráº¡ng thÃ¡i | Hoáº¡t Ä‘á»™ng má»™t pháº§n |
| Má»©c hoÃ n thiá»‡n | 60-76% |
| Lá»—i/rá»§i ro | BÃ¡o cÃ¡o tá»“n kho/lá»£i nhuáº­n theo kho cÃ³ thá»ƒ thiáº¿u hoáº·c lá»‡ch doanh sá»‘ bÃ¡n; dashboard cÃ³ truy váº¥n chÆ°a scope rÃµ theo user/kho |
| áº¢nh hÆ°á»Ÿng | NgÆ°á»i quáº£n lÃ½ cÃ³ thá»ƒ ra quyáº¿t Ä‘á»‹nh dá»±a trÃªn sá»‘ liá»‡u chÆ°a nháº¥t quÃ¡n |
| Äá» xuáº¥t | Chuáº©n hÃ³a dá»¯ liá»‡u order detail, cháº¡y migration cáº§n thiáº¿t, sá»­a POS Ä‘á»ƒ ghi kho, thÃªm test bÃ¡o cÃ¡o theo máº«u dá»¯ liá»‡u nhá» cÃ³ thá»ƒ kiá»ƒm chá»©ng thá»§ cÃ´ng |
| Nguá»“n xÃ¡c minh | `app/Http/Controllers/Admin/ReportController.php`, `app/Http/Controllers/Admin/DailyReportController.php`, `app/Services/ProfitService.php`, `app/Models/OrderDetail.php`, `database/migrations/2024_08_23_103647_add_storage_id_to_order_details_table.php` |


| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | SuperAdmin hoáº·c ngÆ°á»i quáº£n trá»‹ há»‡ thá»‘ng |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | CÃ³ tÃ i khoáº£n SuperAdmin, OA active, app id/secret, access token/refresh token há»£p lá»‡, queue/worker náº¿u gá»­i ná»n |
| Äá» xuáº¥t | XÃ³a hard-code token, rotate credential, khÃ´ng log token, sá»­a key refresh token, thÃªm dispatch job rÃµ rÃ ng, cáº¥u hÃ¬nh queue/failed_jobs/scheduler trÆ°á»›c khi gá»­i tháº­t |

### 5.5.8. Queue, email, file, PDF vÃ  Excel

| Má»¥c | ÄÃ¡nh giÃ¡ |
| --- | --- |
| Má»¥c Ä‘Ã­ch | Xá»­ lÃ½ tÃ¡c vá»¥ ná»n, gá»­i email, export/import file, táº¡o PDF/Excel vÃ  upload áº£nh/attachment |
| Äá»‘i tÆ°á»£ng sá»­ dá»¥ng | Há»‡ thá»‘ng, admin, nhÃ¢n viÃªn, khÃ¡ch hÃ ng nháº­n thÃ´ng bÃ¡o |
| Äiá»u kiá»‡n Ä‘á»ƒ hoáº¡t Ä‘á»™ng | Cáº¥u hÃ¬nh queue, SMTP/SMS/API, storage public, worker, báº£ng jobs/failed_jobs náº¿u dÃ¹ng database queue |
| Luá»“ng Ä‘Ã£ Ä‘á»‘i chiáº¿u | `config/queue.php`, `app/Jobs`, `app/Mail`, `README.dev.md`, helper upload, package DomPDF/Excel |
| Káº¿t quáº£ mong Ä‘á»£i | Queue ghi job vÃ  failed job, mail gá»­i Ä‘Æ°á»£c qua SMTP, file/PDF/Excel hoáº¡t Ä‘á»™ng vá»›i dá»¯ liá»‡u tháº­t |
| Káº¿t quáº£ thá»±c táº¿ | Package vÃ  class cÃ³ tá»“n táº¡i, README cÃ³ hÆ°á»›ng dáº«n queue worker. Tuy nhiÃªn `php artisan about` cho tháº¥y queue Ä‘ang lÃ  `sync`, scheduler khÃ´ng cÃ³ command tháº­t, `php artisan queue:failed` lá»—i do thiáº¿u báº£ng `failed_jobs` |
| Tráº¡ng thÃ¡i | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra Ä‘áº§y Ä‘á»§ |
| Má»©c hoÃ n thiá»‡n | N/A |
| Lá»—i/rá»§i ro | KhÃ´ng ghi nháº­n failed job Ä‘Æ°á»£c; job cháº¡y sync cÃ³ thá»ƒ lÃ m request cháº­m hoáº·c khÃ´ng Ä‘Ãºng ká»³ vá»ng; mail/SMS cáº§n credential ngoÃ i |
| Äá» xuáº¥t | Táº¡o migration `failed_jobs`, cáº¥u hÃ¬nh queue worker/supervisor, thÃªm scheduler náº¿u cÃ³ job Ä‘á»‹nh ká»³, cáº¥u hÃ¬nh mÃ´i trÆ°á»ng test cho mail/file vÃ  test export/import |
| Nguá»“n xÃ¡c minh | `config/queue.php`, `database/migrations/2024_08_28_142348_create_jobs_table.php`, `app/Console/Kernel.php`, `README.dev.md`, káº¿t quáº£ `php artisan queue:failed` |

## 5.6. Chá»©c nÄƒng chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra

| Chá»©c nÄƒng | LÃ½ do chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Cáº§n bá»• sung Ä‘á»ƒ kiá»ƒm tra |
| --- | --- | --- |
| Queue worker vÃ  failed job | Queue hiá»‡n lÃ  `sync`; `php artisan queue:failed` bÃ¡o thiáº¿u báº£ng `failed_jobs`; scheduler chÆ°a cÃ³ command tháº­t | Migration `failed_jobs`, queue connection phÃ¹ há»£p, worker/supervisor, test job lá»—i cÃ³ kiá»ƒm soÃ¡t |
| Email SMTP vÃ  SMS/Kavenegar | Cáº§n credential SMTP/SMS tháº­t hoáº·c sandbox; khÃ´ng nÃªn gá»­i ra ngoÃ i khi chÆ°a cÃ³ mÃ´i trÆ°á»ng test | SMTP sandbox, fake mail transport, SMS sandbox vÃ  test assertion |
| Thanh toÃ¡n trá»±c tuyáº¿n | Route MoMo/payment Ä‘ang bá»‹ comment, chÆ°a cÃ³ luá»“ng hoáº¡t Ä‘á»™ng trong route hiá»‡n táº¡i | Báº­t route sandbox, cáº¥u hÃ¬nh payment gateway test, test callback |
| Hiá»‡u nÄƒng vÃ  dá»¯ liá»‡u lá»›n | ChÆ°a cÃ³ dá»¯ liá»‡u lá»›n, chÆ°a cháº¡y benchmark hoáº·c profiling | Dataset test, query profiling, kiá»ƒm thá»­ phÃ¢n trang/export vá»›i dá»¯ liá»‡u lá»›n |
| Ma tráº­n quyá»n ngÆ°á»i dÃ¹ng tháº­t | Cáº§n tÃ i khoáº£n máº«u cho tá»«ng role vÃ  dá»¯ liá»‡u thuá»™c nhiá»u chi nhÃ¡nh/kho | Seed tÃ i khoáº£n role 1/2/3/4, test truy cáº­p tá»«ng route vÃ  dá»¯ liá»‡u cross-store |

## 5.7. Tá»•ng há»£p theo nhÃ³m chá»©c nÄƒng

| NhÃ³m chá»©c nÄƒng | Tá»•ng chá»©c nÄƒng | á»”n Ä‘á»‹nh | Háº¡n cháº¿ | Má»™t pháº§n | ChÆ°a hoáº¡t Ä‘á»™ng | ChÆ°a cÃ³ | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Äiá»ƒm trung bÃ¬nh cÃ³ thá»ƒ cháº¥m |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| XÃ¡c thá»±c vÃ  phÃ¢n quyá»n | 6 | 1 | 2 | 2 | 0 | 1 | 0 | 58.8% |
| TÃ i khoáº£n ngÆ°á»i dÃ¹ng | 2 | 0 | 2 | 0 | 0 | 0 | 0 | 78.0% |
| Sáº£n pháº©m, danh má»¥c, thÆ°Æ¡ng hiá»‡u, Ä‘á»‘i tÃ¡c | 6 | 0 | 2 | 3 | 1 | 0 | 0 | 59.7% |
| Kho, nháº­p hÃ ng, tá»“n kho, kiá»ƒm kÃª | 4 | 0 | 1 | 3 | 0 | 0 | 0 | 61.0% |
| BÃ¡n hÃ ng vÃ  Ä‘Æ¡n hÃ ng | 3 | 0 | 2 | 1 | 0 | 0 | 0 | 71.0% |
| Thu chi, cÃ´ng ná»£, káº¿ toÃ¡n | 4 | 0 | 3 | 1 | 0 | 0 | 0 | 70.5% |
| Dashboard vÃ  bÃ¡o cÃ¡o | 2 | 0 | 1 | 1 | 0 | 0 | 0 | 68.0% |
| ToÃ n há»‡ thá»‘ng | 34 | 1 | 15 | 12 | 1 | 2 | 3 | 65.5% |

## 5.8. Nháº­n xÃ©t tá»•ng quan vá» má»©c Ä‘á»™ hoÃ n thiá»‡n chá»©c nÄƒng


## 5.9. Khuyáº¿n nghá»‹ xá»­ lÃ½ theo má»©c Ä‘á»™ Æ°u tiÃªn

| Má»©c Æ°u tiÃªn | Váº¥n Ä‘á» | HÆ°á»›ng xá»­ lÃ½ Ä‘á» xuáº¥t |
| --- | --- | --- |
| Kháº©n cáº¥p | `ClientDebtsDetail::truncate()` vÃ  `SupplierDebtsDetail::truncate()` trong thu/chi legacy | Thay báº±ng cáº­p nháº­t/xÃ³a theo Ä‘á»‘i tÆ°á»£ng, phiáº¿u vÃ  ká»³ cÃ´ng ná»£; kiá»ƒm tra láº¡i dá»¯ liá»‡u Ä‘Ã£ phÃ¡t sinh |
| Kháº©n cáº¥p | Báº£ng táº¡m nháº­p hÃ ng/kiá»ƒm kÃª dÃ¹ng global vÃ  truncate toÃ n báº£ng | Scope theo `user_id`, `session_id`, `storage_id`; thay truncate báº±ng delete cÃ³ Ä‘iá»u kiá»‡n; bá»c transaction |
| Kháº©n cáº¥p | POS khÃ´ng giáº£m `product_storage` vÃ  khÃ´ng ghi `order_details.storage_id` | Sá»­a `Staff\OrderController::store` Ä‘á»ƒ giáº£m tá»“n theo kho vÃ  ghi kho vÃ o chi tiáº¿t Ä‘Æ¡n |
| Cao | Middleware role cho phÃ©p role 2 Ä‘i qua má»i nhÃ³m | Thiáº¿t káº¿ láº¡i ma tráº­n quyá»n, dÃ¹ng policy/gate hoáº·c permission table Ä‘ang cÃ³, thÃªm test truy cáº­p route theo role |
| Cao | Product schema khÃ´ng nháº¥t quÃ¡n `brand_id`/`brands_id`, status boolean/enum | Chuáº©n hÃ³a migration, model, request vÃ  view; thÃªm migration Ä‘iá»u chá»‰nh an toÃ n náº¿u cáº§n |
| Cao | Migration pending vÃ  thiáº¿u báº£ng `failed_jobs` | RÃ  láº¡i migration, tÃ¡ch migration trÃ¹ng/khÃ´ng há»£p lá»‡, bá»• sung migration failed jobs vÃ  cháº¡y trÃªn staging trÆ°á»›c |
| Trung bÃ¬nh | Nhiá»u controller validate trá»±c tiáº¿p chÆ°a Ä‘á»“ng Ä‘á»u hoáº·c thiáº¿u FormRequest | Chuáº©n hÃ³a FormRequest cho nháº­p hÃ ng, supplier, receipt/expense, customer update vÃ  report filter |
| Trung bÃ¬nh | BÃ¡o cÃ¡o dÃ¹ng dá»¯ liá»‡u chi tiáº¿t Ä‘Æ¡n chÆ°a thá»‘ng nháº¥t | Chuáº©n hÃ³a `p_quantity`/`quantity`, `storage_id`, scope user/kho vÃ  thÃªm test bÃ¡o cÃ¡o vá»›i dataset nhá» |
| Trung bÃ¬nh | Bá»™ test chÆ°a bao phá»§ nghiá»‡p vá»¥ | Báº­t sqlite memory hoáº·c database test riÃªng, thÃªm test Ä‘Äƒng nháº­p, phÃ¢n quyá»n, POS, nháº­p hÃ ng, tá»“n kho, cÃ´ng ná»£, bÃ¡o cÃ¡o |
| Tháº¥p | Tráº£i nghiá»‡m UI lá»—i thÃ´ng bÃ¡o vÃ  má»™t sá»‘ route/comment cÅ© | Sá»­a `datgin.error`, dá»n route/comment legacy, áº©n hoáº·c gá»¡ chá»©c nÄƒng chÆ°a triá»ƒn khai |

## 5.10. Nguá»“n xÃ¡c minh riÃªng cho má»¥c 5

| NhÃ³m báº±ng chá»©ng | Nguá»“n Ä‘Ã£ kiá»ƒm tra |
| --- | --- |
| Lá»‡nh tráº¡ng thÃ¡i Laravel | `php artisan about`, `php artisan route:list`, `php artisan route:list --json`, `php artisan migrate:status`, `php artisan config:show database` |
| Lá»‡nh kiá»ƒm thá»­ an toÃ n | `php artisan test` tráº£ vá» PASS vá»›i 3 test, 12 assertion; `php artisan queue:failed` bÃ¡o thiáº¿u báº£ng `failed_jobs` |
| Dependency | `composer show --no-interaction`, `npm list --depth=0`, `composer.json`, `package.json` |
| Route vÃ  phÃ¢n quyá»n | `routes/web.php`, `routes/api.php`, `routes/console.php`, `app/Http/Middleware/RoleMiddleware.php`, `CheckLogin.php`, `CheckLoginSuperAdmin.php`, `app/Providers/AuthServiceProvider.php` |
| XÃ¡c thá»±c vÃ  tÃ i khoáº£n | `app/Http/Controllers/AuthController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `Admin/UserController.php`, `Admin/EmployeeController.php`, view login |
| Sáº£n pháº©m vÃ  kho | `ProductController`, `ProductRequest`, `ProductService`, `ProductStorageService`, `ImportProductController`, `importCouponController`, `Staff/CheckInventoryController`, `Staff/WareHomeController` |
| BÃ¡n hÃ ng vÃ  káº¿ toÃ¡n | `Staff/OrderController`, `Order`, `OrderDetail`, `Transaction`, `TransactionEntry`, `DebtController`, `AccountController`, `CashTransactionController`, `BankTransactionController`, `ReceiptController`, `ExpenseController` |
| BÃ¡o cÃ¡o | `DashboardController`, `ReportController`, `DailyReportController`, `ReportdebtController`, `ProfitService` |
| Kiá»ƒm thá»­ hiá»‡n cÃ³ | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`, `tests/Unit/UploadedImageUrlTest.php`, `phpunit.xml` |

# 6. ÄÃNH GIÃ CHáº¤T LÆ¯á»¢NG Ká»¸ THUáº¬T

## 6.1. PhÆ°Æ¡ng phÃ¡p vÃ  pháº¡m vi Ä‘Ã¡nh giÃ¡

Pháº§n nÃ y Ä‘Ã¡nh giÃ¡ cháº¥t lÆ°á»£ng ká»¹ thuáº­t dá»±a trÃªn kiá»ƒm tra trá»±c tiáº¿p mÃ£ nguá»“n, cáº¥u trÃºc thÆ° má»¥c, route, controller, service, model, migration, seeder, factory, view, helper, middleware, job, cáº¥u hÃ¬nh, tÃ i liá»‡u vÃ  káº¿t quáº£ cháº¡y cÃ´ng cá»¥ kiá»ƒm tra an toÃ n. Ná»™i dung khÃ´ng Ä‘Ã¡nh giÃ¡ láº¡i viá»‡c chá»©c nÄƒng cháº¡y Ä‘Ãºng hay sai á»Ÿ má»©c ngÆ°á»i dÃ¹ng cuá»‘i, mÃ  táº­p trung vÃ o nguyÃªn nhÃ¢n vÃ  Ä‘áº·c Ä‘iá»ƒm ká»¹ thuáº­t áº£nh hÆ°á»Ÿng Ä‘áº¿n kháº£ nÄƒng báº£o trÃ¬, má»Ÿ rá»™ng, kiá»ƒm thá»­ vÃ  váº­n hÃ nh tiáº¿p.

CÃ¡c thÃ nh pháº§n Ä‘Ã£ kiá»ƒm tra gá»“m `app/Http/Controllers`, `app/Http/Requests`, `app/Http/Middleware`, `app/Models`, `app/Services`, `app/Providers`, `app/Jobs`, `app/Events`, `app/Listeners`, `app/Mail`, `app/Exports`, `app/Helpers`, `app/Exceptions`, `routes`, `config`, `bootstrap/app.php`, `composer.json`, `composer.lock`, `phpunit.xml`, `database/migrations`, `database/seeders`, `database/factories`, `resources/views`, `resources/js`, `resources/css`, `public`, `tests`, `README.md`, `README.dev.md`, `CHANGELOG.md`, `docs` vÃ  bÃ¡o cÃ¡o phÃ¢n tÃ­ch hiá»‡n cÃ³.

CÃ¡c lá»‡nh kiá»ƒm tra Ä‘Ã£ cháº¡y: `git status --short`, `git ls-files`, `php artisan about`, `php artisan route:list`, `php artisan test`, `composer validate`, `composer show --direct`, `./vendor/bin/pint --test`. KhÃ´ng cháº¡y migration, khÃ´ng cháº¡y seeder, khÃ´ng xÃ³a cache, khÃ´ng format code, khÃ´ng cÃ i package, khÃ´ng thay Ä‘á»•i `.env`, khÃ´ng commit vÃ  khÃ´ng push code.


## 6.2. Cháº¥t lÆ°á»£ng mÃ£ nguá»“n

### 6.2.1. Quy Æ°á»›c Ä‘áº·t tÃªn

| Ná»™i dung kiá»ƒm tra | Hiá»‡n tráº¡ng | VÃ­ dá»¥ hoáº·c nguá»“n xÃ¡c minh | áº¢nh hÆ°á»Ÿng | ÄÃ¡nh giÃ¡ |
| --- | --- | --- | --- | --- |
| TÃªn class | Pháº§n lá»›n class chÃ­nh dÃ¹ng PascalCase, nhÆ°ng cÃ²n ngoáº¡i lá»‡ vÃ  tÃªn chÆ°a nháº¥t quÃ¡n. | `app/Http/Controllers/Admin/importCouponController.php:28` dÃ¹ng class báº¯t Ä‘áº§u báº±ng chá»¯ thÆ°á»ng; `app/Models/warehome.php` dÃ¹ng tÃªn model chá»¯ thÆ°á»ng; cÃ³ `CategorieController`, `BrandSeeer`, `SupperAdminService`. | TÄƒng chi phÃ­ tÃ¬m kiáº¿m, sinh nháº§m tÃªn class/file vÃ  khÃ³ theo convention Laravel/PSR. | Trung bÃ¬nh |
| TÃªn method | Nhiá»u method pháº£n Ã¡nh nghiá»‡p vá»¥, nhÆ°ng cÃ²n láº«n cÃ¡ch Ä‘áº·t tÃªn cÅ©, viáº¿t sai chÃ­nh táº£ hoáº·c chÆ°a thá»‘ng nháº¥t. | `ImportProductController::importadd`, `importupdateprice`, `importdelete`; `WareHomeController::checkwerehouse`; `ProductService::getPRoductInStorage_Staff`. | NgÆ°á»i tiáº¿p quáº£n pháº£i Ä‘á»‘i chiáº¿u route vÃ  code nhiá»u hÆ¡n, khÃ³ suy luáº­n nhanh vai trÃ² method. | Trung bÃ¬nh |
| TÃªn biáº¿n | CÃ³ nhiá»u biáº¿n nghiá»‡p vá»¥ tiáº¿ng Viá»‡t/viáº¿t táº¯t láº«n tiáº¿ng Anh. | `importCouponController.php:55-57` dÃ¹ng `$total`, `$totalncc`, `$congno`; `DebtController` dÃ¹ng biáº¿n nhÆ° `$so_du_no_dau`, `$ghi_no`. | KhÃ´ng gÃ¢y lá»—i trá»±c tiáº¿p nhÆ°ng lÃ m chuáº©n Ä‘á»c code thiáº¿u Ä‘á»“ng nháº¥t giá»¯a module. | Trung bÃ¬nh |
| TÃªn route | Route Ä‘Æ°á»£c Ä‘áº·t tÃªn khÃ¡ Ä‘áº§y Ä‘á»§ theo nhÃ³m `admin.*`, `staff.*`, `super.*`, nhÆ°ng cÃ³ má»™t sá»‘ tÃªn nhÃ³m cÃ²n dÆ° hoáº·c chÆ°a rÃµ. | `routes/web.php` cÃ³ `admin.importproduct.importCoupon.add`, `staff.Inventory.*`, route POST `admin.debts.` khÃ´ng cÃ³ tÃªn cuá»‘i rÃµ rÃ ng. | Khi refactor view/controller, route name khÃ³ Ä‘oÃ¡n vÃ  dá»… dÃ¹ng nháº§m. | KhÃ¡ |

Nháº­n xÃ©t ká»¹ thuáº­t: dá»± Ã¡n cÃ³ quy Æ°á»›c Ä‘áº·t tÃªn á»Ÿ má»©c cÃ³ thá»ƒ Ä‘á»c Ä‘Æ°á»£c, nhÆ°ng chÆ°a nháº¥t quÃ¡n giá»¯a code má»›i vÃ  code legacy. CÃ¡c tÃªn sai chÃ­nh táº£ hoáº·c khÃ´ng theo chuáº©n Laravel nÃªn Ä‘Æ°á»£c gom xá»­ lÃ½ dáº§n trong cÃ¡c láº§n refactor cÃ³ kiá»ƒm soÃ¡t, trÃ¡nh Ä‘á»•i Ä‘á»“ng loáº¡t khi chÆ°a cÃ³ test.

### 6.2.2. TrÃ¡ch nhiá»‡m cá»§a Controller

| Controller | Vai trÃ² hiá»‡n táº¡i | Xá»­ lÃ½ chÃ­nh Ä‘ang Ä‘áº·t trong Controller | CÃ³ sá»­ dá»¥ng Service | Nháº­n xÃ©t ká»¹ thuáº­t |
| --- | --- | --- | --- | --- |
| `Admin\importCouponController` | Táº¡o phiáº¿u nháº­p hÃ ng | TÃ­nh cÃ´ng ná»£, cáº­p nháº­t phiáº¿u chi, táº¡o phiáº¿u nháº­p, táº¡o chi tiáº¿t nháº­p, cáº­p nháº­t tá»“n tá»•ng, cáº­p nháº­t tá»“n theo kho, liÃªn káº¿t cÃ´ng ty-sáº£n pháº©m, truncate báº£ng táº¡m, táº¡o bÃºt toÃ¡n. | Má»™t pháº§n | Method `add()` khoáº£ng 150 dÃ²ng logic nghiá»‡p vá»¥, cÃ³ nhiá»u cáº­p nháº­t tiá»n/kho/cÃ´ng ná»£ nhÆ°ng chÆ°a tháº¥y transaction bao quanh toÃ n bá»™ luá»“ng (`app/Http/Controllers/Admin/importCouponController.php:51`, `:76`, `:129`, `:147`, `:157`, `:162`). |
| `Staff\OrderController` | Táº¡o Ä‘Æ¡n POS vÃ  lá»‹ch sá»­ Ä‘Æ¡n | Validate payload, tÃ­nh láº¡i tá»•ng tiá»n, táº¡o Ä‘Æ¡n, táº¡o chi tiáº¿t, trá»« tá»“n tá»•ng, táº¡o bÃºt toÃ¡n theo phÆ°Æ¡ng thá»©c thanh toÃ¡n. | KhÃ´ng cho nghiá»‡p vá»¥ táº¡o Ä‘Æ¡n chÃ­nh | CÃ³ transaction (`:182`, `:278`, `:284`) vÃ  tá»± tÃ­nh láº¡i tiá»n, Ä‘Ã¢y lÃ  Ä‘iá»ƒm tÃ­ch cá»±c. Tuy nhiÃªn controller váº«n chá»©a nhiá»u logic Ä‘Æ¡n hÃ ng/káº¿ toÃ¡n trá»±c tiáº¿p vÃ  chÆ°a trá»« tá»“n theo `product_storage` (`:203`, `:210`). |
| `Admin\CashTransactionController` | Phiáº¿u tiá»n máº·t | Validate, upload file, táº¡o/cáº­p nháº­t/xÃ³a transaction vÃ  entries, query danh sÃ¡ch báº±ng raw/query builder. | KhÃ´ng rÃµ service riÃªng | Controller dÃ i 590 dÃ²ng, cÃ³ `DB::transaction` cho store/update/destroy, nhÆ°ng chá»©a nhiá»u logic káº¿ toÃ¡n vÃ  query bÃ¡o cÃ¡o trá»±c tiáº¿p. |
| `Admin\BankTransactionController` | Phiáº¿u ngÃ¢n hÃ ng | TÆ°Æ¡ng tá»± cash transaction, xá»­ lÃ½ validate, file, bÃºt toÃ¡n, sá»‘ dÆ°, danh sÃ¡ch. | KhÃ´ng rÃµ service riÃªng | Controller dÃ i 536 dÃ²ng; transaction Ä‘Æ°á»£c dÃ¹ng nhÆ°ng trÃ¡ch nhiá»‡m cÃ²n rá»™ng. |
| `Admin\DebtController` | CÃ´ng ná»£ khÃ¡ch/NCC vÃ  cÃ´ng ná»£ Ä‘áº§u ká»³ | Query builder tÃ­nh sá»‘ dÆ° Ä‘áº§u ká»³, phÃ¡t sinh, táº¡o transaction Ä‘áº§u ká»³. | KhÃ´ng | CÃ¡c query tá»•ng há»£p cÃ´ng ná»£ náº±m trá»±c tiáº¿p trong controller (`DB::table` nhiá»u láº§n táº¡i `app/Http/Controllers/Admin/DebtController.php:33-68`, `:116-151`). |

Nháº­n xÃ©t ká»¹ thuáº­t: controller trong dá»± Ã¡n khÃ´ng chá»‰ Ä‘iá»u phá»‘i request/response mÃ  nhiá»u nÆ¡i cÃ²n chá»©a nghiá»‡p vá»¥ cá»‘t lÃµi, thao tÃ¡c database, tÃ­nh toÃ¡n tiá»n/kho/cÃ´ng ná»£, gá»i API vÃ  xá»­ lÃ½ file. Má»™t sá»‘ controller má»›i hÆ¡n cÃ³ transaction vÃ  validate rÃµ hÆ¡n, nhÆ°ng trÃ¡ch nhiá»‡m tá»•ng thá»ƒ chÆ°a Ä‘Æ°á»£c phÃ¢n ranh Ä‘á»u.

### 6.2.3. Tá»• chá»©c logic nghiá»‡p vá»¥




### 6.2.4. Code láº·p láº¡i

| Máº«u code láº·p | Vá»‹ trÃ­ tiÃªu biá»ƒu | Sá»‘ vá»‹ trÃ­ phÃ¡t hiá»‡n hoáº·c pháº¡m vi áº£nh hÆ°á»Ÿng | áº¢nh hÆ°á»Ÿng | HÆ°á»›ng cáº£i tiáº¿n |
| --- | --- | ---: | --- | --- |
| Service trÃ¹ng thÆ° má»¥c | `app/Services/*`, `app/Models/Services/*` | 31 service á»Ÿ `app/Services`, nhiá»u file tÆ°Æ¡ng á»©ng dÆ°á»›i `app/Models/Services` | Dá»… sá»­a nháº§m file, tÄƒng chi phÃ­ review vÃ  gÃ¢y khÃ¡c biá»‡t logic giá»¯a hai báº£n. | XÃ¡c Ä‘á»‹nh thÆ° má»¥c chÃ­nh thá»©c, kiá»ƒm tra autoload, xÃ³a/dá»n báº£n khÃ´ng dÃ¹ng sau khi cÃ³ test. |
| Query cÃ´ng ná»£/giao dá»‹ch báº±ng Query Builder | `DebtController`, `CashTransactionController`, `BankTransactionController`, `ReportController` | Nhiá»u controller káº¿ toÃ¡n/bÃ¡o cÃ¡o | Dá»… lá»‡ch cÃ´ng thá»©c khi thay Ä‘á»•i tÃ i khoáº£n, ká»³ lá»c hoáº·c Ä‘á»‘i tÆ°á»£ng. | TÃ¡ch query report/debt thÃ nh service/repository Ä‘á»c, thÃªm test dataset nhá». |
| Validate trá»±c tiáº¿p trong controller | `CashTransactionController`, `BankTransactionController`, `AdminController`, `BrandController`, `StorageController`, `Staff\OrderController` | Nhiá»u controller | Quy táº¯c validate khÃ³ tÃ¡i sá»­ dá»¥ng, message/attribute khÃ´ng Ä‘á»“ng nháº¥t. | Bá»• sung Form Request theo nhÃ³m nghiá»‡p vá»¥. |
| Giao diá»‡n Blade láº·p theo theme/admin/email | `resources/views/admin`, `resources/views/Themes/admin`, `resources/views/emails/admin` | Nhiá»u module | Dá»… lá»‡ch UI vÃ  pháº£i sá»­a nhiá»u nÆ¡i khi Ä‘á»•i layout/partial. | XÃ¡c Ä‘á»‹nh view cÃ²n dÃ¹ng, tÃ¡ch component/partial cho báº£ng/form chung, dá»n view legacy cÃ³ kiá»ƒm soÃ¡t. |
| MÃ£ tráº¡ng thÃ¡i vÃ  mÃ£ tÃ i khoáº£n hard-code | `role:1/3/4`, `status`, `TMCH`, `tech`, `131`, `156`, `331`, `5111` | Nhiá»u nghiá»‡p vá»¥ | Thay Ä‘á»•i quy táº¯c vai trÃ²/káº¿ toÃ¡n pháº£i sá»­a nhiá»u vá»‹ trÃ­. | DÃ¹ng enum/constant/config hoáº·c báº£ng cáº¥u hÃ¬nh tÃ i khoáº£n káº¿ toÃ¡n theo cá»­a hÃ ng. |

### 6.2.5. Helper vÃ  Trait

Dá»± Ã¡n cÃ³ 3 file trong `app/Helpers`: `helper.php`, `system.php`, `NumberToWords.php`. Helper Ä‘ang phá»¥c vá»¥ cáº£ tiá»‡n Ã­ch ká»¹ thuáº­t vÃ  má»™t pháº§n thao tÃ¡c háº¡ táº§ng/dá»¯ liá»‡u: `showImage`, `deleteImage`, `formatPrice`, `transaction`, `successResponse`, `errorResponse`, `generateCode`, `uploadImages`. Viá»‡c cÃ³ helper khÃ´ng pháº£i váº¥n Ä‘á» tá»± thÃ¢n; váº¥n Ä‘á» náº±m á»Ÿ pháº¡m vi dÃ¹ng chung khÃ¡ rá»™ng vÃ  phá»¥ thuá»™c trá»±c tiáº¿p vÃ o `request()`, `session()`, `DB`, `Storage`, `response()` trong helper.

| NhÃ³m helper | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | ÄÃ¡nh giÃ¡ |
| --- | --- | --- | --- |
| Helper ká»¹ thuáº­t | CÃ³ hÃ m format tiá»n, áº£nh, chuyá»ƒn sá»‘ thÃ nh chá»¯. | `formatPrice`, `NumberToWords::convert`, `showImage`. | Cháº¥p nháº­n Ä‘Æ°á»£c, nhÆ°ng cáº§n trÃ¡nh trÃ¹ng hÃ m `formatPrice` trong cÃ¹ng file. |
| Helper nghiá»‡p vá»¥/háº¡ táº§ng | CÃ³ `generateCode($table, $prefix)` query DB Ä‘á»ƒ sinh mÃ£, `transaction($callback)` bá»c DB transaction. | `app/Helpers/helper.php:50`, `:101`, `:117`. | DÃ¹ng Ä‘Æ°á»£c nhÆ°ng nÃªn chuyá»ƒn cÃ¡c pháº§n liÃªn quan DB sang service chuyÃªn trÃ¡ch náº¿u nghiá»‡p vá»¥ phá»©c táº¡p hÆ¡n. |
| Helper phá»¥ thuá»™c request/session | `uploadImages()` Ä‘á»c file tá»« `request()`, response helper flash session. | `app/Helpers/helper.php:77-95`, `:123-155`. | LÃ m helper khÃ³ test Ä‘á»™c láº­p vÃ  khÃ³ tÃ¡i sá»­ dá»¥ng ngoÃ i HTTP request. |

ChÆ°a phÃ¡t hiá»‡n thÆ° má»¥c `app/Traits`, `app/Actions`, `app/DTOs`.

### 6.2.6. Hard-code

| GiÃ¡ trá»‹ hard-code | Vá»‹ trÃ­ | Má»¥c Ä‘Ã­ch | áº¢nh hÆ°á»Ÿng khi thay Ä‘á»•i | HÆ°á»›ng quáº£n lÃ½ phÃ¹ há»£p |
| --- | --- | --- | --- | --- |
| `role:1`, `role:3`, `role:4`, `role_id == 1/2/3` | `routes/web.php:102`, `:287`, `:328`, `:390`; `RoleMiddleware.php:24-30`; `CheckLogin.php:28` | PhÃ¢n quyá»n route theo vai trÃ² | Äá»•i vai trÃ² hoáº·c thÃªm permission chi tiáº¿t pháº£i sá»­a route/middleware, khÃ³ test ma tráº­n quyá»n. | Enum/constant + Policy/Gate hoáº·c báº£ng permission. |
| MÃ£ tÃ i khoáº£n káº¿ toÃ¡n `TMCH`, `tech`, `131`, `156`, `331`, `5111` | `Staff\OrderController.php:225`, `:242`, `:253`; `importCouponController.php:159-160` | Táº¡o bÃºt toÃ¡n bÃ¡n hÃ ng/nháº­p hÃ ng | Thay há»‡ thá»‘ng tÃ i khoáº£n hoáº·c dÃ¹ng nhiá»u store cÃ³ chart of accounts khÃ¡c nhau sáº½ cáº§n sá»­a code. | Config hoáº·c báº£ng mapping tÃ i khoáº£n theo store/nghiá»‡p vá»¥. |
| Tráº¡ng thÃ¡i Ä‘Æ¡n hÃ ng `status = 1` | `Staff\OrderController.php:198` | ÄÃ¡nh dáº¥u tráº¡ng thÃ¡i Ä‘Æ¡n | KhÃ´ng rÃµ Ã½ nghÄ©a tráº¡ng thÃ¡i khi thÃªm tráº¡ng thÃ¡i má»›i. | Enum/constant `OrderStatus`. |
| `Import::truncate()` vÃ  truncate cÃ´ng ná»£ detail | `importCouponController.php:157`, `ReceiptController.php:85`, `ExpenseController.php:85` | Dá»n báº£ng táº¡m hoáº·c chi tiáº¿t khi hoÃ n táº¥t | Náº¿u báº£ng dÃ¹ng chung nhiá»u user/Ä‘á»‘i tÆ°á»£ng, cÃ³ nguy cÆ¡ xÃ³a dá»¯ liá»‡u ngoÃ i pháº¡m vi thao tÃ¡c. | Delete theo `user_id/session_id/object_id`, transaction, khÃ³a dá»¯ liá»‡u. |

### 6.2.7. Chuáº©n PSR vÃ  phong cÃ¡ch code

Káº¿t quáº£ `./vendor/bin/pint --test` tráº£ vá» fail: 384 file Ä‘Æ°á»£c kiá»ƒm tra, 221 style issues. NhÃ³m lá»—i gá»“m `class_attributes_separation`, `no_unused_imports`, `single_quote`, `concat_space`, `trailing_comma_in_multiline`, `function_declaration`, `ordered_imports`, `blank_line_before_statement`, `braces_position`, `no_trailing_whitespace` vÃ  má»™t sá»‘ lá»—i tÆ°Æ¡ng tá»±.

MÃ£ nguá»“n dÃ¹ng PSR-4 qua `composer.json` cho `App\\`, `Database\\Factories\\`, `Database\\Seeders\\`. Tuy nhiÃªn phong cÃ¡ch trong file PHP chÆ°a Ä‘á»“ng Ä‘á»u. CÃ³ file class khÃ´ng theo tÃªn chuáº©n (`importCouponController`, `warehome`), nhiá»u import khÃ´ng dÃ¹ng, nhiá»u Ä‘oáº¡n comment/debug cÅ© vÃ  má»™t lá»‡nh `dd($e->getMessage())` cÃ²n hoáº¡t Ä‘á»™ng trong `ProductService::updateProduct` (`app/Services/ProductService.php:170`). ÄÃ¢y lÃ  rá»§i ro ká»¹ thuáº­t vÃ¬ náº¿u nhÃ¡nh exception xáº£y ra, request cÃ³ thá»ƒ bá»‹ dá»«ng báº±ng debug output thay vÃ¬ rollback/log/response nháº¥t quÃ¡n.

### 6.2.8. Comment vÃ  tÃ i liá»‡u trong code


Nguá»“n tÃ i liá»‡u ngoÃ i code cÃ³ `README.dev.md`, `CHANGELOG.md`, `docs/feature-completion-clean.md`, `docs/feature-completion-audit.md`, `docs/database-table-usage-audit.md` vÃ  bÃ¡o cÃ¡o phÃ¢n tÃ­ch trÆ°á»›c. ÄÃ¢y lÃ  Ä‘iá»ƒm tÃ­ch cá»±c, nhÆ°ng tÃ i liá»‡u váº«n thiÃªn vá» audit/phÃ¢n tÃ­ch, chÆ°a pháº£i tÃ i liá»‡u váº­n hÃ nh vÃ  thiáº¿t káº¿ ká»¹ thuáº­t hoÃ n chá»‰nh.

### 6.2.9. Xá»­ lÃ½ lá»—i vÃ  logging

Dá»± Ã¡n cÃ³ `try-catch` vÃ  `Log::error/info/warning` á»Ÿ nhiá»u service/controller, cÃ³ `app/Exceptions/Handler.php` ghi log táº­p trung vÃ  custom response cho `ValidationException` vá»›i Ajax. Má»™t sá»‘ nghiá»‡p vá»¥ quan trá»ng dÃ¹ng transaction vÃ  rollback. Tuy nhiÃªn cÃ¡ch xá»­ lÃ½ lá»—i chÆ°a Ä‘á»“ng Ä‘á»u: cÃ³ nÆ¡i catch `Exception` rá»™ng rá»“i throw láº¡i message chung, cÃ³ nÆ¡i tráº£ tháº³ng `$e->getMessage()` cho client, cÃ³ nÆ¡i log token hoáº·c toÃ n bá»™ API response, vÃ  cÃ³ nÆ¡i debug báº±ng `dd()`.

| Ná»™i dung | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | ÄÃ¡nh giÃ¡ |
| --- | --- | --- | --- |
| Logging táº­p trung | CÃ³ `Handler::report()` ghi message/file/line. | `app/Exceptions/Handler.php:59`. | KhÃ¡ |
| Custom exception | CÃ³ `ProductNotFoundException`, nhÆ°ng chÆ°a tháº¥y pattern exception domain Ä‘Æ°á»£c dÃ¹ng rá»™ng. | `app/Exceptions/ProductNotFoundException.php`. | Má»™t pháº§n |

### Báº£ng tá»•ng há»£p cháº¥t lÆ°á»£ng mÃ£ nguá»“n

| TiÃªu chÃ­ | Äiá»ƒm /5 | Hiá»‡n tráº¡ng chÃ­nh | Báº±ng chá»©ng tiÃªu biá»ƒu | áº¢nh hÆ°á»Ÿng |
| --- | ---: | --- | --- | --- |
| Quy Æ°á»›c Ä‘áº·t tÃªn | 2.5 | CÃ³ convention cÆ¡ báº£n nhÆ°ng cÃ²n tÃªn sai/khÃ´ng nháº¥t quÃ¡n. | `importCouponController`, `warehome`, `SupperAdminService`, `CategorieController`. | KhÃ³ tÃ¬m kiáº¿m vÃ  refactor. |
| TrÃ¡ch nhiá»‡m Controller | 2.3 | Nhiá»u controller chá»©a nghiá»‡p vá»¥, query, file, API vÃ  response. | `importCouponController`, `CashTransactionController`, `BankTransactionController`, `DebtController`. | Thay Ä‘á»•i nghiá»‡p vá»¥ cÃ³ thá»ƒ áº£nh hÆ°á»Ÿng nhiá»u nÆ¡i. |
| Quáº£n lÃ½ helper | 2.8 | Helper há»¯u Ã­ch nhÆ°ng phá»¥ thuá»™c request/session/DB á»Ÿ nhiá»u hÃ m. | `app/Helpers/helper.php`. | KhÃ³ test Ä‘á»™c láº­p. |
| PSR/style | 2.0 | Pint phÃ¡t hiá»‡n nhiá»u style issues. | `pint --test`: 384 files, 221 style issues. | Review khÃ³ hÆ¡n, cháº¥t lÆ°á»£ng style chÆ°a Ä‘á»“ng nháº¥t. |

## 6.3. Kháº£ nÄƒng báº£o trÃ¬

### 6.3.1. Kháº£ nÄƒng xÃ¡c Ä‘á»‹nh vá»‹ trÃ­ cáº§n sá»­a

Cáº¥u trÃºc module theo Laravel giÃºp xÃ¡c Ä‘á»‹nh Ä‘iá»ƒm vÃ o tÆ°Æ¡ng Ä‘á»‘i rÃµ qua `routes/web.php`, controller theo nhÃ³m `Admin`, `Staff`, `SuperAdmin`, model vÃ  service. Route list cÃ³ 200 route, chá»§ yáº¿u náº±m trong `routes/web.php`, nÃªn khi cáº§n láº§n theo nghiá»‡p vá»¥ cÃ³ thá»ƒ báº¯t Ä‘áº§u tá»« route group. Tuy nhiÃªn route file khÃ¡ dÃ i vÃ  chá»©a nhiá»u route/comment cÅ©, khiáº¿n viá»‡c Ä‘á»‹nh vá»‹ nhanh cÃ³ thá»ƒ máº¥t thá»i gian hÆ¡n khi module tÄƒng.

### 6.3.2. Má»©c Ä‘á»™ áº£nh hÆ°á»Ÿng khi thay Ä‘á»•i


### 6.3.3. PhÃ¢n chia module


### 6.3.4. Kháº£ nÄƒng tiáº¿p quáº£n

Kháº£ nÄƒng tiáº¿p quáº£n á»Ÿ má»©c trung bÃ¬nh. NgÆ°á»i má»›i cÃ³ thá»ƒ Ä‘á»c route/controller/model Ä‘á»ƒ hiá»ƒu luá»“ng chÃ­nh, nhÆ°ng cáº§n thá»i gian Ä‘á»‘i chiáº¿u giá»¯a code má»›i vÃ  legacy, giá»¯a tá»“n tá»•ng vÃ  tá»“n theo kho, giá»¯a báº£ng cÃ´ng ná»£ cÅ© vÃ  bÃºt toÃ¡n má»›i, giá»¯a service chÃ­nh vÃ  service sao chÃ©p. CÃ¡c tÃ i liá»‡u `docs` vÃ  bÃ¡o cÃ¡o trÆ°á»›c giÃºp giáº£m rá»§i ro, nhÆ°ng chÆ°a thay tháº¿ Ä‘Æ°á»£c tÃ i liá»‡u kiáº¿n trÃºc/nghiá»‡p vá»¥ chÃ­nh thá»©c.

### 6.3.5. Test vÃ  kiá»ƒm tra há»“i quy


| TiÃªu chÃ­ báº£o trÃ¬ | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | Má»©c rá»§i ro | ÄÃ¡nh giÃ¡ |
| --- | --- | --- | --- | --- |
| Äá»‹nh vá»‹ module | CÃ³ nhÃ³m Admin/Staff/SuperAdmin rÃµ. | `routes/web.php`, `app/Http/Controllers/*`. | Trung bÃ¬nh | KhÃ¡ |
| Thay Ä‘á»•i nghiá»‡p vá»¥ | Logic tiá»n/kho/cÃ´ng ná»£ phÃ¢n tÃ¡n á»Ÿ controller/service. | `Staff\OrderController`, `importCouponController`, `ReceiptController`, `ExpenseController`. | Cao | Trung bÃ¬nh |
| TÃ i liá»‡u há»— trá»£ | CÃ³ docs/bÃ¡o cÃ¡o nhÆ°ng chÆ°a Ä‘áº§y Ä‘á»§ váº­n hÃ nh/kiáº¿n trÃºc. | `docs`, `README.dev.md`, `CHANGELOG.md`. | Trung bÃ¬nh | Trung bÃ¬nh |
| Test há»“i quy | Test ráº¥t má»ng so vá»›i pháº¡m vi nghiá»‡p vá»¥. | `php artisan test`: 3 passed, 12 assertions. | Cao | Yáº¿u |
| Chuáº©n style | CÃ³ Pint nhÆ°ng chÆ°a Ä‘áº¡t `--test`. | `composer.json`, `.styleci.yml`, káº¿t quáº£ Pint. | Trung bÃ¬nh | Yáº¿u-Trung bÃ¬nh |

## 6.4. Kháº£ nÄƒng má»Ÿ rá»™ng

### 6.4.1. Má»Ÿ rá»™ng module

CÃ³ thá»ƒ má»Ÿ rá»™ng module má»›i theo pattern hiá»‡n táº¡i: route -> controller -> service -> model -> Blade. Tuy nhiÃªn Ä‘á»ƒ má»Ÿ rá»™ng an toÃ n, cáº§n chuáº©n hÃ³a service/use case vÃ  giáº£m logic trong controller. Vá»›i module liÃªn quan tiá»n/kho/cÃ´ng ná»£, nÃªn cÃ³ service nghiá»‡p vá»¥ riÃªng vÃ  test trÆ°á»›c khi thÃªm chá»©c nÄƒng.

### 6.4.2. Má»Ÿ rá»™ng database


### 6.4.3. Há»— trá»£ API

`routes/api.php` má»›i cÃ³ route máº·c Ä‘á»‹nh `/api/user` qua `auth:sanctum`. Pháº§n lá»›n endpoint nghiá»‡p vá»¥ hiá»‡n lÃ  web route tráº£ view/JSON cho Ajax. Viá»‡c má»Ÿ rá»™ng API public/mobile cáº§n tÃ¡ch controller API, chuáº©n hÃ³a response, auth, throttle, versioning vÃ  test contract. `ApiResponse` Ä‘Ã£ tá»“n táº¡i nhÆ°ng chÆ°a Ä‘Æ°á»£c dÃ¹ng Ä‘á»“ng Ä‘á»u.

### 6.4.4. TÃ­ch há»£p dá»‹ch vá»¥ ngoÃ i


### 6.4.5. Queue vÃ  xá»­ lÃ½ táº£i lá»›n


### 6.4.6. Má»Ÿ rá»™ng hiá»‡u nÄƒng

Dá»± Ã¡n dÃ¹ng pagination á»Ÿ nhiá»u danh sÃ¡ch vÃ  cÃ³ query filter, Ä‘Ã¢y lÃ  Ä‘iá»ƒm tÃ­ch cá»±c. Tuy nhiÃªn má»™t sá»‘ bÃ¡o cÃ¡o dÃ¹ng nhiá»u query tá»•ng há»£p trong controller/service, má»™t sá»‘ accessor model tá»± query nhÆ° `Order::getOrderdetailAttribute`, `ImportCoupon::getDetailAttribute`, `Product::getImagesAttribute`, cÃ³ nguy cÆ¡ N+1 náº¿u dÃ¹ng trÃªn danh sÃ¡ch lá»›n. CÃ¡c export/report nÃªn Ä‘Æ°á»£c kiá»ƒm tra vá»›i dá»¯ liá»‡u lá»›n vÃ  thÃªm eager loading/chunking khi cáº§n.

| KhÃ­a cáº¡nh má»Ÿ rá»™ng | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | Rá»§i ro chÃ­nh | Má»©c Æ°u tiÃªn |
| --- | --- | --- | --- | --- |
| Module nghiá»‡p vá»¥ | CÃ³ pattern Laravel nhÆ°ng logic chÆ°a tÃ¡ch Ä‘á»u. | Controllers + Services. | Module má»›i dá»… sao chÃ©p pattern cÅ©. | Cao |
| API | Chá»§ yáº¿u web/Ajax, API riÃªng cÃ²n Ã­t. | `routes/api.php` chá»‰ cÃ³ `/api/user`. | KhÃ³ má»Ÿ mobile/public API nhanh. | Trung bÃ¬nh |
| Queue | CÃ³ job/migration jobs, queue runtime Ä‘ang sync. | `php artisan about`, `app/Jobs`, `database/migrations/*jobs*`. | Request cÃ³ thá»ƒ gÃ¡nh tÃ¡c vá»¥ náº·ng, lá»—i job khÃ³ theo dÃµi. | Cao |
| Hiá»‡u nÄƒng | CÃ³ pagination, nhÆ°ng report/query/accessor cáº§n kiá»ƒm tra dá»¯ liá»‡u lá»›n. | `ReportController`, `ProfitService`, `DebtController`, model accessors. | Cháº­m khi dá»¯ liá»‡u tÄƒng. | Trung bÃ¬nh |

## 6.5. Má»©c Ä‘á»™ sá»­ dá»¥ng Laravel

Dá»± Ã¡n sá»­ dá»¥ng nhiá»u thÃ nh pháº§n Laravel cÆ¡ báº£n: route group, middleware, controller, service container qua constructor injection, Eloquent model/relationship, migration, seeder, factory, Form Request má»™t pháº§n, job queue, mail, event/listener, cache, logging, Blade, helper autoload vÃ  PHPUnit. Má»©c Ã¡p dá»¥ng nhÃ¬n chung lÃ  má»™t pháº§n Ä‘áº¿n khÃ¡, nhÆ°ng chÆ°a Ä‘á»“ng Ä‘á»u á»Ÿ Policy/Gate, API Resource, typed domain service, queue failure handling, static analysis vÃ  test nghiá»‡p vá»¥.

| ThÃ nh pháº§n Laravel | Tráº¡ng thÃ¡i sá»­ dá»¥ng | Má»©c Ä‘á»™ phÃ¹ há»£p | Vá»‹ trÃ­ tiÃªu biá»ƒu | Ná»™i dung cÃ²n thiáº¿u hoáº·c cáº§n cáº£i thiá»‡n | Má»©c Æ°u tiÃªn |
| --- | --- | --- | --- | --- | --- |
| Middleware | Äang sá»­ dá»¥ng | CÃ³ tÃ¡c dá»¥ng phÃ¢n vÃ¹ng route, nhÆ°ng role hard-code | `RoleMiddleware`, `CheckLogin`, `CheckLoginSuperAdmin`, `routes/web.php` | Chuáº©n hÃ³a role/permission, thÃªm test phÃ¢n quyá»n. | Cao |
| Policy/Gate | ChÆ°a phÃ¡t hiá»‡n sá»­ dá»¥ng nghiá»‡p vá»¥ | [KHÃ”NG PHÃT HIá»†N TRONG MÃƒ NGUá»’N HIá»†N Táº I] | `app/Providers/AuthServiceProvider.php` chÆ°a Ä‘Äƒng kÃ½ policy | DÃ¹ng Policy/Gate cho resource nháº¡y cáº£m náº¿u cÃ³ nhu cáº§u phÃ¢n quyá»n chi tiáº¿t. | Cao |
| Service Container | Äang sá»­ dá»¥ng | KhÃ¡ | Constructor injection service trong nhiá»u controller | Dá»n `app/Models/Services`, trÃ¡nh service trÃ¹ng. | Trung bÃ¬nh |
| Events/Listeners | Sá»­ dá»¥ng má»™t pháº§n | CÃ³ cáº¥u trÃºc nhÆ°ng pháº¡m vi nhá» | `CustomerLogin`, `SendMailOtpCustomerLogin` | ChÆ°a tháº¥y event/listener cho nghiá»‡p vá»¥ chÃ­nh. | Tháº¥p-Trung bÃ¬nh |
| Logging | Äang sá»­ dá»¥ng | Má»™t pháº§n | `Log::error/info`, `Handler.php` | Mask secret, chuáº©n hÃ³a context, háº¡n cháº¿ log body/token. | Cao |
| Migration/Seeder/Factory | Äang sá»­ dá»¥ng | CÃ³ ná»n táº£ng tá»‘t nhÆ°ng migration cáº§n rÃ  | `database/migrations`, `database/seeders`, `database/factories` | Sá»­a typo/rollback, bá»• sung `failed_jobs`, seed role/account chuáº©n. | Cao |
| Automated Test | Äang sá»­ dá»¥ng ráº¥t háº¡n cháº¿ | Yáº¿u | `tests`, `phpunit.xml`; `php artisan test` pass 3 test | Bá»• sung test nghiá»‡p vá»¥ chÃ­nh vÃ  test database Ä‘á»™c láº­p. | Cao |

## 6.6. Danh sÃ¡ch phÃ¡t hiá»‡n ká»¹ thuáº­t

| MÃ£ | NhÃ³m | PhÃ¡t hiá»‡n ká»¹ thuáº­t | Báº±ng chá»©ng | áº¢nh hÆ°á»Ÿng | Má»©c Ä‘á»™ | HÆ°á»›ng xá»­ lÃ½ Ä‘á» xuáº¥t |
| --- | --- | --- | --- | --- | --- | --- |
| TECH-002 | Dá»¯ liá»‡u kho/káº¿ toÃ¡n | Luá»“ng nháº­p hÃ ng cáº­p nháº­t nhiá»u báº£ng tiá»n/kho/cÃ´ng ná»£/bÃºt toÃ¡n trong controller, chÆ°a tháº¥y transaction bao toÃ n bá»™ method. | `importCouponController.php:51-199`, Ä‘áº·c biá»‡t `:76`, `:129`, `:147`, `:157`, `:162`. | Náº¿u má»™t bÆ°á»›c lá»—i giá»¯a chá»«ng cÃ³ thá»ƒ lá»‡ch phiáº¿u nháº­p, tá»“n kho, cÃ´ng ná»£ hoáº·c bÃºt toÃ¡n. | NghiÃªm trá»ng | TÃ¡ch `ImportPurchaseService`, dÃ¹ng `DB::transaction`, delete báº£ng táº¡m theo pháº¡m vi user/session. |
| TECH-003 | Tá»“n kho | POS táº¡o chi tiáº¿t Ä‘Æ¡n vÃ  trá»« `products.quantity` nhÆ°ng chÆ°a cáº­p nháº­t `product_storage` vÃ  chÆ°a ghi `storage_id` vÃ o order detail trong method má»›i. | `Staff\OrderController.php:203`, `:210`; migration cÃ³ `order_details.storage_id` táº¡i `2024_08_23_103647...:15`. | BÃ¡o cÃ¡o/tá»“n theo kho cÃ³ thá»ƒ lá»‡ch vá»›i bÃ¡n hÃ ng. | NghiÃªm trá»ng | Gáº¯n kho vÃ o line item, giáº£m tá»“n theo kho trong cÃ¹ng transaction, thÃªm test tá»“n tá»•ng/tá»“n kho. |
| TECH-004 | CÃ´ng ná»£ legacy | CÃ³ truncate chi tiáº¿t cÃ´ng ná»£ toÃ n báº£ng khi háº¿t ná»£. | `ReceiptController.php:85`, `ExpenseController.php:85`. | CÃ³ nguy cÆ¡ máº¥t lá»‹ch sá»­ chi tiáº¿t cÃ´ng ná»£ ngoÃ i Ä‘á»‘i tÆ°á»£ng Ä‘ang xá»­ lÃ½. | NghiÃªm trá»ng | Thay truncate báº±ng cáº­p nháº­t/xÃ³a theo phiáº¿u/Ä‘á»‘i tÆ°á»£ng, audit dá»¯ liá»‡u phÃ¡t sinh. |
| TECH-005 | PhÃ¢n quyá»n | Role ID hard-code trong route/middleware; chÆ°a phÃ¡t hiá»‡n Policy/Gate nghiá»‡p vá»¥. | `routes/web.php:102`, `:287`, `:328`, `:390`; `RoleMiddleware.php:24-30`. | Ma tráº­n quyá»n khÃ³ má»Ÿ rá»™ng, khÃ³ kiá»ƒm thá»­ vÃ  cÃ³ nguy cÆ¡ cáº¥p quyá»n rá»™ng ngoÃ i dá»± kiáº¿n. | Cao | Chuáº©n hÃ³a role/permission, dÃ¹ng Policy/Gate hoáº·c permission table, thÃªm feature test tá»«ng role. |
| TECH-009 | Controller scope | Nhiá»u controller dÃ i vÃ  chá»©a nhiá»u trÃ¡ch nhiá»‡m. | `CashTransactionController` 590 dÃ²ng, `BankTransactionController` 536 dÃ²ng, `ReportController` 428 dÃ²ng, `Staff\ProductController` 309 dÃ²ng. | KhÃ³ review, khÃ³ tÃ¡ch lá»—i vÃ  khÃ³ má»Ÿ rá»™ng module. | Trung bÃ¬nh | TÃ¡ch service/query object/request, Æ°u tiÃªn controller tiá»n/kho/bÃ¡o cÃ¡o. |
| TECH-010 | Validation | Form Request má»›i dÃ¹ng má»™t pháº§n; nhiá»u validate cÃ²n trá»±c tiáº¿p trong controller. | `app/Http/Requests` cÃ³ 5 file; `rg` phÃ¡t hiá»‡n nhiá»u `$request->validate()` trong controller. | Quy táº¯c validate vÃ  message dá»… khÃ´ng Ä‘á»“ng nháº¥t. | Trung bÃ¬nh | Má»Ÿ rá»™ng Form Request theo nghiá»‡p vá»¥ vÃ  tÃ¡i sá»­ dá»¥ng attribute/message. |
| TECH-012 | PSR/style | Pint phÃ¡t hiá»‡n nhiá»u style issue. | `./vendor/bin/pint --test`: fail, 384 files, 221 style issues. | Style khÃ´ng thá»‘ng nháº¥t, tÄƒng chi phÃ­ review. | Tháº¥p | Cháº¡y Pint theo tá»«ng PR/nhÃ³m file sau khi cÃ³ branch riÃªng; thiáº¿t láº­p CI `pint --test`. |
| TECH-013 | Debug/dead code | CÃ²n `dd()` hoáº¡t Ä‘á»™ng vÃ  nhiá»u debug/comment cÅ©. | `ProductService.php:170`; cÃ¡c `// dd(...)` trong controller/service. | Exception cÃ³ thá»ƒ dá»«ng request báº±ng debug output, gÃ¢y khÃ³ váº­n hÃ nh. | Trung bÃ¬nh | Gá»¡ `dd()`, thay báº±ng log/exception, dá»n comment cÅ© khi refactor. |

Tá»•ng sá»‘ phÃ¡t hiá»‡n ká»¹ thuáº­t: 14. PhÃ¢n loáº¡i má»©c Ä‘á»™: NghiÃªm trá»ng 4, Cao 5, Trung bÃ¬nh 4, Tháº¥p 1.

## 6.7. Báº£ng cháº¥m Ä‘iá»ƒm tá»•ng há»£p

| NhÃ³m Ä‘Ã¡nh giÃ¡ | Äiá»ƒm /5 | CÆ¡ sá»Ÿ cháº¥m Ä‘iá»ƒm | Rá»§i ro chÃ­nh |
| --- | ---: | --- | --- |
| Cháº¥t lÆ°á»£ng mÃ£ nguá»“n | 2.4 | CÃ³ cáº¥u trÃºc Laravel vÃ  service layer, nhÆ°ng naming/style/debug/hard-code cÃ²n nhiá»u. | KhÃ³ review vÃ  dá»… phÃ¡t sinh lá»—i khi sá»­a module lá»›n. |
| Kháº£ nÄƒng báº£o trÃ¬ | 2.5 | Module rÃµ á»Ÿ má»©c route/controller, tÃ i liá»‡u cÃ³ má»™t pháº§n, nhÆ°ng logic phÃ¢n tÃ¡n vÃ  test má»ng. | Thay Ä‘á»•i nghiá»‡p vá»¥ tiá»n/kho/cÃ´ng ná»£ cáº§n kiá»ƒm tra thá»§ cÃ´ng nhiá»u. |
| Má»©c Ä‘á»™ Ã¡p dá»¥ng Laravel | 3.0 | DÃ¹ng route, middleware, Eloquent, service container, migration, Form Request má»™t pháº§n, job, mail, cache/log. | Policy/Gate, Form Request, queue failure vÃ  test chÆ°a Ä‘á»§. |
| Kiá»ƒm thá»­ tá»± Ä‘á»™ng | 1.5 | PHPUnit cháº¡y pass nhÆ°ng chá»‰ cÃ³ 3 test, 12 assertions. | KhÃ´ng báº£o vá»‡ cÃ¡c luá»“ng nghiá»‡p vá»¥ trá»ng yáº¿u. |
| TÃ i liá»‡u ká»¹ thuáº­t | 2.6 | CÃ³ README, README.dev, CHANGELOG, docs audit vÃ  bÃ¡o cÃ¡o trÆ°á»›c. | Thiáº¿u tÃ i liá»‡u kiáº¿n trÃºc, váº­n hÃ nh, mapping tráº¡ng thÃ¡i/tÃ i khoáº£n. |
| Xá»­ lÃ½ lá»—i vÃ  logging | 2.2 | CÃ³ try-catch/log/Handler/transaction má»™t pháº§n. | Log secret, `dd()`, catch rá»™ng, thiáº¿u failed job. |
| Quáº£n lÃ½ cáº¥u hÃ¬nh | 2.0 | DÃ¹ng config/env Laravel, nhÆ°ng cÃ³ token/endpoint/TTL/mÃ£ tÃ i khoáº£n hard-code. | Rá»§i ro báº£o máº­t vÃ  khÃ³ cáº¥u hÃ¬nh theo mÃ´i trÆ°á»ng/store. |

Äiá»ƒm ká»¹ thuáº­t trung bÃ¬nh = 18.7 / 8 = 2.34 / 5.

Äiá»ƒm sá»‘ pháº£n Ã¡nh cháº¥t lÆ°á»£ng ká»¹ thuáº­t trong pháº¡m vi mÃ£ nguá»“n, cáº¥u hÃ¬nh, tÃ i liá»‡u vÃ  lá»‡nh kiá»ƒm tra Ä‘Ã£ thá»±c hiá»‡n. Äiá»ƒm khÃ´ng pháº£n Ã¡nh Ä‘áº§y Ä‘á»§ kháº£ nÄƒng váº­n hÃ nh production vÃ¬ chÆ°a cÃ³ quyá»n kiá»ƒm tra mÃ´i trÆ°á»ng tháº­t, dá»¯ liá»‡u tháº­t vÃ  dá»‹ch vá»¥ bÃªn ngoÃ i. Äiá»ƒm sá»‘ cáº§n Ä‘Æ°á»£c Ä‘á»c cÃ¹ng danh sÃ¡ch `TECH-xxx` vÃ  nguá»“n xÃ¡c minh á»Ÿ cuá»‘i má»¥c 6.

## 6.8. Nháº­n xÃ©t tá»•ng quÃ¡t


## 6.9. Kiáº¿n nghá»‹ cáº£i tiáº¿n ká»¹ thuáº­t

| Thá»© tá»± Æ°u tiÃªn | Háº¡ng má»¥c | Hiá»‡n tráº¡ng cáº§n xá»­ lÃ½ | HÆ°á»›ng cáº£i tiáº¿n | Káº¿t quáº£ mong Ä‘á»£i |
| ---: | --- | --- | --- | --- |
| 1 | An toÃ n dá»¯ liá»‡u tiá»n, kho, cÃ´ng ná»£ | Nháº­p hÃ ng/POS/thu chi legacy cÃ³ nhiá»u cáº­p nháº­t liÃªn báº£ng, má»™t sá»‘ chá»— chÆ°a cÃ³ transaction toÃ n luá»“ng hoáº·c dÃ¹ng truncate toÃ n báº£ng. | TÃ¡ch service nghiá»‡p vá»¥, dÃ¹ng `DB::transaction`, xÃ³a dá»¯ liá»‡u táº¡m theo scope, thÃªm test dá»¯ liá»‡u. | Giáº£m rá»§i ro lá»‡ch tá»“n kho, cÃ´ng ná»£ vÃ  bÃºt toÃ¡n. |
| 3 | PhÃ¢n quyá»n backend | Role ID hard-code, chÆ°a cÃ³ Policy/Gate nghiá»‡p vá»¥. | Äá»‹nh nghÄ©a ma tráº­n quyá»n, dÃ¹ng enum/constant, Policy/Gate hoáº·c permission table, thÃªm feature test. | Quyá»n truy cáº­p rÃµ rÃ ng vÃ  kiá»ƒm thá»­ Ä‘Æ°á»£c. |
| 4 | Logic Ä‘Æ¡n hÃ ng, kho, thu chi, cÃ´ng ná»£ | Logic cÃ²n phÃ¢n tÃ¡n á»Ÿ controller/service vÃ  chÆ°a Ä‘á»“ng nháº¥t tá»“n tá»•ng/tá»“n kho. | Táº¡o cÃ¡c service/use case nhÆ° `CreateOrderService`, `CreateImportCouponService`, `PostAccountingEntryService`. | Dá»… thay Ä‘á»•i quy táº¯c nghiá»‡p vá»¥ vÃ  giáº£m trÃ¹ng logic. |
| 5 | Migration vÃ  schema | CÃ³ migration typo/rollback sai, thiáº¿u `failed_jobs` theo cáº¥u hÃ¬nh. | RÃ  migration trÃªn staging, bá»• sung migration sá»­a an toÃ n, thÃªm failed jobs. | Schema cÃ³ thá»ƒ dá»±ng/rollback Ä‘Ã¡ng tin hÆ¡n. |
| 7 | Chuáº©n hÃ³a tráº¡ng thÃ¡i/cáº¥u hÃ¬nh | Role, status, mÃ£ tÃ i khoáº£n, endpoint, TTL Ä‘ang hard-code. | DÃ¹ng enum/constant/config/database mapping. | Thay Ä‘á»•i nghiá»‡p vá»¥ khÃ´ng cáº§n sá»­a nhiá»u file. |
| 9 | Chuáº©n hÃ³a style vÃ  CI | Pint `--test` fail 221 style issues, chÆ°a phÃ¡t hiá»‡n CI Ä‘áº§y Ä‘á»§. | Ãp dá»¥ng Pint theo nhÃ³m file, thÃªm CI cháº¡y test + Pint + composer validate. | Review nháº¥t quÃ¡n, giáº£m lá»—i style láº·p láº¡i. |

## Nguá»“n xÃ¡c minh

| Ná»™i dung Ä‘Ã¡nh giÃ¡ | File, thÆ° má»¥c hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| Quy Æ°á»›c Ä‘áº·t tÃªn | `app/Http/Controllers`, `app/Models`, `app/Services`, `routes/web.php`, `database/migrations` |
| TrÃ¡ch nhiá»‡m Controller | `app/Http/Controllers/Admin/importCouponController.php`, `Staff/OrderController.php`, `Admin/CashTransactionController.php`, `Admin/BankTransactionController.php`, `Admin/DebtController.php` |
| Logic nghiá»‡p vá»¥ | Controllers, `app/Services`, `app/Models`, `app/Helpers/helper.php` |
| Code láº·p láº¡i | `app/Services`, `app/Models/Services`, `resources/views/admin`, `resources/views/Themes/admin`, `resources/views/emails/admin` |
| Helper vÃ  Trait | `app/Helpers/helper.php`, `app/Helpers/system.php`, `app/Helpers/NumberToWords.php`; khÃ´ng phÃ¡t hiá»‡n `app/Traits` |
| Chuáº©n PSR | Káº¿t quáº£ `./vendor/bin/pint --test`, `composer.json`, `.styleci.yml` |
| Xá»­ lÃ½ lá»—i vÃ  logging | `app/Exceptions/Handler.php`, `app/Services`, `app/Http/Controllers`, `app/Jobs` |
| Kháº£ nÄƒng báº£o trÃ¬ | Cáº¥u trÃºc `app`, `routes`, `resources/views`, `docs`, `README.dev.md`, káº¿t quáº£ `git ls-files` |
| Kháº£ nÄƒng má»Ÿ rá»™ng | `database/migrations`, `database/seeders`, `database/factories`, `routes/api.php`, `app/Jobs`, `config/queue.php`, `config/services.php` |
| Sá»­ dá»¥ng Laravel | `app/Http/Requests`, `app/Http/Middleware`, `app/Providers`, `app/Models`, `app/Jobs`, `app/Mail`, `config`, `phpunit.xml` |
| Test | `tests`, `phpunit.xml`, káº¿t quáº£ `php artisan test` |
| CÃ´ng cá»¥ kiá»ƒm tra | `composer validate`, `composer show --direct`, `php artisan about`, `php artisan route:list`, `./vendor/bin/pint --test` |
| TÃ i liá»‡u | `README.md`, `README.dev.md`, `CHANGELOG.md`, `docs/feature-completion-clean.md`, `docs/feature-completion-audit.md`, `docs/database-table-usage-audit.md`, `bao-cao-phan-tich-laravel.md` |

# 7. ÄÃNH GIÃ CÆ  Sá»ž Dá»® LIá»†U

## 7.1. Pháº¡m vi vÃ  phÆ°Æ¡ng phÃ¡p Ä‘Ã¡nh giÃ¡

Pháº§n nÃ y chá»‰ Ä‘Ã¡nh giÃ¡ cÆ¡ sá»Ÿ dá»¯ liá»‡u dá»±a trÃªn mÃ£ nguá»“n, migration, model, seeder, factory, service, controller, route vÃ  cÃ¡c tÃ i liá»‡u audit sáºµn cÃ³ trong repository. KhÃ´ng thay Ä‘á»•i schema, khÃ´ng cháº¡y migration má»›i, khÃ´ng ghi dá»¯ liá»‡u vÃ o database vÃ  khÃ´ng sá»­a code nghiá»‡p vá»¥.

CÃ¡c lá»‡nh kiá»ƒm tra Ä‘Ã£ cháº¡y Ä‘á»u á»Ÿ cháº¿ Ä‘á»™ Ä‘á»c:

| NhÃ³m kiá»ƒm tra | Lá»‡nh/nguá»“n Ä‘Ã£ dÃ¹ng | Má»¥c Ä‘Ã­ch |
| --- | --- | --- |
| MÃ´i trÆ°á»ng Laravel | `php artisan about` | XÃ¡c Ä‘á»‹nh Laravel 10.49.0, PHP 8.3.16, driver DB MySQL |
| Tráº¡ng thÃ¡i migration | `php artisan migrate:status` | Äá»‘i chiáº¿u migration Ä‘Ã£ cháº¡y/chÆ°a cháº¡y trÃªn DB local |
| Route nghiá»‡p vá»¥ | `php artisan route:list` | XÃ¡c Ä‘á»‹nh bá» máº·t nghiá»‡p vá»¥ cÃ³ truy váº¥n DB |
| MÃ£ nguá»“n | `rg`, `Get-ChildItem`, Ä‘á»c migration/model/controller/service/export | TÃ¬m schema, quan há»‡, transaction, query, accessors, export |
| TÃ i liá»‡u audit cÃ³ sáºµn | `docs/database-table-usage-audit.md` | Äá»‘i chiáº¿u dá»¯ liá»‡u má»“ cÃ´i vÃ  báº£ng legacy Ä‘Ã£ Ä‘Æ°á»£c kiá»ƒm trÆ°á»›c Ä‘Ã³ |

Káº¿t quáº£ Ä‘á»‹nh lÆ°á»£ng nhanh:

| Chá»‰ sá»‘ | Káº¿t quáº£ |
| --- | ---: |
| File migration trong `database/migrations` | 111 |
| Khai bÃ¡o `Schema::create(...)` | 54 |
| TÃªn báº£ng táº¡o má»›i riÃªng biá»‡t trong migration | 51 |
| Tráº¡ng thÃ¡i DB local theo `migrate:status` | 98 Ran, 16 Pending |
| Route Laravel | 200 |
| Model trong `app/Models` | 52 |
| Seeder | 16 |
| Factory | 13 |

LÆ°u Ã½: `php artisan migrate:status` cÃ³ liá»‡t kÃª migration vendor nhÆ° `personal_access_tokens` cá»§a Sanctum, vÃ¬ váº­y tá»•ng tráº¡ng thÃ¡i DB local cÃ³ thá»ƒ lá»›n hÆ¡n sá»‘ file migration ná»™i bá»™ trong thÆ° má»¥c `database/migrations`.

## 7.2. NhÃ³m dá»¯ liá»‡u chÃ­nh

Há»‡ thá»‘ng hiá»‡n cÃ³ dá»¯ liá»‡u nghiá»‡p vá»¥ rá»™ng, táº­p trung vÃ o bÃ¡n hÃ ng, kho, cÃ´ng ná»£ vÃ  váº­n hÃ nh ná»™i bá»™.

| NhÃ³m dá»¯ liá»‡u | Báº£ng/Ä‘á»‘i tÆ°á»£ng tiÃªu biá»ƒu | Vai trÃ² |
| --- | --- | --- |
| TÃ i khoáº£n vÃ  phÃ¢n quyá»n | `users`, `user_info`, `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `super_admins`, `personal_access_tokens` | ÄÄƒng nháº­p, nhÃ¢n sá»±, quyá»n truy cáº­p |
| Sáº£n pháº©m vÃ  danh má»¥c | `products`, `categories`, `brands`, `product_categories`, `product_code`, `products_code`, `attributes`, `variations`, `images` | Catalog, mÃ£ hÃ ng, biáº¿n thá»ƒ, hÃ¬nh áº£nh |
| Kho vÃ  tá»“n kho | `warehouse`, `product_storage`, `storage`, `imports`, `import_coupons`, `import_coupon_details`, `company_product` | Tá»“n kho, nháº­p hÃ ng, tá»“n theo kho/cÃ´ng ty |
| BÃ¡n hÃ ng vÃ  POS | `orders`, `order_details`, `carts`, `web_info`, `discount_codes`, `discount_products` | ÄÆ¡n hÃ ng, chi tiáº¿t Ä‘Æ¡n, giá» hÃ ng, giáº£m giÃ¡ |
| KhÃ¡ch hÃ ng | `clients`, `client_group`, `client_debts`, `client_receipts`, `customers` | KhÃ¡ch hÃ ng, nhÃ³m khÃ¡ch, cÃ´ng ná»£, dá»¯ liá»‡u legacy |
| NhÃ  cung cáº¥p/cÃ´ng ty | `suppliers`, `supplier_debts`, `supplier_receipts`, `companies`, `city` | Nguá»“n hÃ ng, cÃ´ng ná»£ NCC, cÃ´ng ty |
| Káº¿ toÃ¡n vÃ  dÃ²ng tiá»n | `receipts`, `expenses`, `expense`, `transaction_entries`, `accounts`, `account_funds` | Thu/chi, bÃºt toÃ¡n, quá»¹/tÃ i khoáº£n |
| Cáº¥u hÃ¬nh há»‡ thá»‘ng | `config`, `notifications`, `failed_jobs`, `jobs`, `cache`, `sessions` | Cáº¥u hÃ¬nh, queue/cache/session |

## 7.3. SÆ¡ Ä‘á»“ ERD tá»•ng quan

SÆ¡ Ä‘á»“ sau chá»‰ thá»ƒ hiá»‡n cÃ¡c quan há»‡ nghiá»‡p vá»¥ chÃ­nh vÃ  má»™t sá»‘ báº£ng Ä‘ang Ä‘Æ°á»£c code sá»­ dá»¥ng nhiá»u. ÄÃ¢y khÃ´ng pháº£i lÃ  ERD Ä‘áº§y Ä‘á»§ cá»§a toÃ n bá»™ 51 báº£ng.

```mermaid
erDiagram
    USERS ||--o{ USER_INFO : has
    USERS ||--o{ ORDERS : creates
    USERS ||--o{ IMPORT_COUPONS : creates
    USERS ||--o{ RECEIPTS : records
    USERS ||--o{ EXPENSES : records

    CLIENTS ||--o{ ORDERS : places
    CLIENTS ||--o{ CLIENT_DEBTS : owes
    CLIENTS ||--o{ CLIENT_RECEIPTS : pays
    CLIENT_GROUP ||--o{ CLIENTS : groups

    PRODUCTS ||--o{ ORDER_DETAILS : sold_as
    PRODUCTS ||--o{ PRODUCT_STORAGE : stocked_in
    PRODUCTS ||--o{ WAREHOUSE : legacy_stock
    PRODUCTS ||--o{ PRODUCT_CODE : codes
    PRODUCTS ||--o{ PRODUCTS_CODE : legacy_codes
    PRODUCTS ||--o{ IMPORT_COUPON_DETAILS : imported_as
    BRANDS ||--o{ PRODUCTS : brands
    CATEGORIES ||--o{ PRODUCT_CATEGORIES : maps
    PRODUCTS ||--o{ PRODUCT_CATEGORIES : belongs_to

    ORDERS ||--o{ ORDER_DETAILS : contains
    ORDERS ||--o{ RECEIPTS : paid_by
    ORDERS ||--o{ CLIENT_DEBTS : creates

    SUPPLIERS ||--o{ IMPORT_COUPONS : supplies
    SUPPLIERS ||--o{ SUPPLIER_DEBTS : owed
    SUPPLIERS ||--o{ SUPPLIER_RECEIPTS : paid_by
    COMPANIES ||--o{ IMPORT_COUPONS : receives
    COMPANIES ||--o{ PRODUCT_STORAGE : owns_stock
    STORAGE ||--o{ PRODUCT_STORAGE : stores

    IMPORT_COUPONS ||--o{ IMPORT_COUPON_DETAILS : contains
    ACCOUNTS ||--o{ TRANSACTION_ENTRIES : posts
```

## 7.4. ÄÃ¡nh giÃ¡ thiáº¿t káº¿ schema

| TiÃªu chÃ­ | Äiá»ƒm | Nháº­n xÃ©t |
| --- | ---: | --- |
| Chuáº©n Ä‘áº·t tÃªn | 2/5 | TÃªn báº£ng vÃ  cá»™t khÃ´ng thá»‘ng nháº¥t: `config`, `city`, `warehouse`, `expense`, `import`, `client_group`; cá»™t cÃ³ cáº£ `brands_id`, `companies_id`, `clientgroup_id`, `gia_chenh_lech`. |
| Kiá»ƒu dá»¯ liá»‡u | 2/5 | Má»™t sá»‘ kiá»ƒu dá»¯ liá»‡u khÃ´ng phÃ¹ há»£p nghiá»‡p vá»¥: `products.quantity` lÃ  `string`, giÃ¡ tiá»n dÃ¹ng láº«n `integer`, `bigInteger`, `decimal`, `super_admins.bank_account` dÃ¹ng sá»‘. |
| KhÃ³a chÃ­nh/phá»¥ | 3/5 | Nhiá»u báº£ng cÃ³ foreign key, nhÆ°ng váº«n cÃ²n báº£ng quan trá»ng thiáº¿u rÃ ng buá»™c hoáº·c cÃ³ lá»—i migration, vÃ­ dá»¥ `company_product` dÃ¹ng nháº§m `onDelte('cascade')`. |
| Quan há»‡ model-schema | 3/5 | CÃ³ nhiá»u model vÃ  quan há»‡, nhÆ°ng má»™t sá»‘ model/controller Ä‘ang gá»i cá»™t khÃ´ng khá»›p migration hiá»‡n cÃ³. |
| XÃ³a dá»¯ liá»‡u | 2/5 | KhÃ´ng tÃ¬m tháº¥y `SoftDeletes`/`softDeletes()`, rá»§i ro máº¥t lá»‹ch sá»­ á»Ÿ domain bÃ¡n hÃ ng, cÃ´ng ná»£, tá»“n kho. |
| Kháº£ nÄƒng audit | 2/5 | Nhiá»u nghiá»‡p vá»¥ nháº¡y cáº£m tiá»n/kho chÆ°a tháº¥y pattern audit log nháº¥t quÃ¡n. |

Äiá»ƒm thiáº¿t káº¿ schema trung bÃ¬nh: **2.5/5**.

## 7.5. Rá»§i ro toÃ n váº¹n dá»¯ liá»‡u

CÃ¡c rá»§i ro lá»›n nháº¥t náº±m á»Ÿ tÃ­nh nháº¥t quÃ¡n giá»¯a Ä‘Æ¡n hÃ ng, tá»“n kho, cÃ´ng ná»£ vÃ  dÃ²ng tiá»n.

| MÃ£ | Má»©c Ä‘á»™ | Váº¥n Ä‘á» | Báº±ng chá»©ng | TÃ¡c Ä‘á»™ng |
| --- | --- | --- | --- | --- |
| DB-001 | NghiÃªm trá»ng | Cáº­p nháº­t tá»“n kho/Ä‘Æ¡n hÃ ng/cÃ´ng ná»£ cÃ³ Ä‘Æ°á»ng cháº¡y thiáº¿u transaction hoáº·c transaction chÆ°a bao háº¿t nghiá»‡p vá»¥ | `Staff\ClientController@pay` cÃ³ transaction bá»‹ comment; `Admin\importCouponController@add` cáº­p nháº­t nhiá»u báº£ng nhÆ°ng khÃ´ng tháº¥y transaction bao toÃ n hÃ m | CÃ³ thá»ƒ lá»‡ch tá»“n kho, lá»‡ch cÃ´ng ná»£, ghi thiáº¿u receipt/expense khi lá»—i giá»¯a chá»«ng |
| DB-002 | NghiÃªm trá»ng | KhÃ´ng tháº¥y khÃ³a bi quan/láº¡c quan khi trá»« tá»“n kho | `Staff\OrderController@store` dÃ¹ng `decrement('quantity')`; khÃ´ng tÃ¬m tháº¥y `lockForUpdate()` | Race condition khi nhiá»u nhÃ¢n viÃªn bÃ¡n cÃ¹ng sáº£n pháº©m, cÃ³ thá»ƒ bÃ¡n vÆ°á»£t tá»“n |
| DB-003 | Cao | Migration/model/controller khÃ´ng khá»›p á»Ÿ cÃ¡c báº£ng lÃµi | `products.status` enum nhÆ°ng model cast boolean; `products.brands_id` vs `brand_id`; `orders`/`order_details` cÃ³ field code Ä‘ang dÃ¹ng khÃ¡c migration gá»‘c | Bug áº©n khi deploy DB má»›i hoáº·c rebuild mÃ´i trÆ°á»ng |
| DB-004 | Cao | Dá»¯ liá»‡u má»“ cÃ´i Ä‘Ã£ Ä‘Æ°á»£c audit trÆ°á»›c Ä‘Ã³ | `docs/database-table-usage-audit.md` ghi nháº­n orphan á»Ÿ `receipts.client_id`, `user_info.user_id`, `warehouse.product_id`, `products_code.id_product` | BÃ¡o cÃ¡o sai, lá»—i truy váº¥n quan há»‡, khÃ³ dá»n dá»¯ liá»‡u |
| DB-005 | Cao | Báº£ng legacy vÃ  báº£ng má»›i cÃ¹ng tá»“n táº¡i | `customers` cáº¡nh `clients`, `warehouse` cáº¡nh `product_storage`, `product_code` cáº¡nh `products_code`, `expense` cáº¡nh `expenses` | Dá»… ghi nháº§m nguá»“n dá»¯ liá»‡u, bÃ¡o cÃ¡o khÃ´ng Ä‘á»“ng nháº¥t |
| DB-006 | Cao | Tiá»n tá»‡ vÃ  sá»‘ lÆ°á»£ng dÃ¹ng kiá»ƒu khÃ´ng nháº¥t quÃ¡n | GiÃ¡, ná»£, thu/chi dÃ¹ng láº«n integer/bigint/decimal; `quantity` cÃ³ nÆ¡i lÃ  string | Lá»—i lÃ m trÃ²n, sort/filter sai, khÃ³ chuáº©n hÃ³a bÃ¡o cÃ¡o tÃ i chÃ­nh |
| DB-007 | Cao | Thiáº¿u constraint/unique rÃµ rÃ ng cho mÃ£ nghiá»‡p vá»¥ | MÃ£ Ä‘Æ¡n, mÃ£ nháº­p, mÃ£ giao dá»‹ch, mÃ£ sáº£n pháº©m Ä‘Æ°á»£c sinh trong code á»Ÿ nhiá»u nÆ¡i | TrÃ¹ng mÃ£ khi concurrent request hoáº·c import lá»›n |
| DB-008 | Cao | KhÃ´ng cÃ³ soft delete cho domain cáº§n lá»‹ch sá»­ | KhÃ´ng tÃ¬m tháº¥y `SoftDeletes` trong model/migration | XÃ³a nháº§m lÃ m máº¥t dáº¥u váº¿t Ä‘Æ¡n hÃ ng, cÃ´ng ná»£, nháº­p kho |
| DB-009 | Trung bÃ¬nh | Má»™t sá»‘ migration pending trÃªn DB local | `migrate:status` ghi nháº­n 16 Pending | MÃ´i trÆ°á»ng local cÃ³ thá»ƒ khÃ´ng pháº£n Ã¡nh production, tÄƒng rá»§i ro khi phÃ¢n tÃ­ch schema |
| DB-010 | Trung bÃ¬nh | Seeder/factory cÃ³ thá»ƒ chÆ°a Ä‘á»§ Ä‘á»ƒ dá»±ng dá»¯ liá»‡u kiá»ƒm thá»­ thá»±c táº¿ | 16 seeder, 13 factory cho domain hÆ¡n 50 báº£ng | KhÃ³ test regression cho tá»“n kho, cÃ´ng ná»£, bÃ¡o cÃ¡o |

Tá»•ng sá»‘ phÃ¡t hiá»‡n: **10**.

PhÃ¢n bá»• má»©c Ä‘á»™:

| Má»©c Ä‘á»™ | Sá»‘ lÆ°á»£ng |
| --- | ---: |
| NghiÃªm trá»ng | 2 |
| Cao | 6 |
| Trung bÃ¬nh | 2 |
| Tháº¥p | 0 |

Äiá»ƒm toÃ n váº¹n dá»¯ liá»‡u: **2.33/5**.

## 7.6. ÄÃ¡nh giÃ¡ hiá»‡u nÄƒng truy váº¥n

| NhÃ³m rá»§i ro | Vá»‹ trÃ­ tiÃªu biá»ƒu | Nháº­n xÃ©t |
| --- | --- | --- |
| N+1 query qua accessor/appends | `Order::getOrderdetailAttribute`, `OrderDetail::getProductAttribute`, `Product::getImagesAttribute`, `Product::getCategoryAttribute`, `ImportCoupon::getDetailAttribute`, cÃ¡c accessor debt/receipt/supplier | Accessor tá»± query DB khiáº¿n danh sÃ¡ch Ä‘Æ¡n/sáº£n pháº©m/import dá»… nhÃ¢n sá»‘ query theo sá»‘ báº£n ghi |
| Query trong vÃ²ng láº·p | `DebtController`, `ProductStorageService::inventoryReport`, `DashboardService`, `ProfitService` | CÃ¡c mÃ n hÃ¬nh bÃ¡o cÃ¡o/tá»“n kho/cÃ´ng ná»£ cÃ³ nguy cÆ¡ cháº­m khi dá»¯ liá»‡u tÄƒng |
| Export táº£i toÃ n bá»™ dá»¯ liá»‡u | `DailyReportExport::collection()` dÃ¹ng `Order::all()`, `UsersExport` dÃ¹ng `User::all()`, `ClientsExport` dÃ¹ng `get()` | Export lá»›n dá»… tá»‘n RAM vÃ  timeout |
| HÃ m ngÃ y trÃªn cá»™t indexed | CÃ¡c bÃ¡o cÃ¡o dÃ¹ng `DB::raw('DATE(created_at)')` | CÃ³ thá»ƒ lÃ m MySQL khÃ³ dÃ¹ng index trÃªn `created_at` |
| Thiáº¿u cache táº§ng á»©ng dá»¥ng | KhÃ´ng tháº¥y pattern `Cache::remember(...)` cho dashboard/report | Dashboard vÃ  bÃ¡o cÃ¡o cÃ³ thá»ƒ truy váº¥n láº¡i dá»¯ liá»‡u náº·ng liÃªn tá»¥c |
| PhÃ¢n trang chÆ°a nháº¥t quÃ¡n | Má»™t sá»‘ danh sÃ¡ch/export dÃ¹ng `get()`/`all()` | Rá»§i ro cháº­m khi báº£ng lá»›n |

Module/truy váº¥n cáº§n Æ°u tiÃªn tá»‘i Æ°u:

1. `Admin\DashboardController` vÃ  `DashboardService`.
2. `Admin\DebtController` cho cÃ´ng ná»£ khÃ¡ch hÃ ng/nhÃ  cung cáº¥p.
3. `ProductStorageService::inventoryReport`.
4. `ProfitService` vÃ  `DailyReportService`.
5. CÃ¡c export `DailyReportExport`, `UsersExport`, `ClientsExport`.
6. CÃ¡c model accessor cÃ³ `$appends` vÃ  query phá»¥.
7. Luá»“ng POS vÃ  nháº­p hÃ ng vÃ¬ vá»«a ghi nhiá»u báº£ng vá»«a áº£nh hÆ°á»Ÿng tá»“n kho/tÃ i chÃ­nh.

Äiá»ƒm hiá»‡u nÄƒng truy váº¥n: **2.0/5**.

## 7.7. ÄÃ¡nh giÃ¡ migration vÃ  kháº£ nÄƒng váº­n hÃ nh

Migration hiá»‡n pháº£n Ã¡nh quÃ¡ trÃ¬nh phÃ¡t triá»ƒn dÃ i, cÃ³ nhiá»u thay Ä‘á»•i domain vÃ  má»™t sá»‘ dáº¥u hiá»‡u legacy.

Äiá»ƒm tÃ­ch cá»±c:

- CÃ³ migration cho pháº§n lá»›n domain chÃ­nh.
- Nhiá»u báº£ng Ä‘Ã£ cÃ³ `foreignId`, `constrained()` hoáº·c `foreign()`.
- CÃ³ seeder/factory há»— trá»£ dá»±ng dá»¯ liá»‡u cÆ¡ báº£n.
- CÃ³ route/controller/service tÆ°Æ¡ng Ä‘á»‘i rÃµ theo module admin/staff/client.

Äiá»ƒm cáº§n chÃº Ã½:

- Má»™t sá»‘ migration cÃ³ lá»—i hoáº·c typo, vÃ­ dá»¥ `onDelte('cascade')`.
- CÃ³ nhiá»u báº£ng/cá»™t Ä‘áº·t tÃªn lá»‡ch chuáº©n, lÃ m khÃ³ báº£o trÃ¬ lÃ¢u dÃ i.
- DB local Ä‘ang cÃ³ migration pending, cáº§n Ä‘á»‘i chiáº¿u vá»›i production/staging trÆ°á»›c khi káº¿t luáº­n tráº¡ng thÃ¡i tháº­t.
- CÃ¡c báº£ng `accounts` vÃ  `transaction_entries` Ä‘Æ°á»£c code dÃ¹ng nhiá»u nhÆ°ng khÃ´ng tháº¥y rÃµ migration tÆ°Æ¡ng á»©ng trong thÆ° má»¥c hiá»‡n táº¡i; cáº§n xÃ¡c minh nguá»“n schema thá»±c táº¿.
- KhÃ´ng cÃ³ soft delete cho nghiá»‡p vá»¥ cáº§n lá»‹ch sá»­.

Äiá»ƒm migration/váº­n hÃ nh: **2.5/5**.

## 7.8. Æ¯u tiÃªn xá»­ lÃ½ Ä‘á» xuáº¥t

### Æ¯u tiÃªn P0 - cáº§n xá»­ lÃ½ trÆ°á»›c khi má»Ÿ rá»™ng tÃ­nh nÄƒng

| Viá»‡c cáº§n lÃ m | LÃ½ do |
| --- | --- |
| Bao transaction Ä‘áº§y Ä‘á»§ cho POS, táº¡o Ä‘Æ¡n, nháº­p hÃ ng, thu/chi, cÃ´ng ná»£ | TrÃ¡nh ghi ná»­a chá»«ng giá»¯a nhiá»u báº£ng |
| ThÃªm cÆ¡ cháº¿ chá»‘ng race khi trá»« tá»“n kho | TrÃ¡nh bÃ¡n Ã¢m/bÃ¡n vÆ°á»£t tá»“n khi concurrent request |
| Äá»‘i chiáº¿u schema tháº­t vá»›i migration cho `orders`, `order_details`, `products`, `accounts`, `transaction_entries` | Giáº£m rá»§i ro deploy/rebuild lá»—i |
| Chuáº©n hÃ³a kiá»ƒu dá»¯ liá»‡u tiá»n vÃ  sá»‘ lÆ°á»£ng | Äáº£m báº£o bÃ¡o cÃ¡o tÃ i chÃ­nh/kho chÃ­nh xÃ¡c |

### Æ¯u tiÃªn P1 - cáº§n xá»­ lÃ½ trong nhá»‹p refactor gáº§n

| Viá»‡c cáº§n lÃ m | LÃ½ do |
| --- | --- |
| Chuáº©n hÃ³a naming convention báº£ng/cá»™t má»›i | Giáº£m chi phÃ­ báº£o trÃ¬ |
| Thay accessor query Ä‘á»™ng báº±ng eager loading/relation rÃµ rÃ ng | Giáº£m N+1 query |
| ThÃªm/kiá»ƒm tra index cho foreign key, `created_at`, mÃ£ Ä‘Æ¡n, mÃ£ sáº£n pháº©m, mÃ£ giao dá»‹ch | TÄƒng tá»‘c dashboard, bÃ¡o cÃ¡o, tÃ¬m kiáº¿m |
| Chuyá»ƒn export lá»›n sang chunk/query streaming | TrÃ¡nh timeout vÃ  háº¿t RAM |
| ThÃªm soft delete/audit log cho Ä‘Æ¡n hÃ ng, cÃ´ng ná»£, nháº­p kho, thu chi | Báº£o toÃ n lá»‹ch sá»­ nghiá»‡p vá»¥ |

### Æ¯u tiÃªn P2 - cáº£i thiá»‡n dÃ i háº¡n

| Viá»‡c cáº§n lÃ m | LÃ½ do |
| --- | --- |
| TÃ¡ch báº£ng legacy hoáº·c táº¡o káº¿ hoáº¡ch deprecate | TrÃ¡nh nháº§m nguá»“n dá»¯ liá»‡u |
| Bá»• sung test tÃ­ch há»£p cho bÃ¡n hÃ ng, nháº­p hÃ ng, thanh toÃ¡n ná»£ | Báº¯t regression dá»¯ liá»‡u |
| TÃ i liá»‡u hÃ³a ERD/schema dictionary | GiÃºp onboarding vÃ  review migration nhanh hÆ¡n |
| ThÃªm cache cÃ³ kiá»ƒm soÃ¡t cho dashboard/report | Giáº£m táº£i DB |

## 7.9. Äiá»ƒm tá»•ng há»£p

| Háº¡ng má»¥c | Äiá»ƒm |
| --- | ---: |
| Thiáº¿t káº¿ schema | 2.5/5 |
| ToÃ n váº¹n dá»¯ liá»‡u | 2.33/5 |
| Hiá»‡u nÄƒng truy váº¥n | 2.0/5 |
| Migration/váº­n hÃ nh | 2.5/5 |
| Kháº£ nÄƒng audit/lá»‹ch sá»­ | 2.0/5 |
| **Trung bÃ¬nh** | **2.36/5** |

Káº¿t luáº­n: cÆ¡ sá»Ÿ dá»¯ liá»‡u Ä‘Ã£ Ä‘á»§ rá»™ng Ä‘á»ƒ váº­n hÃ nh cÃ¡c nghiá»‡p vá»¥ chÃ­nh, nhÆ°ng Ä‘ang cÃ³ rá»§i ro cao á»Ÿ tÃ­nh nháº¥t quÃ¡n dá»¯ liá»‡u, Ä‘á»“ng bá»™ schema-code vÃ  hiá»‡u nÄƒng bÃ¡o cÃ¡o khi dá»¯ liá»‡u tÄƒng. TrÆ°á»›c khi má»Ÿ rá»™ng tÃ­nh nÄƒng, nÃªn Æ°u tiÃªn khÃ³a láº¡i cÃ¡c luá»“ng ghi nhiá»u báº£ng, chuáº©n hÃ³a kiá»ƒu dá»¯ liá»‡u tiá»n/tá»“n kho vÃ  xá»­ lÃ½ N+1 á»Ÿ dashboard/report/export.

## 7.10. Cam káº¿t pháº¡m vi thá»±c hiá»‡n

Trong láº§n Ä‘Ã¡nh giÃ¡ nÃ y:

- KhÃ´ng sá»­a file migration.
- KhÃ´ng táº¡o migration má»›i.
- KhÃ´ng cháº¡y migrate/rollback/seed.
- KhÃ´ng thay Ä‘á»•i dá»¯ liá»‡u trong database.
- KhÃ´ng sá»­a controller, model, service, route hoáº·c view.
- KhÃ´ng commit vÃ  khÃ´ng push.
- Chá»‰ cáº­p nháº­t ná»™i dung bÃ¡o cÃ¡o táº¡i file `BAO_CAO_NGHIEN_CUU_DU_AN.md`.

# 8. ÄÃNH GIÃ Báº¢O Máº¬T

## 8.1. PhÆ°Æ¡ng phÃ¡p vÃ  pháº¡m vi Ä‘Ã¡nh giÃ¡

Pháº§n Ä‘Ã¡nh giÃ¡ báº£o máº­t Ä‘Æ°á»£c thá»±c hiá»‡n trong pháº¡m vi an toÃ n: chá»‰ Ä‘á»c mÃ£ nguá»“n, cáº¥u hÃ¬nh, route, middleware, controller, model, view, helper, dependency vÃ  cháº¡y cÃ¡c lá»‡nh kiá»ƒm tra khÃ´ng phÃ¡ há»§y dá»¯ liá»‡u. KhÃ´ng thá»±c hiá»‡n táº¥n cÃ´ng production, khÃ´ng dÃ² máº­t kháº©u, khÃ´ng upload file Ä‘á»™c háº¡i, khÃ´ng thay Ä‘á»•i database, khÃ´ng sá»­a `.env`, khÃ´ng commit vÃ  khÃ´ng push code.

| Nguá»“n Ä‘Ã£ kiá»ƒm tra | CÃ¡ch Ä‘á»‘i chiáº¿u | Giá»›i háº¡n kiá»ƒm tra |
| --- | --- | --- |
| `routes/web.php`, `routes/api.php`, `php artisan route:list -v` | Äá»‘i chiáº¿u route cÃ´ng khai, route admin, staff, super-admin, API, CKFinder vá»›i middleware thá»±c táº¿ | [CHá»ˆ XÃC MINH ÄÆ¯á»¢C Tá»ª MÃƒ NGUá»’N], chÆ°a kiá»ƒm thá»­ báº±ng nhiá»u tÃ i khoáº£n |
| `app/Http/Middleware`, `app/Providers/AuthServiceProvider.php`, `app/Providers/RouteServiceProvider.php` | Kiá»ƒm tra xÃ¡c thá»±c, phÃ¢n quyá»n, rate limit, CSRF, policy/gate | ChÆ°a xÃ¡c minh runtime production/reverse proxy |
| `app/Http/Controllers`, `app/Services`, `app/Http/Requests`, `app/Models` | Äá»‘i chiáº¿u validation, query, upload, mass assignment, ID trÃªn URL, xá»­ lÃ½ token/password | ChÆ°a cÃ³ dá»¯ liá»‡u staging/production Ä‘á»ƒ kiá»ƒm thá»­ IDOR thá»±c táº¿ |
| `resources/views`, `public/js`, `config/ckfinder.php` | Kiá»ƒm tra Blade escape, `{!! !!}`, JS ghi HTML, lÆ°u máº­t kháº©u phÃ­a client, CKEditor/CKFinder | ChÆ°a cháº¡y trÃ¬nh duyá»‡t vá»›i tÃ i khoáº£n tháº­t |
| `.gitignore`, `.env.example`, `config/*`, Git tracked files | Kiá»ƒm tra secret/config máº«u, session/cookie, logging, filesystem, CORS | KhÃ´ng Ä‘á»c ná»™i dung `.env` vÃ  khÃ´ng cÃ´ng khai secret |
| `composer audit`, `npm audit --omit=dev`, `php artisan test` | XÃ¡c minh dependency advisory vÃ  tráº¡ng thÃ¡i test local | Audit phá»¥ thuá»™c vÃ o cÆ¡ sá»Ÿ dá»¯ liá»‡u advisory táº¡i thá»i Ä‘iá»ƒm cháº¡y |

## 8.2. XÃ¡c thá»±c vÃ  phÃ¢n quyá»n

### 8.2.1. MÃ£ hÃ³a máº­t kháº©u

| Ná»™i dung | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | Rá»§i ro | ÄÃ¡nh giÃ¡ |
| --- | --- | --- | --- | --- |
| LÆ°u máº­t kháº©u User | User dÃ¹ng cast `password => hashed`; nhiá»u nÆ¡i táº¡o máº­t kháº©u dÃ¹ng `Hash::make()` hoáº·c `bcrypt()` | `app/Models/User.php:31-39`, `app/Services/AdminService.php:184-188`, `app/Services/SignUpService.php:50-60` | NhÃ¬n chung cÃ³ cÆ¡ cháº¿ hash phÃ¹ há»£p cho User | ÄÃ£ xÃ¡c minh an toÃ n trong pháº¡m vi kiá»ƒm tra |
| Kiá»ƒm tra máº­t kháº©u | ÄÄƒng nháº­p thÆ°á»ng dÃ¹ng `Auth::attempt()`; super-admin/service dÃ¹ng `Hash::check()` | `app/Http/Controllers/AuthController.php:22-31`, `app/Services/SupperAdminService.php:25-33` | CÃ³ cÆ¡ cháº¿ so khá»›p hash, khÃ´ng tháº¥y so sÃ¡nh chuá»—i trá»±c tiáº¿p cho User/SuperAdmin | ÄÃ£ xÃ¡c minh an toÃ n trong pháº¡m vi kiá»ƒm tra |
| Äáº·t láº¡i/Ä‘á»•i máº­t kháº©u | CÃ³ Ä‘á»•i máº­t kháº©u admin kiá»ƒm tra máº­t kháº©u hiá»‡n táº¡i vÃ  `Hash::make()` máº­t kháº©u má»›i | `app/Services/AdminService.php:114-138`, `app/Http/Controllers/Admin/AdminController.php:36-102` | ChÆ°a tháº¥y luá»“ng quÃªn máº­t kháº©u/reset password Ä‘Æ°á»£c báº­t; chÆ°a tháº¥y yÃªu cáº§u Ä‘á»•i máº­t kháº©u láº§n Ä‘áº§u | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n xÃ¡c minh |
| áº¨n máº­t kháº©u khá»i response | `User` vÃ  `SuperAdmin` cÃ³ `$hidden` cho `password`, `remember_token` | `app/Models/User.php:31-34`, `app/Models/SuperAdmin.php:33-36` | Giáº£m rá»§i ro lá»™ hash khi serialize model | ÄÃ£ xÃ¡c minh an toÃ n trong pháº¡m vi kiá»ƒm tra |
| Gá»­i/lÆ°u máº­t kháº©u rÃµ | View email gá»­i máº­t kháº©u khá»Ÿi táº¡o; form super-admin lÆ°u máº­t kháº©u vÃ o `localStorage` vÃ  `console.log` | `resources/views/emails/send-mail-info.blade.php:15-19`, `resources/views/superadmin/formlogin/index.blade.php:176-214` | Lá»™ máº­t kháº©u rÃµ qua email, trÃ¬nh duyá»‡t, console/devtools | PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t |

### 8.2.2. ÄÄƒng nháº­p, Ä‘Äƒng xuáº¥t vÃ  session

Luá»“ng Ä‘Äƒng nháº­p thÆ°á»ng sá»­ dá»¥ng `LoginRequest` Ä‘á»ƒ validate email/password, sau Ä‘Ã³ gá»i `Auth::attempt()`. Controller kiá»ƒm tra tráº¡ng thÃ¡i `inactive` vÃ  `locked`, há»— trá»£ remember me, nhÆ°ng chÆ°a tháº¥y gá»i `$request->session()->regenerate()` sau Ä‘Äƒng nháº­p. ÄÄƒng xuáº¥t thÆ°á»ng cÃ³ `Auth::logout()`, `session()->invalidate()` vÃ  `regenerateToken()`.

Super-admin dÃ¹ng `SuperAdminController@login()` nháº­n `email`, `password` báº±ng `$request->only()`, gá»i service tá»± viáº¿t, Ä‘áº·t `session('authSuper')` vÃ  khÃ´ng tháº¥y validate Form Request, khÃ´ng tháº¥y regenerate session sau Ä‘Äƒng nháº­p. Middleware `CheckLoginSuperAdmin` chá»‰ kiá»ƒm tra session tá»“n táº¡i.

Session/cookie trong `config/session.php` cÃ³ `http_only => true`, `same_site => lax`, lifetime máº·c Ä‘á»‹nh 120 phÃºt, nhÆ°ng `secure` phá»¥ thuá»™c `SESSION_SECURE_COOKIE`; `php artisan about` cho biáº¿t mÃ´i trÆ°á»ng local hiá»‡n `Debug Mode ENABLED`, chÆ°a pháº£i báº±ng chá»©ng production.

### 8.2.3. Rate limit Ä‘Äƒng nháº­p

ChÆ°a tháº¥y route `login` hoáº·c `super-dang-nhap` gáº¯n middleware `throttle`. `RouteServiceProvider` chá»‰ Ä‘á»‹nh nghÄ©a `RateLimiter::for('api', Limit::perMinute(60))`, Ã¡p dá»¥ng cho nhÃ³m API. VÃ¬ váº­y cÃ³ rá»§i ro brute-force trÃªn form Ä‘Äƒng nháº­p thÆ°á»ng vÃ  super-admin náº¿u khÃ´ng cÃ³ kiá»ƒm soÃ¡t á»Ÿ web server/reverse proxy.

### 8.2.4. Middleware route quáº£n trá»‹

| NhÃ³m route | Middleware xÃ¡c thá»±c | Middleware vai trÃ²/quyá»n | Route Ä‘Ã¡ng chÃº Ã½ | Káº¿t luáº­n |
| --- | --- | --- | --- | --- |
| Admin | CÃ³ `auth` á»Ÿ group `/admin` | CÃ³ `role:1`, `role:3`, `role:4` theo nhÃ³m; `role_id` 1/2 Ä‘Æ°á»£c cho qua má»i role trong `RoleMiddleware` | `admin/bulk/{type}` chá»‰ cÃ³ `auth`, route tÃ i chÃ­nh/kho Ä‘Æ°á»£c chia role | CÃ³ kiá»ƒm soÃ¡t cÆ¡ báº£n, nhÆ°ng cáº§n rÃ  láº¡i route bulk vÃ  quyá»n chi tiáº¿t theo báº£n ghi |
| Staff | CÃ³ `CheckLogin` vÃ  `role:3` | `CheckLogin` cho phÃ©p role 1,2,3; `role:3` cho phÃ©p role 1,2 do logic middleware | `ban-hang/warehome/delete` dÃ¹ng GET Ä‘á»ƒ xÃ³a | CÃ³ kiá»ƒm soÃ¡t route, nhÆ°ng phÆ°Æ¡ng thá»©c HTTP vÃ  quyá»n báº£n ghi cáº§n cáº£i thiá»‡n |
| API | `/api/user` dÃ¹ng `auth:sanctum` | KhÃ´ng tháº¥y API nghiá»‡p vá»¥ khÃ¡c | `routes/api.php:17-19` | Pháº¡m vi API nhá», chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n Ä‘Ã¡nh giÃ¡ API nghiá»‡p vá»¥ |
| CKFinder | Route do package náº¡p, middleware CKFinderBridge | Config access role `*` cho nhiá»u quyá»n file | `ckfinder/browser`, `ckfinder/connector`; `config/ckfinder.php` | Cáº§n siáº¿t quyá»n upload/file manager trÆ°á»›c production |

### 8.2.5. Kiá»ƒm tra quyá»n backend


### 8.2.6. Policy vÃ  Gate

KhÃ´ng tháº¥y thÆ° má»¥c `app/Policies`; `AuthServiceProvider` Ä‘á»ƒ trá»‘ng `$policies` vÃ  `boot()`. TÃ¬m kiáº¿m `Gate::`, `@can`, middleware `can` khÃ´ng ghi nháº­n policy/gate nghiá»‡p vá»¥, ngoÃ i cÃ¡c method `authorize()` máº·c Ä‘á»‹nh cá»§a Form Request. ÄÃ¢y chÆ°a pháº£i lá»—i máº·c Ä‘á»‹nh vá»›i há»‡ thá»‘ng phÃ¢n quyá»n Ä‘Æ¡n giáº£n theo vai trÃ², nhÆ°ng vá»›i dá»¯ liá»‡u theo cá»­a hÃ ng/ngÆ°á»i dÃ¹ng/chi nhÃ¡nh nhÆ° sáº£n pháº©m, kho, khÃ¡ch hÃ ng, nhÃ  cung cáº¥p, cÃ´ng ná»£ vÃ  giao dá»‹ch tÃ i chÃ­nh, thiáº¿u Policy/Gate lÃ m tÄƒng rá»§i ro IDOR vÃ  khÃ³ audit quyá»n theo báº£n ghi.

### 8.2.7. IDOR vÃ  quyá»n sá»Ÿ há»¯u dá»¯ liá»‡u


| TiÃªu chÃ­ | Äiá»ƒm /5 | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | Rá»§i ro chÃ­nh |
| --- | ---: | --- | --- | --- |
| MÃ£ hÃ³a máº­t kháº©u | 3.5 | CÃ³ hash/cast vÃ  hidden, nhÆ°ng cÃ²n gá»­i/lÆ°u máº­t kháº©u rÃµ á»Ÿ má»™t sá»‘ luá»“ng | `User.php`, `AdminService.php`, email login info | Lá»™ máº­t kháº©u khá»Ÿi táº¡o/client-side |
| Báº£o vá»‡ luá»“ng Ä‘Äƒng nháº­p | 2.5 | CÃ³ validation cho login thÆ°á»ng, thiáº¿u regenerate session sau login; super-admin yáº¿u hÆ¡n | `AuthController.php`, `SuperAdminController.php` | Session fixation, login custom thiáº¿u chuáº©n |
| Chá»‘ng brute-force | 2.0 | Chá»‰ tháº¥y rate limit API, chÆ°a tháº¥y throttle login web | `RouteServiceProvider.php`, `routes/web.php` | Brute-force login |
| Middleware route quáº£n trá»‹ | 3.0 | CÃ³ auth/role group, nhÆ°ng má»™t sá»‘ route nháº¡y cáº£m chá»‰ auth hoáº·c dÃ¹ng GET xÃ³a | `routes/web.php`, route:list -v | Bypass á»Ÿ thao tÃ¡c nháº¡y cáº£m náº¿u role chÆ°a Ä‘á»§ cháº·t |
| Kiá»ƒm tra quyá»n backend | 2.5 | CÃ³ role route, chÆ°a nháº¥t quÃ¡n theo báº£n ghi | Controllers/services | IDOR, truy cáº­p dá»¯ liá»‡u ngoÃ i pháº¡m vi |
| Policy/Gate | 1.5 | ChÆ°a tháº¥y policy/gate nghiá»‡p vá»¥ | `AuthServiceProvider.php`, tÃ¬m kiáº¿m code | KhÃ³ kiá»ƒm soÃ¡t owner/branch |
| Báº£o máº­t session | 2.5 | Cookie cÆ¡ báº£n á»•n, secure phá»¥ thuá»™c env; super-admin session custom | `config/session.php`, `CheckLoginSuperAdmin.php` | Session fixation/custom session yáº¿u |

## 8.3. Kiá»ƒm tra dá»¯ liá»‡u Ä‘áº§u vÃ o

### 8.3.1. Validation phÃ­a server

| Module | Thao tÃ¡c | CÆ¡ cháº¿ validation | Ná»™i dung chÃ­nh Ä‘Æ°á»£c kiá»ƒm tra | Thiáº¿u sÃ³t |
| --- | --- | --- | --- | --- |
| ÄÄƒng nháº­p thÆ°á»ng | Login | `LoginRequest` | email required/exists, password required/max | ThÃ´ng bÃ¡o `exists` cÃ³ thá»ƒ phÃ¢n biá»‡t email tá»“n táº¡i |
| Sáº£n pháº©m | ThÃªm/Sá»­a | `ProductRequest` | tÃªn, giÃ¡, tá»“n kho, category/brand exists, tráº¡ng thÃ¡i, thumbnail image/mimes/max | Upload nhiá»u áº£nh `images[]` trong view/service chÆ°a tháº¥y rule tÆ°Æ¡ng á»©ng rÃµ rÃ ng |
| Danh má»¥c | ThÃªm/Sá»­a | `CategoryRequest` | name unique, description string, status in 1/0 | Ná»™i dung HTML/CKEditor chÆ°a sanitize táº­p trung |
| CÃ´ng ty | ThÃªm/Sá»­a | `CompanyRequest` | name unique, phone, address, tax, bank account, note | ChÆ°a tháº¥y owner scope á»Ÿ má»i thao tÃ¡c |
| NhÃ¢n viÃªn/User | ThÃªm/Sá»­a | `$this->validate()` | name/email/phone/password/role/storage/img_url image | Email gá»­i máº­t kháº©u rÃµ; dÃ¹ng `$credentials` táº¡o User |
| TÃ i khoáº£n káº¿ toÃ¡n | CRUD | `Validator::make()` | code/name/type/parent/status | Cáº§n kiá»ƒm tra quyá»n káº¿ toÃ¡n theo báº£n ghi |
| Phiáº¿u tiá»n máº·t/ngÃ¢n hÃ ng | ThÃªm/Sá»­a | `$request->validate()` | account, obj_type, obj_id exists, amount, attachment mimes/max | File lÆ°u public; update/delete dÃ¹ng ID transaction cáº§n owner scope |
| Staff order | Táº¡o Ä‘Æ¡n | `Validator::make()` | items, customer, payment, totals | Cáº§n kiá»ƒm tra race condition/tá»“n kho, owner scope |

### 8.3.2. TÃ¬m kiáº¿m, lá»c vÃ  sáº¯p xáº¿p

CÃ¡c tham sá»‘ tÃ¬m kiáº¿m chá»§ yáº¿u dÃ¹ng Eloquent/Query Builder vá»›i binding (`where('name', 'like', "%...%")`) vÃ  khÃ´ng tháº¥y `orderByRaw` nháº­n trá»±c tiáº¿p input ngÆ°á»i dÃ¹ng trong pháº¡m vi kiá»ƒm tra. CÃ³ sá»­ dá»¥ng `DB::raw`, `selectRaw`, `DB::selectOne($query, [$accountId])` cho bÃ¡o cÃ¡o/káº¿ toÃ¡n/dashboard, pháº§n lá»›n query raw cÃ³ binding hoáº·c giÃ¡ trá»‹ ná»™i bá»™. ChÆ°a phÃ¡t hiá»‡n SQL Injection cÃ³ báº±ng chá»©ng khai thÃ¡c tá»« input trá»±c tiáº¿p, nhÆ°ng cáº§n tiáº¿p tá»¥c rÃ  cÃ¡c bÃ¡o cÃ¡o cÃ³ `DB::raw` phá»©c táº¡p.

### 8.3.3. Upload file

Upload áº£nh sáº£n pháº©m/user/config cÃ³ rule `image|mimes|max`. Phiáº¿u tiá»n máº·t/ngÃ¢n hÃ ng validate attachment `file|max:2048|mimes:jpg,jpeg,png,pdf,webp` vÃ  lÆ°u báº±ng UUID vÃ o public disk. Helper `uploadImages()` chuyá»ƒn áº£nh sang `.webp`, nhÆ°ng helper `uploadFile()` dÃ¹ng `getClientOriginalName()` ghÃ©p vÃ o tÃªn file. CKFinder cáº¥u hÃ¬nh `maxSize => 0`, lÆ°u vÃ o `storage/app/public`, cho phÃ©p nhiá»u extension tÃ i liá»‡u/media/archive vÃ  cáº¥p quyá»n view/create/rename/delete/upload cho role `*`.

### 8.3.4. Ná»™i dung HTML

Blade pháº§n lá»›n dÃ¹ng `{{ }}` Ä‘á»ƒ escape. CÃ³ má»™t sá»‘ `{!! !!}` Ä‘á»ƒ render badge tÄ©nh hoáº·c pagination translation; rá»§i ro tháº¥p náº¿u dá»¯ liá»‡u khÃ´ng Ä‘áº¿n tá»« ngÆ°á»i dÃ¹ng. Tuy nhiÃªn cÃ¡c view legacy/email cÃ³ render description báº±ng `{!! $category->description !!}` vÃ  CKEditor Ä‘Æ°á»£c báº­t cho description á»Ÿ má»™t sá»‘ mÃ n, trong khi request chá»‰ validate `nullable|string`, chÆ°a tháº¥y sanitize HTML táº­p trung.

### 8.3.5. Import dá»¯ liá»‡u


| NhÃ³m dá»¯ liá»‡u Ä‘áº§u vÃ o | Tráº¡ng thÃ¡i | Báº±ng chá»©ng | Rá»§i ro |
| --- | --- | --- | --- |
| Form Request | CÃ³ nhÆ°ng chÆ°a phá»§ toÃ n bá»™ | `app/Http/Requests` | Validation khÃ´ng Ä‘á»“ng nháº¥t |
| Controller validation | CÃ³ á»Ÿ nhiá»u module | User/Employee/Account/Cash/Bank/StaffOrder controllers | Má»™t sá»‘ dÃ¹ng `$request->all()` sang service |
| Search/filter | Chá»§ yáº¿u dÃ¹ng Query Builder/Eloquent | Services/controllers search | ChÆ°a phÃ¡t hiá»‡n SQLi trá»±c tiáº¿p |
| Upload áº£nh/file | CÃ³ rule á»Ÿ nhiá»u nÆ¡i | `ProductRequest`, `ConfigController`, `CashTransactionController`, `config/ckfinder.php` | Public disk, CKFinder rá»™ng quyá»n |
| HTML/CKEditor | CÃ³ ná»™i dung HTML | Blade `{!! !!}`, CKEditor views | Stored XSS náº¿u thiáº¿u sanitize |

## 8.4. CÃ¡c nguy cÆ¡ báº£o máº­t

### 8.4.1. SQL Injection

ChÆ°a phÃ¡t hiá»‡n vá»‹ trÃ­ dÃ¹ng raw SQL ghÃ©p trá»±c tiáº¿p input ngÆ°á»i dÃ¹ng theo cÃ¡ch cÃ³ thá»ƒ káº¿t luáº­n SQL Injection. CÃ¡c raw query Ä‘Ã¡ng chÃº Ã½ á»Ÿ bÃ¡o cÃ¡o/káº¿ toÃ¡n dÃ¹ng binding hoáº·c giÃ¡ trá»‹ ná»™i bá»™. Tráº¡ng thÃ¡i: **ÄÃ£ xÃ¡c minh an toÃ n trong pháº¡m vi kiá»ƒm tra**, nhÆ°ng cáº§n rÃ  tiáº¿p khi thÃªm sort/filter Ä‘á»™ng.

### 8.4.2. XSS

CÃ³ nguy cÆ¡ Stored XSS á»Ÿ ná»™i dung HTML/CKEditor do má»™t sá»‘ view legacy render `{!! $category->description !!}` vÃ  JS `.html(response.table)` hiá»ƒn thá»‹ HTML tráº£ vá» tá»« server. Blade chÃ­nh hiá»‡n escape nhiá»u dá»¯ liá»‡u báº±ng `{{ }}`. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

### 8.4.3. CSRF

Web middleware cÃ³ `VerifyCsrfToken`; form login dÃ¹ng `@csrf` vÃ  AJAX gá»­i header CSRF. CKFinder báº­t `csrfProtection => true`. Tráº¡ng thÃ¡i: **ÄÃ£ xÃ¡c minh an toÃ n trong pháº¡m vi kiá»ƒm tra**, nhÆ°ng route GET thá»±c hiá»‡n xÃ³a/cáº­p nháº­t váº«n lÃ  rá»§i ro thiáº¿t káº¿ HTTP.

### 8.4.4. IDOR


### 8.4.5. Mass Assignment

Model `User` cho phÃ©p fillable `password`, `status`, `role_id`; `SuperAdmin` cho phÃ©p `password`, `bank_account`. Má»™t sá»‘ service/controller truyá»n `$request->all()` hoáº·c `$data->all()` vÃ o update. CÃ³ validate á»Ÿ nhiá»u Ä‘iá»ƒm, nhÆ°ng luá»“ng super-admin profile update dÃ¹ng `$request->all()` vÃ o `updateSuperAdmin()`. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

### 8.4.6. Upload file Ä‘á»™c háº¡i

Upload áº£nh/controller chÃ­nh cÃ³ validation, nhÆ°ng CKFinder cho phÃ©p nhiá»u extension, khÃ´ng giá»›i háº¡n dung lÆ°á»£ng (`maxSize => 0`), public backend vÃ  quyá»n rá»™ng. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

### 8.4.7. Lá»™ cáº¥u hÃ¬nh vÃ  secret


### 8.4.8. Lá»™ lá»—i chi tiáº¿t

`Handler::report()` log exception message/file/line; helper transaction log trace Ä‘áº§y Ä‘á»§. Má»™t sá»‘ response tráº£ `$e->getMessage()` hoáº·c token/API response vÃ o log. `php artisan about` local Ä‘ang debug enabled, nhÆ°ng chÆ°a xÃ¡c minh production. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

### 8.4.9. Rate limiting

API cÃ³ throttle 60/min. Login web vÃ  super-admin chÆ°a tháº¥y throttle. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

### 8.4.10. Logging vÃ  audit

CÃ³ nhiá»u `Log::info/error` trong service/controller, nhÆ°ng chá»§ yáº¿u log ká»¹ thuáº­t/lá»—i, chÆ°a tháº¥y audit log nghiá»‡p vá»¥ chuáº©n cho thay Ä‘á»•i vai trÃ², giao dá»‹ch, cÃ´ng ná»£, token, upload/xÃ³a file. CÃ³ nÆ¡i log token/API response. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

### 8.4.11. Redirect vÃ  URL bÃªn ngoÃ i


### 8.4.12. Dependency

`composer audit` phÃ¡t hiá»‡n 33 advisory áº£nh hÆ°á»Ÿng 16 package, trong Ä‘Ã³ cÃ³ advisory critical/high liÃªn quan `phpoffice/phpspreadsheet`, `laravel/framework`, `aws/aws-sdk-php`, `symfony/*`, `guzzle*`; cÃ³ 2 package abandoned. `npm audit --omit=dev` phÃ¡t hiá»‡n 60 vulnerability, gá»“m CKEditor/lodash-es vÃ  sweetalert2. Tráº¡ng thÃ¡i: **PhÃ¡t hiá»‡n rá»§i ro hoáº·c thiáº¿u kiá»ƒm soÃ¡t**.

| Nguy cÆ¡ | Tráº¡ng thÃ¡i | Báº±ng chá»©ng | Má»©c Ä‘á»™ |
| --- | --- | --- | --- |
| SQL Injection | ChÆ°a phÃ¡t hiá»‡n báº±ng chá»©ng trá»±c tiáº¿p | Query Builder/raw cÃ³ binding | Tháº¥p/ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n |
| XSS | CÃ³ rá»§i ro stored HTML | `{!! $category->description !!}`, CKEditor, npm audit CKEditor | Cao |
| CSRF | CÃ³ middleware CSRF | `Kernel.php`, Blade `@csrf`, CKFinder csrf | Tháº¥p |
| IDOR | CÃ³ rá»§i ro theo ID | Routes `{id}`, controller/service chÆ°a scope Ä‘á»u | Cao |
| Mass Assignment | CÃ³ rá»§i ro á»Ÿ User/SuperAdmin/request all | Models fillable, controllers/services | Cao |
| Upload Ä‘á»™c háº¡i | Rá»§i ro CKFinder vÃ  public disk | `config/ckfinder.php`, helpers | Cao |
| Rate limit | Thiáº¿u login throttle | `routes/web.php`, `RouteServiceProvider.php` | Trung bÃ¬nh |
| Logging/audit | CÃ³ log ká»¹ thuáº­t, thiáº¿u audit chuáº©n | `Log::`, `Handler.php` | Trung bÃ¬nh |
| Dependency | Nhiá»u advisory | composer/npm audit | Cao |

## 8.5. Quáº£n lÃ½ dá»¯ liá»‡u nháº¡y cáº£m

### 8.5.1. Máº­t kháº©u

Máº­t kháº©u Ä‘Æ°á»£c hash khi lÆ°u cho User vÃ  khi Ä‘á»•i máº­t kháº©u. Tuy nhiÃªn máº­t kháº©u khá»Ÿi táº¡o Ä‘Æ°á»£c gá»­i qua email, máº­t kháº©u super-admin cÃ³ thá»ƒ bá»‹ lÆ°u localStorage khi chá»n remember vÃ  bá»‹ ghi console. Cáº§n thay báº±ng reset link/token má»™t láº§n vÃ  khÃ´ng lÆ°u máº­t kháº©u rÃµ phÃ­a client.

### 8.5.2. Token


### 8.5.3. ThÃ´ng tin khÃ¡ch hÃ ng

KhÃ¡ch hÃ ng gá»“m tÃªn, phone, email, Ä‘á»‹a chá»‰, Ä‘Æ¡n hÃ ng, cÃ´ng ná»£. Route admin/staff cÃ³ middleware, nhÆ°ng owner/branch scope chÆ°a nháº¥t quÃ¡n trong má»i controller/service. ChÆ°a cÃ³ kiá»ƒm thá»­ IDOR báº±ng nhiá»u tÃ i khoáº£n.

### 8.5.4. ThÃ´ng tin thanh toÃ¡n vÃ  tÃ i chÃ­nh

Module thu/chi, tÃ i khoáº£n káº¿ toÃ¡n, cÃ´ng ná»£, giao dá»‹ch cÃ³ validate cÆ¡ báº£n vÃ  middleware role káº¿ toÃ¡n/admin. Tuy nhiÃªn audit log nghiá»‡p vá»¥ chÆ°a rÃµ, file Ä‘Ã­nh kÃ¨m lÆ°u public disk, má»™t sá»‘ thao tÃ¡c update/delete dÃ¹ng ID trá»±c tiáº¿p.

### 8.5.5. File ná»™i bá»™

File public disk dÃ¹ng cho áº£nh, attachment, CKFinder. ChÆ°a tháº¥y private disk/route download cÃ³ kiá»ƒm tra quyá»n cho file nháº¡y cáº£m. CKFinder cÃ³ quyá»n upload/xÃ³a/rename rá»™ng.

### 8.5.6. Backup

KhÃ´ng tháº¥y backup/dump Ä‘Æ°á»£c Git tracked theo lá»‡nh kiá»ƒm tra `git ls-files "*.sql" "*.bak"`. ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n xÃ¡c minh backup production, quyá»n truy cáº­p backup vÃ  mÃ£ hÃ³a backup.

### 8.5.7. Dá»¯ liá»‡u trong log

CÃ³ log exception, trace, API response, access token á»Ÿ má»™t sá»‘ service/controller. Cáº§n redaction dá»¯ liá»‡u nháº¡y cáº£m trÆ°á»›c khi log.

| NhÃ³m dá»¯ liá»‡u | Hiá»‡n tráº¡ng | Báº±ng chá»©ng | Má»©c rá»§i ro | HÆ°á»›ng xá»­ lÃ½ |
| --- | --- | --- | --- | --- |
| Máº­t kháº©u | Hash khi lÆ°u, nhÆ°ng cÃ³ gá»­i/lÆ°u rÃµ | `User.php`, email template, superadmin login view | Cao | Reset link, bá» localStorage password, Ã©p Ä‘á»•i máº­t kháº©u láº§n Ä‘áº§u |
| KhÃ¡ch hÃ ng | Middleware cÃ³, owner scope chÆ°a Ä‘á»u | Client/Staff/Admin controllers | Cao | Policy/owner/branch scope |
| TÃ i chÃ­nh | CÃ³ role káº¿ toÃ¡n, audit chÆ°a rÃµ | Account/Cash/Bank/Transaction controllers | Cao | Audit log, scope, private attachment |
| File ná»™i bá»™ | Public disk/CKFinder rá»™ng | `config/filesystems.php`, `config/ckfinder.php` | Cao | Private disk, kiá»ƒm tra quyá»n download/upload |
| Backup | ChÆ°a tháº¥y tracked dump | `git ls-files` | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Kiá»ƒm tra production backup |
| Log | CÃ³ log token/lá»—i chi tiáº¿t | `Log::`, `Handler.php` | Cao | Mask token/PII, phÃ¢n quyá»n log |

## 8.6. Danh sÃ¡ch phÃ¡t hiá»‡n báº£o máº­t

| MÃ£ | NhÃ³m báº£o máº­t | PhÃ¡t hiá»‡n | Vá»‹ trÃ­ hoáº·c báº±ng chá»©ng | Dá»¯ liá»‡u/chá»©c nÄƒng áº£nh hÆ°á»Ÿng | Má»©c Ä‘á»™ | HÆ°á»›ng xá»­ lÃ½ |
| --- | --- | --- | --- | --- | --- | --- |
| SEC-002 | Máº­t kháº©u phÃ­a client | Form super-admin lÆ°u máº­t kháº©u rÃµ vÃ o `localStorage` vÃ  `console.log` | `resources/views/superadmin/formlogin/index.blade.php:176-214` | TÃ i khoáº£n super-admin | Cao | Bá» lÆ°u máº­t kháº©u, dÃ¹ng remember token chuáº©n Laravel, xÃ³a console log |
| SEC-004 | XÃ¡c thá»±c/Session | ÄÄƒng nháº­p thÆ°á»ng vÃ  super-admin chÆ°a tháº¥y regenerate session sau login; super-admin dÃ¹ng session custom `authSuper` | `AuthController.php:22-56`; `SuperAdminController.php:66-72`; `CheckLoginSuperAdmin.php:18-22` | Login admin/staff/super-admin | Cao | Regenerate session sau login, dÃ¹ng guard chuáº©n, invalidate/token logout Ä‘áº§y Ä‘á»§ |
| SEC-005 | Rate limiting | Login web/super-admin chÆ°a gáº¯n throttle; rate limit má»›i tháº¥y á»Ÿ API | `routes/web.php:55-66,419-420`; `RouteServiceProvider.php:27-29` | Form Ä‘Äƒng nháº­p | Trung bÃ¬nh | ThÃªm `throttle`/RateLimiter theo IP+email, log failed login |
| SEC-006 | Upload/CKFinder | CKFinder cáº¥u hÃ¬nh upload rá»™ng, khÃ´ng giá»›i háº¡n dung lÆ°á»£ng, public storage, quyá»n role `*` cho upload/xÃ³a/rename | `config/ckfinder.php:82-112,121-138,144-184`; route `ckfinder/*` | File public, media/tÃ i liá»‡u | Cao | Giá»›i háº¡n role, MIME/extension/maxSize, private disk, kiá»ƒm tra quyá»n |
| SEC-008 | Mass Assignment | `User`/`SuperAdmin` fillable chá»©a trÆ°á»ng nháº¡y cáº£m vÃ  má»™t sá»‘ luá»“ng dÃ¹ng `$request->all()` | `User.php:18-29`; `SuperAdmin.php:14-21`; `SuperAdminController.php:38-42`; `AdminService.php:48-63,75-83` | Role/status/password/profile | Cao | DÃ¹ng `$request->validated()`, tÃ¡ch DTO/allowlist, báº£o vá»‡ role/status/password |
| SEC-009 | XSS/HTML | Má»™t sá»‘ view render HTML khÃ´ng escape vÃ  CKEditor/CKFinder/dependency cÃ³ rá»§i ro XSS | `resources/views/emails/admin/category/table.blade.php:16`; `resources/views/emails/admin/category/detail.blade.php:102`; `npm audit` CKEditor | Ná»™i dung danh má»¥c/HTML quáº£n trá»‹ | Cao | Sanitize HTML, háº¡n cháº¿ `{!! !!}`, nÃ¢ng cáº¥p CKEditor/lodash |
| SEC-011 | Dependency | Composer/NPM audit phÃ¡t hiá»‡n nhiá»u advisory, gá»“m critical/high | `composer audit`; `npm audit --omit=dev`; `composer.json`, `package-lock.json` | Laravel, PhpSpreadsheet, CKEditor, lodash-es, Symfony/Guzzle | Cao | Láº­p káº¿ hoáº¡ch nÃ¢ng cáº¥p cÃ³ test regression, Æ°u tiÃªn PhpSpreadsheet/Laravel/CKEditor |

## 8.7. Báº£ng cháº¥m Ä‘iá»ƒm báº£o máº­t

| NhÃ³m Ä‘Ã¡nh giÃ¡ | Äiá»ƒm /5 | CÆ¡ sá»Ÿ cháº¥m Ä‘iá»ƒm | Rá»§i ro chÃ­nh |
| --- | ---: | --- | --- |
| MÃ£ hÃ³a máº­t kháº©u | 3.0 | CÃ³ hash/cast, nhÆ°ng cÃ²n gá»­i/lÆ°u máº­t kháº©u rÃµ | Máº­t kháº©u rÃµ qua email/localStorage |
| XÃ¡c thá»±c | 2.5 | Login thÆ°á»ng dÃ¹ng Laravel, super-admin custom | Session custom, thiáº¿u regenerate |
| PhÃ¢n quyá»n backend | 2.5 | CÃ³ route middleware role, thiáº¿u policy/permission chi tiáº¿t | Role rá»™ng, bulk/custom route |
| Kiá»ƒm soÃ¡t IDOR | 2.0 | Má»™t sá»‘ owner scope, nhiá»u route `{id}` chÆ°a rÃµ | Truy cáº­p dá»¯ liá»‡u ngoÃ i pháº¡m vi |
| Validation server | 3.0 | CÃ³ Form Request/validate á»Ÿ nhiá»u module | ChÆ°a Ä‘á»“ng nháº¥t, `$request->all()` |
| Upload file | 2.0 | CÃ³ validate áº£nh/attachment, CKFinder rá»™ng | Public file/extension/maxSize |
| SQL Injection | 3.5 | ChÆ°a tháº¥y ghÃ©p input trá»±c tiáº¿p vÃ o raw SQL | Cáº§n rÃ  tiáº¿p query raw bÃ¡o cÃ¡o |
| XSS | 2.0 | Blade escape nhiá»u nÆ¡i, nhÆ°ng cÃ³ `{!! !!}`/CKEditor audit | Stored XSS |
| CSRF | 4.0 | Web middleware/Blade CSRF/CKFinder CSRF | GET thay Ä‘á»•i dá»¯ liá»‡u |
| Mass Assignment | 2.0 | Fillable nháº¡y cáº£m, request all | Role/status/password |
| Quáº£n lÃ½ secret | 1.0 | CÃ³ hardcoded token vÃ  token log/response | Lá»™ token production náº¿u dÃ¹ng tháº­t |
| Session vÃ  cookie | 2.5 | HttpOnly/SameSite, secure phá»¥ thuá»™c env | Super-admin session custom |
| Rate limiting | 2.0 | API throttle, login thiáº¿u | Brute-force |
| Logging vÃ  audit | 2.0 | CÃ³ log ká»¹ thuáº­t, thiáº¿u audit; log token | Lá»™ dá»¯ liá»‡u nháº¡y cáº£m |
| Dá»¯ liá»‡u nháº¡y cáº£m | 2.0 | CÃ³ middleware, nhÆ°ng PII/tÃ i chÃ­nh/file/token cÃ²n rá»§i ro | KhÃ¡ch hÃ ng/tÃ i chÃ­nh/token |
| Dependency | 1.5 | Composer/NPM audit nhiá»u advisory | Critical/high dependency |

**Äiá»ƒm báº£o máº­t trung bÃ¬nh = 2.41/5** trÃªn 16 tiÃªu chÃ­ Ä‘Ã£ Ä‘Ã¡nh giÃ¡. Äiá»ƒm sá»‘ chá»‰ pháº£n Ã¡nh má»©c kiá»ƒm soÃ¡t dá»±a trÃªn mÃ£ nguá»“n vÃ  audit local, khÃ´ng thay tháº¿ danh sÃ¡ch phÃ¡t hiá»‡n `SEC-xxx`.

## 8.8. Nháº­n xÃ©t tá»•ng quÃ¡t


## 8.9. Kiáº¿n nghá»‹ cáº£i tiáº¿n báº£o máº­t

| Thá»© tá»± Æ°u tiÃªn | Háº¡ng má»¥c | Hiá»‡n tráº¡ng cáº§n xá»­ lÃ½ | HÆ°á»›ng cáº£i tiáº¿n | Káº¿t quáº£ mong Ä‘á»£i |
| ---: | --- | --- | --- | --- |
| 2 | Máº­t kháº©u | Gá»­i/lÆ°u máº­t kháº©u rÃµ, máº­t kháº©u máº·c Ä‘á»‹nh | Reset link/token má»™t láº§n, bá» localStorage password, Ã©p Ä‘á»•i máº­t kháº©u láº§n Ä‘áº§u | Giáº£m nguy cÆ¡ lá»™ tÃ i khoáº£n |
| 3 | Auth/session | Thiáº¿u regenerate login, super-admin session custom | DÃ¹ng guard chuáº©n cho SuperAdmin, regenerate/invalidate session Ä‘áº§y Ä‘á»§ | Giáº£m session fixation/bypass |
| 4 | PhÃ¢n quyá»n backend | Role route cÆ¡ báº£n, thiáº¿u Policy/Gate | Táº¡o Policy/Gate hoáº·c service authorization theo module | Kiá»ƒm soÃ¡t quyá»n nháº¥t quÃ¡n |
| 5 | IDOR | Nhiá»u route `{id}` chÆ°a scope rÃµ | Scope query theo user/company/branch/storage; test báº±ng nhiá»u tÃ i khoáº£n | KhÃ´ng xem/sá»­a dá»¯ liá»‡u ngoÃ i pháº¡m vi |
| 6 | Upload/CKFinder | Quyá»n rá»™ng, public, maxSize 0 | Giá»›i háº¡n role/extension/MIME/size, private disk, download qua controller | Giáº£m upload Ä‘á»™c háº¡i/lá»™ file |
| 7 | XSS/HTML | CKEditor vÃ  `{!! !!}` | Sanitize HTML báº±ng allowlist, escape máº·c Ä‘á»‹nh, nÃ¢ng CKEditor | Giáº£m stored XSS |
| 8 | Mass Assignment | Fillable nháº¡y cáº£m, `$request->all()` | DÃ¹ng `$request->validated()`, allowlist trÆ°á»ng, tÃ¡ch update role/password | TrÃ¡nh sá»­a role/status/password trÃ¡i phÃ©p |
| 9 | CSRF/HTTP method | GET xÃ³a á»Ÿ má»™t sá»‘ route | Chuyá»ƒn sang DELETE/POST cÃ³ CSRF, xÃ¡c nháº­n quyá»n backend | Giáº£m CSRF/side effect qua GET |
| 10 | Rate limiting | Login thiáº¿u throttle | RateLimiter theo IP+email, lockout táº¡m thá»i, log failed login | Giáº£m brute-force |
| 11 | Logging/audit | Log token/lá»—i chi tiáº¿t, thiáº¿u audit nghiá»‡p vá»¥ | Redact token/PII, audit log cho tÃ i chÃ­nh/quyá»n/file/token | Truy váº¿t Ä‘Æ°á»£c mÃ  khÃ´ng lá»™ dá»¯ liá»‡u |
| 12 | Dependency | Nhiá»u advisory composer/npm | NÃ¢ng cáº¥p cÃ³ kiá»ƒm thá»­, Æ°u tiÃªn PhpSpreadsheet/Laravel/CKEditor/lodash | Giáº£m lá»— há»•ng tá»« thÆ° viá»‡n |
| 13 | Cáº¥u hÃ¬nh production | ChÆ°a xÃ¡c minh APP_DEBUG, HTTPS, cookie secure | Checklist production: debug off, HTTPS, secure cookie, CORS domain cá»¥ thá»ƒ, security headers | Cáº¥u hÃ¬nh váº­n hÃ nh an toÃ n hÆ¡n |
| 14 | Quy trÃ¬nh báº£o máº­t | ChÆ°a tháº¥y quy trÃ¬nh thu há»“i token/sá»± cá»‘ | TÃ i liá»‡u hÃ³a incident response, secret rotation, backup encryption | Sáºµn sÃ ng xá»­ lÃ½ sá»± cá»‘ |

## 8.10. Ná»™i dung chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra

| STT | Ná»™i dung | Pháº§n Ä‘Ã£ xÃ¡c minh | LÃ½ do chÆ°a kiá»ƒm tra Ä‘áº§y Ä‘á»§ | Äiá»u kiá»‡n cáº§n bá»• sung | áº¢nh hÆ°á»Ÿng Ä‘áº¿n káº¿t luáº­n |
| --: | --- | --- | --- | --- | --- |
| 1 | Cáº¥u hÃ¬nh production | Config máº«u, `php artisan about` local | ChÆ°a cÃ³ quyá»n production | Env production, web server, reverse proxy | ChÆ°a káº¿t luáº­n tráº¡ng thÃ¡i debug/cookie/HTTPS tháº­t |
| 2 | Web server/HTTPS/security header | ChÆ°a tháº¥y config web server | KhÃ´ng cÃ³ Nginx/Apache/CDN config | File deploy/server | ChÆ°a Ä‘Ã¡nh giÃ¡ HSTS/CSP/header |
| 3 | Rate limit á»Ÿ reverse proxy | App chá»‰ tháº¥y API limiter | KhÃ´ng cÃ³ config proxy/WAF | Config Nginx/CDN/WAF | Login brute-force cÃ³ thá»ƒ Ä‘Æ°á»£c cháº·n ngoÃ i app nhÆ°ng chÆ°a xÃ¡c minh |
| 4 | Kiá»ƒm thá»­ IDOR thá»±c táº¿ | Äá»c route/controller/service | ChÆ°a cÃ³ nhiá»u tÃ i khoáº£n vÃ  dá»¯ liá»‡u test | TÃ i khoáº£n Admin/Staff/SuperAdmin nhiá»u chi nhÃ¡nh | ChÆ°a xÃ¡c nháº­n khai thÃ¡c thá»±c táº¿ |
| 5 | File storage production | Äá»c filesystem/CKFinder config | ChÆ°a xem quyá»n file server vÃ  public storage tháº­t | Server/storage policy | ChÆ°a káº¿t luáº­n lá»™ file thá»±c táº¿ |
| 6 | Log production | Äá»c code logging | KhÃ´ng truy cáº­p log tháº­t | Log Ä‘Ã£ mask/rotation/access control | ChÆ°a biáº¿t dá»¯ liá»‡u nháº¡y cáº£m Ä‘Ã£ tá»«ng bá»‹ log chÆ°a |
| 7 | Backup | Git khÃ´ng tháº¥y dump tracked | KhÃ´ng cÃ³ quy trÃ¬nh backup/restore | TÃ i liá»‡u backup, quyá»n kiá»ƒm tra | ChÆ°a Ä‘Ã¡nh giÃ¡ mÃ£ hÃ³a/khÃ´i phá»¥c backup |
| 8 | Token Ä‘Ã£ lá»™ trong lá»‹ch sá»­ Git | CÃ³ phÃ¡t hiá»‡n trong working tree | ChÆ°a cháº¡y rÃ  lá»‹ch sá»­ Ä‘áº§y Ä‘á»§ vÃ  khÃ´ng cÃ´ng khai secret | Quy trÃ¬nh secret scanning an toÃ n | Cáº§n thu há»“i token ngay náº¿u tá»«ng dÃ¹ng tháº­t |
| 9 | Penetration test | ChÆ°a thá»±c hiá»‡n | Pháº¡m vi yÃªu cáº§u chá»‰ Ä‘á»c mÃ£ nguá»“n/lá»‡nh an toÃ n | Staging, phÃª duyá»‡t pentest | BÃ¡o cÃ¡o khÃ´ng pháº£i káº¿t quáº£ pentest |
| 10 | Dependency trÃªn server | Audit theo lockfile local | KhÃ´ng biáº¿t version deployed | SBOM/deploy artifact | ChÆ°a cháº¯c production dÃ¹ng Ä‘Ãºng lockfile hiá»‡n táº¡i |

## Nguá»“n xÃ¡c minh

| Ná»™i dung Ä‘Ã¡nh giÃ¡ | File, thÆ° má»¥c hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| XÃ¡c thá»±c | `config/auth.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/SuperAdmin/SuperAdminController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Models/User.php`, `app/Models/SuperAdmin.php` |
| Session vÃ  cookie | `config/session.php`, `AuthController@logout`, `SuperAdminController@login/logout`, `CheckLoginSuperAdmin.php`, `php artisan about` |
| Middleware | `routes/web.php`, `routes/api.php`, `app/Http/Kernel.php`, `app/Http/Middleware/RoleMiddleware.php`, `CheckLogin.php`, `CheckLoginSuperAdmin.php`, `php artisan route:list -v` |
| Policy vÃ  Gate | `app/Providers/AuthServiceProvider.php`, káº¿t quáº£ tÃ¬m kiáº¿m `Gate::`, `@can`, `middleware can` |
| Validation | `app/Http/Requests`, controller dÃ¹ng `$request->validate()`/`Validator::make()` |
| SQL Injection | TÃ¬m kiáº¿m `DB::raw`, `selectRaw`, `DB::select`, `whereRaw`, `orderByRaw`, controllers/services bÃ¡o cÃ¡o/káº¿ toÃ¡n |
| XSS | `resources/views`, CKEditor/CKFinder views, tÃ¬m kiáº¿m `{!! !!}`, `.html()`, `innerHTML`, `npm audit --omit=dev` |
| CSRF | `app/Http/Kernel.php`, `resources/views/auth/login.blade.php`, `config/ckfinder.php`, route methods |
| Mass Assignment | `app/Models/User.php`, `app/Models/SuperAdmin.php`, controllers/services dÃ¹ng `$request->all()` hoáº·c `$data->all()` |
| Upload file | `app/Helpers/helper.php`, `app/Helpers/system.php`, `app/Http/Requests/Product/ProductRequest.php`, `CashTransactionController`, `BankTransactionController`, `config/filesystems.php`, `config/ckfinder.php` |
| Rate limiting | `app/Providers/RouteServiceProvider.php`, `app/Http/Kernel.php`, `routes/web.php`, `routes/api.php` |
| Logging vÃ  audit | `config/logging.php`, `app/Exceptions/Handler.php`, `app/Helpers/helper.php`, tÃ¬m kiáº¿m `Log::` |
| Dá»¯ liá»‡u nháº¡y cáº£m | Models khÃ¡ch hÃ ng/Ä‘Æ¡n hÃ ng/tÃ i chÃ­nh/token, controllers Admin/Staff/SuperAdmin, storage config |
| Dependency | `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `composer validate`, `composer audit`, `npm audit --omit=dev` |
| Test báº£o máº­t/cháº¥t lÆ°á»£ng liÃªn quan | `tests`, `php artisan test` vá»›i 3 test pass, gá»“m redirect login vÃ  helper áº£nh upload |

# 9. ÄÃNH GIÃ GIAO DIá»†N VÃ€ TRáº¢I NGHIá»†M Sá»¬ Dá»¤NG

## 9.1. PhÆ°Æ¡ng phÃ¡p vÃ  pháº¡m vi Ä‘Ã¡nh giÃ¡

ÄÃ¡nh giÃ¡ nÃ y Ä‘Æ°á»£c thá»±c hiá»‡n theo hÆ°á»›ng Ä‘á»c mÃ£ nguá»“n giao diá»‡n vÃ  Ä‘á»‘i chiáº¿u vá»›i cÃ¡c luá»“ng váº­n hÃ nh chÃ­nh, khÃ´ng chá»‰nh sá»­a Blade, CSS, JavaScript, asset, dá»¯ liá»‡u, commit hoáº·c push code. Nguá»“n Ä‘Ã£ kiá»ƒm tra gá»“m `resources/views`, `resources/css`, `resources/js`, `public/assets/css`, `public/assets/js`, `routes/web.php`, cÃ¡c controller Admin/Staff/SuperAdmin liÃªn quan, `package.json`, `php artisan route:list`, `npm list --depth=0` vÃ  `php artisan about`. CÃ³ tham chiáº¿u thÃªm bá»™ quy táº¯c Web Interface Guidelines á»Ÿ má»©c nguyÃªn táº¯c accessibility, form, focus, feedback, navigation vÃ  destructive action.


Thiáº¿t bá»‹ vÃ  kÃ­ch thÆ°á»›c mÃ n hÃ¬nh: chÆ°a cháº¡y kiá»ƒm thá»­ viewport thá»±c táº¿ báº±ng trÃ¬nh duyá»‡t á»Ÿ 1366x768, 1920x1080, 768x1024, 375x812 vÃ  320x568. CÃ¡c nháº­n Ä‘á»‹nh responsive dÆ°á»›i Ä‘Ã¢y Ä‘Æ°á»£c Ä‘á»‘i chiáº¿u tá»« class Bootstrap, media query, cáº¥u trÃºc báº£ng, wrapper `table-responsive`, CSS inline vÃ  JavaScript hiá»‡n cÃ³. Ná»™i dung Ä‘Ã¡nh giÃ¡ responsive, tÆ°Æ¡ng tÃ¡c vÃ  kháº£ nÄƒng sá»­ dá»¥ng thá»±c táº¿ má»›i Ä‘Æ°á»£c xÃ¡c minh má»™t pháº§n dá»±a trÃªn Blade, CSS vÃ  JavaScript hiá»‡n cÃ³. CÃ¡c má»¥c cáº§n cháº¡y trÃ¬nh duyá»‡t hoáº·c tÃ i khoáº£n theo vai trÃ² Ä‘Æ°á»£c ghi rÃµ lÃ  `[CHÆ¯A Äá»¦ ÄIá»€U KIá»†N KIá»‚M TRA RESPONSIVE THá»°C Táº¾]`.

## 9.2. ÄÃ¡nh giÃ¡ giao diá»‡n

### 9.2.1. TÃ­nh Ä‘á»“ng nháº¥t

Há»‡ thá»‘ng cÃ³ ná»n táº£ng layout dÃ¹ng chung cho Admin thÃ´ng qua `admin.layout.index`, `header`, `sidebar`, `footer`, `style` vÃ  `script`. Nhiá»u trang danh sÃ¡ch Admin sá»­ dá»¥ng cÃ¹ng cáº¥u trÃºc `page-inner`, breadcrumb component, card, search, table partial vÃ  pagination, vÃ­ dá»¥ sáº£n pháº©m, khÃ¡ch hÃ ng vÃ  kho. Sidebar Admin chia nhÃ³m nghiá»‡p vá»¥ rÃµ hÆ¡n theo sáº£n pháº©m, kho hÃ ng, khÃ¡ch hÃ ng, bÃ¡n hÃ ng, bÃ¡o cÃ¡o, káº¿ toÃ¡n vÃ  cáº¥u hÃ¬nh.

TÃ­nh Ä‘á»“ng nháº¥t chÆ°a Ä‘áº¡t hoÃ n toÃ n vÃ¬ Staff/POS dÃ¹ng layout riÃªng trong `Themes.layout_staff`, SuperAdmin dÃ¹ng layout riÃªng trong `superadmin.layout`, cÃ²n má»™t sá»‘ view cÅ© náº±m trong `Themes/admin`, `emails/admin` vÃ  `sa`. SuperAdmin cÃ²n náº¡p Ä‘á»“ng thá»i Bootstrap CDN 4.5.2 vÃ  asset Bootstrap/Kaiadmin ná»™i bá»™, trong khi Admin náº¡p Kaiadmin theo partial riÃªng; Ä‘iá»u nÃ y lÃ m tÄƒng rá»§i ro khÃ¡c biá»‡t spacing, modal, button vÃ  dropdown giá»¯a cÃ¡c vai trÃ². Má»™t sá»‘ trang dÃ¹ng breadcrumb component, má»™t sá»‘ trang tá»± viáº¿t breadcrumb hoáº·c khÃ´ng dÃ¹ng component.

| ThÃ nh pháº§n | Má»©c Ä‘á»™ Ä‘á»“ng nháº¥t | Vá»‹ trÃ­ tiÃªu biá»ƒu | Váº¥n Ä‘á» phÃ¡t hiá»‡n | áº¢nh hÆ°á»Ÿng |
| --- | --- | --- | --- | --- |
| Layout | TÆ°Æ¡ng Ä‘á»‘i Ä‘á»“ng nháº¥t | `resources/views/admin/layout/index.blade.php`, `resources/views/superadmin/layout/index.blade.php`, `resources/views/Themes/layout_staff/app.blade.php` | Admin, Staff vÃ  SuperAdmin dÃ¹ng layout khÃ¡c nhau; SuperAdmin/Staff cÃ³ nhiá»u CSS/JS inline | NgÆ°á»i dÃ¹ng chuyá»ƒn vai trÃ² cÃ³ thá»ƒ gáº·p khÃ¡c biá»‡t thao tÃ¡c vÃ  hiá»ƒn thá»‹ |
| TiÃªu Ä‘á» trang | TÆ°Æ¡ng Ä‘á»‘i Ä‘á»“ng nháº¥t | Dashboard, sáº£n pháº©m, Ä‘Æ¡n hÃ ng, bÃ¡o cÃ¡o | Má»™t sá»‘ trang dÃ¹ng card title, má»™t sá»‘ dÃ¹ng heading trá»±c tiáº¿p, má»™t sá»‘ tá»± viáº¿t breadcrumb | Giáº£m kháº£ nÄƒng Ä‘á»‹nh vá»‹ nhanh |
| Form | TÆ°Æ¡ng Ä‘á»‘i Ä‘á»“ng nháº¥t | Product form, employee form, config form, receipt/expense add | CÃ¡ch chia cá»™t, label, error vÃ  submit chÆ°a Ä‘á»“ng nháº¥t | NgÆ°á»i dÃ¹ng pháº£i há»c láº¡i cÃ¡ch nháº­p á»Ÿ má»™t sá»‘ module |
| Modal | TÆ°Æ¡ng Ä‘á»‘i Ä‘á»“ng nháº¥t | Storage modal, POS invoice/customer modal, Profit date range modal | Bootstrap 4/5 attribute láº«n lá»™n á»Ÿ má»™t sá»‘ khu vá»±c | Rá»§i ro tÆ°Æ¡ng tÃ¡c khÃ´ng nháº¥t quÃ¡n náº¿u script xung Ä‘á»™t |

### 9.2.2. MÃ u sáº¯c vÃ  tráº¡ng thÃ¡i

Admin chá»§ yáº¿u dÃ¹ng Bootstrap/Kaiadmin, cÃ¡c tráº¡ng thÃ¡i phá»• biáº¿n cÃ³ mÃ u kÃ¨m chá»¯: sáº£n pháº©m dÃ¹ng `bg-success` cho "KÃ­ch hoáº¡t" vÃ  `bg-danger` cho "KhÃ´ng kÃ­ch hoáº¡t"; Ä‘Æ¡n hÃ ng dÃ¹ng badge kÃ¨m chá»¯ "ÄÃ£ hoÃ n thÃ nh"/"ChÆ°a hoÃ n thÃ nh"; dashboard dÃ¹ng class `positive`, `negative`, `low-stock`, `in-stock`. ÄÃ¢y lÃ  Ä‘iá»ƒm tÃ­ch cá»±c vÃ¬ tráº¡ng thÃ¡i khÃ´ng phá»¥ thuá»™c hoÃ n toÃ n vÃ o mÃ u.


### 9.2.3. Font chá»¯ vÃ  kháº£ nÄƒng Ä‘á»c

Layout Admin náº¡p Public Sans qua WebFont; dashboard tá»± Ä‘á»‹nh nghÄ©a nhiá»u kÃ­ch thÆ°á»›c chá»¯ há»£p lÃ½ cho metric, table vÃ  card. Báº£ng tÃ i chÃ­nh/cÃ´ng ná»£ Ä‘Ã£ cÄƒn pháº£i sá»‘ tiá»n á»Ÿ nhiá»u nÆ¡i, vÃ­ dá»¥ Ä‘Æ¡n hÃ ng vÃ  cÃ´ng ná»£. Ná»™i dung tiáº¿ng Viá»‡t trong file bÃ¡o cÃ¡o vÃ  pháº§n lá»›n Blade hiá»ƒn thá»‹ Ä‘Ãºng dáº¥u.


### 9.2.4. Button vÃ  thao tÃ¡c

NÃºt chÃ­nh/phá»¥ cÆ¡ báº£n cÃ³ phÃ¢n biá»‡t báº±ng Bootstrap (`btn-primary`, `btn-outline-secondary`, `btn-danger`, `btn-success`). CÃ¡c thao tÃ¡c xÃ³a hÃ ng loáº¡t trong Admin dÃ¹ng `handleDestroy()` vá»›i SweetAlert xÃ¡c nháº­n; thay Ä‘á»•i tráº¡ng thÃ¡i hÃ ng loáº¡t dÃ¹ng `handleChangeStatus()` vÃ  cÃ³ ná»™i dung cáº£nh bÃ¡o.

Tuy nhiÃªn, nhiá»u nÃºt icon-only khÃ´ng cÃ³ `aria-label` hoáº·c tooltip: nÃºt toggle sidebar/header, reset search, sá»­a/xÃ³a trong báº£ng sáº£n pháº©m/khÃ¡ch hÃ ng/kho, icon thÃªm khÃ¡ch hÃ ng trong POS. Má»™t sá»‘ nÃºt quan trá»ng chÆ°a disable trong lÃºc request, vÃ­ dá»¥ `#pay-button` á»Ÿ POS gá»­i Ajax táº¡o Ä‘Æ¡n nhÆ°ng khÃ´ng khÃ³a nÃºt hoáº·c hiá»ƒn thá»‹ spinner trong khi xá»­ lÃ½; `handleSubmit()` dÃ¹ng overlay nhÆ°ng khÃ´ng disable submit button. NÃºt "XÃ³a giá»" á»Ÿ POS xÃ³a ngay giá» hÃ ng mÃ  chÆ°a tháº¥y xÃ¡c nháº­n hoáº·c undo.

### 9.2.5. Báº£ng dá»¯ liá»‡u

Báº£ng dá»¯ liá»‡u cÃ³ tiÃªu Ä‘á» cá»™t rÃµ á»Ÿ nhiá»u module, cÃ³ phÃ¢n trang Laravel hoáº·c phÃ¢n trang AJAX, cÃ³ empty row tá»‘i thiá»ƒu khi khÃ´ng cÃ³ dá»¯ liá»‡u. Má»™t sá»‘ báº£ng bÃ¡o cÃ¡o cÃ³ `table-responsive` vÃ  cá»™t sá»‘ tiá»n cÄƒn pháº£i, vÃ­ dá»¥ cÃ´ng ná»£, tá»“n kho vÃ  lá»£i nhuáº­n.

NhÃ³m báº£ng Admin AJAX nhÆ° sáº£n pháº©m, khÃ¡ch hÃ ng, kho vÃ  Ä‘Æ¡n hÃ ng render partial trá»±c tiáº¿p vÃ o `#table-wrapper` nhÆ°ng báº£n thÃ¢n partial chá»‰ lÃ  `<table>` vÃ  chÆ°a cÃ³ wrapper `table-responsive`. CÃ¡c báº£ng nÃ y cÃ³ nhiá»u cá»™t, checkbox vÃ  nÃºt hÃ nh Ä‘á»™ng, nÃªn rá»§i ro trÃ n chiá»u rá»™ng trÃªn tablet/Ä‘iá»‡n thoáº¡i. Má»™t sá»‘ empty state chá»‰ ghi "KhÃ´ng cÃ³ dá»¯ liá»‡u" hoáº·c "KhÃ´ng cÃ³ sáº£n pháº©m nÃ o", chÆ°a cÃ³ hÆ°á»›ng dáº«n hÃ nh Ä‘á»™ng tiáº¿p theo.

### 9.2.6. Responsive

Login cÃ³ media query á»Ÿ `max-width: 768px`, Staff header cÃ³ media query á»Ÿ `max-width: 768px`, dashboard dÃ¹ng grid Bootstrap `col-xl`, `col-md`, vÃ  nhiá»u trang form dÃ¹ng `col-md-*`. ÄÃ¢y lÃ  cÄƒn cá»© cho kháº£ nÄƒng co giÃ£n cÆ¡ báº£n.

Äiá»ƒm chÆ°a Ä‘á»§ lÃ  chÆ°a cÃ³ kiá»ƒm thá»­ screenshot/viewport thá»±c táº¿. Má»™t sá»‘ layout cÃ³ rá»§i ro: báº£ng partial Admin thiáº¿u `table-responsive`; POS dÃ¹ng bá»‘ cá»¥c `col-lg-9` vÃ  `col-lg-3`, cart row flex, sticky summary, modal hÃ³a Ä‘Æ¡n `modal-xl`; Staff header Ä‘áº·t `overflow-x: hidden` trÃªn body, cÃ³ submenu dá»±a hover/click; nhiá»u input search Ä‘áº·t `style="width: 300px"` hoáº·c `350px`. CÃ¡c dáº¥u hiá»‡u nÃ y chÆ°a kháº³ng Ä‘á»‹nh lá»—i trÃªn mobile, nhÆ°ng Ä‘á»§ cÄƒn cá»© Ä‘á»ƒ xáº¿p vÃ o rá»§i ro responsive cáº§n kiá»ƒm thá»­ thá»±c táº¿.

| Trang hoáº·c module | Desktop | Tablet | Äiá»‡n thoáº¡i | Váº¥n Ä‘á» chÃ­nh |
| --- | --- | --- | --- | --- |
| ÄÄƒng nháº­p | Äáº¡t theo mÃ£ nguá»“n | Chá»‰ Ä‘Ã¡nh giÃ¡ theo mÃ£ nguá»“n | Chá»‰ Ä‘Ã¡nh giÃ¡ theo mÃ£ nguá»“n | CÃ³ media query, nhÆ°ng chÆ°a chá»¥p viewport thá»±c táº¿ |
| Dashboard | Äáº¡t theo mÃ£ nguá»“n | Chá»‰ Ä‘Ã¡nh giÃ¡ theo mÃ£ nguá»“n | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Grid Bootstrap cÃ³, báº£ng/dashboard custom cáº§n kiá»ƒm tra overflow |
| Danh sÃ¡ch sáº£n pháº©m | Äáº¡t theo mÃ£ nguá»“n | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Rá»§i ro | Báº£ng nhiá»u cá»™t, partial chÆ°a cÃ³ `table-responsive` |
| ÄÆ¡n hÃ ng | Äáº¡t theo mÃ£ nguá»“n | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Rá»§i ro | Bá»™ lá»c nhiá»u input, báº£ng nhiá»u cá»™t |
| POS bÃ¡n hÃ ng | Äáº¡t theo mÃ£ nguá»“n | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Rá»§i ro cao | Bá»‘ cá»¥c bÃ¡n hÃ ng/cart/khÃ¡ch hÃ ng dÃ y, modal hÃ³a Ä‘Æ¡n lá»›n |
| Kho/kiá»ƒm kÃª | Äáº¡t theo mÃ£ nguá»“n | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n | Rá»§i ro | Báº£ng kiá»ƒm kÃª nhiá»u cá»™t vÃ  thao tÃ¡c inline |
| BÃ¡o cÃ¡o tá»“n kho/lá»£i nhuáº­n | Äáº¡t theo mÃ£ nguá»“n | Chá»‰ Ä‘Ã¡nh giÃ¡ theo mÃ£ nguá»“n | Rá»§i ro tháº¥p hÆ¡n | CÃ³ `table-responsive`, nhÆ°ng báº£ng nhiá»u cá»™t |

### 9.2.7. Loading, thÃ nh cÃ´ng, lá»—i vÃ  dá»¯ liá»‡u trá»‘ng

Há»‡ thá»‘ng Ä‘Ã£ cÃ³ nhiá»u cÆ¡ cháº¿ pháº£n há»“i: login cÃ³ overlay loading; Admin layout cÃ³ `datgin.success/error/warning`; `handleSubmit()` báº­t/táº¯t `#loadingOverlay`; dashboard/report cÃ³ loader; POS dÃ¹ng Toast/SweetAlert; SuperAdmin dÃ¹ng SweetAlert hoáº·c alert.

Háº¡n cháº¿ lÃ  pháº£n há»“i chÆ°a thá»‘ng nháº¥t. Native `alert()` cÃ²n xuáº¥t hiá»‡n trong dashboard, bÃ¡o cÃ¡o tá»“n kho, cÃ´ng ná»£, POS vÃ  SuperAdmin template. Má»™t sá»‘ Ajax list khÃ´ng cÃ³ tráº¡ng thÃ¡i loading khi fetch báº£ng; ngÆ°á»i dÃ¹ng cÃ³ thá»ƒ khÃ´ng biáº¿t há»‡ thá»‘ng Ä‘ang táº£i láº¡i. Nhiá»u submit khÃ´ng disable button, tÄƒng nguy cÆ¡ gá»­i trÃ¹ng. Empty state Ä‘Ã£ cÃ³ nhÆ°ng thÆ°á»ng ngáº¯n, chÆ°a hÆ°á»›ng dáº«n ngÆ°á»i dÃ¹ng lá»c láº¡i, thÃªm dá»¯ liá»‡u hoáº·c kiá»ƒm tra Ä‘iá»u kiá»‡n.

| Tráº¡ng thÃ¡i | CÃ¡ch hiá»ƒn thá»‹ hiá»‡n táº¡i | Má»©c Ä‘á»™ rÃµ rÃ ng | Váº¥n Ä‘á» | Äá» xuáº¥t |
| --- | --- | --- | --- | --- |
| Loading | Login overlay, report loader, `handleSubmit()` overlay | Trung bÃ¬nh | Ajax list/POS payment chÆ°a Ä‘á»“ng nháº¥t loading vÃ  disable | Chuáº©n hÃ³a spinner/disable theo component |
| ThÃ nh cÃ´ng | Toastr/datgin, SweetAlert, Toast | Trung bÃ¬nh | Nhiá»u thÆ° viá»‡n thÃ´ng bÃ¡o cÃ¹ng tá»“n táº¡i | Chá»n má»™t há»‡ thá»‘ng thÃ´ng bÃ¡o chÃ­nh |
| Lá»—i validation | Inline span á»Ÿ má»™t sá»‘ form, toast á»Ÿ POS, server flash | Trung bÃ¬nh | KhÃ´ng Ä‘á»“ng nháº¥t vá»‹ trÃ­ lá»—i; nhiá»u label khÃ´ng gáº¯n `for/id` Ä‘áº§y Ä‘á»§ | Hiá»ƒn thá»‹ lá»—i gáº§n trÆ°á»ng nháº­p vÃ  focus trÆ°á»ng lá»—i Ä‘áº§u tiÃªn |
| Lá»—i há»‡ thá»‘ng | `datgin.error`, `alert()`, console log | Tháº¥p Ä‘áº¿n trung bÃ¬nh | Native alert vÃ  console log khÃ´ng nháº¥t quÃ¡n, thiáº¿u hÆ°á»›ng dáº«n xá»­ lÃ½ | Chuáº©n hÃ³a lá»—i dá»… hiá»ƒu vÃ  cÃ³ hÃ nh Ä‘á»™ng tiáº¿p theo |

| NhÃ³m giao diá»‡n | ÄÃ¡nh giÃ¡ | CÆ¡ sá»Ÿ xÃ¡c minh | Váº¥n Ä‘á» chÃ­nh |
| --- | --- | --- | --- |
| Layout/theme | TÆ°Æ¡ng Ä‘á»‘i Ä‘á»“ng nháº¥t | Admin layout, SuperAdmin layout, Staff layout | Nhiá»u layout vÃ  Bootstrap/script khÃ¡c nhau |
| Form | ÄÃ¡p á»©ng cÆ¡ báº£n | Product/employee/config/receipt/expense/POS | Label/id, validation vÃ  loading chÆ°a Ä‘á»“ng nháº¥t |
| Báº£ng | ÄÃ¡p á»©ng cÆ¡ báº£n | Product/client/storage/order/report | Thiáº¿u responsive wrapper á»Ÿ nhiá»u partial |
| Button/modal | Cáº§n cáº£i thiá»‡n | Admin actions, POS, SuperAdmin | NÃºt icon-only thiáº¿u aria/tooltip, confirm chÆ°a Ä‘á»u |
| Feedback | Cáº§n cáº£i thiá»‡n | datgin, SweetAlert, Toast, alert | Nhiá»u há»‡ thá»‘ng thÃ´ng bÃ¡o vÃ  thiáº¿u disable submit |

## 9.3. ÄÃ¡nh giÃ¡ tráº£i nghiá»‡m ngÆ°á»i dÃ¹ng

### 9.3.1. Kháº£ nÄƒng tÃ¬m chá»©c nÄƒng

Admin sidebar Ä‘Æ°á»£c chia nhÃ³m nghiá»‡p vá»¥ tÆ°Æ¡ng Ä‘á»‘i rÃµ: Tá»•ng quan, Sáº£n pháº©m, Kho hÃ ng, KhÃ¡ch hÃ ng, BÃ¡n hÃ ng, BÃ¡o cÃ¡o, Káº¿ toÃ¡n, Cáº¥u hÃ¬nh chung. Menu active/collapse dá»±a trÃªn `request()->routeIs()` giÃºp Ä‘á»‹nh vá»‹ trang hiá»‡n táº¡i. ÄÃ¢y lÃ  Ä‘iá»ƒm tá»‘t cho vai trÃ² quáº£n trá»‹.


| Vai trÃ² | NhÃ³m chá»©c nÄƒng chÃ­nh | Sá»‘ bÆ°á»›c truy cáº­p dá»± kiáº¿n | Má»©c Ä‘á»™ dá»… tÃ¬m | Ghi chÃº |
| --- | --- | ---: | --- | --- |
| Admin | Dashboard, sáº£n pháº©m, kho, bÃ¡n hÃ ng, khÃ¡ch hÃ ng, bÃ¡o cÃ¡o, káº¿ toÃ¡n, cáº¥u hÃ¬nh | 1-2 | Dá»… | Sidebar nhÃ³m nghiá»‡p vá»¥ rÃµ, cÃ³ active state |
| Staff | POS bÃ¡n hÃ ng, kiá»ƒm kho, lá»‹ch sá»­ mua hÃ ng | 1-2 | Trung bÃ¬nh | Menu gá»n nhÆ°ng Ã­t breadcrumb/ngá»¯ cáº£nh |

### 9.3.2. Form nháº­p liá»‡u

CÃ¡c form chÃ­nh cÃ³ label, input/select/textarea vÃ  chia cá»™t rÃµ á»Ÿ nhiá»u trang. Product form tÃ¡ch pháº§n ná»™i dung chÃ­nh vÃ  sidebar "Xuáº¥t báº£n/Tráº¡ng thÃ¡i/áº¢nh Ä‘áº¡i diá»‡n"; config/employee dÃ¹ng cáº¥u trÃºc tÆ°Æ¡ng tá»±; receipt/expense cÃ³ validation JS hiá»ƒn thá»‹ span `invalid-feedback`.

Háº¡n cháº¿ lÃ  nhiá»u label cÃ³ `for` nhÆ°ng input khÃ´ng cÃ³ `id` tÆ°Æ¡ng á»©ng, vÃ­ dá»¥ product form label `for="name"` nhÆ°ng input chá»‰ cÃ³ `name="name"`; config/employee cÅ©ng láº·p láº¡i máº«u nÃ y. Storage modal cÃ³ label nhÆ°ng khÃ´ng cÃ³ `for`. Má»™t sá»‘ type input chÆ°a chuáº©n nhÆ° `type="phone"` thay vÃ¬ `type="tel"`. NÃºt submit á»Ÿ nhiá»u form chÆ°a disable khi request báº¯t Ä‘áº§u. Form POS Ä‘áº·t nhiá»u trÆ°á»ng khÃ¡ch hÃ ng, thanh toÃ¡n vÃ  giá» hÃ ng trÃªn cÃ¹ng mÃ n hÃ¬nh, thuáº­n tiá»‡n cho desktop nhÆ°ng cáº§n kiá»ƒm tra ká»¹ trÃªn mobile.

### 9.3.3. ThÃ´ng bÃ¡o validation

Validation inline Ä‘Ã£ xuáº¥t hiá»‡n á»Ÿ login, Ä‘Äƒng kÃ½, receipt/expense vÃ  profile. POS kiá»ƒm tra giá» hÃ ng, tÃªn, email, Ä‘iá»‡n thoáº¡i vÃ  focus vÃ o trÆ°á»ng lá»—i. Tuy nhiÃªn Admin AJAX form dÃ¹ng `datgin.error()` cho lá»—i chung, chÆ°a tháº¥y cÆ¡ cháº¿ map lá»—i server vá» tá»«ng trÆ°á»ng á»Ÿ `handleSubmit()`. Product/config/employee form dÃ¹ng `handleSubmit()` nhÆ°ng khÃ´ng cÃ³ vÃ¹ng lá»—i inline cáº¡nh tá»«ng input.

### 9.3.4. XÃ¡c nháº­n xÃ³a


### 9.3.5. XÃ¡c nháº­n thao tÃ¡c quan trá»ng

CÃ¡c thao tÃ¡c thay Ä‘á»•i tráº¡ng thÃ¡i hÃ ng loáº¡t trong Admin Ä‘Ã£ cÃ³ `handleChangeStatus()`. POS cÃ³ modal hÃ³a Ä‘Æ¡n trÆ°á»›c khi thanh toÃ¡n, Ä‘Ã¢y lÃ  má»™t bÆ°á»›c xÃ¡c nháº­n giÃ¡n tiáº¿p. NhÆ°ng nÃºt thanh toÃ¡n cuá»‘i cÃ¹ng chÆ°a cÃ³ disable/loading trong request; thao tÃ¡c refresh Access Token hoáº·c káº¿t ná»‘i OA trong SuperAdmin thá»±c hiá»‡n trá»±c tiáº¿p sau click vÃ  chá»‰ bÃ¡o káº¿t quáº£ sau Ä‘Ã³, chÆ°a cÃ³ bÆ°á»›c xÃ¡c nháº­n trÆ°á»›c vá»›i thao tÃ¡c cÃ³ rá»§i ro váº­n hÃ nh.

### 9.3.6. TÃ¬m kiáº¿m


### 9.3.7. Bá»™ lá»c

ÄÆ¡n hÃ ng cÃ³ bá»™ lá»c ngÃ y, tráº¡ng thÃ¡i vÃ  phÆ°Æ¡ng thá»©c thanh toÃ¡n. Dashboard vÃ  bÃ¡o cÃ¡o cÃ³ date range/period/storage filter. CÃ´ng ná»£ cÃ³ lá»c ngÃ y vÃ  tÃªn. Bá»™ lá»c Ä‘Ã¡p á»©ng nghiá»‡p vá»¥ cÆ¡ báº£n, nhÆ°ng cÃ³ nhiá»u cÃ¡ch UI khÃ¡c nhau: date range input, select, nÃºt lá»c, filter tá»± Ä‘á»™ng qua change. ChÆ°a tháº¥y quy Æ°á»›c chung cho reset filter vÃ  hiá»ƒn thá»‹ sá»‘ báº£n ghi sau lá»c.

### 9.3.8. PhÃ¢n trang

CÃ¡c danh sÃ¡ch chÃ­nh dÃ¹ng `links('vendor.pagination.custom')` hoáº·c pagination tá»± render. AJAX pagination báº¯t sá»± kiá»‡n `a.page-link` vÃ  fetch láº¡i partial. ÄÃ¢y lÃ  Ä‘iá»ƒm tá»‘t cho báº£ng dá»¯ liá»‡u vá»«a pháº£i. Háº¡n cháº¿ lÃ  tráº¡ng thÃ¡i page/search/filter khÃ´ng Ä‘á»“ng bá»™ URL á»Ÿ nhiá»u mÃ n, nÃªn nÃºt Back/Forward trÃ¬nh duyá»‡t vÃ  deep link chÆ°a thuáº­n tiá»‡n.

### 9.3.9. Sáº¯p xáº¿p

ChÆ°a tháº¥y UI sort cá»™t rÃµ rÃ ng trong cÃ¡c báº£ng chÃ­nh. Controller chá»§ yáº¿u dÃ¹ng `latest()`, `orderByDesc()` hoáº·c sáº¯p xáº¿p máº·c Ä‘á»‹nh. Vá»›i ngÆ°á»i dÃ¹ng quáº£n trá»‹ dá»¯ liá»‡u nhiá»u, thiáº¿u sort theo ngÃ y, tÃªn, tá»“n kho, tá»•ng tiá»n hoáº·c tráº¡ng thÃ¡i cÃ³ thá»ƒ lÃ m tÄƒng thá»i gian tÃ¬m dá»¯ liá»‡u.

### 9.3.10. NgÄƒn thao tÃ¡c trÃ¹ng láº·p

ÄÃ¢y lÃ  Ä‘iá»ƒm cáº§n Æ°u tiÃªn. Login cÃ³ overlay nhÆ°ng khÃ´ng disable button. `handleSubmit()` hiá»ƒn thá»‹ overlay nhÆ°ng khÃ´ng khÃ³a submit button; POS `#pay-button` gá»­i Ajax táº¡o Ä‘Æ¡n nhÆ°ng khÃ´ng khÃ³a nÃºt trong lÃºc request; receipt/expense submit form trá»±c tiáº¿p sau validate JS mÃ  chÆ°a cÃ³ tráº¡ng thÃ¡i loading. NgÆ°á»i dÃ¹ng cÃ³ thá»ƒ báº¥m nhiá»u láº§n náº¿u máº¡ng cháº­m.

### 9.3.11. Empty state vÃ  hÆ°á»›ng dáº«n


| NhÃ³m tráº£i nghiá»‡m | Má»©c Ä‘á»™ hiá»‡n táº¡i | Báº±ng chá»©ng | Váº¥n Ä‘á» chÃ­nh | Æ¯u tiÃªn |
| --- | --- | --- | --- | --- |
| TÃ¬m chá»©c nÄƒng | KhÃ¡ á»Ÿ Admin, trung bÃ¬nh á»Ÿ Staff/SuperAdmin | Sidebar Admin, Staff header, SuperAdmin sidebar | SuperAdmin active/link chÆ°a rÃµ; Staff Ã­t ngá»¯ cáº£nh | Trung bÃ¬nh |
| Form nháº­p liá»‡u | ÄÃ¡p á»©ng cÆ¡ báº£n | Product/config/employee/POS/receipt/expense | Label/id, lá»—i inline vÃ  loading chÆ°a Ä‘á»“ng nháº¥t | Cao |
| Validation | Má»™t pháº§n | Inline span, Toast, datgin | ChÆ°a map lá»—i server vá» tá»«ng trÆ°á»ng á»Ÿ nhiá»u form AJAX | Trung bÃ¬nh |
| XÃ¡c nháº­n xÃ³a | Má»™t pháº§n tá»‘t | `handleDestroy()`, `confirm()` | KhÃ´ng thá»‘ng nháº¥t, má»™t sá»‘ thao tÃ¡c thiáº¿u confirm/undo | Cao |
| TÃ¬m kiáº¿m/lá»c | ÄÃ¡p á»©ng cÆ¡ báº£n | AJAX search/filter nhiá»u module | ChÆ°a sync URL, reset/filter style chÆ°a thá»‘ng nháº¥t | Trung bÃ¬nh |
| PhÃ¢n trang | ÄÃ¡p á»©ng cÆ¡ báº£n | `links('vendor.pagination.custom')` | AJAX page khÃ´ng deep-link tá»‘t | Trung bÃ¬nh |
| Sáº¯p xáº¿p | Háº¡n cháº¿ | ChÆ°a tháº¥y UI sort cá»™t | KhÃ³ xá»­ lÃ½ dá»¯ liá»‡u lá»›n | Trung bÃ¬nh |
| Chá»‘ng thao tÃ¡c trÃ¹ng | Háº¡n cháº¿ | POS pay, `handleSubmit()`, form thu chi | Thiáº¿u disable/loading á»Ÿ thao tÃ¡c chÃ­nh | Cao |

## 9.4. Kháº£ nÄƒng tiáº¿p cáº­n

### 9.4.1. Kháº£ nÄƒng Ä‘á»c

Font vÃ  cá»¡ chá»¯ pháº§n lá»›n dá»±a trÃªn Bootstrap/Kaiadmin, dá»… Ä‘á»c á»Ÿ desktop. Báº£ng sá»‘ tiá»n á»Ÿ nhiá»u nÆ¡i cÃ³ cÄƒn pháº£i. Tuy nhiÃªn chÆ°a tháº¥y kiá»ƒm tra contrast báº±ng cÃ´ng cá»¥ vÃ  chÆ°a kiá»ƒm tra trÃªn thiáº¿t bá»‹ tháº­t. Má»™t sá»‘ ná»™i dung dÃ i trong báº£ng chÆ°a cÃ³ xá»­ lÃ½ wrap/truncate rÃµ rÃ ng.

### 9.4.2. Label vÃ  input

Nhiá»u form cÃ³ label nhÃ¬n tháº¥y, nhÆ°ng khÃ´ng pháº£i táº¥t cáº£ label Ä‘Æ°á»£c liÃªn káº¿t Ä‘Ãºng vá»›i input báº±ng `for/id`. Product/config/employee cÃ³ nhiá»u input chá»‰ cÃ³ `name` mÃ  thiáº¿u `id` tÆ°Æ¡ng á»©ng; storage modal cÃ³ label khÃ´ng `for`. Äiá»u nÃ y áº£nh hÆ°á»Ÿng ngÆ°á»i dÃ¹ng dÃ¹ng screen reader vÃ  vÃ¹ng báº¥m label.

### 9.4.3. HÃ¬nh áº£nh vÃ  icon

áº¢nh logo/avatar háº§u háº¿t cÃ³ `alt`, nhÆ°ng má»™t sá»‘ áº£nh Ä‘áº¡i diá»‡n upload Ä‘á»ƒ `alt=""`. CÃ¡c nÃºt chá»‰ cÃ³ icon thiáº¿u `aria-label` hoáº·c tooltip, vÃ­ dá»¥ reset, sá»­a, xÃ³a, toggle sidebar, icon thÃªm khÃ¡ch hÃ ng POS. Icon trang trÃ­ cÅ©ng chÆ°a tháº¥y quy Æ°á»›c `aria-hidden="true"` nháº¥t quÃ¡n.

### 9.4.4. Äá»™ tÆ°Æ¡ng pháº£n

ChÆ°a Ä‘o báº±ng cÃ´ng cá»¥. MÃ u Bootstrap/Kaiadmin thÆ°á»ng Ä‘áº£m báº£o cÆ¡ báº£n, nhÆ°ng má»™t sá»‘ style inline nhÆ° text tráº¯ng trÃªn card header, mÃ u xÃ¡m nhá», gradient SuperAdmin vÃ  badge tÃ¹y biáº¿n cáº§n kiá»ƒm tra contrast thá»±c táº¿.

### 9.4.5. Äiá»u hÆ°á»›ng báº±ng bÃ n phÃ­m

ChÆ°a kiá»ƒm tra báº±ng bÃ n phÃ­m thá»±c táº¿. MÃ£ nguá»“n chÆ°a thá»ƒ hiá»‡n skip link, focus management sau validation, focus trap cho modal tÃ¹y chá»‰nh, hoáº·c phÃ­m cho icon role button á»Ÿ POS. Má»™t sá»‘ click handler gáº¯n lÃªn icon/link `href="#"` phá»¥ thuá»™c chuá»™t.

### 9.4.6. Cáº¥u trÃºc HTML

Blade dÃ¹ng nhiá»u thÃ nh pháº§n semantic cÆ¡ báº£n cá»§a Bootstrap, nhÆ°ng cÃ³ nhiá»u script/style inline vÃ  má»™t sá»‘ anchor dÃ¹ng nhÆ° button. Báº£ng cÃ³ header, nhÆ°ng checkbox trong table chÆ°a cÃ³ label/aria-label riÃªng. `html lang` cá»§a Admin/SuperAdmin Ä‘áº·t `en` dÃ¹ ná»™i dung tiáº¿ng Viá»‡t, cÃ²n login Ä‘áº·t `vi`.

### 9.4.7. ThÃ´ng bÃ¡o Ä‘á»™ng

Toast/SweetAlert/Toastr cÃ³ thá»ƒ Ä‘á»c Ä‘Æ°á»£c tÃ¹y thÆ° viá»‡n, nhÆ°ng chÆ°a tháº¥y quy Æ°á»›c `aria-live` cho thÃ´ng bÃ¡o Ä‘á»™ng tá»± render báº±ng JS, vÃ¹ng káº¿t quáº£ search popup hoáº·c table Ajax. CÃ¡c cáº­p nháº­t báº£ng qua `innerHTML`/`.html()` chÆ°a cÃ´ng bá»‘ thay Ä‘á»•i cho screen reader.

| NhÃ³m tiáº¿p cáº­n | Äiá»ƒm /5 | Báº±ng chá»©ng | Váº¥n Ä‘á» | Má»©c áº£nh hÆ°á»Ÿng |
| --- | ---: | --- | --- | --- |
| Kháº£ nÄƒng Ä‘á»c | 3.0 | Bootstrap/Kaiadmin, Public Sans, table sá»‘ tiá»n | ChÆ°a Ä‘o contrast, chÆ°a kiá»ƒm tra mobile | Trung bÃ¬nh |
| Label vÃ  input | 2.5 | Product/config/employee/storage/POS forms | Label khÃ´ng gáº¯n Ä‘Ãºng input á»Ÿ nhiá»u nÆ¡i | Trung bÃ¬nh |
| HÃ¬nh áº£nh vÃ  icon | 2.0 | Icon-only buttons, image alt | Thiáº¿u aria-label/tooltip; alt rá»—ng chÆ°a phÃ¢n biá»‡t trang trÃ­ | Trung bÃ¬nh |
| Äá»™ tÆ°Æ¡ng pháº£n | 2.5 | CSS/theme/inline style | ChÆ°a Ä‘o báº±ng cÃ´ng cá»¥ | ChÆ°a xÃ¡c minh Ä‘áº§y Ä‘á»§ |
| Äiá»u hÆ°á»›ng bÃ n phÃ­m | 2.0 | Click handlers, modal, icon role button | ChÆ°a cÃ³ báº±ng chá»©ng focus/keyboard Ä‘áº§y Ä‘á»§ | Trung bÃ¬nh |
| Cáº¥u trÃºc HTML | 2.5 | Layout Blade/table/form | `lang` chÆ°a Ä‘Ãºng, anchor/button láº«n lá»™n | Trung bÃ¬nh |
| ThÃ´ng bÃ¡o Ä‘á»™ng | 2.0 | Toast/alert/Ajax `.html()` | Thiáº¿u `aria-live`/announcement cho cáº­p nháº­t Ä‘á»™ng | Trung bÃ¬nh |

## 9.5. ÄÃ¡nh giÃ¡ cÃ¡c trang quan trá»ng

### 9.5.1. Trang Ä‘Äƒng nháº­p

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin/nhÃ¢n viÃªn Ä‘Äƒng nháº­p há»‡ thá»‘ng.

**Má»¥c Ä‘Ã­ch:** XÃ¡c thá»±c tÃ i khoáº£n vÃ  chuyá»ƒn vÃ o khu vá»±c phÃ¹ há»£p.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** bá»‘ cá»¥c hai cá»™t, form email/password, nhá»› máº­t kháº©u, loading overlay, toastr lá»—i, media query `max-width: 768px`.

**Äiá»ƒm tÃ­ch cá»±c:** CÃ³ label rÃµ, input type email/password, overlay loading, thÃ´ng bÃ¡o lá»—i Ajax vÃ  logo cÃ³ alt.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** NÃºt submit chÆ°a bá»‹ disable khi gá»­i; password toggle lÃ  `span` cÃ³ onclick, chÆ°a cÃ³ button/aria-label; placeholder tiáº¿ng Anh/Viá»‡t chÆ°a thá»‘ng nháº¥t; chÆ°a xÃ¡c minh responsive thá»±c táº¿.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** CÃ³ thá»ƒ gá»­i nhiá»u láº§n khi máº¡ng cháº­m vÃ  khÃ³ dÃ¹ng vá»›i cÃ´ng cá»¥ há»— trá»£.

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/auth/login.blade.php`, `AuthController`, `LoginRequest`.

### 9.5.2. Dashboard Admin

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin/chá»§ cá»­a hÃ ng.

**Má»¥c Ä‘Ã­ch:** Theo dÃµi doanh thu, Ä‘Æ¡n hÃ ng, lá»£i nhuáº­n, tá»“n kho vÃ  báº£ng tÃ³m táº¯t.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** card metric, báº£ng trong dashboard, date range picker, Ajax thá»‘ng kÃª ngÃ y/thÃ¡ng/nÄƒm, CSS inline.

**Äiá»ƒm tÃ­ch cá»±c:** Bá»‘ cá»¥c dashboard cÃ³ card, icon, tráº¡ng thÃ¡i tÄƒng/giáº£m kÃ¨m chá»¯, báº£ng cÃ³ `table-responsive` á»Ÿ má»™t sá»‘ khu vá»±c.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Lá»—i Ajax dÃ¹ng `alert()`; CSS dashboard náº±m inline trong Blade; responsive chá»‰ xÃ¡c minh qua grid/CSS; cÃ³ script slider tham chiáº¿u class nhÆ°ng chÆ°a tháº¥y HTML tÆ°Æ¡ng á»©ng trong Ä‘oáº¡n kiá»ƒm tra.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** Pháº£n há»“i lá»—i khÃ´ng thá»‘ng nháº¥t vÃ  dashboard cáº§n kiá»ƒm tra trÃªn mÃ n hÃ¬nh nhá».

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/welcome.blade.php`, `DashboardController`.

### 9.5.3. Danh sÃ¡ch sáº£n pháº©m

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin quáº£n lÃ½ hÃ ng hÃ³a.

**Má»¥c Ä‘Ã­ch:** TÃ¬m kiáº¿m, xem, sá»­a, xÃ³a, import/export vÃ  thay Ä‘á»•i tráº¡ng thÃ¡i sáº£n pháº©m.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** breadcrumb, card header, search AJAX, bulk action, table partial, pagination, delete/status confirmation.

**Äiá»ƒm tÃ­ch cá»±c:** CÃ³ search debounce, phÃ¢n trang AJAX, empty row, badge tráº¡ng thÃ¡i, xÃ¡c nháº­n xÃ³a/thay Ä‘á»•i tráº¡ng thÃ¡i qua SweetAlert.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Báº£ng nhiá»u cá»™t chÆ°a cÃ³ `table-responsive` táº¡i partial; nÃºt sá»­a/xÃ³a/reset chá»‰ cÃ³ icon vÃ  thiáº¿u aria-label/tooltip; import/export Ä‘ang trá» cÃ¹ng URL `/admin/company/create`, cáº§n xÃ¡c minh láº¡i á»Ÿ pháº§n chá»©c nÄƒng; khÃ´ng cÃ³ loading khi fetch báº£ng.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** KhÃ³ thao tÃ¡c trÃªn mÃ n hÃ¬nh nhá» vÃ  ngÆ°á»i dÃ¹ng há»— trá»£ khÃ³ nháº­n biáº¿t nÃºt.

**Má»©c Ä‘á»™:** Cao Ä‘á»‘i vá»›i responsive báº£ng, Trung bÃ¬nh vá»›i accessibility.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/product/index.blade.php`, `resources/views/admin/product/table.blade.php`, `ProductController`.

### 9.5.4. Form thÃªm/sá»­a sáº£n pháº©m

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin nháº­p dá»¯ liá»‡u sáº£n pháº©m.

**Má»¥c Ä‘Ã­ch:** Táº¡o hoáº·c cáº­p nháº­t thÃ´ng tin sáº£n pháº©m.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** bá»‘ cá»¥c form, giÃ¡ nháº­p/bÃ¡n, danh má»¥c, thÆ°Æ¡ng hiá»‡u, mÃ´ táº£, tráº¡ng thÃ¡i, áº£nh Ä‘áº¡i diá»‡n, submit Ajax.

**Äiá»ƒm tÃ­ch cá»±c:** Form chia nhÃ³m chÃ­nh/phá»¥; cÃ³ select danh má»¥c/thÆ°Æ¡ng hiá»‡u; Ä‘á»‹nh dáº¡ng giÃ¡ theo `Intl.NumberFormat("vi-VN")`; cÃ³ preview áº£nh.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Nhiá»u label khÃ´ng gáº¯n Ä‘Ãºng `id`; lá»—i validation server chÆ°a tháº¥y hiá»ƒn thá»‹ inline theo tá»«ng trÆ°á»ng; submit khÃ´ng disable button; áº£nh preview `alt=""`; nÃºt quay láº¡i trá» `/admin/brand` trong form sáº£n pháº©m.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** TÄƒng rá»§i ro nháº­p sai, khÃ³ hiá»ƒu khi lá»—i vÃ  khÃ³ dÃ¹ng vá»›i screen reader.

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/product/form.blade.php`, `ProductRequest`, `ProductController`.

### 9.5.5. Danh sÃ¡ch Ä‘Æ¡n hÃ ng

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin theo dÃµi Ä‘Æ¡n.

**Má»¥c Ä‘Ã­ch:** Lá»c/tÃ¬m Ä‘Æ¡n theo thá»i gian, tráº¡ng thÃ¡i, phÆ°Æ¡ng thá»©c thanh toÃ¡n vÃ  xem chi tiáº¿t.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** date range picker, select tráº¡ng thÃ¡i/phÆ°Æ¡ng thá»©c, search, AJAX pagination, table partial.

**Äiá»ƒm tÃ­ch cá»±c:** CÃ³ bá»™ lá»c nghiá»‡p vá»¥ cÆ¡ báº£n; tá»•ng tiá»n cÄƒn pháº£i; mÃ£ Ä‘Æ¡n lÃ  link rÃµ; phÃ¢n trang AJAX.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Partial table chÆ°a cÃ³ `table-responsive`; filter/search khÃ´ng sync URL; lá»—i Ajax chá»‰ `console.log`; badge tráº¡ng thÃ¡i dÃ¹ng `bg-primary` cho hoÃ n thÃ nh chÆ°a cÃ¹ng quy Æ°á»›c success á»Ÿ nhiá»u nÆ¡i.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** TrÃªn mobile/tablet cÃ³ thá»ƒ khÃ³ Ä‘á»c báº£ng; khi lá»—i táº£i báº£ng ngÆ°á»i dÃ¹ng khÃ´ng nháº­n Ä‘Æ°á»£c thÃ´ng bÃ¡o rÃµ.

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/order/index.blade.php`, `resources/views/admin/order/table.blade.php`, `OrderController`.

### 9.5.6. POS bÃ¡n hÃ ng

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Staff/thu ngÃ¢n.

**Má»¥c Ä‘Ã­ch:** TÃ¬m sáº£n pháº©m, táº¡o giá» hÃ ng, chá»n/nháº­p khÃ¡ch hÃ ng, chiáº¿t kháº¥u, xem hÃ³a Ä‘Æ¡n vÃ  thanh toÃ¡n.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** layout POS, search product/customer, cart empty state, customer form, modal hÃ³a Ä‘Æ¡n, pay Ajax, Toast, responsive CSS.

**Äiá»ƒm tÃ­ch cá»±c:** Luá»“ng bÃ¡n hÃ ng táº­p trung má»™t mÃ n hÃ¬nh; cÃ³ empty state hÆ°á»›ng dáº«n; validation client focus trÆ°á»ng thiáº¿u; modal hÃ³a Ä‘Æ¡n cho xem láº¡i trÆ°á»›c khi thanh toÃ¡n.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** `#pay-button` khÃ´ng disable/loading khi gá»­i Ä‘Æ¡n; `XÃ³a giá»` xÃ³a ngay khÃ´ng confirm/undo; icon thÃªm khÃ¡ch hÃ ng lÃ  `<i role="button">` thiáº¿u keyboard/aria; lá»—i fetch dÃ¹ng `alert()`; layout dÃ y cáº§n kiá»ƒm thá»­ mobile thá»±c táº¿.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** CÃ³ nguy cÆ¡ gá»­i trÃ¹ng Ä‘Æ¡n hoáº·c máº¥t giá» ngoÃ i Ã½ muá»‘n; mobile cÃ³ thá»ƒ khÃ³ thao tÃ¡c trong ca bÃ¡n hÃ ng.

**Má»©c Ä‘á»™:** Cao.

**Nguá»“n xÃ¡c minh:** `resources/views/Themes/pages/layout_staff/index.blade.php`, `Staff\OrderController`, `Staff\ProductController`.

### 9.5.7. Quáº£n lÃ½ kho vÃ  kiá»ƒm kÃª

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin kho vÃ  Staff kiá»ƒm kÃª.

**Má»¥c Ä‘Ã­ch:** Quáº£n lÃ½ kho, tÃ¬m kiáº¿m kho, chá»‰nh sá»­a/xÃ³a, kiá»ƒm kÃª hÃ ng hÃ³a.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** storage list/modal, table partial, Staff inventory list/add, warehome Ajax, confirm delete.

**Äiá»ƒm tÃ­ch cá»±c:** Storage modal há»— trá»£ thÃªm/sá»­a nhanh; Admin xÃ³a dÃ¹ng SweetAlert; Staff kiá»ƒm kÃª cÃ³ xÃ¡c nháº­n xÃ³a sáº£n pháº©m khá»i phiáº¿u báº±ng `confirm()`.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Storage table partial thiáº¿u `table-responsive`; label trong modal chÆ°a gáº¯n `for`; Staff inventory cÃ³ báº£ng nhiá»u cá»™t, modal width cá»‘ Ä‘á»‹nh `650px`, native alert vÃ  body `overflow-x:hidden`.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** Dá»… gáº·p trÃ n/áº©n ná»™i dung trÃªn mÃ n hÃ¬nh nhá»; thÃ´ng bÃ¡o lá»—i chÆ°a Ä‘á»“ng nháº¥t.

**Má»©c Ä‘á»™:** Trung bÃ¬nh Ä‘áº¿n Cao tÃ¹y thiáº¿t bá»‹.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/storage/index.blade.php`, `resources/views/admin/storage/table.blade.php`, `resources/views/Themes/pages/Inventory/add.blade.php`, `StorageController`, `Staff\WareHomeController`.

### 9.5.8. KhÃ¡ch hÃ ng

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin/Staff.

**Má»¥c Ä‘Ã­ch:** TÃ¬m, xuáº¥t Excel, xÃ³a khÃ¡ch hÃ ng vÃ  dÃ¹ng thÃ´ng tin khÃ¡ch hÃ ng trong POS.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** client index/table, export button, POS customer search/add modal.

**Äiá»ƒm tÃ­ch cá»±c:** CÃ³ search debounce, export Excel, empty row, bulk delete xÃ¡c nháº­n qua helper Admin; POS cÃ³ search popup vÃ  form thÃªm nhanh.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Client table partial thiáº¿u responsive wrapper; nÃºt xÃ³a chá»‰ icon; export lá»—i dÃ¹ng `alert()`; search/filter khÃ´ng sync URL.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** KhÃ³ thao tÃ¡c danh sÃ¡ch trÃªn mÃ n hÃ¬nh nhá»; lá»—i export chÆ°a thÃ¢n thiá»‡n.

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/client/index.blade.php`, `resources/views/admin/client/table.blade.php`, `ClientController`, POS view.

### 9.5.9. Thu chi vÃ  cÃ´ng ná»£

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Káº¿ toÃ¡n/Admin.

**Má»¥c Ä‘Ã­ch:** Láº­p phiáº¿u thu/chi, xem danh sÃ¡ch, lá»c cÃ´ng ná»£ khÃ¡ch hÃ ng/nhÃ  cung cáº¥p.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** form thÃªm phiáº¿u thu/chi, danh sÃ¡ch phiáº¿u, cÃ´ng ná»£ customer/supplier, date range filter.

**Äiá»ƒm tÃ­ch cá»±c:** Form thu/chi cÃ³ validation JS vÃ  error span; danh sÃ¡ch phiáº¿u cÃ³ `table-responsive`; cÃ´ng ná»£ cÃ³ báº£ng cÄƒn pháº£i sá»‘ tiá»n vÃ  empty row.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Submit thu/chi khÃ´ng cÃ³ loading/disable; label `for="content"` dÃ¹ng cho select khÃ¡ch hÃ ng/nhÃ  cung cáº¥p khÃ´ng Ä‘Ãºng id; lá»—i Ajax cÃ´ng ná»£ dÃ¹ng `alert()`; filter khÃ´ng sync URL Ä‘áº§y Ä‘á»§.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** Vá»›i nghiá»‡p vá»¥ tÃ i chÃ­nh, gá»­i trÃ¹ng hoáº·c lá»—i khÃ´ng rÃµ cÃ³ thá»ƒ gÃ¢y nháº§m láº«n váº­n hÃ nh.

**Má»©c Ä‘á»™:** Cao vá»›i chá»‘ng gá»­i trÃ¹ng, Trung bÃ¬nh vá»›i form/accessibility.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/quanlythuchi/receipt/add.blade.php`, `resources/views/admin/quanlythuchi/expense/add.blade.php`, `resources/views/admin/debt/customer.blade.php`, `resources/views/admin/debt/supplier.blade.php`.

### 9.5.10. BÃ¡o cÃ¡o

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin/chá»§ cá»­a hÃ ng/káº¿ toÃ¡n.

**Má»¥c Ä‘Ã­ch:** Xem bÃ¡o cÃ¡o xuáº¥t nháº­p tá»“n, lá»£i nhuáº­n, bÃ¡o cÃ¡o Ä‘Æ¡n/nháº­p hÃ ng.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** report filters, loader, table responsive, AJAX render, pagination custom.

**Äiá»ƒm tÃ­ch cá»±c:** BÃ¡o cÃ¡o tá»“n kho/lá»£i nhuáº­n cÃ³ loader, `table-responsive`, item count vÃ  empty row khi khÃ´ng cÃ³ sáº£n pháº©m.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Má»™t sá»‘ lá»—i Ajax váº«n dÃ¹ng `alert()` tiáº¿ng Anh; report filter/search cÃ³ nhiá»u cÆ¡ cháº¿ khÃ¡c nhau; chÆ°a cÃ³ tráº¡ng thÃ¡i loading cho má»i request/pagination; chÆ°a kiá»ƒm tra báº£ng nhiá»u cá»™t trÃªn mobile.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** Khi bÃ¡o cÃ¡o cháº­m hoáº·c lá»—i, pháº£n há»“i chÆ°a Ä‘á»§ nháº¥t quÃ¡n.

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/inventory/index.blade.php`, `resources/views/admin/profit/index.blade.php`, `ReportController`.

### 9.5.11. NgÆ°á»i dÃ¹ng vÃ  phÃ¢n quyá»n

**Äá»‘i tÆ°á»£ng sá»­ dá»¥ng:** Admin quáº£n lÃ½ chi nhÃ¡nh/nhÃ¢n viÃªn.

**Má»¥c Ä‘Ã­ch:** Táº¡o/cáº­p nháº­t tÃ i khoáº£n, tráº¡ng thÃ¡i vÃ  thÃ´ng tin liÃªn há»‡.

**Ná»™i dung Ä‘Ã£ kiá»ƒm tra:** employee/user form, tráº¡ng thÃ¡i tÃ i khoáº£n, password toggle, submit Ajax.

**Äiá»ƒm tÃ­ch cá»±c:** Form chia nhÃ³m rÃµ, cÃ³ tráº¡ng thÃ¡i active/inactive/locked, password toggle há»— trá»£ thao tÃ¡c.

**Váº¥n Ä‘á» phÃ¡t hiá»‡n:** Input Ä‘iá»‡n thoáº¡i dÃ¹ng `type="phone"`; label thiáº¿u id tÆ°Æ¡ng á»©ng á»Ÿ nhiá»u trÆ°á»ng; password toggle lÃ  icon click thiáº¿u aria-label/keyboard; submit chÆ°a disable.

**áº¢nh hÆ°á»Ÿng Ä‘áº¿n ngÆ°á»i dÃ¹ng:** Giáº£m accessibility vÃ  tÄƒng rá»§i ro gá»­i trÃ¹ng.

**Má»©c Ä‘á»™:** Trung bÃ¬nh.

**Nguá»“n xÃ¡c minh:** `resources/views/admin/employee/form.blade.php`, `resources/views/admin/configuration/config.blade.php`, `UserController`, `EmployeeController`.








**Má»©c Ä‘á»™:** Trung bÃ¬nh Ä‘áº¿n Cao vá»›i thao tÃ¡c token/OA.


## 9.6. Danh sÃ¡ch phÃ¡t hiá»‡n UI/UX

| MÃ£ | NhÃ³m | Trang hoáº·c thÃ nh pháº§n | PhÃ¡t hiá»‡n | áº¢nh hÆ°á»Ÿng ngÆ°á»i dÃ¹ng | Má»©c Ä‘á»™ | HÆ°á»›ng cáº£i tiáº¿n |
| --- | --- | --- | --- | --- | --- | --- |
| UX-001 | Responsive | Product/client/storage/order table partial | Nhiá»u báº£ng Admin AJAX chÆ°a cÃ³ wrapper `table-responsive` dÃ¹ cÃ³ nhiá»u cá»™t | Báº£ng cÃ³ thá»ƒ trÃ n hoáº·c khÃ³ thao tÃ¡c trÃªn tablet/Ä‘iá»‡n thoáº¡i | Cao | Bá»c báº£ng trong component responsive vÃ  kiá»ƒm tra viewport |
| UX-002 | Chá»‘ng thao tÃ¡c trÃ¹ng | POS pay, form Admin Ajax, thu/chi | Nhiá»u submit/Ajax chÆ°a disable nÃºt hoáº·c hiá»ƒn thá»‹ loading táº¡i nÃºt | NgÆ°á»i dÃ¹ng cÃ³ thá»ƒ gá»­i trÃ¹ng Ä‘Æ¡n/phiáº¿u khi máº¡ng cháº­m | Cao | Chuáº©n hÃ³a loading button, disable khi request báº¯t Ä‘áº§u |
| UX-004 | Accessibility | Icon-only buttons | NÃºt reset/sá»­a/xÃ³a/toggle/sidebar/icon thÃªm khÃ¡ch hÃ ng thiáº¿u `aria-label` hoáº·c tooltip | NgÆ°á»i dÃ¹ng screen reader/keyboard khÃ³ hiá»ƒu hÃ nh Ä‘á»™ng | Trung bÃ¬nh | ThÃªm aria-label, tooltip vÃ  dÃ¹ng `<button>` Ä‘Ãºng ngá»¯ nghÄ©a |
| UX-005 | Form | Product/config/employee/storage/thu chi | Label vÃ  input chÆ°a luÃ´n liÃªn káº¿t báº±ng `for/id`; má»™t sá»‘ type chÆ°a chuáº©n | Giáº£m kháº£ nÄƒng tiáº¿p cáº­n vÃ  vÃ¹ng báº¥m label | Trung bÃ¬nh | Chuáº©n hÃ³a field component cÃ³ id/name/label/error |
| UX-006 | Feedback | Toastr/datgin/SweetAlert/Toast/native alert | Nhiá»u há»‡ thá»‘ng thÃ´ng bÃ¡o cÃ¹ng tá»“n táº¡i, cÃ²n `alert()` | Tráº£i nghiá»‡m lá»—i/thÃ nh cÃ´ng khÃ´ng nháº¥t quÃ¡n | Trung bÃ¬nh | Chá»n má»™t notification service vÃ  map message chuáº©n |
| UX-007 | Validation | Product/config/employee Ajax forms | Lá»—i server nhiá»u nÆ¡i hiá»ƒn thá»‹ tá»•ng quÃ¡t, chÆ°a gáº¯n cáº¡nh trÆ°á»ng nháº­p | NgÆ°á»i dÃ¹ng khÃ³ biáº¿t cáº§n sá»­a trÆ°á»ng nÃ o | Trung bÃ¬nh | Hiá»ƒn thá»‹ lá»—i inline, focus lá»—i Ä‘áº§u tiÃªn |
| UX-008 | Navigation | SuperAdmin sidebar/layout | Dashboard active tÄ©nh, logo/sidebar cÃ³ link vá» Admin dashboard, title `Document` | Dá»… nháº§m ngá»¯ cáº£nh giá»¯a Admin vÃ  SuperAdmin | Trung bÃ¬nh | Chuáº©n hÃ³a active route, URL vá» dashboard SuperAdmin vÃ  title |
| UX-009 | Search/filter | Product/client/storage/order/report | Search/filter Ajax khÃ´ng pháº£n Ã¡nh Ä‘áº§y Ä‘á»§ trong URL | Máº¥t tráº¡ng thÃ¡i khi refresh, Back/Forward hoáº·c chia sáº» link | Trung bÃ¬nh | Äá»“ng bá»™ `s`, page, filter vÃ o query string |
| UX-010 | Layout/theme | Admin/Staff/SuperAdmin | CSS/JS inline vÃ  Bootstrap 4/5/CDN/asset láº«n nhau á»Ÿ nhiá»u layout | TÄƒng rá»§i ro khÃ¡c biá»‡t modal/dropdown/spacing | Trung bÃ¬nh | TÃ¡ch asset dÃ¹ng chung vÃ  giá»›i háº¡n version theo layout |
| UX-011 | Empty state | Báº£ng Admin/SuperAdmin | Empty row chá»§ yáº¿u chá»‰ thÃ´ng bÃ¡o khÃ´ng cÃ³ dá»¯ liá»‡u | NgÆ°á»i dÃ¹ng chÆ°a biáº¿t nÃªn thÃªm má»›i, Ä‘á»•i bá»™ lá»c hay kiá»ƒm tra quyá»n | Tháº¥p | ThÃªm empty state cÃ³ hÆ°á»›ng dáº«n hÃ nh Ä‘á»™ng tiáº¿p theo |
| UX-012 | ThÃ´ng bÃ¡o Ä‘á»™ng | Ajax table/search popup/POS render | Cáº­p nháº­t Ä‘á»™ng qua `.html()`/`innerHTML` chÆ°a cÃ³ vÃ¹ng `aria-live` | Screen reader khÃ´ng Ä‘Æ°á»£c thÃ´ng bÃ¡o khi dá»¯ liá»‡u thay Ä‘á»•i | Tháº¥p | Bá»• sung `aria-live` cho vÃ¹ng káº¿t quáº£/notification |

Tá»•ng sá»‘ phÃ¡t hiá»‡n: 12. PhÃ¢n loáº¡i má»©c Ä‘á»™: Cao 3, Trung bÃ¬nh 7, Tháº¥p 2.

## 9.7. Báº£ng cháº¥m Ä‘iá»ƒm UI/UX

| NhÃ³m Ä‘Ã¡nh giÃ¡ | Äiá»ƒm /5 | CÆ¡ sá»Ÿ cháº¥m Ä‘iá»ƒm | Váº¥n Ä‘á» chÃ­nh |
| --- | ---: | --- | --- |
| TÃ­nh Ä‘á»“ng nháº¥t giao diá»‡n | 3.0 | Admin cÃ³ layout/component chung, nhÆ°ng Staff/SuperAdmin tÃ¡ch nhiá»u | Nhiá»u layout vÃ  CSS/JS inline |
| Bá»‘ cá»¥c vÃ  kháº£ nÄƒng Ä‘á»c | 3.0 | Bootstrap grid/card/table rÃµ á»Ÿ desktop | ChÆ°a kiá»ƒm tra mobile, báº£ng dÃ i |
| Button vÃ  thao tÃ¡c | 2.5 | CÃ³ Bootstrap button vÃ  icon | Icon-only thiáº¿u nhÃ£n, thiáº¿u disable/loading |
| Báº£ng dá»¯ liá»‡u | 2.5 | CÃ³ table, pagination, empty row | Thiáº¿u `table-responsive` á»Ÿ nhiá»u partial |
| Responsive | 2.0 | CÃ³ class Bootstrap/media query | ChÆ°a kiá»ƒm tra viewport thá»±c táº¿, nhiá»u fixed width |
| Loading vÃ  pháº£n há»“i | 2.5 | CÃ³ overlay/loader/toast á»Ÿ nhiá»u nÆ¡i | KhÃ´ng Ä‘á»“ng nháº¥t, cÃ²n `alert()`, thiáº¿u disable |
| Kháº£ nÄƒng tÃ¬m chá»©c nÄƒng | 3.0 | Sidebar Admin rÃµ nhÃ³m | SuperAdmin/Staff Ä‘iá»u hÆ°á»›ng chÆ°a Ä‘á»§ ngá»¯ cáº£nh |
| Form nháº­p liá»‡u | 2.5 | CÃ³ label vÃ  chia cá»™t | Label/id/error/loading chÆ°a Ä‘á»“ng nháº¥t |
| ThÃ´ng bÃ¡o validation | 2.5 | CÃ³ inline á»Ÿ vÃ i form vÃ  toast á»Ÿ POS | Ajax form chÆ°a map lá»—i trÆ°á»ng |
| XÃ¡c nháº­n thao tÃ¡c | 2.5 | Admin bulk cÃ³ SweetAlert | POS/SuperAdmin/Staff chÆ°a Ä‘á»“ng nháº¥t |
| TÃ¬m kiáº¿m, lá»c vÃ  phÃ¢n trang | 3.0 | CÃ³ search/filter/pagination á»Ÿ nhiá»u module | ChÆ°a sync URL vÃ  sort cá»™t háº¡n cháº¿ |
| Kháº£ nÄƒng tiáº¿p cáº­n | 2.0 | CÃ³ HTML/Bootstrap cÆ¡ báº£n | Thiáº¿u aria-label, aria-live, keyboard/focus evidence |

Äiá»ƒm UI/UX trung bÃ¬nh = 30.0 / 12 = 2.50/5. KhÃ´ng tÃ­nh cÃ¡c tiÃªu chÃ­ cáº§n thiáº¿t bá»‹ tháº­t hoáº·c screen reader thá»±c táº¿ á»Ÿ má»©c xÃ¡c minh Ä‘áº§y Ä‘á»§. TÃ¡ch theo nhÃ³m: Ä‘iá»ƒm giao diá»‡n 2.64/5, Ä‘iá»ƒm tráº£i nghiá»‡m ngÆ°á»i dÃ¹ng 2.69/5, Ä‘iá»ƒm kháº£ nÄƒng tiáº¿p cáº­n 2.21/5.

## 9.8. Nháº­n xÃ©t tá»•ng quÃ¡t


## 9.9. Kiáº¿n nghá»‹ cáº£i tiáº¿n UI/UX

| Thá»© tá»± Æ°u tiÃªn | Háº¡ng má»¥c | Hiá»‡n tráº¡ng | HÆ°á»›ng cáº£i tiáº¿n | Káº¿t quáº£ mong Ä‘á»£i |
| ---: | --- | --- | --- | --- |
| 1 | Chá»‘ng gá»­i trÃ¹ng thao tÃ¡c chÃ­nh | POS thanh toÃ¡n, form Ajax vÃ  thu/chi chÆ°a disable nÃºt Ä‘áº§y Ä‘á»§ | Táº¡o helper/component submit loading, disable button, spinner vÃ  restore khi lá»—i | Giáº£m trÃ¹ng Ä‘Æ¡n/phiáº¿u vÃ  tÄƒng niá»m tin khi xá»­ lÃ½ |
| 2 | XÃ¡c nháº­n thao tÃ¡c nguy hiá»ƒm | Admin tá»‘t hÆ¡n, Staff/POS/SuperAdmin chÆ°a Ä‘á»“ng nháº¥t | Chuáº©n hÃ³a SweetAlert/confirm modal cho xÃ³a, refresh token, káº¿t ná»‘i OA, xÃ³a giá» hoáº·c thÃªm undo | Giáº£m thao tÃ¡c ngoÃ i Ã½ muá»‘n |
| 3 | Responsive báº£ng | Product/client/storage/order partial thiáº¿u `table-responsive` | Táº¡o Blade component table responsive, kiá»ƒm thá»­ 1366/1920/768/375/320 | Báº£ng dÃ¹ng Ä‘Æ°á»£c trÃªn tablet/mobile |
| 4 | Form nghiá»‡p vá»¥ chÃ­nh | Label/id/error/loading chÆ°a Ä‘á»“ng nháº¥t | Táº¡o component field cÃ³ label, id, error, help text, autocomplete/inputmode | Nháº­p liá»‡u rÃµ hÆ¡n vÃ  dá»… tiáº¿p cáº­n |
| 5 | Validation inline | Nhiá»u lá»—i Ajax hiá»ƒn thá»‹ tá»•ng quÃ¡t | Map lá»—i server vá» tá»«ng field, focus lá»—i Ä‘áº§u tiÃªn | NgÆ°á»i dÃ¹ng sá»­a lá»—i nhanh hÆ¡n |
| 6 | Há»‡ thá»‘ng thÃ´ng bÃ¡o | Toastr/datgin/SweetAlert/Toast/alert láº«n nhau | Chá»n má»™t notification service, bá» native alert, chuáº©n hÃ³a message | Pháº£n há»“i thá»‘ng nháº¥t |
| 7 | Äiá»u hÆ°á»›ng SuperAdmin/Staff | SuperAdmin active/link chÆ°a chuáº©n, Staff Ã­t ngá»¯ cáº£nh | Chuáº©n hÃ³a sidebar active, dashboard URL, title, breadcrumb cáº§n thiáº¿t | Dá»… xÃ¡c Ä‘á»‹nh Ä‘ang á»Ÿ Ä‘Ã¢u |
| 8 | Search/filter/pagination | ChÆ°a sync URL, sort háº¡n cháº¿ | Äá»“ng bá»™ query string vÃ  thÃªm sort cá»™t quan trá»ng | Dá»… quay láº¡i/chia sáº» tráº¡ng thÃ¡i dá»¯ liá»‡u |
| 9 | Accessibility icon/button | NÃºt icon-only thiáº¿u aria/tooltip | ThÃªm `aria-label`, tooltip, `aria-hidden` cho icon trang trÃ­, dÃ¹ng button Ä‘Ãºng | DÃ¹ng tá»‘t hÆ¡n vá»›i keyboard/screen reader |
| 10 | Empty state | ThÆ°á»ng chá»‰ cÃ³ "KhÃ´ng cÃ³ dá»¯ liá»‡u" | ThÃªm hÆ°á»›ng dáº«n xÃ³a lá»c/thÃªm má»›i/liÃªn há»‡ quyá»n | NgÆ°á»i dÃ¹ng biáº¿t bÆ°á»›c tiáº¿p theo |
| 11 | CSS/layout dÃ¹ng chung | Nhiá»u CSS inline vÃ  Bootstrap láº«n version | Gom style vÃ o asset/component theo vai trÃ², giáº£m inline | Giáº£m lá»‡ch giao diá»‡n vÃ  xung Ä‘á»™t |
| 12 | Kiá»ƒm thá»­ UI thá»±c táº¿ | ChÆ°a cÃ³ screenshot/viewport/screen reader | Thá»±c hiá»‡n checklist thiáº¿t bá»‹, trÃ¬nh duyá»‡t, keyboard vÃ  contrast | Káº¿t luáº­n UI/UX cÃ³ báº±ng chá»©ng váº­n hÃ nh |

## 9.10. Ná»™i dung chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra

| STT | Ná»™i dung | Pháº§n Ä‘Ã£ xÃ¡c minh | LÃ½ do chÆ°a kiá»ƒm tra Ä‘áº§y Ä‘á»§ | Äiá»u kiá»‡n cáº§n bá»• sung |
| --: | --- | --- | --- | --- |
| 1 | Responsive thá»±c táº¿ á»Ÿ 1366x768, 1920x1080, 768x1024, 375x812, 320x568 | Blade, CSS, Bootstrap class, media query | ChÆ°a cháº¡y trÃ¬nh duyá»‡t/screenshot tá»«ng viewport | Dev server á»•n Ä‘á»‹nh, tÃ i khoáº£n test, cÃ´ng cá»¥ chá»¥p viewport |
| 2 | ÄÄƒng nháº­p vÃ  phÃ¢n quyá»n theo tá»«ng vai trÃ² | Route/middleware/menu/view theo Admin/Staff/SuperAdmin | ChÆ°a cÃ³ tÃ i khoáº£n kiá»ƒm thá»­ tá»«ng vai trÃ² | Bá»™ tÃ i khoáº£n Admin, Staff, SuperAdmin vÃ  dá»¯ liá»‡u máº«u |
| 3 | Tráº£i nghiá»‡m thao tÃ¡c end-to-end | POS/form/báº£ng/Ajax qua mÃ£ nguá»“n | ChÆ°a thao tÃ¡c tháº­t Ä‘á»ƒ trÃ¡nh thay Ä‘á»•i dá»¯ liá»‡u | MÃ´i trÆ°á»ng staging, dá»¯ liá»‡u test, ká»‹ch báº£n khÃ´ng áº£nh hÆ°á»Ÿng production |
| 4 | Thiáº¿t bá»‹ tháº­t vÃ  mÃ n hÃ¬nh cáº£m á»©ng | CSS/responsive class | ChÆ°a kiá»ƒm tra trÃªn Ä‘iá»‡n thoáº¡i/tablet tháº­t | Thiáº¿t bá»‹ má»¥c tiÃªu hoáº·c cloud device |
| 5 | TrÃ¬nh duyá»‡t khÃ¡c nhau | MÃ£ nguá»“n HTML/CSS/JS | ChÆ°a kiá»ƒm tra Chrome/Edge/Firefox/Safari | Ma tráº­n trÃ¬nh duyá»‡t cáº§n há»— trá»£ |
| 6 | Äá»™ tÆ°Æ¡ng pháº£n mÃ u | CSS/theme/inline style | ChÆ°a Ä‘o báº±ng cÃ´ng cá»¥ contrast | CÃ´ng cá»¥ audit accessibility vÃ  chuáº©n WCAG má»¥c tiÃªu |
| 7 | Äiá»u hÆ°á»›ng báº±ng bÃ n phÃ­m | HTML/JS handler | ChÆ°a tab qua tá»«ng form/menu/modal | Checklist keyboard vÃ  trÃ¬nh duyá»‡t |
| 8 | Screen reader vÃ  thÃ´ng bÃ¡o Ä‘á»™ng | Toast/Ajax render/HTML | ChÆ°a kiá»ƒm tra báº±ng NVDA/VoiceOver | CÃ´ng cá»¥ screen reader vÃ  ká»‹ch báº£n kiá»ƒm thá»­ |
| 9 | Dá»¯ liá»‡u lá»›n | Pagination/table/search code | ChÆ°a cÃ³ dataset Ä‘á»§ lá»›n Ä‘á»ƒ quan sÃ¡t báº£ng dÃ i, lá»c nhiá»u Ä‘iá»u kiá»‡n | Dá»¯ liá»‡u máº«u lá»›n vÃ  tiÃªu chÃ­ hiá»‡u nÄƒng UX |
| 10 | Pháº£n há»“i ngÆ°á»i dÃ¹ng nghiá»‡p vá»¥ | Chá»‰ Ä‘á»c mÃ£ nguá»“n | ChÆ°a quan sÃ¡t ngÆ°á»i dÃ¹ng váº­n hÃ nh tháº­t | Buá»•i usability test vá»›i Admin/Staff/káº¿ toÃ¡n/SuperAdmin |

## Nguá»“n xÃ¡c minh

| Ná»™i dung Ä‘Ã¡nh giÃ¡ | File, thÆ° má»¥c hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| Layout vÃ  tÃ­nh Ä‘á»“ng nháº¥t | `resources/views/admin/layout/*`, `resources/views/Themes/layout_staff/*`, `resources/views/superadmin/layout/*`, `resources/views/sa/layout/*` |
| Giao diá»‡n trang | `resources/views/auth/login.blade.php`, `resources/views/welcome.blade.php`, `resources/views/admin`, `resources/views/Themes/pages`, `resources/views/superadmin` |
| CSS vÃ  responsive | `resources/css/app.css`, `public/assets/css/bootstrap.min.css`, `public/assets/css/kaiadmin.css`, `public/assets/css/kaiadmin.min.css`, `public/assets/css/main.css`, inline `@push('style')` trong Blade |
| JavaScript vÃ  tÆ°Æ¡ng tÃ¡c | `resources/js`, `public/assets/js`, `resources/views/admin/layout/script.blade.php`, script inline trong dashboard, POS, report, SuperAdmin |
| Button vÃ  modal | Bootstrap/Kaiadmin assets, `handleDestroy()`, `handleChangeStatus()`, storage modal, POS customer/invoice modal, profit date modal, SuperAdmin template modal |
| Form vÃ  validation message | Product/config/employee/receipt/expense/POS forms, `app/Http/Requests`, controllers Admin/Staff/SuperAdmin |
| Loading vÃ  thÃ´ng bÃ¡o | Login overlay, `handleSubmit()`, report loader, datgin/toastr, SweetAlert/SweetAlert2, Toast, native `alert()` search results |
| Kháº£ nÄƒng tiáº¿p cáº­n | HTML label/input/icon/button/alt/aria, Web Interface Guidelines, tÃ¬m kiáº¿m `aria-label`, `role="button"`, `alt=""`, `onclick`, `aria-live` |
| Responsive thá»±c táº¿ | `[CHÆ¯A Äá»¦ ÄIá»€U KIá»†N KIá»‚M TRA RESPONSIVE THá»°C Táº¾]` ChÆ°a cÃ³ screenshot viewport hoáº·c kiá»ƒm thá»­ thiáº¿t bá»‹ tháº­t |
| CÃ¡c trang theo vai trÃ² | `routes/web.php`, `RoleMiddleware`, `CheckLogin`, `CheckLoginSuperAdmin`, sidebar/menu Admin/Staff/SuperAdmin |

# 10. KIá»‚M THá»¬ VÃ€ Äá»˜ á»”N Äá»ŠNH

## 10.1. TÃ¬nh tráº¡ng kiá»ƒm thá»­ hiá»‡n táº¡i

Pháº¡m vi kiá»ƒm tra gá»“m Ä‘á»c `tests`, `phpunit.xml`, `composer.json`, `routes`, controller/service/job/middleware liÃªn quan vÃ  cháº¡y cÃ¡c lá»‡nh an toÃ n: `php artisan test`, `php artisan test --testsuite=Unit`, `php artisan test --testsuite=Feature`, `php artisan route:list`, `php artisan queue:failed`, `composer validate`, `git status --short`. KhÃ´ng sá»­a mÃ£ nguá»“n, khÃ´ng thay Ä‘á»•i database vÃ  khÃ´ng gá»i dá»‹ch vá»¥ tháº­t.

| Háº¡ng má»¥c | TÃ¬nh tráº¡ng | Pháº¡m vi hiá»‡n cÃ³ | Nguá»“n xÃ¡c minh |
| --- | --- | --- | --- |
| Unit Test | Má»™t pháº§n | 2 test: `ExampleTest` assert `true`; `UploadedImageUrlTest` kiá»ƒm tra 3 Blade khÃ´ng dÃ¹ng máº«u asset áº£nh upload cÅ©. | `tests/Unit/*`, `php artisan test --testsuite=Unit`: 2 passed, 10 assertions |
| Feature Test | Má»™t pháº§n | 1 test kiá»ƒm tra `/` redirect tá»›i route login. | `tests/Feature/ExampleTest.php`, `php artisan test --testsuite=Feature`: 1 passed, 2 assertions |
| API Test | KhÃ´ng | ChÆ°a cÃ³ test API; `routes/api.php` chá»‰ cÃ³ `/api/user` máº·c Ä‘á»‹nh qua Sanctum. | `routes/api.php`, `tests` |
| Test Controller/Service/Job/Middleware | KhÃ´ng | ChÆ°a phÃ¡t hiá»‡n test trá»±c tiáº¿p cho controller, service, job hoáº·c middleware. | `tests`, `app/Http/Controllers`, `app/Services`, `app/Jobs`, `app/Http/Middleware` |
| Test thá»§ cÃ´ng | Má»™t pháº§n | CÃ³ cÃ¡c bÃ¡o cÃ¡o/audit trong repo, nhÆ°ng chÆ°a tháº¥y checklist QA cÃ³ bÆ°á»›c thao tÃ¡c, dá»¯ liá»‡u vÃ o, káº¿t quáº£ mong Ä‘á»£i vÃ  káº¿t quáº£ thá»±c thi. | `docs/*`, cÃ¡c pháº§n 1-9 cá»§a bÃ¡o cÃ¡o |
| Database test riÃªng | Má»™t pháº§n | `APP_ENV=testing`, mail/cache/session array, queue sync; SQLite in-memory Ä‘ang bá»‹ comment. | `phpunit.xml` |
| CI/CD | KhÃ´ng | KhÃ´ng phÃ¡t hiá»‡n workflow/pipeline trong repo. | KhÃ´ng cÃ³ `.github/workflows`, `.gitlab-ci.yml`, `Jenkinsfile`, `.circleci` |
| Cháº¡y toÃ n bá»™ báº±ng `php artisan test` | CÃ³ | Cháº¡y Ä‘Æ°á»£c toÃ n bá»™ test hiá»‡n cÃ³: 3 passed, 12 assertions. | Káº¿t quáº£ `php artisan test` |

| Lá»‡nh kiá»ƒm tra | Káº¿t quáº£ | Ghi chÃº |
| --- | --- | --- |
| `php artisan test` | Pass | 3 tests passed, 12 assertions, 12.15s |
| `php artisan test --testsuite=Unit` | Pass | 2 tests passed, 10 assertions |
| `php artisan test --testsuite=Feature` | Pass | 1 test passed, 2 assertions |
| `php artisan route:list` | Pass | Hiá»ƒn thá»‹ 200 routes |
| `php artisan queue:failed` | KhÃ´ng hoÃ n táº¥t | Lá»—i MySQL `SQLSTATE[HY000] [2002]`, DB `ai_crm_2026` táº¡i `127.0.0.1:3306` khÃ´ng káº¿t ná»‘i Ä‘Æ°á»£c |
| `composer validate` | Há»£p lá»‡ cÃ³ cáº£nh bÃ¡o | Cáº£nh bÃ¡o exact version `intervention/image: 2.7` |
| `git status --short` | CÃ³ file untracked | TrÆ°á»›c cáº­p nháº­t pháº§n 10 Ä‘Ã£ cÃ³ `BAO_CAO_NGHIEN_CUU_DU_AN.md`, `bao-cao-phan-tich-laravel.md`, `docs/` untracked |

## 10.2. CÃ¡c ná»™i dung Ä‘Ã£ kiá»ƒm thá»­

| Chá»©c nÄƒng | Loáº¡i kiá»ƒm thá»­ | Káº¿t quáº£ | Báº±ng chá»©ng | Ná»™i dung chÆ°a kiá»ƒm tra |
| --- | --- | --- | --- | --- |
| ÄÄƒng nháº­p vÃ  Ä‘Äƒng xuáº¥t | Tá»± Ä‘á»™ng má»™t pháº§n, kiá»ƒm tra tÄ©nh | Má»™t pháº§n | Feature test redirect `/` vá» `auth.login`; `AuthController`, `LoginRequest` | ChÆ°a test credential Ä‘Ãºng/sai, session, logout thá»±c táº¿, rate limit |
| PhÃ¢n quyá»n theo vai trÃ² | Kiá»ƒm tra tÄ©nh | Má»™t pháº§n | Route group `auth`, `role:1`, `role:3`, `role:4`, `CheckLogin`, `CheckLoginSuperAdmin` | ChÆ°a test báº±ng nhiá»u tÃ i khoáº£n vÃ  chÆ°a kiá»ƒm tra quyá»n theo báº£n ghi |
| ThÃªm, sá»­a, xÃ³a dá»¯ liá»‡u | Kiá»ƒm tra tÄ©nh | Má»™t pháº§n | Route CRUD cho product, category, brand, storage, client, supplier, company, account, transaction | ChÆ°a cÃ³ test CRUD tá»± Ä‘á»™ng hoáº·c thao tÃ¡c UI |
| Sáº£n pháº©m, Ä‘Æ¡n hÃ ng, kho, khÃ¡ch hÃ ng, nhÃ  cung cáº¥p | Kiá»ƒm tra tÄ©nh | Má»™t pháº§n | `ProductController`, `Staff\OrderController`, `StorageController`, `ClientController`, `SupplierController` | ChÆ°a test end-to-end nháº­p hÃ ng -> bÃ¡n hÃ ng -> trá»« tá»“n -> bÃ¡o cÃ¡o -> cÃ´ng ná»£ |
| Upload file | Tá»± Ä‘á»™ng ráº¥t háº¹p, kiá»ƒm tra tÄ©nh | Má»™t pháº§n | `UploadedImageUrlTest`; CKFinder/upload/import/export trong mÃ£ nguá»“n | ChÆ°a upload file tháº­t, chÆ°a test mime/size/storage |
| TÃ¬m kiáº¿m, lá»c, phÃ¢n trang | Kiá»ƒm tra tÄ©nh | Má»™t pháº§n | Nhiá»u route `search`, `filter`, Ajax list vÃ  `paginate()` | ChÆ°a test dá»¯ liá»‡u lá»›n, tham sá»‘ biÃªn, sort, giá»¯ tráº¡ng thÃ¡i URL |
| Validation dá»¯ liá»‡u khÃ´ng há»£p lá»‡ | Kiá»ƒm tra tÄ©nh | Má»™t pháº§n | `LoginRequest`, `ProductRequest`, `CategoryRequest`, `CompanyRequest`, nhiá»u `$request->validate()` | ChÆ°a cÃ³ test validation tá»± Ä‘á»™ng cho nghiá»‡p vá»¥ chÃ­nh |
| Thanh toÃ¡n, thu chi, cÃ´ng ná»£ | Kiá»ƒm tra tÄ©nh | Má»™t pháº§n | `Staff\OrderController`, `CashTransactionController`, `BankTransactionController`, `DebtController`, `ReceiptController`, `ExpenseController` | ChÆ°a test háº¡ch toÃ¡n kÃ©p, sá»‘ dÆ°, rollback, duplicate submit |
| Queue vÃ  tÃ¡c vá»¥ ná»n | Kiá»ƒm tra tÄ©nh, lá»‡nh tráº¡ng thÃ¡i tháº¥t báº¡i | Má»™t pháº§n | Jobs implement `ShouldQueue`; `QUEUE_CONNECTION=sync`; `queue:failed` lá»—i DB | ChÆ°a xÃ¡c minh failed jobs, retry, worker/supervisor |
| Nhiá»u ngÆ°á»i dÃ¹ng Ä‘á»“ng thá»i | Kiá»ƒm tra tÄ©nh | ChÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra | CÃ³ transaction á»Ÿ má»™t sá»‘ luá»“ng, unique index á»Ÿ má»™t sá»‘ báº£ng | ChÆ°a test race condition, double submit, lock tá»“n kho/cÃ´ng ná»£ |

## 10.3. CÃ¡c váº¥n Ä‘á» vá» Ä‘á»™ á»•n Ä‘á»‹nh

á»¨ng dá»¥ng cÃ³ ná»n táº£ng xá»­ lÃ½ lá»—i cÆ¡ báº£n: `app/Exceptions/Handler.php` ghi log message/file/line; nhiá»u service/controller cÃ³ `try-catch`, `Log::error()` vÃ  transaction. Tuy nhiÃªn Ä‘á»™ á»•n Ä‘á»‹nh chÆ°a Ä‘á»“ng Ä‘á»u. Má»™t sá»‘ service cÃ³ dáº¥u hiá»‡u gá»i `DB::commit()` hoáº·c `DB::rollBack()` trong phÆ°Æ¡ng thá»©c khÃ´ng tháº¥y `DB::beginTransaction()` tÆ°Æ¡ng á»©ng, vÃ­ dá»¥ `CategoryService::createCategory()` vÃ  `ClientService::addClient()`. CÃ¡c luá»“ng nÃ y cÃ³ thá»ƒ sinh lá»—i phá»¥ khi exception xáº£y ra.


## 10.4. Danh sÃ¡ch phÃ¡t hiá»‡n `TEST-xxx`

| MÃ£ | PhÃ¡t hiá»‡n | Vá»‹ trÃ­ hoáº·c báº±ng chá»©ng | áº¢nh hÆ°á»Ÿng | Má»©c Ä‘á»™ | Äá» xuáº¥t |
| --- | --- | --- | --- | --- | --- |
| TEST-002 | ChÆ°a cÃ³ database test riÃªng Ä‘á»§ an toÃ n; SQLite in-memory Ä‘ang comment. | `phpunit.xml`; `queue:failed` lá»—i MySQL local | Test cÃ³ truy váº¥n DB sáº½ phá»¥ thuá»™c mÃ´i trÆ°á»ng vÃ  khÃ³ cháº¡y CI á»•n Ä‘á»‹nh. | Cao | Táº¡o `.env.testing`, báº­t SQLite in-memory hoáº·c DB test riÃªng, seed fixture tá»‘i thiá»ƒu. |
| TEST-003 | Má»™t sá»‘ service cÃ³ transaction khÃ´ng nháº¥t quÃ¡n. | `app/Services/CategoryService.php`, `app/Services/ClientService.php`, `ClientGroupService`, `CompanyService`, `StoreService` | Rollback cÃ³ thá»ƒ khÃ´ng Ä‘Ãºng ká»³ vá»ng hoáº·c che lá»—i gá»‘c. | Trung bÃ¬nh | Chuáº©n hÃ³a báº±ng `DB::transaction()` vÃ  test nhÃ¡nh exception. |
| TEST-004 | Luá»“ng táº¡o Ä‘Æ¡n/trá»« tá»“n chÆ°a Ä‘Æ°á»£c test Ä‘á»“ng thá»i vÃ  chÆ°a tháº¥y khÃ³a tá»“n kho. | `app/Http/Controllers/Staff/OrderController.php`, trá»« `products.quantity` | CÃ³ nguy cÆ¡ bÃ¡n vÆ°á»£t tá»“n, lá»‡ch tá»“n theo kho, sai bÃ¡o cÃ¡o/cÃ´ng ná»£. | Cao | TÃ¡ch service táº¡o Ä‘Æ¡n, lock tá»“n kho theo kho, thÃªm test double submit/race condition. |
| TEST-006 | ChÆ°a cÃ³ CI/CD tá»± Ä‘á»™ng cháº¡y test. | KhÃ´ng phÃ¡t hiá»‡n pipeline trong repo | Cháº¥t lÆ°á»£ng phá»¥ thuá»™c vÃ o viá»‡c cháº¡y test thá»§ cÃ´ng. | Trung bÃ¬nh | ThÃªm CI cháº¡y `composer validate`, `php artisan test`, Pint/static analysis náº¿u Ã¡p dá»¥ng. |

## 10.5. Báº£ng cháº¥m Ä‘iá»ƒm

| TiÃªu chÃ­ | Äiá»ƒm /5 | Nháº­n xÃ©t |
| --- | ---: | --- |
| Unit Test | 1.0 | CÃ³ 2 Unit test, nhÆ°ng 1 test máº«u vÃ  1 test kiá»ƒm tra chuá»—i Blade. |
| Feature Test | 1.0 | CÃ³ 1 Feature test redirect login, chÆ°a test nghiá»‡p vá»¥. |
| API Test | 0.0 | ChÆ°a phÃ¡t hiá»‡n API test. |
| Xá»­ lÃ½ exception | 2.5 | CÃ³ Handler vÃ  nhiá»u `try-catch`, nhÆ°ng chÆ°a Ä‘á»“ng nháº¥t. |
| Transaction vÃ  rollback | 2.5 | CÃ³ transaction á»Ÿ nhiá»u luá»“ng, nhÆ°ng cÃ²n dáº¥u hiá»‡u thiáº¿u `beginTransaction` vÃ  chÆ°a test rollback. |
| Logging | 2.5 | CÃ³ log lá»—i, nhÆ°ng thiáº¿u audit log nghiá»‡p vá»¥ vÃ  cÃ²n rá»§i ro log dá»¯ liá»‡u nháº¡y cáº£m/API response. |
| Queue vÃ  xá»­ lÃ½ lá»—i ná»n | 1.5 | CÃ³ jobs `ShouldQueue`, nhÆ°ng thiáº¿u retry/backoff/failed handler rÃµ; `queue:failed` lá»—i DB. |
| Kháº£ nÄƒng kiá»ƒm tra há»“i quy | 1.0 | `php artisan test` cháº¡y Ä‘Æ°á»£c nhÆ°ng coverage quÃ¡ má»ng vÃ  chÆ°a cÃ³ CI. |
| Äá»™ á»•n Ä‘á»‹nh tá»•ng thá»ƒ | 2.0 | CÃ³ ná»n validation/transaction/logging, nhÆ°ng rá»§i ro dá»¯ liá»‡u vÃ  thiáº¿u test nghiá»‡p vá»¥ cÃ²n lá»›n. |

Äiá»ƒm trung bÃ¬nh sÆ¡ bá»™: 1.45/5. Äiá»ƒm nÃ y chá»‰ pháº£n Ã¡nh pháº¡m vi repository local vÃ  lá»‡nh an toÃ n Ä‘Ã£ cháº¡y, chÆ°a Ä‘áº¡i diá»‡n cho production.

## 10.6. Nháº­n xÃ©t tá»•ng quÃ¡t


## 10.7. Kiáº¿n nghá»‹ cáº£i tiáº¿n

| Æ¯u tiÃªn | Háº¡ng má»¥c | HÆ°á»›ng thá»±c hiá»‡n | Káº¿t quáº£ mong Ä‘á»£i |
| ---: | --- | --- | --- |
| 1 | Test Ä‘Äƒng nháº­p vÃ  phÃ¢n quyá»n | Viáº¿t Feature test cho login Ä‘Ãºng/sai, logout, middleware `auth`, `role`, `CheckLogin`, `CheckLoginSuperAdmin`; táº¡o user factory theo role. | NgÄƒn lá»—i truy cáº­p sai quyá»n vÃ  xÃ¡c minh route chÃ­nh theo vai trÃ². |
| 2 | Test Ä‘Æ¡n hÃ ng, kho vÃ  cÃ´ng ná»£ | Táº¡o fixture sáº£n pháº©m/kho/khÃ¡ch/tÃ i khoáº£n; test POS táº¡o Ä‘Æ¡n cash/bank/debt, trá»« tá»“n, táº¡o bÃºt toÃ¡n vÃ  cÃ´ng ná»£. | PhÃ¡t hiá»‡n sá»›m lá»‡ch tá»“n kho, sai tá»•ng tiá»n, sai cÃ´ng ná»£ hoáº·c thiáº¿u account. |
| 3 | Bá»• sung transaction vÃ  rollback | Chuáº©n hÃ³a cÃ¡c service nhiá»u bÆ°á»›c báº±ng `DB::transaction`; thÃªm test giáº£ láº­p exception giá»¯a luá»“ng. | Äáº£m báº£o lá»—i má»™t bÆ°á»›c khÃ´ng Ä‘á»ƒ láº¡i dá»¯ liá»‡u ná»­a chá»«ng. |
| 4 | Chuáº©n hÃ³a exception vÃ  logging | DÃ¹ng exception domain, log cÃ³ context nghiá»‡p vá»¥, trÃ¡nh log token/API response nháº¡y cáº£m; thÃªm audit log cho tiá»n-kho-cÃ´ng ná»£. | Dá»… Ä‘iá»u tra lá»—i mÃ  khÃ´ng lá»™ dá»¯ liá»‡u nháº¡y cáº£m. |
| 5 | Thiáº¿t láº­p CI cháº¡y test | ThÃªm pipeline cháº¡y `composer validate`, `php artisan test`, cÃ³ thá»ƒ thÃªm Pint/static analysis; cáº¥u hÃ¬nh `.env.testing`. | Má»—i thay Ä‘á»•i Ä‘á»u cÃ³ kiá»ƒm tra há»“i quy tá»± Ä‘á»™ng. |

## 10.8. Ná»™i dung chÆ°a Ä‘á»§ Ä‘iá»u kiá»‡n kiá»ƒm tra

| STT | Ná»™i dung | Pháº§n Ä‘Ã£ xÃ¡c minh | LÃ½ do chÆ°a kiá»ƒm tra Ä‘áº§y Ä‘á»§ | Äiá»u kiá»‡n cáº§n bá»• sung |
| --: | --- | --- | --- | --- |
| 1 | ÄÄƒng nháº­p thá»±c táº¿ báº±ng nhiá»u vai trÃ² | Route, controller, request, middleware | ChÆ°a cÃ³ bá»™ tÃ i khoáº£n kiá»ƒm thá»­ vÃ  dá»¯ liá»‡u phÃ¢n quyá»n Ä‘áº¡i diá»‡n | TÃ i khoáº£n Admin, Staff, káº¿ toÃ¡n/kho, SuperAdmin; ma tráº­n quyá»n |
| 2 | Luá»“ng end-to-end nháº­p hÃ ng -> bÃ¡n hÃ ng -> tá»“n kho -> cÃ´ng ná»£ | Controller/service/model/migration liÃªn quan | KhÃ´ng thao tÃ¡c dá»¯ liá»‡u tháº­t, khÃ´ng cháº¡y migration hoáº·c seed má»›i | Staging DB, dá»¯ liá»‡u máº«u, ká»‹ch báº£n QA vÃ  tiÃªu chÃ­ Ä‘á»‘i soÃ¡t |
| 3 | Queue worker vÃ  failed jobs | Job classes, `phpunit.xml`, lá»‡nh `queue:failed` | MySQL local tá»« chá»‘i káº¿t ná»‘i; chÆ°a xÃ¡c minh worker/supervisor/báº£ng failed jobs | DB test/staging, cáº¥u hÃ¬nh queue, worker, báº£ng failed jobs |
| 5 | Upload file thá»±c táº¿ | Test chuá»—i Blade, controller/view upload/import, CKFinder | ChÆ°a upload file qua UI/API, chÆ°a kiá»ƒm tra storage permission/dung lÆ°á»£ng/mime | Storage test, file máº«u há»£p lá»‡/khÃ´ng há»£p lá»‡, giá»›i háº¡n dung lÆ°á»£ng |
| 6 | Kiá»ƒm thá»­ Ä‘á»“ng thá»i | Transaction á»Ÿ má»™t sá»‘ luá»“ng, unique index á»Ÿ má»™t sá»‘ báº£ng | ChÆ°a cÃ³ cÃ´ng cá»¥/ká»‹ch báº£n cháº¡y song song, chÆ°a dÃ¹ng dá»¯ liá»‡u cÃ´ láº­p | Integration test hoáº·c script táº£i nháº¹ trÃªn staging |
| 7 | Hiá»‡u nÄƒng vÃ  dá»¯ liá»‡u lá»›n | Route/filter/pagination code | ChÆ°a cÃ³ dataset lá»›n vÃ  benchmark | Dataset Ä‘áº¡i diá»‡n, tiÃªu chÃ­ thá»i gian pháº£n há»“i, profiling query |
| 8 | CI/CD thá»±c táº¿ | KhÃ´ng phÃ¡t hiá»‡n pipeline trong repo | KhÃ´ng cÃ³ cáº¥u hÃ¬nh remote/pipeline Ä‘á»ƒ cháº¡y thá»­ | Git provider/pipeline runner vÃ  secret test an toÃ n |

## Nguá»“n xÃ¡c minh

| Ná»™i dung Ä‘Ã¡nh giÃ¡ | File, thÆ° má»¥c, lá»‡nh hoáº·c nguá»“n xÃ¡c minh |
| --- | --- |
| Test hiá»‡n cÃ³ | `tests/Unit/ExampleTest.php`, `tests/Unit/UploadedImageUrlTest.php`, `tests/Feature/ExampleTest.php`, `tests/TestCase.php`, `tests/CreatesApplication.php` |
| Cáº¥u hÃ¬nh test | `phpunit.xml`, `composer.json`, `composer.lock` |
| Káº¿t quáº£ test | `php artisan test`, `php artisan test --testsuite=Unit`, `php artisan test --testsuite=Feature` |
| Route vÃ  pháº¡m vi chá»©c nÄƒng | `routes/web.php`, `routes/api.php`, káº¿t quáº£ `php artisan route:list` |
| Controller/service/job/middleware | `app/Http/Controllers`, `app/Services`, `app/Models/Services`, `app/Jobs`, `app/Http/Middleware`, `app/Exceptions/Handler.php` |
| Factory/seeder | `database/factories`, `database/seeders` |
| Queue failed | Káº¿t quáº£ `php artisan queue:failed` lá»—i káº¿t ná»‘i MySQL `127.0.0.1:3306`, DB `ai_crm_2026` |
| Composer | Káº¿t quáº£ `composer validate`: há»£p lá»‡, cáº£nh bÃ¡o exact version `intervention/image: 2.7` |
| CI/CD | KhÃ´ng phÃ¡t hiá»‡n `.github/workflows`, `.gitlab-ci.yml`, `azure-pipelines.yml`, `Jenkinsfile`, `.circleci` |
| Tráº¡ng thÃ¡i git | `git status --short` |
