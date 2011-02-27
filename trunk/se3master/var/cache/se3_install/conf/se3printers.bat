:: Ce script permet
:: - utilisé sans argument : de nettoyer les connexions aux imprimantes qui persistent avec windows xp, de se connecter aux imprimantes partagées par le SE3 depuis %computername%.
:: - utilisé avec 1 argument : de fixer comme imprimante par défaut %1

@echo off

if "%1" == "" goto connexions

echo Configuration de l'imprimante %1 par defaut
rundll32 printui.dll,PrintUIEntry /y /n "%LOGONSERVER%\%1" /q

goto fin
:connexions
echo Connexion aux imprimantes disponibles
Set PrintersDispo=%SystemDrive%\netinst\logs\printersdispo.tmp
Set PrintersConnected=%SystemDrive%\netinst\logs\printersconnected.tmp
Set PrintersRegistre=%SystemDrive%\netinst\logs\printersregistre.tmp

if exist %PrintersConnected% del /F /Q %PrintersConnected%

:: on regarde les imprimantes disponibles sur le se3,
net view %LOGONSERVER% | find "Impr." > %PrintersDispo%

:: on liste les connexions aux imprimantes pour l'utilisateur courant : on supprime celles inutiles
reg query hkcu\Printers\Connections | find "," > %PrintersRegistre%
for /F "tokens=3* delims=," %%a in (%PrintersRegistre%) do (echo %%a>>%PrintersConnected%)

:: si l'un des fichiers n'existe pas, on le crée vide car, sinon, les boucles for ne s'exécutent pas
if not exist %PrintersConnected% echo . > %PrintersConnected%
if not exist %PrintersDispo% echo . > %PrintersDispo%

echo Suppression des imprimantes en trop dans le profil
for /F "tokens=1* delims=," %%a in (%PrintersConnected%) do (
	type %PrintersDispo% | findstr /i "\<%%a\>" >NUL
	if errorlevel 1 echo Suppression de %%a && rundll32 printui.dll,PrintUIEntry /dn /n "%LOGONSERVER%\%%a" /q
) 

echo Connexion aux imprimantes du parc actuel
for /F "tokens=1 delims= " %%a in (%PrintersDispo%) do (
	type %PrintersConnected% | findstr /i "\<%%a\>" >NUL
	if errorlevel 1 echo Ajout de l'imprimante %%a && rundll32 printui.dll,PrintUIEntry /in /n "%LOGONSERVER%\%%a" /q
) 2>NUL

if exist %PrintersDispo% del /F /Q %PrintersDispo%
if exist %PrintersConnected% del /F /Q %PrintersConnected%
if exist %PrintersRegistre% del /F /Q %PrintersRegistre%

:fin
