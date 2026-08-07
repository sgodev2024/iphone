
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
Cháº¡y Ná»n (Background):

Äá»ƒ Ä‘áº£m báº£o ráº±ng worker queue luÃ´n hoáº¡t Ä‘á»™ng, báº¡n cÃ³ thá»ƒ cháº¡y lá»‡nh nÃ y dÆ°á»›i dáº¡ng má»™t process ná»n. TrÃªn server Linux, báº¡n cÃ³ thá»ƒ sá»­ dá»¥ng cÃ¡c cÃ´ng cá»¥ nhÆ° screen hoáº·c tmux Ä‘á»ƒ cháº¡y lá»‡nh nÃ y trong má»™t phiÃªn ná»n.
VÃ­ dá»¥, dÃ¹ng screen:

bash
Copy code
screen -dmS laravel-worker php artisan queue:work
Sá»­ dá»¥ng Supervisor:

Äá»ƒ quáº£n lÃ½ vÃ  tá»± Ä‘á»™ng khá»Ÿi Ä‘á»™ng worker queue khi server khá»Ÿi Ä‘á»™ng hoáº·c khi worker bá»‹ dá»«ng, báº¡n nÃªn sá»­ dá»¥ng cÃ´ng cá»¥ quáº£n lÃ½ process nhÆ° supervisor.
VÃ­ dá»¥ cáº¥u hÃ¬nh supervisor:

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
Sau khi cáº¥u hÃ¬nh supervisor, báº¡n cÃ³ thá»ƒ khá»Ÿi Ä‘á»™ng nÃ³ báº±ng lá»‡nh:

bash
Copy code
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
