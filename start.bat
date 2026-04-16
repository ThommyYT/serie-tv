@echo off
title Serie-TV Dev Environment
color 0A

set ROOT=%cd%

echo Avvio ambiente...

:: Build composer
:: start cmd /k ^
:: ""%ROOT%\tools\composer\composer"

:: PHP server (dal tuo progetto)
start cmd /k ^
""%ROOT%\tools\php\php.exe" -S localhost:8000 -t "%ROOT%""

:: Node build (TS watch)
:: start cmd /k ^
:: "cd /d "%ROOT%\js" && npm install && npm run dev"

:: FlareSolverr
:: start cmd /k ^
:: "cd /d "%ROOT%\tools\flaresolverr" && start.bat"

echo.
echo ==========================
echo SERVER AVVIATI
echo ==========================
echo PHP: http://localhost:8000
pause