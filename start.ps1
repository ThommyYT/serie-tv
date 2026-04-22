$host.ui.RawUI.WindowTitle = "Serie-TV Dev Environment"
$ROOT = Get-Location
$TOOLS = Join-Path $ROOT "tools"

Write-Host "Avvio ambiente in corso..." -ForegroundColor Cyan

# 1. PHP Server (Dashboard)
Start-Process -FilePath "$TOOLS\php\php.exe" -ArgumentList "-S localhost:80", "-t `"$ROOT`""

# 2. PHP Server DEDICATO a phpMyAdmin
# Avviamo phpMyAdmin sulla porta 8080 puntando direttamente alla sua cartella
Start-Process -FilePath "$TOOLS\php\php.exe" -ArgumentList "-S localhost:81", "-t `"$TOOLS\phpmyadmin`""

# 2. MySQL Server (Protocollo Classic)
# Lo avviamo con --console per vedere eventuali errori nella sua finestra
Start-Process -FilePath "$TOOLS\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=`"$TOOLS\mysql\my.ini`"", "--console"

# 3. Node JS (TS Watch / Dev)
Start-Process -FilePath "$TOOLS\node\node.exe" -ArgumentList "`"$TOOLS\node\node_modules\npm\bin\npm-cli.js`"", "run", "dev" -WorkingDirectory "$ROOT\js"

# 4. FlareSolverr
Start-Process -FilePath "$TOOLS\flaresolverr\flaresolverr.exe" -WorkingDirectory "$TOOLS\flaresolverr"

Write-Host ""
Write-Host "===========================================" -ForegroundColor Green
Write-Host "           SERVIZI AVVIATI" -ForegroundColor Green
Write-Host "===========================================" -ForegroundColor Green
Write-Host ""
Write-Host " PHP Dashboard:  http://localhost:80"
Write-Host " phpMyAdmin:     http://localhost:81"
Write-Host " FlareSolverr:   http://localhost:8191"
Write-Host ""
Write-Host "===========================================" -ForegroundColor Green
Write-Host "Puoi chiudere questa finestra. I servizi resteranno attivi."
Pause
