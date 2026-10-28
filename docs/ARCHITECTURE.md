# Architektur – My Golf Course

Status: **Vorschlag zur gemeinsamen Freigabe** (MGC-002). Begründungen der Einzelentscheidungen in [DECISIONS.md](DECISIONS.md).

---

## 1. Leitlinien

1. **Klein betreiben, groß denken.** Ein Laravel-Monolith, ein Server, ein Medienanbieter. Keine Microservices, keine eigene Video-Infrastruktur.
2. **Medien gehören nicht auf den Webserver.** Panoramen, Videos und Luftbilder liegen bei einem spezialisierten Speicher-/Streaming-Dienst mit CDN. Der Laravel-Server speichert nur Metadaten und Verweise.
3. **Geo-Daten sind die Wahrheit.** Abschläge, Grüns, Hindernisse und Aufnahmepositionen haben (wo möglich) echte Koordinaten. Darstellungen in Panorama, Luftbild und Video sind daraus abgeleitet oder manuell nachjustiert.
4. **Module statt Schichten.** Jede größere Funktion ist ein Ordner mit eigenem Code, Routen, Views, Übersetzungen und Tests. Zwei Entwickler können an zwei Modulen arbeiten, ohne dieselben Dateien anzufassen.
5. **Server-gerendert, interaktiv nur wo nötig.** Seiten kommen als HTML (SEO, schnell, barrierearm). Nur der Medien-Viewer ist eine JavaScript-Insel.

---

## 2. Systemüberblick

```
                        ┌───────────────────────────────────────────┐
  Browser / PWA         │ Öffentliche Seiten (Blade, HTML)          │
  (Desktop, Tablet,     │  + Viewer-Insel (TypeScript):             │
   Smartphone)          │    Photo Sphere Viewer · hls.js           │
                        └──────┬───────────────────────┬────────────┘
                               │ HTML / JSON           │ Bilder, Kacheln, HLS-Video
                               ▼                       ▼
┌──────────────────────────────────────┐   ┌───────────────────────────────────┐
│ Laravel-App (Hostinger)              │   │ Medien-CDN (Vorschlag: Bunny.net) │
│  Module: Courses · Explorer · Media  │   │  Storage-Zone: Panoramen, Bilder  │
│          Hazards · Accounts · …      │──▶│  Stream: Drohnenvideos (HLS)      │
│  Admin: Filament (/admin)            │API│  EU-Standorte, AVV möglich        │
│  MySQL (Metadaten, Geo, Nutzer)      │   └───────────────────────────────────┘
└──────────────────────────────────────┘                 ▲
                                                         │ Upload (direkt, nicht über PHP)
                                    ┌────────────────────┴──────────────────┐
                                    │ Admin-Browser (Video-Upload per TUS)  │
                                    │ Lokales Werkzeug tools/media          │
                                    │  (Panoramen skalieren/kacheln, Upload)│
                                    └───────────────────────────────────────┘
```

---

## 3. Technologie-Stack (Empfehlung)

| Schicht | Wahl | Kurzbegründung | Alternative (verworfen, warum) |
|---|---|---|---|
| Backend | **Laravel 13, PHP 8.3+** | Team-Erfahrung, läuft auf vorhandenem Hosting, Auth/Queue/Storage/Policies an Bord | Next.js + Supabase: neu für das Team, zweites Ökosystem, Vorteile hier gering |
| Admin | **Filament** (Version beim Setup gegen Laravel 13 prüfen) | Formulare, Tabellen, Relationen, Uploads, Rechte in Tagen statt Wochen; eigene Seiten für Marker-Platzierung möglich | Selbstbau wie im Marketplace: zu teuer für reine Datenpflege |
| Öffentliches Frontend | **Blade + Vite + TypeScript-Module** | SEO, schnelle Erstanzeige; TypeScript nur für den komplexen Viewer | SPA (React/Vue): mehr Aufwand, schlechteres SEO ohne SSR |
| 360°-Viewer | **Photo Sphere Viewer v5** (MIT) | Aktiv gepflegt; Plugins für Marker, Virtual Tour (inkl. GPS-Modus), Kachel-Panoramen, Video, Kompass | Pannellum (weniger Plugins), Marzipano (kaum gepflegt), Kuula/3DVista/Matterport (SaaS, Lock-in, laufende Kosten) |
| Video | **HLS über Bunny Stream**, Wiedergabe mit **hls.js** im eigenen Player | Adaptives Streaming (Handy bekommt kleinere Auflösung), Transcoding übernimmt der Dienst | Eigenes ffmpeg auf Shared Hosting: nicht möglich; YouTube/Vimeo-Einbettung: Branding, Datenschutz, keine Kontrolle über UI |
| Medienspeicher | **Bunny Storage + CDN** (EU) | Günstig pro GB, CDN integriert, europäischer Anbieter (DSGVO) | Cloudflare R2 + Stream: gleichwertige Alternative, US-Anbieter; AWS S3/CloudFront: komplexer und teurer im Traffic |
| Datenbank | **MySQL** (Hostinger), lokal **SQLite**, CI zusätzlich MySQL | Bewährtes Muster aus dem Marketplace (D010) | PostgreSQL/PostGIS: Geo-Funktionen im MVP nicht nötig (Punkte + GeoJSON reichen) |
| Styling | **Eigenes CSS mit Design-Tokens** (öffentlich), Filament-Theme (Admin) | Bewährt, keine zweite Konvention im öffentlichen Teil | Tailwind im öffentlichen Teil: möglich, aber zweite Konvention ohne Mehrwert |
| Tests | PHPUnit (Feature/Unit), **Playwright** für wenige Viewer-Smoke-Tests | Viewer ist JavaScript – ohne Browser-Test nicht prüfbar | – |
| Code-Qualität | Laravel Pint, Larastan (Stufe 5 zum Start), ESLint + Prettier, TypeScript `strict` | Einheitliche Formatierung = weniger Diff-Rauschen und Konflikte | – |
| Lokale Umgebung | **Laravel Herd** (Windows) oder **Laravel Sail** (Docker) | Lehre aus dem Marketplace: Tests müssen lokal laufen | – |

Preise und Konditionen der Medienanbieter vor der Entscheidung (MGC-006) aktuell prüfen; die Größenordnung im MVP liegt bei wenigen Euro pro Monat (Rechnung in [MEDIA_PIPELINE.md](MEDIA_PIPELINE.md) § 7).

---

## 4. Module (Baukasten)

| # | Modul | Ordner | MVP | Verantwortung |
|---|---|---|---|---|
| 1 | Golfplatzverzeichnis | `Courses` | ✅ | Plätze, Abschlag-Sets, Suche/Liste, Detailseite, Veröffentlichung |
| 2 | Golfbahnverwaltung | `Courses` | ✅ | Bahnen, Abschläge je Bahn, Grün, Bahnseite (gehört fachlich zum Platz, daher gleiches Modul) |
| 3 | 360-Grad-Erkundung | `Explorer` | ✅ | Aufnahmepositionen, Verbindungen, Viewer-Daten-Endpunkt |
| 4 | Drohnenüberflüge | `Explorer` | ✅ | Überflüge, Cue-Punkte (Infos im Zeitverlauf) |
| 5 | Hindernisinformationen | `Hazards` | ✅ | Objekte (POIs), Messungen, Platzierungen in Medien, Distanzen |
| – | Medienverwaltung | `Media` | ✅ | MediaAssets, Anbindung Speicher/Stream, Status, Varianten (technisches Basismodul) |
| 6 | Benutzerverwaltung | `Accounts` | ✅ (einfach) | Registrierung, Login, Profil, Einstellungen, Rollen, DSGVO |
| 7 | Favoriten | `Favorites` | ✅ | Lieblingsplätze, zuletzt angesehen |
| 8 | Administration | `app/Filament` | ✅ | Admin-Oberfläche, nutzt die Modelle der Fachmodule |
| 9 | Community | `Community` | ⏳ später | Clubzugehörigkeit, Profile, Kontakte |
| 10 | Nachrichten/Benachrichtigungen | `Messaging` | ⏳ später | Direktnachrichten, Benachrichtigungen |
| 11 | Match-/Rundenplanung | `Rounds` | ⏳ später | Runden, Einladungen, Teilnahme |
| 12 | Marketplace | `Marketplace` | ⏳ später | Anbindung an Golf Marketplace (API oder Link), nicht Code-Kopie |

### Abhängigkeitsregeln

```
Accounts ◀── Favorites
   ▲
Media ◀── Explorer ──▶ Courses ◀── Hazards ──▶ Media
                          ▲
                Community/Rounds (später) ──▶ Accounts, Courses
```

- Pfeile zeigen „nutzt“. Zyklen sind verboten.
- `Courses` kennt weder `Explorer` noch `Hazards` – die hängen sich an Bahnen an, nicht umgekehrt. So bleibt das Verzeichnis ohne Medien lauffähig (wichtig für Tests und schrittweisen Ausbau).
- Ein Modul schreibt nur in **eigene** Tabellen. Lesen über Eloquent-Beziehungen anderer Module ist erlaubt.
- Spätere Module (Community, Rounds, …) bekommen eigene Ordner, eigene Tabellen, eigene Routen-Datei und einen **Feature-Flag** in `config/features.php`. Sie verändern bestehende Module höchstens durch neue Beziehungen.

---

## 5. Verzeichnisstruktur

```
my-golf-course/
├── .github/
│   ├── workflows/ci.yml             # Lint, Static Analysis, Tests (SQLite + MySQL), Build
│   ├── pull_request_template.md
│   ├── ISSUE_TEMPLATE/task.yml
│   └── CODEOWNERS
├── app/
│   ├── Core/                        # Querschnitt: Basisklassen, Rollen-Enum, Feature-Flags, Helfer
│   │   ├── Enums/Role.php
│   │   ├── Support/                 # Units (m/yd), Geo (Distanz, Peilung), Slugs
│   │   └── Http/Middleware/
│   ├── Modules/
│   │   ├── Courses/
│   │   │   ├── Models/              # Course, TeeSet, Hole, HoleTee
│   │   │   ├── Enums/               # CourseStatus, TeeColor
│   │   │   ├── Http/Controllers/    # CourseIndexController, CourseShowController, HoleShowController
│   │   │   ├── Actions/             # PublishCourse, …
│   │   │   ├── Policies/
│   │   │   └── Data/                # DTOs/Resources für JSON-Ausgaben
│   │   ├── Explorer/                # Panorama, PanoramaLink, Flyover, FlyoverCue; Viewer-JSON
│   │   ├── Hazards/                 # PointOfInterest, Measurement, Placement
│   │   ├── Media/                   # MediaAsset, MediaVariant, BunnyStorage, BunnyStream, Webhooks
│   │   ├── Accounts/                # Auth-Controller (aus Marketplace übernommen), Profil, Export
│   │   └── Favorites/
│   ├── Filament/                    # Admin-Panel
│   │   ├── Resources/Courses/…      # je Modul ein Unterordner
│   │   ├── Resources/Explorer/…
│   │   └── Pages/PlaceMarkers.php   # Eigene Seite: Marker im Panorama setzen
│   └── Providers/
├── config/
│   ├── features.php                 # Feature-Flags (community=false, …)
│   └── media.php                    # Anbieter, Varianten, Grenzwerte
├── database/
│   ├── migrations/                  # zentral, Laravel-Standard (Zeitstempel verhindern Konflikte)
│   ├── factories/
│   └── seeders/DemoCourseSeeder.php # Demoplatz mit Platzhaltermedien
├── docs/                            # diese Dokumentation
├── lang/{de,en}/<modul>.php         # eine Datei je Modul
├── public/
├── resources/
│   ├── css/
│   │   ├── tokens.css               # einzige Quelle für Farben, Abstände, Radien
│   │   ├── base.css  layout.css
│   │   └── components/*.css         # eine Datei je Komponente
│   ├── js/
│   │   ├── app.ts
│   │   └── explorer/                # Viewer-Insel: panorama.ts, flyover.ts, markers.ts, types.ts
│   └── views/
│       ├── layouts/  components/
│       ├── courses/  explorer/  accounts/  favorites/
│       └── pages/                   # Start, Impressum, Datenschutz
├── routes/
│   ├── web.php                      # bindet nur routes/modules/*.php ein
│   ├── api.php                      # /api/v1/… (vom Viewer genutzt, später von Apps)
│   └── modules/{courses,explorer,accounts,favorites}.php
├── tests/
│   ├── Feature/<Modul>/
│   ├── Unit/
│   └── e2e/                         # Playwright: Viewer-Smoke-Tests
├── tools/
│   └── media/                       # Node-CLI: Panoramen prüfen, skalieren, kacheln, hochladen
├── .editorconfig  .gitattributes  .gitignore  .env.example
├── CLAUDE.md                        # Regeln für KI-Agenten
└── README.md
```

**Warum diese Aufteilung Konflikte vermeidet:** Neue Funktionen erzeugen überwiegend *neue* Dateien in *einem* Modulordner. Gemeinsam berührte Dateien (`routes/web.php`, `tokens.css`, `config/features.php`) sind bewusst klein und ändern sich selten.

---

## 6. Datenfluss Bahnseite

1. `GET /plaetze/{course:slug}/bahn/{number}` → `HoleShowController` rendert HTML mit Bahndaten, Hindernisliste (Textform, SEO + Barrierefreiheit) und Platzhalterbild (Poster).
2. Der Viewer lädt `GET /api/v1/holes/{id}/explorer` (JSON): Aufnahmepositionen, Verbindungen, Überflug (HLS-URL, Poster, Cues), POIs mit Platzierungen, Einheiten. Antwort wird pro Bahn gecacht und bei Admin-Änderung invalidiert.
3. Panoramen/Kacheln und Video-Segmente kommen direkt vom CDN; der Laravel-Server ist danach unbeteiligt.

Diese JSON-Schnittstelle ist dieselbe, die eine spätere native App nutzt – sie wird damit von Anfang an im Alltag getestet.

---

## 7. Rollen und Rechte

| Rolle | Rechte | MVP |
|---|---|---|
| Gast | Alle veröffentlichten Plätze und Bahnen ansehen | ✅ |
| `user` | + Favoriten, zuletzt angesehen, Einstellungen (Einheit, Sprache) | ✅ |
| `editor` | + Admin: Plätze/Bahnen/Medien/Hindernisse anlegen und bearbeiten, **nicht** veröffentlichen | ✅ |
| `admin` | + veröffentlichen/deaktivieren, Nutzer und Rollen, Audit-Log | ✅ |
| `club_manager` | Später: eigene Platzdaten pflegen (Golfclub als Kunde) | ⏳ |

Umsetzung über Rollen-Enum am Nutzer (wie Marketplace) plus Laravel-Policies je Modell. Ein Rechte-Paket (z. B. spatie/laravel-permission) erst, wenn Club-Manager pro Platz berechtigt werden müssen. Admin-Zugang wie im Marketplace: `ADMIN_EMAILS`, E-Mail-Zweitfaktor, Audit-Log.

---

## 8. Web-App vs. native App

**Empfehlung: responsive Web-App mit PWA-Funktionen im MVP, native App frühestens nach Validierung.**

| Kriterium | PWA | Native App |
|---|---|---|
| 360°-Viewer, Video | Funktioniert im Browser (WebGL, HLS) vollständig | Gleiches Ergebnis, doppelte Umsetzung |
| Auffindbarkeit | Google findet jede Bahnseite (SEO) | Store-Suche, keine Websuche |
| Kosten | keine Store-Gebühren, ein Codebestand | Apple 99 $/Jahr, Google 25 $ einmalig, Review-Prozesse, zwei Plattformen |
| Offline auf dem Platz | Begrenzt (Service Worker kann Daten einzelner Plätze vorhalten) | Besser bei großen Medien |
| GPS „Wo stehe ich auf der Bahn?“ | Geolocation-API im Browser möglich | Komfortabler im Hintergrund |

Vorbereitung für später: Viewer-Daten bereits als versionierte JSON-API (`/api/v1`), Auth später per Laravel Sanctum ergänzbar; Hülle über Capacitor möglich, ohne den Kern umzubauen.

---

## 9. Querschnittsthemen

| Thema | Maßnahme |
|---|---|
| **SEO** | Sprechende URLs (`/plaetze/gc-musterstadt/bahn/7`), serverseitiges HTML, `schema.org/GolfCourse`, Sitemap, Open-Graph-Bild je Bahn (Poster), `noindex` außerhalb Produktion (aus Marketplace) |
| **Performance** | Viewer-JavaScript nur auf Bahnseiten nachladen; Poster als LCP-Bild; Panoramen erst niedrig, dann hoch aufgelöst; adaptive Videos; HTML-Antworten öffentlicher Seiten cachen; Ziel-Budget siehe [DESIGN_CONCEPT.md](DESIGN_CONCEPT.md) § 8 |
| **Barrierefreiheit** | Alle Hindernisinfos auch als Textliste; Viewer per Tastatur bedienbar; keine automatische Videowiedergabe mit Ton; `prefers-reduced-motion`; Kontraste nach WCAG 2.2 AA |
| **DSGVO** | Keine Tracker im MVP; Schriften lokal; EU-Medienanbieter mit AVV; Personen in Aufnahmen unkenntlich machen; Datenexport und Kontolöschung (aus Marketplace) |
| **Sicherheit** | Policies, CSRF, Rate-Limits, Admin-MFA, signierte Upload-URLs (Uploads gehen direkt zum Anbieter), Content-Security-Policy mit erlaubten Medien-Domains, keine Secrets im Repo |
| **Caching** | Viewer-JSON pro Bahn im Cache, Invalidierung über Model-Events; Medien mit langen Cache-Zeiten und versionierten Dateinamen |
| **Mehrsprachigkeit** | UI über `lang/de`, `lang/en`; Inhalte (Beschreibungen) im MVP deutsch, Felder für spätere Übersetzung vorbereitet (siehe [DATA_MODEL.md](DATA_MODEL.md)) |
| **Einheiten** | Intern immer Meter; Anzeige Meter oder Yards je Nutzereinstellung |

---

## 10. Betrieb

| Umgebung | Wo | Zweck |
|---|---|---|
| Lokal | Herd oder Sail, SQLite, Medien-Platzhalter oder Bunny-Test-Zone | Entwicklung |
| CI | GitHub Actions | Qualitätstor für jeden PR |
| Staging | Subdomain auf Hostinger, eigene DB, eigene Medien-Zone, `noindex`, Passwortschutz | Abnahme vor Release |
| Produktion | Hostinger Business, Bunny CDN | Öffentlicher Betrieb |

Deployment manuell nach Freigabe (Muster aus dem Marketplace), Medien-Uploads unabhängig vom Code-Deployment. Sollten Speicher- oder Rechenbedarf wachsen, ist der Umzug auf einen kleinen VPS (z. B. mit Laravel Forge) möglich, ohne den Code zu ändern.
