# Regeln für KI-Agenten – My Golf Course

Projekt: Plattform zur digitalen Golfplatzerkundung (360° wie Street View, Drohnenüberflug, vermessene Hindernisse). Stack: Laravel 13, Blade, Plain JavaScript, Photo Sphere Viewer (lokal in `public/vendor`), SQLite auf Staging / MySQL später, Hostinger.

## Arbeitsweise des Teams
- **Nichts lokal ausführen** – kein Docker, kein lokales PHP/Composer, keine lokalen Testläufe. Getestet wird durch Hochladen (`scripts/hostinger/deploy.cmd`) und manuelles Prüfen auf dem Hosting.
- Code deshalb besonders sorgfältig schreiben und selbst gegenlesen; Annahmen, die nur auf dem Server prüfbar sind, offen benennen.
- Git wie beim Golf Marketplace: Feature-Branch `feature/MGC-###-kurzname`, pushen/pullen; Commit oder Push nur, wenn das Team es sagt.

## Vor der Arbeit
- Relevante Abschnitte lesen: `docs/PROJEKTPLAN.md`, `docs/ARCHITECTURE.md` (Module), `docs/DATA_MODEL.md`, `docs/DECISIONS.md`.
- `git status`, Branch und `git log -5 --oneline` prüfen. Uncommittete Änderungen nie verwerfen.

## Code
- Module: `app/Modules/<Modul>/`, `routes/modules/<modul>.php`, `resources/views/<modul>/`, `lang/de/<modul>.php`, `tests/Feature/<Modul>/`. `Courses` kennt `Explorer`/`Hazards` nicht (Abhängigkeiten in `docs/ARCHITECTURE.md` § 4).
- Kein Build-Schritt: CSS in `public/css` (Farben nur über `tokens.css`), JS in `public/js`, Einbindung mit `asset_v()`.
- Maße in Metern, Koordinaten WGS84; im Viewer GPS-Format `[lng, lat, höhe]`.
- Seeder ohne Faker (Server installiert ohne Dev-Pakete).
- In Blade keine Strings wie `'@context'` (kollidiert mit Blade-Direktiven) – solche Daten im Controller erzeugen.

## Nur mit ausdrücklicher Freigabe
Neue Abhängigkeiten, Deployments, Änderungen an Server-`.env`/Secrets, Daten löschen, Änderungen am Golf-Marketplace-Projekt (nur lesend nutzen).
