@echo off
chcp 65001 >nul
rem ---------------------------------------------------------------
rem  Prépare la remise du projet :
rem   1) sauvegarde la base de données (état réel du jour) ;
rem   2) crée sur le Bureau un fichier .zip prêt à déposer, qui contient
rem      le site (dossier bibliotheque), la sauvegarde et les fichiers .bat.
rem  MySQL doit être démarré (demarrer_site.bat).
rem ---------------------------------------------------------------
setlocal
set RACINE=%~dp0
set BD=bibliotheque_port_cartier

set MYSQL=
for /d %%m in ("C:\laragon\bin\mysql\mysql-*") do set "MYSQL=%%m"
if not exist "%MYSQL%\bin\mysqldump.exe" (
    echo ERREUR : MySQL est introuvable dans C:\laragon\bin\mysql.
    pause & exit /b 1
)

for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set QUAND=%%d
for /f "delims=" %%b in ('powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"') do set BUREAU=%%b
set NOM=sauvegarde_bd_%QUAND%.sql
set ZIP=%BUREAU%\Remise_biblio_port_cartier_%QUAND%.zip
set DEPOT=%TEMP%\remise_biblio_%QUAND%\biblio-port-cartier

rem --- 1) Sauvegarde de la base ---
echo Sauvegarde de la base de données...
"%MYSQL%\bin\mysqldump.exe" -u root --default-character-set=utf8mb4 --single-transaction --routines --databases %BD% --result-file="%RACINE%bibliotheque\database\%NOM%"
if errorlevel 1 (
    echo ERREUR : la sauvegarde a échoué. Démarrez d'abord le site avec demarrer_site.bat.
    pause & exit /b 1
)

rem --- 2) Copie de ce qu'il faut remettre (sans les anciennes sauvegardes) ---
echo Préparation du dossier de remise...
robocopy "%RACINE%bibliotheque" "%DEPOT%\bibliotheque" /E /XF sauvegarde_bd_*.sql /NFL /NDL /NJH /NJS /NP >nul
if errorlevel 8 (
    echo ERREUR : la copie des fichiers a échoué.
    pause & exit /b 1
)
copy /y "%RACINE%bibliotheque\database\%NOM%" "%DEPOT%\bibliotheque\database\" >nul
copy /y "%RACINE%demarrer_site.bat" "%DEPOT%\" >nul
copy /y "%RACINE%sauvegarder_bd.bat" "%DEPOT%\" >nul

rem --- 3) Création du fichier .zip ---
echo Création du fichier .zip...
powershell -NoProfile -Command "Compress-Archive -Path '%DEPOT%' -DestinationPath '%ZIP%' -Force"
if errorlevel 1 (
    echo ERREUR : le fichier .zip n'a pas pu être créé.
    pause & exit /b 1
)
rmdir /s /q "%TEMP%\remise_biblio_%QUAND%"

echo.
echo Remise prête :
echo   %ZIP%
echo Il contient le site, la sauvegarde de la base (%NOM%) et le README.
echo N'oubliez pas de remettre aussi l'énoncé avec la date de remise.
pause
