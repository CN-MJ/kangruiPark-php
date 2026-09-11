@echo off
cd /d %~dp0

REM Windows: ensure public\storage points to storage\app\public
REM Empty real folder would block image URLs (404).

if exist "public\storage" (
  dir /AL "public\storage" >nul 2>&1
  if errorlevel 1 (
    echo Removing non-link public\storage directory...
    rmdir /s /q "public\storage" 2>nul
    if exist "public\storage" (
      echo Failed to remove public\storage. Close programs using it, then retry.
      exit /b 1
    )
  ) else (
    echo public\storage already looks like a link.
    exit /b 0
  )
)

php artisan storage:link
if errorlevel 1 (
  echo artisan storage:link failed, trying junction...
  mklink /J "public\storage" "%~dp0storage\app\public"
)

echo Done. Test: http://YOUR-LAN-IP:3922/storage/uploads/...
