@echo off
REM Get the directory of the batch file
setlocal
cd /d "%~dp0"

:: Start Laravel backend
start "Laravel Server" cmd /k "php artisan serve"

:: Start Vite frontend (npm run dev)
start "Vite Dev Server" cmd /k "npm run dev"
endlocal
