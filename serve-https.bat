@echo off
echo Demarrage de Laravel + HTTPS...
echo.
echo  Laravel  : http://127.0.0.1:8000
echo  HTTPS    : https://localhost
echo  HTTPS IP : https://192.168.1.246
echo.

:: Lancer php artisan serve en arriere-plan
start "Laravel" php artisan serve --host=127.0.0.1 --port=8000

:: Attendre 2 secondes que Laravel demarre
timeout /t 2 /nobreak > nul

:: Lancer Caddy (proxy HTTPS) - necessite droits admin pour port 443
"%USERPROFILE%\bin\caddy.exe" run --config Caddyfile
