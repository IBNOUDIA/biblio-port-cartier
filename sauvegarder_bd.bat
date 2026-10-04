@echo off
chcp 65001 >nul
rem ---------------------------------------------------------------
rem  Sauvegarde de la base de données de la bibliothèque (à remettre).
rem  Double-cliquez sur ce fichier : MySQL doit être démarré dans Laragon.
rem  Le fichier créé se trouve dans bibliotheque\database\
rem ---------------------------------------------------------------
setlocal
set BD=bibliotheque_port_cartier
set DOSSIER=%~dp0bibliotheque\database

set DUMP=
for /d %%m in ("C:\laragon\bin\mysql\mysql-*") do set "DUMP=%%m\bin\mysqldump.exe"
if not exist "%DUMP%" (
    echo ERREUR : mysqldump est introuvable. Laragon est-il installé dans C:\laragon ?
    pause & exit /b 1
)

for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set QUAND=%%d
set FICHIER=%DOSSIER%\sauvegarde_bd_%QUAND%.sql

echo Sauvegarde de la base %BD% en cours...
"%DUMP%" -u root --default-character-set=utf8mb4 --single-transaction --routines --databases %BD% --result-file="%FICHIER%"
if errorlevel 1 (
    echo.
    echo ERREUR : la sauvegarde a échoué. Vérifiez que MySQL est démarré dans Laragon ^(bouton « Démarrer tout »^).
    pause & exit /b 1
)

echo.
echo Sauvegarde terminée :
echo   %FICHIER%
echo C'est ce fichier qu'il faut remettre avec le projet.
pause
