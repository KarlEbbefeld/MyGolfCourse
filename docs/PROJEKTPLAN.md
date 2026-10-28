# Projektplan – My Golf Course

**Einzige zentrale Aufgabenliste.** Keine weiteren TODO-, Roadmap- oder Status-Dateien.

- `[ ]` offen · `[x]` auf `main` gemergt, CI grün, vom anderen Entwickler freigegeben
- Aktive Bearbeitung = GitHub-Issue mit gleicher ID ist zugewiesen
- Priorität: **M** = Muss für MVP-Launch · **S** = Soll (vor Launch, falls Zeit) · **K** = Kann (nach Launch)
- Aufwand in Personentagen (PT), grob geschätzt; wird nach Phase 2 anhand der tatsächlichen Geschwindigkeit nachgeschärft
- Zuständigkeit A/B gemäß Vorschlag in [WORKFLOW.md](WORKFLOW.md) § 4

## MVP-Definition

> Ein Besucher findet ohne Konto **ein bis drei echte Golfplätze**, erkundet **jede Bahn** per **Drohnenüberflug** und **360°-Positionen**, sieht **Hindernisse mit Messwerten** (z. B. Baumhöhen) in Bild und Liste und wechselt intuitiv zwischen den Bahnen – auf Handy und Desktop. Das Team pflegt Plätze, Bahnen, Medien und Hindernisse **ohne Code** im Admin. Registrierte Nutzer können Plätze merken.

Nicht im MVP: Community, Nachrichten, Runden/Matches, Bewertungen, Marketplace-Anbindung, 3D-Modelle, native Apps, automatische Objekterkennung.

---

## Phase 1 – Analyse und Konzeption

| ID | Aufgabe | Prio | PT | Wer | Abnahme |
|---|---|---|---|---|---|
| [ ] MGC-001 | Analyse Golf Marketplace | M | – | – | [ANALYSE_GOLF_MARKETPLACE.md](ANALYSE_GOLF_MARKETPLACE.md) von beiden gelesen |
| [ ] MGC-002 | Architektur und Stack freigeben | M | 0,5 | A+B | Offene Fragen in [DECISIONS.md](DECISIONS.md) beantwortet, Status „Festgelegt“ |
| [ ] MGC-003 | Datenmodell freigeben | M | 0,5 | A+B | [DATA_MODEL.md](DATA_MODEL.md) bestätigt oder angepasst |
| [ ] MGC-004 | Designprototyp (3 Schlüsselansichten, Handy + Desktop) | M | 4 | A | Farben, Schriften, Ansprache festgelegt; mit Pilotbildern geprüft |
| [ ] MGC-005 | **Aufnahme-Pilot**: 1 Platz, 2–3 Bahnen (Überflug, 360° Luft + Boden, Bahnbild, 5 Messungen) | M | 3 + Termin | A+B | Rechtliches geklärt (§ 2.3 [MEDIA_PIPELINE.md](MEDIA_PIPELINE.md)), Rohdaten archiviert, Aufnahmeprotokoll v1 |
| [ ] MGC-006 | Medienanbieter prüfen (Bunny vs. Cloudflare) mit Pilotmaterial | M | 1 | B | Testupload, Player-Test auf iPhone/Android, Kosten- und AVV-Vergleich, Entscheidung D-007 |

Phase 1 Gesamt: ca. 9 PT + Aufnahmetag(e). MGC-004/005/006 laufen parallel.

## Phase 2 – Technische Grundlage

| ID | Aufgabe | Prio | PT | Wer | Abnahme |
|---|---|---|---|---|---|
| [ ] MGC-010 | Erster Deploy auf Hostinger mit `scripts/hostinger/deploy.cmd` (Ersteinrichtung automatisch) | M | 0,5 | B | Demoplatz im Browser erreichbar |
| [ ] MGC-011 | Laravel-Projekt, Modulordner, `routes/modules` (Grundgerüst aus dem Marketplace) | M | 2 | B | Seiten laufen auf dem Hosting – **aufgebaut 27.10.2026, Test auf Hosting offen** |
| [ ] MGC-012 | GitHub-Repo anlegen, beide pushen/pullen | M | 0,5 | B | Beide haben den Stand |
| [ ] MGC-013 | Optional: GitHub-Prüfungen (keine `.env` im Repo, Tests) | K | 1 | B | – |
| [ ] MGC-014 | Design-Tokens, Schriften lokal, Basislayout, Header/Footer, Basis-Komponenten (Button, Link, Card, Badge, Formfelder) | M | 3 | A | Styleguide-Seite, Handy + Desktop geprüft |
| [ ] MGC-015 | Hostinger prüfen, Staging-Subdomain, Deploy-Skript (aus Marketplace angepasst) | M | 1,5 | B | Leere App läuft auf Staging per HTTPS, `noindex` |
| [ ] MGC-016 | Auth aus Marketplace übernehmen (Login, Reset, Verifizierung) + Rollen user/editor/admin | M | 2 | B | Tests übernommen und grün |
| [ ] MGC-017 | Filament-Admin unter `/admin`, Zugang nur editor/admin, Admin-Zweitfaktor, Audit-Log | M | 2 | B | Nicht-Admin bekommt 403; Tests |

Phase 2 Gesamt: ca. 13 PT.

## Phase 3 – Golfplatzportal

| ID | Aufgabe | Prio | PT | Wer | Abnahme |
|---|---|---|---|---|---|
| [ ] MGC-020 | Migrationen + Modelle Course, TeeSet, Hole, HoleTee; Factories; Demo-Seeder | M | 2 | B | Schema-Tests, Seeder erzeugt 18-Loch-Demoplatz |
| [ ] MGC-021 | Admin: Plätze, Abschlag-Sets, Bahnen (inkl. Längen je Abschlag), Status/Veröffentlichung | M | 2,5 | B | Platz ohne Code anlegbar; editor kann nicht veröffentlichen |
| [ ] MGC-022 | Platzübersicht: Karten-Liste, Suche, Filter Region/Lochzahl | M | 2 | A | Nur veröffentlichte Plätze, Pagination, Tests |
| [ ] MGC-023 | Platzdetailseite mit Faktenzeile und Scorecard-Tabelle | M | 2,5 | A | Wie Prototyp, Handy + Desktop |
| [ ] MGC-024 | Bahnseite Grundgerüst: Fakten, Abschlagwahl, Scorecard-Leiste, vor/zurück, Tastatur | M | 2,5 | A | Navigation 1→18, Poster als Platzhalter |
| [ ] MGC-025 | SEO: Slugs, Meta, `schema.org/GolfCourse`, Sitemap, Open-Graph-Bilder | M | 1 | A | Validierung mit Rich-Results-Test |
| [ ] MGC-026 | Einheiten Meter/Yards (Anzeige), Geo-Helfer (Distanz, Peilung) mit Unit-Tests | M | 1 | B | Unit-Tests |
| [ ] MGC-027 | Kartenansicht der Plätze | K | 2 | A | – |

Phase 3 Gesamt (M): ca. 13,5 PT.

## Phase 4 – Interaktive Medien

| ID | Aufgabe | Prio | PT | Wer | Abnahme |
|---|---|---|---|---|---|
| [ ] MGC-030 | Medien-Modul: MediaAsset/Variant, Bunny-Storage-Disk, Bunny-Stream-API, TUS-Upload aus Admin, Webhook | M | 4 | B | Video im Admin hochladen → Status „bereit“ |
| [ ] MGC-031 | `tools/media`: Panorama prüfen, EXIF lesen/entfernen, Fassungen, Upload, Anmeldung per API | M | 2,5 | B | Pilotpanoramen landen fertig im Admin |
| [ ] MGC-032 | **Spike** Photo Sphere Viewer: Virtual Tour (GPS-Modus), Marker, Handy-Performance | M | 1,5 | A | Kurzbericht + Entscheidung, Prototyp mit Pilotdaten |
| [ ] MGC-033 | Explorer-Modelle (Panorama, Links, Flyover, Cues) + Admin + JSON-Endpunkt `/api/v1/holes/{id}/explorer` | M | 3 | B | API-Tests, Cache-Invalidierung |
| [ ] MGC-034 | 360°-Modus im Viewer: Positionen, Wechsel, Startblick, Fallback ohne WebGL | M | 4 | A | Läuft auf iPhone, Android, Desktop; Playwright-Smoke-Test |
| [ ] MGC-035 | Überflug-Modus: HLS-Player, Play/Pause/Neustart/Vollbild, Poster, Cue-Karten, „Hier umsehen“ | M | 3 | A | Wie oben |
| [ ] MGC-036 | Hazards: POI, Messungen (mit Historie), Admin-Formular (handytauglich), Distanzband, Hindernisliste | M | 3 | B (Daten) / A (Anzeige) | Beispiel „Baumhöhe ca. 17 m (± 1 m)“ sichtbar |
| [ ] MGC-037 | Automatische Marker-Platzierung aus GPS + Nordkalibrierung per Klick | M | 2,5 | B | Pilotmarker liegen ohne Handarbeit ungefähr richtig |
| [ ] MGC-038 | Admin-Seite „Marker setzen/korrigieren“ im Panorama und Bahnbild | M | 2,5 | B | Klick/Ziehen speichert yaw/pitch bzw. x/y |
| [ ] MGC-039 | Marker im Viewer (Messmarke, Details, Verknüpfung mit Liste), Plan-Modus | M | 2,5 | A | Liste ↔ Viewer synchron |
| [ ] MGC-040 | Medien-Performance: Lazy Loading, Varianten nach Gerät, Vorschau zuerst, Budget prüfen | M | 1,5 | A | Budget aus Designkonzept § 8 erreicht |
| [ ] MGC-041 | Kachel-Panoramen | K | 2 | B | – |

Phase 4 Gesamt (M): ca. 30 PT. **Größte und riskanteste Phase** – deshalb Spike (MGC-032) und Pilotdaten vorher.

## Phase 5 – Administration und Nutzerfunktionen

| ID | Aufgabe | Prio | PT | Wer | Abnahme |
|---|---|---|---|---|---|
| [ ] MGC-050 | Admin-Feinschliff: Vorschau unveröffentlichter Plätze, Pflicht-Checks vor Veröffentlichung (Datenschutzprüfung Medien, Pflichtfelder) | M | 2 | B | Veröffentlichen blockiert bei fehlender Prüfung |
| [ ] MGC-051 | Öffentliche Registrierung + Profil + Einstellungen (Einheit, Abschlagfarbe, Sprache) | S | 2 | B | Tests |
| [ ] MGC-052 | Favoriten (Plätze merken) inkl. Merkliste | S | 1,5 | A | Tests, Gast-Hinweis |
| [ ] MGC-053 | Zuletzt angesehen (Gäste lokal, Nutzer serverseitig) | K | 1 | A | – |
| [ ] MGC-054 | DSGVO: Datenexport, Kontolöschung (aus Marketplace), Löschregeln | M | 1 | B | Tests |

Phase 5 Gesamt (M+S): ca. 8,5 PT.

## Phase 6 – Optimierung und Veröffentlichung

| ID | Aufgabe | Prio | PT | Wer | Abnahme |
|---|---|---|---|---|---|
| [ ] MGC-060 | Testlücken schließen, Playwright-Smoke-Tests für alle Kernseiten | M | 2 | A+B | CI grün |
| [ ] MGC-061 | Barrierefreiheit prüfen (Tastatur, Screenreader, Kontrast) | M | 1,5 | A | Prüfprotokoll im PR |
| [ ] MGC-062 | Performance + Caching (HTML-Cache öffentlicher Seiten, Cache-Header, Bildformate) | M | 1,5 | A+B | Budget erfüllt |
| [ ] MGC-063 | Sicherheitsprüfung: Header, CSP mit Medien-Domains, Rate-Limits, Rechte-Negativtests | M | 1,5 | B | Checkliste |
| [ ] MGC-064 | Rechtliches: Impressum, Datenschutzerklärung (inkl. Medienanbieter), Bildrechte-Vereinbarung mit Clubs | M | 1 + extern | A+B | Rechtlich geprüft |
| [ ] MGC-065 | Produktion: Domain, HTTPS, Backups (DB + Medien-Metadaten), Uptime-Überwachung, Release `v1.0.0` | M | 1,5 | B | Restore einmal getestet |
| [ ] MGC-066 | Inhalte: 1–3 Plätze vollständig aufgenommen, vermessen und gepflegt | M | je Platz 2–4 + Aufnahme | A+B | Alle Bahnen mit Überflug + 360° |
| [ ] MGC-067 | PWA: Manifest, Icons, Service Worker (aus Marketplace angepasst) | S | 1 | A | Installierbar auf iOS/Android |

Phase 6 Gesamt: ca. 11 PT + Inhaltsproduktion.

---

## Zusammenfassung

| Phase | Entwicklung (PT, grob) |
|---|---|
| 1 Konzeption | ~9 + Aufnahmetag |
| 2 Grundlage | ~13 |
| 3 Portal | ~13,5 |
| 4 Medien | ~30 |
| 5 Admin & Nutzer | ~8,5 |
| 6 Launch | ~11 + Inhalte |
| **Gesamt** | **~85 PT** (≈ 42 Arbeitstage je Entwickler) zzgl. Aufnahme und Pflege der Plätze |

Bei Teilzeit (z. B. 2 Tage pro Woche und Person) entspricht das grob 5 Monaten. Puffer für Unbekanntes in Phase 4 einplanen.

## Kritischer Pfad

```
MGC-005 Pilot ─┬─▶ MGC-006 Anbieter ─▶ MGC-030 Medien-Modul ─┐
               ├─▶ MGC-004 Design ─▶ MGC-014 Tokens ─▶ MGC-023/024 Seiten ─┤
               └─▶ MGC-032 Viewer-Spike ───────────────────────────────────┴─▶ MGC-034/035/039 Viewer ─▶ Launch
MGC-010/011 Setup ─▶ MGC-020 Modelle ─▶ MGC-021 Admin ─▶ MGC-033/036 Daten ─────┘
```

## Später (nach Validierung der Kernplattform, nicht einplanen)

Community (Clubzugehörigkeit, Profile, Kontakte) · Nachrichten und Benachrichtigungen · Runden-/Matchplanung · Platzbewertungen und Erfahrungsberichte · Statistiken · Marketplace-Anbindung · Club-Manager-Zugänge · Mehrsprachige Inhalte · 3D/Photogrammetrie · Geo-Marker im Video · Offline-Platzpaket · native App-Hülle
