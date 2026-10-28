# Zusammenarbeit, Git und Deployment

Wir arbeiten wie beim Golf Marketplace: Feature-Branches, regelmäßig pushen, gegenseitig pullen. Getestet wird auf dem Hosting – hochladen, im Browser prüfen.

---

## 1. Git

- Branches: `feature/MGC-<nr>-<kurzname>` (z. B. `feature/MGC-034-street-view`), Fehler: `fix/…`.
- Regelmäßig committen und pushen; den Stand des anderen mit `git pull` bzw. `git merge origin/<branch>` holen.
- Vor größeren Merges optional Sicherungs-Tag: `git tag backup/vor-<was>`.
- Fertige, auf dem Hosting geprüfte Stände nach `main` bringen, damit `main` der gemeinsame Bezugspunkt bleibt.
- Kein Force-Push auf Branches, die der andere schon gepullt hat.
- Commit-Nachrichten kurz auf Deutsch, gern mit ID oder Modul vorne: `MGC-023 Platzdetailseite mit Scorecard`.

## 2. Konflikte klein halten

- Code nach Modulen getrennt (`app/Modules/<Modul>`, `routes/modules/<modul>.php`, `resources/views/<modul>/`, `lang/de/<modul>.php`) – meist arbeitet jeder in anderen Dateien.
- Migrationen nie nachträglich ändern, sondern neue anlegen.
- Gemeinsame Kerndateien (`public/css/tokens.css`, `components/layout.blade.php`, `User`-Modell, `routes/web.php`) kurz absprechen.

## 3. Testen = auf das Hosting hochladen

Es wird nichts lokal ausgeführt. Ablauf:

```powershell
& .\scripts\hostinger\deploy.cmd
```

Ein SSH-Passwort, dann erledigt `scripts/hostinger/update.sh` auf dem Server alles:

| Schritt | Was passiert |
|---|---|
| Erster Lauf | Öffentlicher Ordner `public_html/MyGolfCourse` + angepasste `index.php`, `.env` (Staging, SQLite, Demodaten), App-Schlüssel |
| Composer | `composer install --no-dev`, nur wenn sich `composer.json` geändert hat |
| Datenbank | Backup (SQLite-Kopie bzw. MySQL-Dump), dann `migrate --force` |
| Demodaten | Bei `DEMO_DATA=true` wird der Demoplatz neu angelegt |
| Abschluss | Caches neu, Rechte setzen, Adresse ausgeben |

Danach im Browser: `https://<domain>/MyGolfCourse`.

- Kein Build-Schritt: CSS und JavaScript liegen fertig in `public/css` und `public/js`, Bibliotheken in `public/vendor`. Nach dem Hochladen sehen Browser sofort die neue Fassung (`?v=<Änderungszeit>`).
- Hochgeladen wird nie: `.env`, `public/index.php`, lokale Datenbanken.
- Server-Zugang, Domain und Ordnername stehen oben in `scripts/hostinger/deploy.cmd`.

## 4. Umgebungsvariablen und Zugangsdaten

- `.env` wird nie committet; `.env.example` enthält alle Schlüssel. Neue Variable → gleich dort eintragen.
- Echte Zugangsdaten (Medienanbieter, SMTP, MySQL) nur in der Server-`.env`.

## 5. Dokumentation

Aufgaben in [PROJEKTPLAN.md](PROJEKTPLAN.md), Entscheidungen in [DECISIONS.md](DECISIONS.md). Dokumente beschreiben den aktuellen Zielzustand.
