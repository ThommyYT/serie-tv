@echo off
title Setup Serie-TV Portable
color 0A
setlocal enabledelayedexpansion

set ROOT=%cd%
set TOOLS=%ROOT%\tools

:: copy /y "%TOOLS%\php\php.ini-development" "%TOOLS%\php\php.ini" >nul
:: echo extension_dir = "ext" >> "%TOOLS%\php\php.ini"
:: echo extension=curl >> "%TOOLS%\php\php.ini"
:: echo extension=mbstring >> "%TOOLS%\php\php.ini"
:: echo extension=openssl >> "%TOOLS%\php\php.ini"
:: echo extension=pdo_mysql >> "%TOOLS%\php\php.ini"
:: pause
:: exit /b

:: --- CONFIGURAZIONE CONTEGGIO ---
set STEP=0
set TOTAL_STEPS=9
:: --------------------------------

echo ==============================
echo  SETUP AMBIENTE PORTABLE
echo ==============================

mkdir "%TOOLS%" 2>nul

:: --- INSTALLAZIONE COMPONENTI ---
set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] PHP...
if not exist "%TOOLS%\php" (
    mkdir "%TOOLS%\php"
	
    powershell -Command "Invoke-WebRequest https://downloads.php.net/~windows/releases/archives/php-8.5.5-nts-Win32-vs17-x64.zip -OutFile php.zip"
    powershell -Command "Expand-Archive php.zip -DestinationPath '%TOOLS%\php' -Force"
    del php.zip
    :: Crea un php.ini base necessario per Composer
    copy /y "%TOOLS%\php\php.ini-development" "%TOOLS%\php\php.ini" >nul
    echo extension_dir = "ext" >> "%TOOLS%\php\php.ini"
    echo extension=curl >> "%TOOLS%\php\php.ini"
    echo extension=mbstring >> "%TOOLS%\php\php.ini"
    echo extension=openssl >> "%TOOLS%\php\php.ini"
    echo extension=pdo_mysql >> "%TOOLS%\php\php.ini"
)
call :normalize php

set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] Composer...
if exist "%TOOLS%\php" (
	if not exist "%TOOLS%\php\composer.phar" (
		powershell -Command "Invoke-WebRequest https://getcomposer.org/composer.phar -OutFile '%TOOLS%\php\composer.phar'"
	)
)

set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] Node.js...
if not exist "%TOOLS%\node" (
    mkdir "%TOOLS%\node"
    powershell -Command "Invoke-WebRequest https://nodejs.org/dist/v24.15.0/node-v24.15.0-win-x64.zip -OutFile node.zip"
    powershell -Command "Expand-Archive node.zip -DestinationPath '%TOOLS%\node' -Force"
    del node.zip
)
call :normalize node

set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] FlareSolverr...
if not exist "%TOOLS%\flaresolverr" (
    mkdir "%TOOLS%\flaresolverr"
    powershell -Command "Invoke-WebRequest https://github.com/FlareSolverr/FlareSolverr/releases/download/v3.4.6/flaresolverr_windows_x64.zip -OutFile fs.zip"
    powershell -Command "Expand-Archive fs.zip -DestinationPath '%TOOLS%\flaresolverr' -Force"
    del fs.zip
)
call :normalize flaresolverr

set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] phpMyAdmin...
if not exist "%TOOLS%\phpmyadmin" (
    mkdir "%TOOLS%\phpmyadmin"
    powershell -Command "Invoke-WebRequest https://files.phpmyadmin.net/phpMyAdmin/5.2.3/phpMyAdmin-5.2.3-all-languages.zip -OutFile phpmyadmin.zip"
    powershell -Command "Expand-Archive phpmyadmin.zip -DestinationPath '%TOOLS%\phpmyadmin' -Force"
    del phpmyadmin.zip
)
call :normalize phpmyadmin

set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] MySQL...
if not exist "%TOOLS%\mysql" (
    mkdir "%TOOLS%\mysql"
	powershell -Command "Invoke-WebRequest -Uri 'https://cdn.mysql.com//Downloads/MySQL-9.6/mysql-9.6.0-winx64.zip' -OutFile 'mysql.zip'"
    powershell -Command "Expand-Archive mysql.zip -DestinationPath '%TOOLS%\mysql' -Force"
	del mysql.zip
)
call :normalize mysql

:: =====================================================
:: ESECUZIONE SETUP DIPENDENZE (Sottocartelle php/js)
:: =====================================================
echo.
echo ------------------------------
echo INSTALLAZIONE DIPENDENZE PROGETTO
echo ------------------------------

:: Definiamo i percorsi locali assoluti degli eseguibili
set "PHP_BIN=%TOOLS%\php\php.exe"
set "COMPOSER_BIN=%TOOLS%\php\composer.phar"
set "NPM_BIN=%TOOLS%\node\npm.cmd"

:: 1. Setup Composer (nella cartella php)
set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] Composer Install (cartella /php)...
if exist "%ROOT%\php\composer.json" (
    pushd "%ROOT%\php"
    "%PHP_BIN%" "%COMPOSER_BIN%" install
    popd
) else (
    echo [SKIP] %ROOT%\php\composer.json non trovato.
)

:: 2. Setup NPM (nella cartella js)
set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] NPM Install (cartella /js)...
if exist "%ROOT%\js\package.json" (
    pushd "%ROOT%\js"
    :: Aggiungiamo node al path locale solo per questo processo per far funzionare npm
    set "PATH=%TOOLS%\node;%PATH%"
    call "%NPM_BIN%" install
	call "%NPM_BIN%" run build
    popd
) else (
    echo [SKIP] %ROOT%\js\package.json non trovato.
)

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
set /a "dirCount=0"
set /a "fileCount=0"
for /f %%A in ('dir /b /ad 2^>nul') do set /a "dirCount+=1" & set "SUBDIR=%%A"
for /f %%A in ('dir /b /a-d 2^>nul') do set /a "fileCount+=1"
if %dirCount% equ 1 if %fileCount% equ 0 (
    echo [AUTO-NORM] Ottimizzazione struttura per %~1...
    move "%SUBDIR%\*" . >nul 2>&1
    for /d %%D in ("%SUBDIR%\*") do move "%%D" . >nul 2>&1
    rd "%SUBDIR%" /s /q >nul 2>&1
)
popd >nul
exit /b
