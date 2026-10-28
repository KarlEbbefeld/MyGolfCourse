#!/usr/bin/env bash
# Update auf Hostinger: läuft auf dem Server, aufgerufen von scripts/hostinger/deploy.cmd (ein Passwort).
# Beim ersten Lauf richtet es alles ein (öffentlicher Ordner, index.php, .env, SQLite, Composer).
# Danach: Abhängigkeiten bei Bedarf, Migrationen, Demodaten (falls DEMO_DATA=true), Caches, Rechte.
set -euo pipefail

DOMAIN="${DOMAIN:?DOMAIN fehlt}"
APP_DIR="${APP_DIR:-MyGolfCourse}"
APP=~/domains/$DOMAIN/$APP_DIR
WEB=~/domains/$DOMAIN/public_html/$APP_DIR
cd "$APP"

env_val() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

echo "== 1/7 Ordner"
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache database

echo "== 2/7 Öffentlicher Ordner ($WEB)"
# public/ der App ist ein Verweis auf public_html/<App> – nur dieser Teil ist aus dem Internet erreichbar.
# public_html liegt auf einem anderen Dateisystem: tar kann nicht durch den Verweis entpacken.
# deploy.cmd entpackt die Website-Dateien deshalb nach .public-upload, von dort wird kopiert.
mkdir -p "$WEB"
if [ -d .public-upload ]; then
  cp -r .public-upload/. "$WEB/"
  rm -rf .public-upload
  echo "Website-Dateien nach $WEB kopiert"
fi
if [ ! -L public ]; then
  rm -rf public
  ln -s "$WEB" public
  echo "public → $WEB eingerichtet"
fi
# index.php im öffentlichen Ordner zeigt auf den App-Ordner außerhalb von public_html
if ! grep -q 'MGC-SERVER-INDEX' "$WEB/index.php" 2>/dev/null; then
  cat > "$WEB/index.php" <<PHP
<?php
// MGC-SERVER-INDEX: von scripts/hostinger/update.sh erzeugt, nicht hochladen/überschreiben
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
\$base = __DIR__.'/../../$APP_DIR';

if (file_exists(\$maintenance = \$base.'/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require \$base.'/vendor/autoload.php';

\$app = require_once \$base.'/bootstrap/app.php';
\$app->usePublicPath(__DIR__);
\$app->handleRequest(Request::capture());
PHP
  echo "index.php geschrieben"
fi

echo "== 3/7 .env"
if [ ! -f .env ]; then
  cp .env.example .env
  sed -i "s#^APP_ENV=.*#APP_ENV=staging#" .env
  sed -i "s#^APP_DEBUG=.*#APP_DEBUG=false#" .env
  sed -i "s#^APP_URL=.*#APP_URL=https://$DOMAIN/$APP_DIR#" .env
  sed -i "s#^DB_CONNECTION=.*#DB_CONNECTION=sqlite#" .env
  echo "DB_DATABASE=$APP/database/database.sqlite" >> .env
  echo ".env angelegt (Staging, SQLite, Demodaten an)"
fi
[ -f database/database.sqlite ] || { touch database/database.sqlite; echo "SQLite-Datenbank angelegt"; }
# Mit SQLite Sitzungen in Dateien statt in der Datenbank (weniger gleichzeitige Schreibzugriffe → keine „database is locked“-Fehler)
if grep -q '^DB_CONNECTION=sqlite' .env && grep -q '^SESSION_DRIVER=database' .env; then
  sed -i 's/^SESSION_DRIVER=database/SESSION_DRIVER=file/' .env
  echo "Sitzungen auf Dateien umgestellt (einmalig werden alle abgemeldet)"
fi

echo "== 4/7 Composer"
# Hostinger sperrt proc_open: Composer kann seine Nachlauf-Skripte (php artisan …) nicht starten.
# Deshalb --no-scripts und package:discover direkt aufrufen.
hash_now=$(md5sum composer.json | cut -d' ' -f1)
if [ ! -f vendor/autoload.php ] || [ "$(cat storage/composer.md5 2>/dev/null)" != "$hash_now" ]; then
  composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts
  echo "$hash_now" > storage/composer.md5
else
  composer dump-autoload --optimize --no-dev --no-interaction --no-scripts >/dev/null
  echo "Abhängigkeiten unverändert (Autoloader aktualisiert)"
fi
php artisan package:discover --ansi
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force

echo "== 5/7 Migrationen"
if [ "$(env_val DB_CONNECTION)" != "sqlite" ]; then
  BACKUP=~/backup-mgc-$(date +%Y%m%d-%H%M%S).sql
  MYSQL_PWD="$(env_val DB_PASSWORD)" mysqldump --no-tablespaces -h "$(env_val DB_HOST)" -u "$(env_val DB_USERNAME)" "$(env_val DB_DATABASE)" > "$BACKUP"
  echo "Backup: $BACKUP"
else
  # Vollständige Kopie auch im WAL-Modus (letzte Änderungen stecken sonst in database.sqlite-wal)
  BACKUP=~/backup-mgc-$(date +%Y%m%d-%H%M%S).sqlite
  php -r '$db = new PDO("sqlite:database/database.sqlite"); $db->exec("VACUUM INTO " . $db->quote($argv[1]));' "$BACKUP" \
    || cp database/database.sqlite "$BACKUP"
  echo "Backup: $BACKUP"
fi
php artisan migrate --force

echo "== 6/7 Demodaten"
if [ "$(env_val DEMO_DATA)" = "true" ]; then
  php artisan db:seed --class='Database\Seeders\DemoCourseSeeder' --force
  echo "Demoplatz geprüft (neu anlegen: DEMO_DATA_RESET=true in der .env)"
else
  echo "DEMO_DATA nicht true – übersprungen"
fi

echo "== 7/7 Caches und Rechte"
php artisan optimize:clear
php artisan optimize || { echo "  WARNUNG: optimize fehlgeschlagen – Caches wieder geleert"; php artisan optimize:clear; }
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite 2>/dev/null || true
find "$WEB" -type d -exec chmod 755 {} +
find "$WEB" -type f -exec chmod 644 {} +
[ "$(env_val APP_DEBUG)" = "true" ] && echo "  WARNUNG: APP_DEBUG=true – auf dem Server unsicher"

php artisan mgc:status || true
echo "FERTIG → $(env_val APP_URL)"
