@echo off
title Setup Serie-TV Portable
:: color 0A
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
echo [%STEP%/%TOTAL_STEPS%] Choco...
if not exist "%TOOLS%\choco" (
    mkdir "%TOOLS%\choco"
    
    :: Imposta le variabili per la sessione CMD corrente
    SET "ChocolateyInstall=%TOOLS%\choco"
    SET "ChocolateyEnvironmentAnywhere=true"

    :: Lanciamo l'installazione passando la variabile anche a PowerShell
    powershell -NoProfile -ExecutionPolicy Bypass -Command "$env:ChocolateyInstall='%TOOLS%\choco'; & '%ROOT%\install.ps1'"
)


:: Impostiamo le variabili per questa sessione
set "CHOCO_EXE=%TOOLS%\choco\bin\choco.exe"
SET "ChocolateyInstall=%TOOLS%\choco"

:: --- INSTALLAZIONE PACCHETTI SINGOLI (Params forzati) ---
set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] PHP...

:: 1. PHP (Versione specifica e percorso forzato)
echo -> Installazione PHP...
"%CHOCO_EXE%" install php -y --params "'/DontAddToPath /InstallDir:%TOOLS%\php'"

:: 2. FlareSolverr (Percorso forzato e nessuna icona)
:: echo -> Installazione FlareSolverr...
:: "%CHOCO_EXE%" install flaresolverr -y --params "'/NoDesktopShortcut /NoStartMenuShortcut'"

:: 3. MySQL (Isolato e senza servizi globali se possibile)
:: Nota: usiamo installLocation per coerenza con lo script di mysql
:: echo -> Installazione MySQL...
:: "%CHOCO_EXE%" install mysql -y --params "'/installLocation:%TOOLS%\mysql /dataLocation:%TOOLS%\mysql\data /serviceName:MySQL_Portable'"

:: 4. Node.js (Versione portable vera per evitare Program Files)
:: echo -> Installazione Node.js...
:: "%CHOCO_EXE%" install nodejs-lts -y

:: --- CONFIGURAZIONE PHP (Abilitazione estensioni) ---
if exist "%TOOLS%\php" (
    echo Configurazione php.ini...
    copy /y "%TOOLS%\php\php.ini-development" "%TOOLS%\php\php.ini" >nul
    (
        echo.
        echo extension_dir = "ext"
        echo extension=curl
        echo extension=mbstring
        echo extension=openssl
        echo extension=pdo_mysql
    ) >> "%TOOLS%\php\php.ini"
)


:: if not exist "%TOOLS%\php" (
    :: mkdir "%TOOLS%\php"
	
    :: powershell -Command "Invoke-WebRequest https://downloads.php.net/~windows/releases/archives/php-8.5.5-nts-Win32-vs17-x64.zip -OutFile php.zip"
    :: powershell -Command "Expand-Archive php.zip -DestinationPath '%TOOLS%\php' -Force"
    :: del php.zip
    :: Crea un php.ini base necessario per Composer
    :: copy /y "%TOOLS%\php\php.ini-development" "%TOOLS%\php\php.ini" >nul
    :: echo extension_dir = "ext" >> "%TOOLS%\php\php.ini"
    :: echo extension=curl >> "%TOOLS%\php\php.ini"
    :: echo extension=mbstring >> "%TOOLS%\php\php.ini"
    :: echo extension=openssl >> "%TOOLS%\php\php.ini"
    :: echo extension=pdo_mysql >> "%TOOLS%\php\php.ini"
:: )
:: call :normalize php

set /a STEP+=1
echo [%STEP%/%TOTAL_STEPS%] Composer...
if not exist "%TOOLS%\composer" (
    mkdir "%TOOLS%\composer"
    powershell -Command "Invoke-WebRequest https://getcomposer.org/composer.phar -OutFile '%TOOLS%\composer\composer.phar'"
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
echo.
echo [%STEP%/%TOTAL_STEPS%] MySQL...

:: Se esiste già, saltiamo tutto il blocco
if exist "%TOOLS%\mysql" goto :skip_mysql

mkdir "%TOOLS%\mysql"
echo Download in corso (attendi)...

:: Usiamo variabili per evitare le parentesi nel comando diretto
set "URL=https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-8.4.8-winx64.zip"

:: Usiamo WebClient invece di Invoke-WebRequest per bypassare i blocchi di Oracle
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$c = New-Object System.Net.WebClient; " ^
    "$c.Headers.Add('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'); " ^
    "$c.DownloadFile('%URL%', 'mysql.zip')"

if not exist mysql.zip (
    echo [ERRORE] Impossibile scaricare MySQL.
    rd /s /q "%TOOLS%\mysql" 2>nul
    goto :skip_mysql
)

echo Estrazione in corso...
powershell -Command "Expand-Archive mysql.zip -DestinationPath '%TOOLS%\mysql' -Force"
del mysql.zip

:skip_mysql
pause
exit /b
:: call :normalize mysql


    :: :: powershell -Command "Invoke-WebRequest https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-8.4.8-winx64.zip -OutFile mysql.zip"
:: :: =====================================================
:: :: ESECUZIONE SETUP DIPENDENZE (Sottocartelle php/js)
:: :: =====================================================
:: echo.
:: echo ------------------------------
:: echo INSTALLAZIONE DIPENDENZE PROGETTO
:: echo ------------------------------

:: :: Definiamo i percorsi locali assoluti degli eseguibili
:: set "PHP_BIN=%TOOLS%\php\php.exe"
:: set "COMPOSER_BIN=%TOOLS%\composer\composer.phar"
:: set "NPM_BIN=%TOOLS%\node\npm.cmd"

:: :: 1. Setup Composer (nella cartella php)
:: set /a STEP+=1
:: echo [%STEP%/%TOTAL_STEPS%] Composer Install (cartella /php)...
:: if exist "%ROOT%\php\composer.json" (
    :: pushd "%ROOT%\php"
    :: "%PHP_BIN%" "%COMPOSER_BIN%" install
    :: popd
:: ) else (
    :: echo [SKIP] %ROOT%\php\composer.json non trovato.
:: )

:: :: 2. Setup NPM (nella cartella js)
:: set /a STEP+=1
:: echo [%STEP%/%TOTAL_STEPS%] NPM Install (cartella /js)...
:: if exist "%ROOT%\js\package.json" (
    :: pushd "%ROOT%\js"
    :: :: Aggiungiamo node al path locale solo per questo processo per far funzionare npm
    :: set "PATH=%TOOLS%\node;%PATH%"
    :: call "%NPM_BIN%" install
    :: popd
:: ) else (
    :: echo [SKIP] %ROOT%\js\package.json non trovato.
:: )

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
