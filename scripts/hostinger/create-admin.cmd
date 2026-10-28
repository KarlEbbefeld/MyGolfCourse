@echo off
rem My Golf Course: Admin-Konto anlegen oder Passwort neu setzen.
rem Aufruf aus PowerShell im Projektordner:  & .\scripts\hostinger\create-admin.cmd name@beispiel.de
rem Zeigt das neue Passwort einmal an.
set SSH_TARGET=u491326318@46.202.142.126
set SSH_PORT=65002
set DOMAIN=karlebbefeld-itx-digitals-consulting.de
set APP_DIR=MyGolfCourse

if "%~1"=="" (
  echo Bitte E-Mail-Adresse angeben, z. B.:  ^& .\scripts\hostinger\create-admin.cmd name@beispiel.de
  exit /b 1
)

ssh -p %SSH_PORT% %SSH_TARGET% "cd ~/domains/%DOMAIN%/%APP_DIR% && php artisan mgc:admin %~1"
