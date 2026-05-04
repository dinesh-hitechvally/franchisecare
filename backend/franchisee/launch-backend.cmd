@echo off
cd /d D:\laragon\www\franchisecare\backend\franchisee
"D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan serve --host=127.0.0.1 --port=8000 >> server.out.log 2>> server.err.log
