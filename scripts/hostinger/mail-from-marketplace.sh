#!/usr/bin/env bash
# Übernimmt die Mail-Einstellungen (MAIL_*) aus der Golf-Marketplace-.env in die My-Golf-Course-.env – nur auf dem Server,
# Zugangsdaten werden nicht angezeigt. Aufruf über scripts/hostinger/use-marketplace-mail.cmd.
# Zum Testen gedacht; vor dem öffentlichen Start ein eigenes Postfach eintragen.
set -euo pipefail

DOMAIN="${DOMAIN:?DOMAIN fehlt}"
APP_DIR="${APP_DIR:-MyGolfCourse}"
APP=~/domains/$DOMAIN/$APP_DIR
SOURCE=~/domains/$DOMAIN/Golfmarketplace/.env

[ -f "$SOURCE" ] || { echo "ABBRUCH: Marketplace-.env nicht gefunden ($SOURCE)"; exit 1; }
cd "$APP"
[ -f .env ] || { echo "ABBRUCH: .env von My Golf Course fehlt – zuerst deploy.cmd ausführen"; exit 1; }
grep -q '^MAIL_HOST=' "$SOURCE" || { echo "ABBRUCH: In der Marketplace-.env stehen keine MAIL_-Einstellungen"; exit 1; }

cp .env ".env.bak-$(date +%Y%m%d-%H%M%S)"
{
    grep -v '^MAIL_' .env
    grep '^MAIL_' "$SOURCE" | grep -v '^MAIL_FROM_NAME='
    echo 'MAIL_FROM_NAME="My Golf Course"'
} > .env.new
chmod --reference=.env .env.new 2>/dev/null || chmod 640 .env.new
mv .env.new .env

php artisan optimize:clear >/dev/null
php artisan optimize >/dev/null || php artisan optimize:clear >/dev/null

echo "Mail-Einstellungen übernommen (Backup: .env.bak-…):"
grep -E '^MAIL_(MAILER|HOST|PORT|FROM_ADDRESS|FROM_NAME)=' .env | sed 's/^/  /'
