@echo off
rem My Golf Course: Videos und Bilder aus public\media auf Hostinger laden - EINE SSH-Verbindung, EIN Passwort.
rem Aufruf aus PowerShell im Projektordner:  & .\scripts\hostinger\upload-media.cmd
rem Getrennt vom normalen Deploy, damit grosse Videos nicht bei jedem Update mitgeschickt werden.
rem Danach wird der Demoplatz neu angelegt (media\demo\bahn-1.mp4 = Ueberflug Bahn 1) und der Zustand angezeigt.
rem Voraussetzung: vorher einmal deploy.cmd mit dem aktuellen Code.
set SSH_TARGET=u491326318@46.202.142.126
set SSH_PORT=65002
set DOMAIN=karlebbefeld-itx-digitals-consulting.de
set APP_DIR=MyGolfCourse

cd /d "%~dp0..\.."
tar -cf - -C public media | ssh -p %SSH_PORT% %SSH_TARGET% "cd domains/%DOMAIN%/public_html/%APP_DIR% && tar -xf - && find media -type d -exec chmod 755 {} + && find media -type f -exec chmod 644 {} + && cd ~/domains/%DOMAIN%/%APP_DIR% && php artisan db:seed --class='Database\Seeders\DemoCourseSeeder' --force && php artisan mgc:status"
