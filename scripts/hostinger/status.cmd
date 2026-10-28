@echo off
rem My Golf Course: Zustand auf dem Server anzeigen (nur lesen, aendert nichts).
rem Aufruf aus PowerShell im Projektordner:  & .\scripts\hostinger\status.cmd
set SSH_TARGET=u491326318@46.202.142.126
set SSH_PORT=65002
set DOMAIN=karlebbefeld-itx-digitals-consulting.de
set APP_DIR=MyGolfCourse

ssh -p %SSH_PORT% %SSH_TARGET% "cd ~/domains/%DOMAIN%/%APP_DIR% && php artisan mgc:status"
