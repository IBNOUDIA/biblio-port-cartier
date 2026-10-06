@echo off
chcp 65001 >nul
rem ---------------------------------------------------------------
rem  Démarre le site de la bibliothèque SANS ouvrir Laragon :
rem  lance MySQL et Apache, puis ouvre le site dans le navigateur.
rem  Double-cliquez sur ce fichier après chaque redémarrage du PC.
rem ---------------------------------------------------------------
setlocal
set SITE=http://localhost/biblio-port-cartier/bibliotheque/

set MYSQL=
for /d %%m in ("C:\laragon\bin\mysql\mysql-*") do set "MYSQL=%%m"
set APACHE=
for /d %%a in ("C:\laragon\bin\apache\httpd-*") do set "APACHE=%%a"
if not exist "%MYSQL%\bin\mysqld.exe" goto introuvable
if not exist "%APACHE%\bin\httpd.exe" goto introuvable

rem --- MySQL (base de données) ---
tasklist /fi "imagename eq mysqld.exe" | find /i "mysqld.exe" >nul
if errorlevel 1 (
    echo Démarrage de MySQL...
    powershell -NoProfile -Command "Start-Process -FilePath '%MYSQL%\bin\mysqld.exe' -ArgumentList '--defaults-file=\"%MYSQL%\my.ini\"' -WindowStyle Hidden"
) else (
    echo MySQL est déjà démarré.
)

rem --- Apache (serveur web) ---
tasklist /fi "imagename eq httpd.exe" | find /i "httpd.exe" >nul
if errorlevel 1 (
    echo Démarrage d'Apache...
    powershell -NoProfile -Command "Start-Process -FilePath '%APACHE%\bin\httpd.exe' -WorkingDirectory '%APACHE%' -WindowStyle Hidden"
) else (
    echo Apache est déjà démarré.
)

rem --- Attendre que la base de données réponde (30 secondes au maximum) ---
echo Vérification...
set /a ESSAI=0
:attente
"%MYSQL%\bin\mysqladmin.exe" -u root --silent ping >nul 2>&1
if not errorlevel 1 goto pret
set /a ESSAI+=1
if %ESSAI% geq 30 goto echec
ping -n 2 127.0.0.1 >nul
goto attente

:pret
echo.
echo Le site est prêt : %SITE%
start "" "%SITE%"
ping -n 4 127.0.0.1 >nul
exit /b 0

:echec
echo.
echo ERREUR : la base de données ne répond pas. Redémarrez le PC puis réessayez.
pause
exit /b 1

:introuvable
echo ERREUR : Apache ou MySQL est introuvable dans C:\laragon\bin.
pause
exit /b 1
