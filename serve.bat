@echo off
cd /d %~dp0

set HOST=127.0.0.1
set PORT=3922

echo Starting Kangrui Park API at http://%HOST%:%PORT%
php artisan route:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan serve --host=%HOST% --port=%PORT%
