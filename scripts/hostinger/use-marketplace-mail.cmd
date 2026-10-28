@echo off
rem My Golf Course: Mail-Einstellungen vom Golf Marketplace uebernehmen und Testmail schicken.
rem Aufruf aus PowerShell im Projektordner:  & .\scripts\hostinger\use-marketplace-mail.cmd name@beispiel.de
rem Die Zugangsdaten bleiben auf dem Server (Kopie von .env zu .env), angezeigt werden nur Host und Absender.
set SSH_TARGET=u491326318@46.202.142.126
set SSH_PORT=65002
set DOMAIN=karlebbefeld-itx-digitals-consulting.de
set APP_DIR=MyGolfCourse

if "%~1"=="" (
  echo Bitte E-Mail-Adresse fuer die Testmail angeben, z. B.:  ^& .\scripts\hostinger\use-marketplace-mail.cmd name@beispiel.de
  exit /b 1
)

ssh -p %SSH_PORT% %SSH_TARGET% "DOMAIN=%DOMAIN% APP_DIR=%APP_DIR% bash -s && cd ~/domains/%DOMAIN%/%APP_DIR% && php artisan mgc:mail-test %~1" < "%~dp0mail-from-marketplace.sh"
