Write-Host "=== SETUP SERIE-TV ==="

# root path (sicuro al 100%)
$root = Split-Path -Parent $PSScriptRoot

# check docker
docker --version > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker non installato"
    exit
}

# .env path assoluto
$envPath = Join-Path $root ".env"
$envExample = Join-Path $root ".env.example"

if (-not (Test-Path $envPath)) { 
    Write-Host "Creo il file .env..."
    Copy-Item $envExample $envPath -Force 
} else { 
    Write-Host ".env già presente" 
}

# build docker
Set-Location $root
docker compose build

Write-Host "Setup completato"