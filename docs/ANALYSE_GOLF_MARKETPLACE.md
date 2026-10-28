# Analyse: Golf Marketplace als Referenz für My Golf Course

Stand: 27.10.2026 · Analysiert wurde ausschließlich lesend `Desktop/Golfmarketplace/Golfmarketplace` (keine Datei verändert).

Ziel dieser Analyse: Was hat sich bewährt, was übernehmen wir, was bewusst nicht – und welche Lehren ziehen wir für die Zusammenarbeit?

---

## 1. Befund

### 1.1 Technologie

| Bereich | Golf Marketplace | Quelle |
|---|---|---|
| Backend | Laravel 13 (`laravel/framework ^13.17`), PHP ≥ 8.3, modularer Monolith | `composer.json`, `docs/DECISIONS.md` D002/D003/D011 |
| Frontend | Blade-Templates, Vanilla-JavaScript-Module (24 Dateien), Vite 8 | `resources/js/`, `vite.config.js` |
| Styling | Eigenes CSS mit Design-Tokens, kein Tailwind; CI verbietet Farbcodes außerhalb `tokens.css` | `resources/css/tokens.css`, D012 |
| Schriften | Archivo + Source Sans 3, lokal als woff2 (keine externen Webfonts) | `resources/fonts/` |
| Datenbank | SQLite lokal und in Tests, MySQL auf Hostinger; Schema nur über Migrationen (55 Migrationen) | D010, `database/migrations/` |
| Auth | Laravel-Bordmittel ohne Breeze/Fortify: Registrierung, Login, E-Mail-Verifizierung, Passwort-Reset, Rate-Limits | D013, `app/Http/Controllers/Auth/` |
| Rollen | Enum `user`, `team`, `admin`; Admin zusätzlich über `ADMIN_EMAILS` begrenzt, E-Mail-Code als 2. Faktor | `app/Enums/Role.php`, `EnsureAdminMfa` |
| Sicherheit | Policies, Audit-Log, Security-Events (90 Tage), EXIF-Entfernung bei Uploads, private Dateiablage | `app/Policies/`, `ImageProcessor` |
| Datenschutz | Datenexport (Art. 15) und Kontolöschung, keine Tracker, Google Maps nur per Zwei-Klick | `docs/DATABASE.md` → Datenschutz, D015 |
| Mehrsprachigkeit | `lang/de` und `lang/en`, je Modul eine Datei | `lang/` |
| PWA | Manifest-Route, Service Worker, Offline-Seite, Installationsseite | D033 |
| Tests | PHPUnit 12, 71 Feature-Testdateien, SQLite `:memory:` | `tests/`, `phpunit.xml` |
| CI | GitHub Actions: Pflichtdokumente, keine `.env` im Repo, Plan-Konsistenz, Laravel-Tests, Migrationen auf MySQL 8 (up/rollback/up), Asset-Build, Farb-Token-Prüfung | `.github/workflows/checks.yml` |
| Hosting | Hostinger Business (Shared Hosting), kein Node auf dem Server, keine dauerhaften Queue-Worker, Cron für Scheduler | `docs/DEPLOYMENT.md` |
| Deployment | Manuell per Skript (`deploy.cmd` → `update.sh`): DB-Backup, Migration, Cache, Rechte | `scripts/` |

### 1.2 Struktur

- Standard-Laravel-Ordner, flach: ca. 50 Controller direkt in `app/Http/Controllers/`, Unterordner nur für `Admin/`, `Auth/`, `Team/`.
- Wiederverwendbare Blade-Komponenten in `resources/views/components/` (Button, Input, Select, Alert, Avatar, Icon, Empty-State …).
- Fachlogik in `app/Services/` und `app/Support/`, Enums nach Domäne gruppiert (`app/Enums/Listing/…`).

### 1.3 Organisation und Dokumentation

- **Ein** Masterplan (`docs/PROJEKTPLAN.md`) mit Aufgaben-IDs `P001…`, Abnahmekriterien und Abhängigkeiten.
- ADR-light (`docs/DECISIONS.md`) mit Grund, Optionen und Auswirkungen.
- PR-Vorlage mit Test-, Auswirkungs- und Abnahme-Checkliste; Issue-Vorlage zum Reservieren einer Aufgabe.
- Agentenregeln (`CLAUDE.md`, `AGENTS.md`) und Prompts für Sitzungsstart, Abschluss und Übergabe.
- Rückbau-Anleitungen für Designexperimente in `docs/DESIGN_SYSTEM.md`.

### 1.4 Git – dokumentiert vs. tatsächlich

Dokumentiert (`docs/WORKFLOW.md`): eine Aufgabe = ein Issue = ein Branch = ein PR, keine direkten Commits auf `main`, menschliche Abnahme vor dem Merge.

Tatsächlich gefundener Stand (27.10.2026):

| Messpunkt | Wert |
|---|---|
| Commits auf lokalem `main` | 1 (Initial Commit, lokal nie aktualisiert) |
| Commits auf `origin/main` | 54, zuletzt „Merge pull request #10“ (P048) |
| Commits auf dem aktuellen Branch `feature/inserat-handy-test` | 204 |
| Nicht in `main` gemergte lokale Branches | 55 |
| Backup-Tags (`backup/vor-…`) | 16 |
| Merges zwischen Feature-Branches | mehrfach (z. B. „Merge Noahs feature/profil-bewertungen in feature/chat-design“) |
| Autoren | 2 (151 / 53 Commits) |

Anfangs (P001–P048) wurde der Workflow sauber über Pull Requests eingehalten. Danach sind rund 150 Commits auf verketteten Feature-Branches entstanden, die nie nach `main` zurückgeflossen sind.

### 1.5 Entwicklungsumgebung

- Laut `docs/DECISIONS.md` (D011) und Übergabedokument ist auf den Entwicklerrechnern **kein PHP/Composer installiert**; Tests liefen nur in GitHub Actions bzw. wurden nicht ausgeführt („Tests wurden nicht ausgeführt (kein PHP auf Karls Rechner)“).
- Auf diesem Rechner bestätigt: `php` und `composer` nicht gefunden; vorhanden sind Node 24, npm 11, pnpm 10, Docker 29, Git 2.49. Die GitHub-CLI `gh` ist nicht installiert.

---

## 2. Bewertung für My Golf Course

### 2.1 Übernehmen (bewährt, passt)

| Was | Warum |
|---|---|
| **Laravel als Backend, modularer Monolith** | Team beherrscht es nachweislich (200+ Commits in wenigen Tagen), läuft auf vorhandenem Hosting, ausgereifte Auth/Queue/Storage-Abstraktion. |
| **Governance-Set**: ein Masterplan, ADR-light, PR-/Issue-Vorlagen, `CLAUDE.md` | Hat in der ersten Phase gut funktioniert; wird schlanker übernommen (siehe [WORKFLOW.md](WORKFLOW.md)). |
| **CI-Prüfungen**: keine Secrets, Migrationen auf MySQL, Asset-Build, Farb-Token-Prüfung | Billig, verhindern reale Fehlerklassen. |
| **Design-Token-Prinzip** und lokale Schriften | Zentral austauschbar, DSGVO-neutral. Die Farben und Schriften selbst werden **nicht** übernommen (eigene Marke, siehe [DESIGN_CONCEPT.md](DESIGN_CONCEPT.md)). |
| **Auth-Implementierung + Tests** (Registrierung, Login, Verifizierung, Reset, Rate-Limits) | Eigener, bereits getesteter Code; spart mehrere Tage. Wird kopiert und angepasst, nicht als Abhängigkeit verlinkt. |
| **Admin-Absicherung**: `ADMIN_EMAILS`, E-Mail-Zweitfaktor, Audit-Log, Security-Events | Direkt wiederverwendbar für den neuen Admin-Bereich. |
| **DSGVO-Bausteine**: Datenexport, Kontolöschung, `config/legal.php`, Zwei-Klick für Drittanbieter | Gleiche Rechtslage, gleiche Betreiber. |
| **PWA-Grundlage** (Manifest, Service Worker, Offline-Seite) | Sinnvoll als erste „App“, siehe [ARCHITECTURE.md](ARCHITECTURE.md) § 8. |
| **`.editorconfig`, `.gitattributes`, `.gitignore`, `.npmrc`** | Neutral, bewährt. |
| **Deploy-Skript-Muster** (Backup → Migration → Cache → Rechte, nie `.env` hochladen) | Bewährt auf demselben Hosting. |
| **i18n-Struktur** `lang/{de,en}/<modul>.php` | Eine Datei je Modul vermeidet Merge-Konflikte. |

### 2.2 Anpassen

| Was | Anpassung |
|---|---|
| Flache Controller-Struktur | Für die geplante Modul-Erweiterung (Community, Matches, Marketplace) Code nach Modulen gruppieren: `app/Modules/<Modul>/`. |
| Selbstgebauter Admin-Bereich | Für Platz-/Bahn-/Medienpflege Admin-Framework **Filament** statt handgebauter Blade-Formulare (siehe D-005 in [DECISIONS.md](DECISIONS.md)). |
| Medien auf lokalem Disk + GD-Verarbeitung auf dem Server | Für Inseratfotos (≤ 1600 px) passend, für 8K-Panoramen und 4K-Videos nicht: Ein 8192 × 4096-Panorama belegt in GD unkomprimiert rund 130 MB RAM, Videos brauchen Transcoding. → Externer Medienspeicher + Videostreaming-Dienst, Aufbereitung außerhalb des Webservers. |
| Ein Dokument `DATABASE.md` als Änderungsprotokoll mit Branch-Verweisen | Datenmodell-Dokument beschreibt den Zielzustand; Historie steht in Git und Migrationen. |
| Aufgaben-IDs `P###` | Neues Präfix `MGC-###`, damit Issues/Branches beider Projekte nicht verwechselt werden. |

### 2.3 Nicht übernehmen

- Marketplace-Module (Inserate, Chat-Polling, Ankauf, Angebote, Bewertungen, Caddie-Chatbot, Schläger-Finder) – fachlich nicht relevant. Chat/Bewertungen werden später für die Community neu bewertet, nicht vorab kopiert.
- Visuelles Design (an Vinted/Kleinanzeigen angelehnt) – My Golf Course braucht eine bildzentrierte, eigene Designsprache.
- Geocoding/Adresshilfe (Nominatim/Photon) – im MVP nicht nötig, da Plätze vom Team gepflegt werden.

---

## 3. Lehren für die Zusammenarbeit (wichtigster Teil)

| Beobachtung | Folge | Regel für My Golf Course |
|---|---|---|
| ~150 Commits nie in `main` | Niemand weiß sicher, welcher Branch „der Stand“ ist; Übergaben brauchen lange Dokumente. | **`main` ist immer der Stand.** Branches leben höchstens wenige Tage und gehen per PR zurück. Branch-Schutz auf GitHub technisch erzwingen. |
| Feature-Branches bauen aufeinander auf und werden gegenseitig gemergt | Änderungen des anderen kommen ungeprüft mit, Konflikte wachsen. | Jeder Branch startet von aktuellem `main`. Merges zwischen Feature-Branches sind verboten; stattdessen kleinen PR zuerst mergen. |
| 16 Backup-Tags, Hero mehrfach eingebaut und zurückgenommen | Designentscheidungen wurden im Code ausprobiert; Zeitverlust, unruhige Historie. | **Design vor Code:** Schlüsselscreens als Prototyp abstimmen (MGC-004). Unfertiges hinter Feature-Flags statt auf Langläufer-Branches. |
| Kein PHP lokal, Tests nicht ausgeführt | Fehler fallen erst in CI oder auf Staging auf. | **Beide Rechner können `php artisan test` ausführen** (MGC-010, Laravel Herd oder Docker). Ein PR ohne grüne CI wird nicht gemergt. |
| Sehr lange Commit-Nachrichten in Prosa, keine Konvention | Historie schwer durchsuchbar, kein automatisches Changelog. | Conventional Commits mit Modul als Scope, z. B. `feat(courses): Detailseite mit Scorecard`. |
| Dokumentation als Protokoll (Übergaben, Rückbau-Anleitungen) | Doku wächst schneller als Code. | Doku beschreibt Zielzustand und Entscheidungen; Verlauf steht in PRs. |

---

## 4. Offene Punkte aus der Analyse

- Ob die Feature-Branches des Marketplace noch nach `main` sollen, betrifft das bestehende Projekt und wird hier nicht angefasst.
- Hostinger-Tarif-Details (PHP-Memory-Limit, Speicherplatz, Inodes) sind im Marketplace nur als Annahmen dokumentiert („☐ bestätigen“); für My Golf Course in MGC-015 einmal real prüfen.
