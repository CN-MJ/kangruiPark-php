@echo off
cd /d %~dp0
echo Starting Kangrui Park API at http://127.0.0.1:3922
php artisan route:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan serve --host=127.0.0.1 --port=3922
