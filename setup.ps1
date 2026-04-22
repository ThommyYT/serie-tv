[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$ErrorActionPreference = "Stop"

$host.ui.RawUI.WindowTitle = "Setup Serie-TV Portable"

Write-Host "===================================" -ForegroundColor Blue
Write-Host "   SETUP AMBIENTE SERIE-TV" -ForegroundColor Blue
Write-Host "===================================" -ForegroundColor Blue

$ROOT = Get-Location
$TOOLS = Join-Path $ROOT "tools"
$LOCK_FILE = Join-Path $TOOLS ".setup_done"

# -------------------------------
# BLOCCO RI-ESECUZIONE
# -------------------------------
if (Test-Path $LOCK_FILE) {
    Write-Host "ERRORE: Setup gia' eseguito." -ForegroundColor Red
    Write-Host "Elimina la cartella 'tools' per rifarlo." -ForegroundColor Cyan
    pause
	exit 1
}

# -------------------------------
# FUNZIONE DOWNLOAD
# -------------------------------
function Download($url, $output) {
    try {
        Write-Host "Download: $output" -ForegroundColor Blue

        curl.exe -L $url -o $output

        if ($LASTEXITCODE -ne 0) {
            throw "Download fallito (codice $LASTEXITCODE)"
        }

        if (!(Test-Path $output) -or (Get-Item $output).Length -lt 100000) {
            throw "File non valido o incompleto"
        }

        Write-Host "Download completato" -ForegroundColor Green
    }
    catch {
        Write-Host "ERRORE download: $output" -ForegroundColor Red
        Write-Host $_
        exit 1
    }
}
# -------------------------------
# NORMALIZE (tipo tuo .bat)
# -------------------------------
function Normalize($path) {
    $dirs = Get-ChildItem $path -Directory
    if ($dirs.Count -eq 1) {
        $inner = $dirs[0].FullName
        Get-ChildItem $inner | Move-Item -Destination $path -Force
        Remove-Item $inner -Recurse -Force
    }
}

# -------------------------------
# CREA CARTELLE
# -------------------------------
New-Item -ItemType Directory -Force -Path $TOOLS | Out-Null

# -------------------------------
# PHP
# -------------------------------
$phpZip = "$TOOLS\php.zip"
Download "https://downloads.php.net/~windows/releases/archives/php-8.5.3-nts-Win32-vs17-x64.zip" $phpZip

Expand-Archive $phpZip -DestinationPath "$TOOLS\php" -Force
Remove-Item $phpZip

Normalize "$TOOLS\php"

Copy-Item "$TOOLS\php\php.ini-development" "$TOOLS\php\php.ini"

$ini = "$TOOLS\php\php.ini"

if ((Get-Content $ini) -match "^;extension_dir = `"ext`"") {
	(Get-Content $ini) |
		ForEach-Object { $_ -replace ";extension_dir = `"ext`"", "extension_dir = `"ext`"" } |
		Set-Content $ini
}

function Enable-Extension($ext) {

    $patternEnabled = "^extension=$ext"
    $patternDisabled = "^;extension=$ext"

    $content = Get-Content $ini

    # Caso 1: già attiva
    if ($content -match $patternEnabled) {
        Write-Host "Extension gia attiva: $ext"
        return
    }

    # Caso 2: commentata -> la scommenta
    if ($content -match $patternDisabled) {
        Write-Host "Abilitata extension (scommentata): $ext"
        (Get-Content $ini) |
            ForEach-Object { $_ -replace ";extension=$ext", "extension=$ext" } |
            Set-Content $ini
        return
    }

    # Caso 3: non esiste -> aggiunge
    Write-Host "Aggiunta extension: $ext"
    Add-Content $ini "extension=$ext"
}

Enable-Extension "curl"
Enable-Extension "openssl"
Enable-Extension "pdo_mysql"
Enable-Extension "zip"

# -------------------------------
# COMPOSER
# -------------------------------
Download "https://getcomposer.org/composer-stable.phar" "$TOOLS\php\composer.phar"

# -------------------------------
# NODE
# -------------------------------
$nodeZip = "$TOOLS\node.zip"
Download "https://nodejs.org/dist/v24.15.0/node-v24.15.0-win-x64.zip" $nodeZip

Expand-Archive $nodeZip -DestinationPath "$TOOLS\node" -Force
Remove-Item $nodeZip

Normalize "$TOOLS\node"

# -------------------------------
# FLARESOLVERR
# -------------------------------
$flareZip = "$TOOLS\flaresolverr.zip"
Download "https://github.com/FlareSolverr/FlareSolverr/releases/latest/download/flaresolverr_windows_x64.zip" $flareZip

Expand-Archive $flareZip -DestinationPath "$TOOLS\flaresolverr" -Force
Remove-Item $flareZip

Normalize "$TOOLS\flaresolverr"

# -------------------------------
# PHPMYADMIN
# -------------------------------
$pmaZip = "$TOOLS\phpmyadmin.zip"
Download "https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip" $pmaZip

Expand-Archive $pmaZip -DestinationPath "$TOOLS\phpmyadmin" -Force
Remove-Item $pmaZip

Normalize "$TOOLS\phpmyadmin"

# -------------------------------
# CONFIGURAZIONE PHPMYADMIN
# -------------------------------
Write-Host "Configurazione phpMyAdmin..." -ForegroundColor Cyan

$PMA_PATH = "$TOOLS\phpmyadmin"
$PMA_CONFIG = "$PMA_PATH\config.inc.php"

# Genera una chiave casuale per il blowfish_secret (richiesta da PMA)
$secret = [Guid]::NewGuid().ToString().Replace("-", "").Substring(0, 32)

$PMA_CONTENT = @"
<?php
`$i = 0;
`$i++;
`$cfg['blowfish_secret'] = '$secret';
`$cfg['Servers'][`$i]['auth_type'] = 'config';
`$cfg['Servers'][`$i]['host'] = '127.0.0.1';
`$cfg['Servers'][`$i]['port'] = '3306';
`$cfg['Servers'][`$i]['user'] = 'root';
`$cfg['Servers'][`$i]['password'] = '';
`$cfg['Servers'][`$i]['AllowNoPassword'] = true;
`$cfg['DefaultLang'] = 'it';
?>
"@

# Scrittura del file config.inc.php
[System.IO.File]::WriteAllText($PMA_CONFIG, $PMA_CONTENT)

# ABILITA ESTENSIONE MBSTRING (Necessaria per phpMyAdmin)
# Aggiungi mbstring alle estensioni abilitate nel tuo blocco PHP precedente
Enable-Extension "mbstring"
Enable-Extension "mysqli"

# -------------------------------
# MYSQL
# -------------------------------
$mysqlZip = "$TOOLS\mysql.zip"
Download "https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-8.4.9-winx64.zip" $mysqlZip

Expand-Archive $mysqlZip -DestinationPath "$TOOLS\mysql" -Force
Remove-Item $mysqlZip

Normalize "$TOOLS\mysql"

$MYSQL_PATH = "$TOOLS\mysql"

# Write-Host "Inizializzazione MySQL con config personalizzata..." -ForegroundColor Blue

$CONFIG_FILE = "$MYSQL_PATH\my.ini"

# 2. Crea il contenuto del file ini
$INI_CONTENT = @"
[mysqld]
# Impostazioni base
port=3306
basedir="$($MYSQL_PATH.Replace('\', '/'))"
datadir="$($MYSQL_PATH.Replace('\', '/'))/data"
character-set-server=utf8mb4

# Ottimizzazione
innodb_buffer_pool_size=512M
innodb_log_file_size=128M
max_connections=100

# Protocollo Classic
mysqlx=OFF
"@

# 3. Scrivi il file su disco (Encoding fondamentale per MySQL)
Write-Host "Creazione file di configurazione..." -ForegroundColor Cyan
$INI_CONTENT | Out-File -FilePath $CONFIG_FILE -Encoding ascii

# 4. Inizializzazione (Nota: --defaults-file deve essere il PRIMO parametro)
Write-Host "Inizializzazione MySQL..." -ForegroundColor Blue
& "$MYSQL_PATH\bin\mysqld.exe" --defaults-file="$CONFIG_FILE" --initialize-insecure --basedir="$MYSQL_PATH" --datadir="$MYSQL_PATH\data"

# -------------------------------
# COMPOSER INSTALL
# -------------------------------
Write-Host "Composer install..." -ForegroundColor Blue

Push-Location "$ROOT\php"

& "$TOOLS\php\php.exe" "$TOOLS\php\composer.phar" install

Pop-Location

# -------------------------------
# NPM INSTALL
# -------------------------------
Write-Host "NPM install..." -ForegroundColor Blue

Push-Location "$ROOT\js"

$npm = "$TOOLS\node\node_modules\npm\bin\npm-cli.js"

& "$TOOLS\node\node.exe" $npm install
& "$TOOLS\node\node.exe" $npm run build

Pop-Location

# -------------------------------
# LOCK FILE
# -------------------------------
New-Item -ItemType File -Path $LOCK_FILE | Out-Null

# -------------------------------
# FINE
# -------------------------------
Write-Host "" 
Write-Host "===================================" -ForegroundColor Green
Write-Host "SETUP COMPLETATO" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
Pause
Exit