Write-Host "=== START SERIE-TV ==="

docker compose up -d

Write-Host "Attendo avvio servizi..."
Start-Sleep -Seconds 5

Start-Process "http://localhost:8000"
