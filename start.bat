@echo off
title Serie-TV Dev Environment
color 0A

set "ROOT=%cd%"
set "TOOLS=%ROOT%\tools"
set "NPM_BIN=%TOOLS%\node\npm.cmd"

echo Avvio ambiente in corso...

:: 1. PHP Server
:: Usiamo cmd /c per eseguire e "start" con un titolo tra virgolette per evitare bug di parsing
start "PHP Server" cmd /c ""%ROOT%\tools\php\php.exe" -S localhost:8000 -t "%ROOT%""

:: 2. Node JS (TS Watch)
:: Importante: racchiudere l'intero blocco di comandi tra virgolette dopo /k o /c
start "Node Build" cmd /k "cd /d "%ROOT%\js" && "%NPM_BIN%" run dev"

:: 3. FlareSolverr
:: Corretto l'errore delle virgolette mancanti e il concatenamento
start "FlareSolverr" cmd /k "cd /d "%ROOT%\tools\flaresolverr" && flaresolverr.exe"

echo.
echo ===========================================
echo            SERVIZI AVVIATI
echo ===========================================
echo.
echo  PHP Dashboard:  http://localhost:8000
echo  FlareSolverr:   http://localhost:8191
echo.
echo  (Premi CTRL + CLICK sui link per aprirli)
echo ===========================================
echo.
pause
