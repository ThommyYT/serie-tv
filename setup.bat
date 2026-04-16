@echo off
title Setup Serie-TV Portable
color 0A
setlocal enabledelayedexpansion

set ROOT=%cd%
set TOOLS=%ROOT%\tools

echo ==============================
echo  SETUP AMBIENTE PORTABLE
echo ==============================

mkdir "%TOOLS%" 2>nul
cd /d "%TOOLS%"

echo.
echo [1/4] PHP Portable...

if not exist php (
    mkdir php
    powershell -Command ^
    "Invoke-WebRequest https://downloads.php.net/~windows/releases/archives/php-8.5.5-nts-Win32-vs17-x64.zip -OutFile php.zip"
    powershell -Command ^
    "Expand-Archive php.zip -DestinationPath php -Force"
    del php.zip
)

call :normalize php

echo.
echo [2/4] Composer...

if not exist composer (
    mkdir composer
    powershell -Command ^
    "Invoke-WebRequest https://getcomposer.org/composer.phar -OutFile composer\composer.phar"
)

call :normalize composer

echo.
echo [3/4] Node.js (portable)...

if not exist node (
    mkdir node
    powershell -Command ^
    "Invoke-WebRequest https://nodejs.org/dist/v24.15.0/node-v24.15.0-win-x64.zip -OutFile node.zip"
    powershell -Command ^
    "Expand-Archive node.zip -DestinationPath node -Force"
    del node.zip
)

call :normalize node

echo.
echo [4/4] FlareSolverr...

if not exist flaresolverr (
    mkdir flaresolverr
    powershell -Command ^
    "Invoke-WebRequest https://github.com/FlareSolverr/FlareSolverr/releases/download/v3.4.6/flaresolverr_windows_x64.zip -OutFile fs.zip"
    powershell -Command ^
    "Expand-Archive fs.zip -DestinationPath flaresolverr -Force"
    del fs.zip
)

call :normalize flaresolverr

echo.
echo ==============================
echo INSTALLAZIONE COMPLETATA
echo ==============================
pause
exit /b


:: =====================================================
:: NORMALIZZATORE GENERICO
:: =====================================================
:normalize
set "TARGET=%TOOLS%\%~1"

if not exist "%TARGET%" exit /b

pushd "%TARGET%" >nul

for /d %%A in (*) do (

    set "HAS_EXEC=0"
    set "ROOTFILES=0"
    set "SUBDIRS=0"

    if exist "%%A\*.exe" set "HAS_EXEC=1"
    if exist "%%A\_internal" set "HAS_EXEC=1"
    if exist "%%A\php.exe" set "HAS_EXEC=1"
    if exist "%%A\node.exe" set "HAS_EXEC=1"

    for /f %%F in ('dir /b /a-d "%%A" 2^>nul ^| find /c /v ""') do set ROOTFILES=%%F
    for /f %%D in ('dir /b /ad "%%A" 2^>nul ^| find /c /v ""') do set SUBDIRS=%%D

    if "!SUBDIRS!"=="1" if "!ROOTFILES!"=="0" if "!HAS_EXEC!"=="1" (

        echo [AUTO-NORM] Flatten safe: %%A

        for /d %%D in ("%%A\*") do (
            move "%%D\*" "%%A" >nul 2>&1
            rmdir /s /q "%%D" >nul 2>&1
        )
    )
)

popd >nul
exit /b