@echo off
title Lab Terpadu Server
cd /d "%~dp0backend"

echo [1/2] Menjalankan Laravel (port 8000)...
start "LabTerpadu-Laravel" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

timeout /t 4 /nobreak >nul

echo [2/2] Menjalankan tunnel ngrok...
start "LabTerpadu-ngrok" cmd /k ""C:\Users\ASUS\ngrok\ngrok.exe" start labterpadu"

echo.
echo Server berjalan. Cek URL di jendela ngrok (localhost:4040).