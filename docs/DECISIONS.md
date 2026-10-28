# Technische Entscheidungen (ADR-light)

Jede Entscheidung: eine Zeile in der Tabelle, bei Bedarf ein Abschnitt mit Grund, Optionen und Auswirkungen. Änderungen nicht überschreiben, sondern neue Zeile mit Verweis.

Status: **Vorschlag** (zur Freigabe in MGC-002) · **Festgelegt** · **Offen** (Frage an das Team) · **Ersetzt**

| ID | Entscheidung | Status |
|---|---|---|
| D-001 | Arbeitsname „My Golf Course“, technischer Name `my-golf-course`; Markenname offen | Vorschlag |
| D-002 | Eigenständiges Projekt; Golf Marketplace nur als Referenz, Code wird kopiert und angepasst, nicht geteilt | Festgelegt (Projektauftrag) |
| D-003 | Laravel 13 / PHP 8.3+ als modularer Monolith, Module unter `app/Modules/` | Vorschlag |
| D-004 | Öffentliches Frontend: Blade + Vite; TypeScript nur für die Viewer-Insel; keine SPA | Vorschlag |
| D-005 | Admin mit Filament statt Eigenbau | Vorschlag |
| D-006 | 360°-Viewer: Photo Sphere Viewer v5 | Vorschlag, Bestätigung durch Spike MGC-032 |
| D-007 | Medien bei Bunny.net (Storage + CDN + Stream), Alternative Cloudflare R2 + Stream | Offen, Entscheidung in MGC-006 |
| D-008 | Panorama-Aufbereitung lokal mit `tools/media` (Node + sharp), nicht auf dem Webserver | Vorschlag |
| D-009 | Video-Upload direkt vom Browser zum Anbieter (TUS), nie über PHP | Vorschlag |
| D-010 | Datenbank: MySQL in Produktion, SQLite lokal/Tests, CI zusätzlich MySQL (wie Marketplace) | Vorschlag |
| D-011 | Geo-Daten als WGS84-Dezimalspalten + GeoJSON, keine DB-Geo-Typen | Vorschlag |
| D-012 | Maße intern in Metern, Anzeige Meter/Yards nach Nutzereinstellung | Vorschlag |
| D-013 | Marker: Geo-Koordinate ist führend, Platzierung in Medien automatisch berechnet, manuell überschreibbar | Vorschlag |
| D-014 | Marker im Video im MVP zeitbasiert (Cues); Telemetrie wird ab dem ersten Flug archiviert | Vorschlag |
| D-015 | Eigenes CSS mit Design-Tokens (Prinzip aus Marketplace), eigene Farben/Schriften | Vorschlag |
| D-016 | Schriften lokal ausgeliefert, keine externen Webfonts, keine Tracker im MVP | Vorschlag |
| D-017 | Responsive Web-App mit PWA-Funktionen; native App erst nach Validierung | Vorschlag |
| D-018 | Rollen per Enum `user`, `editor`, `admin`; Rechte-Paket erst bei Club-Managern | Vorschlag |
| D-019 | Trunk-basiert: kurzlebige Branches, Squash-Merge, geschützter `main`, kein `develop` | Ersetzt durch D-025 |
| D-020 | Conventional Commits mit Modul-Scope, Aufgaben-IDs `MGC-###` | Ersetzt durch D-025 (IDs `MGC-###` bleiben) |
| D-021 | Lokale PHP-Umgebung auf beiden Rechnern Pflicht (Herd oder Sail) | Ersetzt durch D-026 |
| D-022 | Hosting der App auf vorhandenem Hostinger Business (Annahme, Tarif prüfen) | Offen |
| D-023 | Inhalte im MVP nur Deutsch, UI-Texte de/en vorbereitet | Offen |
| D-024 | Ansprache „du“ oder „Sie“ | Offen; Texte vorerst mit „du“ |
| D-025 | Git wie beim Golf Marketplace: Feature-Branches, gegenseitig pushen/pullen, kein Pflicht-Review per PR (ersetzt D-019/D-020) | Festgelegt 27.10.2026 |
| D-026 | Nichts lokal ausführen; getestet wird durch Hochladen auf Hostinger (`scripts/hostinger/deploy.cmd`) und manuelles Prüfen (ersetzt D-021) | Festgelegt 27.10.2026 |
| D-027 | Kein Build-Schritt: CSS/JS direkt in `public/`, Photo Sphere Viewer + three.js als Dateien in `public/vendor` (Import-Map), Plain JavaScript statt TypeScript (ersetzt den Vite/TypeScript-Teil von D-004) | Festgelegt 27.10.2026 |
| D-028 | Staging zunächst mit SQLite (Datei außerhalb von `public_html`), MySQL vor dem öffentlichen Start | Festgelegt 27.10.2026 |
| D-029 | 360°-Navigation wie Street View: Virtual-Tour-Plugin im GPS-Modus, Pfeile und Marker aus Koordinaten berechnet; ohne Aufnahme zeichnet der Browser ein Platzhalter-Panorama | Festgelegt 27.10.2026 |
| D-030 | Admin (Filament) und Medienanbieter folgen nach dem ersten Hosting-Test; bis dahin pflegt der Demo-Seeder die Daten | Ersetzt durch D-031 |
| D-031 | Eigener schlanker Admin (Blade + Plain JS) statt Filament – ohne Build-Schritt und ohne Server-Installation testbar (ersetzt D-005) | Festgelegt 27.10.2026 |
| D-032 | Bahn-Editor auf Satellitenbild (Leaflet lokal, Kacheln Esri World Imagery, umschaltbar auf OpenStreetMap) – nur im Admin; Nutzungsbedingungen vor kommerziellem Start prüfen | Festgelegt 27.10.2026 |
| D-033 | Medien vorerst auf dem Hosting unter `public/media/courses/<platz>/bahn-<nr>/` (MediaStore); Wechsel zu einem CDN-Anbieter betrifft nur `App\Modules\Media` | Festgelegt 27.10.2026 |
| D-034 | Aufnahme: Überflug + 360° aus der Luft mit DJI Mini (Panoramamodus „Sphäre“), später Bodenebene mit 360°-Kamera am Golfcart; Hindernis-Koordinaten per Klick auf das Satellitenbild, Baumhöhen per Laser | Festgelegt 27.10.2026 |
| D-035 | Überflug-Haltepunkte und Hindernis-Hinweise werden aus Koordinaten berechnet (Anteil der Strecke Abschlag → Grün × Videolänge), keine gepflegten Zeiten | Festgelegt 27.10.2026 |
| D-037 | Neues Erscheinungsbild: Instrument Sans für Text und Überschriften (statt Fraunces), tiefes Grün + warmes Off-White, Gold nur als Akzent, Signalrot nur für Messmarken; Platzgrafik aus Koordinaten ersetzt fehlende Luftbilder; Startseite mit echtem Überflug im Hintergrund | Festgelegt 28.10.2026 |
| D-038 | Eine Anmeldung für alle unter `/anmelden` (Nutzer, Redaktion, Admin); Registrierung ohne Pflicht-Bestätigung der E-Mail, Passwort-Reset per Mail; Merkliste, zuletzt angesehen (Gäste nur in der Sitzung), Einheit m/yd, bevorzugter Abschlag, Datenexport, Kontolöschung | Festgelegt 28.10.2026 |
| D-039 | Vorteile mit Konto (Modul `Player`): „Mein Bag“ (Carry-Distanzen in users.settings, Spielplan je Bahn, Hinweise je Hindernis), private Notizen je Bahn (`hole_notes`), Runden-Tagebuch mit Statistik (`rounds`), Heimatplatz, druckbares Yardage Book; Gäste sehen Hinweise auf diese Vorteile | Festgelegt 28.10.2026 |
| D-040 | Community (Modul `Community`) nach dem Vorbild Golf Marketplace: Spielerprofile `/spieler/<nutzername>` (Standard privat, Art. 25 DSGVO), Club = Platz (`users.club_id`, Selbstauskunft, ein Club je Person, ersetzt „Heimatplatz“), Club-Seite mit Mitgliedern, Club-Chat je Club, Direktnachrichten (öffentliche Profile oder Freunde), Folgen/Freunde (gegenseitig), Blockieren, Melden + Moderation im Admin, Mitteilungen; Chat per Polling (3–15 s, nur sichtbarer Tab), keine WebSockets | Festgelegt 28.10.2026 |
| D-036 | Automatische Erkennung (Bunker/Wasser im Video, Auswertung von Bahngrafiken per KI) erst nach dem MVP, als Vorschläge mit menschlicher Bestätigung | Vorschlag |

---

### D-003 – Laravel statt JavaScript-Stack

- **Grund:** Das Team hat mit Laravel im Golf Marketplace in kurzer Zeit eine umfangreiche App gebaut; Hosting, Deploy-Skripte, Auth- und Sicherheitscode existieren. Die eigentliche Schwierigkeit von My Golf Course liegt im Medien-Viewer (JavaScript im Browser) und in der Medien-Infrastruktur – beides ist unabhängig vom Backend-Framework.
- **Optionen:** (a) Laravel + Blade, (b) Next.js + Headless-CMS/Supabase, (c) Laravel-API + SPA.
- **Gewählt:** (a). (b) würde ein neues Ökosystem und anderes Hosting erfordern, ohne beim Viewer zu helfen. (c) verdoppelt den Aufwand und schadet SEO.
- **Auswirkungen:** Viewer als eigenständiges TypeScript-Modul mit klarer JSON-Schnittstelle, damit er bei Bedarf später in eine App übernommen werden kann.

### D-005 – Filament für den Admin

- **Grund:** Der Admin ist reine Datenpflege mit vielen Relationen (Platz → Bahnen → Abschläge, Positionen, Objekte, Messungen) und Uploads. Filament liefert das fertig, inkl. Rechteprüfung über Policies.
- **Risiko:** Kompatibilität der aktuellen Filament-Version mit Laravel 13 beim Setup (MGC-017) prüfen. Filament bringt Tailwind/Livewire nur für den Admin mit; das öffentliche Frontend bleibt davon getrennt.
- **Ausnahme:** Marker-Platzierung im Panorama ist eine eigene Filament-Seite, die den gleichen Viewer wie die öffentliche Seite einbettet.

### D-007 – Medienanbieter (offen)

- **Anforderungen:** Speicher + CDN für große Bilder, Video-Transkodierung zu HLS, direkter Browser-Upload, Webhooks, EU-Datenverarbeitung bzw. AVV, geringe Fixkosten.
- **Kandidaten:** Bunny.net (EU-Firma, Storage + Stream aus einer Hand), Cloudflare (R2 ohne Traffic-Gebühren + Stream, US-Firma), Mux (sehr gute Video-API, teurer, nur Video).
- **Entscheidung** nach Test mit echten Pilotdaten (MGC-006). Der Code kapselt den Anbieter im Modul `Media`, ein Wechsel betrifft nur dieses Modul.
