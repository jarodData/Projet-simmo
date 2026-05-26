@echo off
title SIMMo - Demarrage des serveurs

echo ================================================
echo    SIMMo - Demarrage automatique des serveurs
echo ================================================

echo.
echo [1/2] Demarrage de Laravel...
start "Laravel API" cmd /k "cd /d %~dp0 && php artisan serve"

echo [2/2] Demarrage de l'API IA Python...
start "SIMMo IA" cmd /k "cd /d %~dp0simmo_ia && python -m uvicorn main:app --reload --port 8001"

echo.
echo Serveurs demarres !
echo Laravel : http://localhost:8000
echo API IA  : http://localhost:8001
echo Frontend: Ouvrez index.html avec http://localhost:8000/index.html
echo.
pause