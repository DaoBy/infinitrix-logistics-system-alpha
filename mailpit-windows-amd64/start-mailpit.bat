@echo off
echo ======================================
echo Starting Mailpit (SMTP + Web UI)...
echo SMTP Server : 127.0.0.1:1025
echo Web UI      : http://127.0.0.1:8025
echo ======================================
echo.

REM Change this path if you placed mailpit.exe elsewhere
cd /d "C:\Tools\Mailpit"

start mailpit.exe

pause
