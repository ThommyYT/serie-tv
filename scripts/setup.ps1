Write-Host "=== SETUP SERIE-TV ==="

docker --version > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker non installato"
    exit
}

if (!(Test-Path "..\.env")) {
    Copy-Item "..\.env.example" "..\.env"
    Write-Host ".env creato ✔"
}

docker compose build

Write-Host "Setup completato ✔"
