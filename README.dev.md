
composer require tymon/jwt-auth --ignore-platform-req=ext-ftp
&&
composer require tymon/jwt-auth

php artisan vendor:publish --tag=laravel-assets --ansi --force

composer require maatwebsite/excel

php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
composer require barryvdh/laravel-dompdf

php artisan vendor:publish --tag=laravel-pagination

22/7
composer require phpoffice/phpspreadsheet

composer require kavenegar/laravel

php artisan queue:table + migrate


//local
php artisan queue:work

//server
Chạy Nền (Background):

Để đảm bảo rằng worker queue luôn hoạt động, bạn có thể chạy lệnh này dưới dạng một process nền. Trên server Linux, bạn có thể sử dụng các công cụ như screen hoặc tmux để chạy lệnh này trong một phiên nền.
Ví dụ, dùng screen:

bash
Copy code
screen -dmS laravel-worker php artisan queue:work
Sử dụng Supervisor:

Để quản lý và tự động khởi động worker queue khi server khởi động hoặc khi worker bị dừng, bạn nên sử dụng công cụ quản lý process như supervisor.
Ví dụ cấu hình supervisor:

ini
Copy code
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-worker.log
Sau khi cấu hình supervisor, bạn có thể khởi động nó bằng lệnh:

bash
Copy code
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
