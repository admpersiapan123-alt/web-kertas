@echo off
title Auto Start ngrok

echo ==========================================
echo Menunggu Nginx (Laragon) aktif...
echo ==========================================

:WAIT
powershell -Command "try { $c = New-Object Net.Sockets.TcpClient('127.0.0.1',8080); $c.Close(); exit 0 } catch { exit 1 }"

if errorlevel 1 (
    timeout /t 2 >nul
    goto WAIT
)

echo.
echo Nginx aktif!
echo Menjalankan ngrok...
echo.

cd /d "C:\NGROK"

ngrok.exe http http://web-kertas-app.test:8080 --host-header=web-kertas-app.test

pause