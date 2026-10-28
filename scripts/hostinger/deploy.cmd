@echo off
rem My Golf Course: Stand dieses Rechners auf Hostinger bringen - EINE SSH-Verbindung, EIN Passwort.
rem Aufruf aus PowerShell im Projektordner:  & .\scripts\hostinger\deploy.cmd
rem Kein Build noetig: CSS/JS liegen fertig in public/.
rem Packt App-Code + Website-Dateien + update.sh, entpackt im App-Ordner und fuehrt update.sh aus.
rem Nie dabei: .env, public/index.php (auf dem Server an den Unterordner angepasst), lokale Datenbanken.
rem Server und Domain wie beim Golf Marketplace (gleicher Hostinger-Zugang) - bei Bedarf hier anpassen.
set SSH_TARGET=u491326318@46.202.142.126
set SSH_PORT=65002
set DOMAIN=karlebbefeld-itx-digitals-consulting.de
set APP_DIR=MyGolfCourse

cd /d "%~dp0..\.."
tar -cf - --exclude=*.sqlite --exclude=*.sqlite-wal --exclude=*.sqlite-shm --exclude=public/index.php app bootstrap/app.php bootstrap/providers.php config database lang routes resources artisan composer.json .env.example public/.htaccess public/robots.txt public/favicon.svg public/css public/js public/fonts public/images public/vendor -C "%~dp0." update.sh | ssh -p %SSH_PORT% %SSH_TARGET% "mkdir -p domains/%DOMAIN%/%APP_DIR% && cd domains/%DOMAIN%/%APP_DIR% && tar --transform 's#^public/#.public-upload/#' -xf - && DOMAIN=%DOMAIN% APP_DIR=%APP_DIR% bash update.sh; rm -f update.sh"
