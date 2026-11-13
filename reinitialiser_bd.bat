@echo off
chcp 65001 >nul
rem ---------------------------------------------------------------
rem  Remet la base de données dans son état de démonstration :
rem  réimporte bibliotheque\database\bibliotheque.sql.
rem  Les dates des prêts sont recalculées à partir d'aujourd'hui
rem  (ex. : « Les Misérables » en retard de 9 jours).
rem  À faire juste avant une démonstration. MySQL doit être démarré
rem  (demarrer_site.bat). ATTENTION : les essais faits depuis sont effacés.
rem ---------------------------------------------------------------
setlocal
set SCRIPT=%~dp0bibliotheque\database\bibliotheque.sql

set MYSQL=
for /d %%m in ("C:\laragon\bin\mysql\mysql-*") do set "MYSQL=%%m"
if not exist "%MYSQL%\bin\mysql.exe" (
    echo ERREUR : MySQL est introuvable dans C:\laragon\bin\mysql.
    pause & exit /b 1
)

"%MYSQL%\bin\mysqladmin.exe" -u root --silent ping >nul 2>&1
if errorlevel 1 (
    echo ERREUR : MySQL n'est pas démarré. Lancez d'abord demarrer_site.bat.
    pause & exit /b 1
)

echo Toutes les données actuelles seront remplacées par les données de démonstration.
choice /c ON /m "Continuer (O = oui, N = non) ?"
if errorlevel 2 exit /b 0

echo Réimportation de la base de données...
"%MYSQL%\bin\mysql.exe" -u root --default-character-set=utf8mb4 < "%SCRIPT%"
if errorlevel 1 (
    echo.
    echo ERREUR : l'importation a échoué.
    pause & exit /b 1
)

echo.
echo Base de données remise à neuf : le scénario de démonstration peut commencer.
pause
