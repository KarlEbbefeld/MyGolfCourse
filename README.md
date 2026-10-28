# My Golf Course

Digitale Golfplatzerkundung mit Drohnenüberflügen, 360°-Aufnahmen und vermessenen Hindernissen – damit Golfer jede Bahn kennen, bevor sie abschlagen.

> **Stand:** Erster Aufbau – Platzübersicht, Platzdetailseite mit Scorecard und Bahnseite mit Street-View-Navigation auf einem 18-Loch-Demoplatz (Platzhalter-Panoramen, vermessene Demo-Hindernisse). Admin-Bereich und echte Medien folgen.

## Ausprobieren

```powershell
& .\scripts\hostinger\deploy.cmd
```

Lädt alles auf Hostinger, richtet beim ersten Mal den Ordner, die `.env` und die SQLite-Datenbank ein und legt den Demoplatz an. Danach: `https://<domain>/MyGolfCourse` → „Golfplätze“ → „Golfclub Lippeauen (Demo)“ → „Bahn 1 erkunden“. Details in [docs/WORKFLOW.md](docs/WORKFLOW.md).

## Dokumentation

| Dokument | Inhalt |
|---|---|
| [docs/ANALYSE_GOLF_MARKETPLACE.md](docs/ANALYSE_GOLF_MARKETPLACE.md) | Was wir aus dem Golf Marketplace übernehmen, anpassen und bewusst nicht übernehmen |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Stack, Module, Verzeichnisstruktur, Rollen, SEO, Performance, DSGVO, Betrieb |
| [docs/DATA_MODEL.md](docs/DATA_MODEL.md) | Plätze, Bahnen, Abschläge, Medien, Aufnahmepositionen, Hindernisse, Messungen, Nutzer |
| [docs/MEDIA_PIPELINE.md](docs/MEDIA_PIPELINE.md) | Aufnahme, Verarbeitung und Darstellung von 360°, Überflug und Markern – Herausforderungen und Lösungen |
| [docs/DESIGN_CONCEPT.md](docs/DESIGN_CONCEPT.md) | Designsprache „digitales Yardage Book“, Signatur-Komponenten, Seitenaufbau |
| [docs/WORKFLOW.md](docs/WORKFLOW.md) | Git-Workflow, Commits, Pull Requests, Konfliktvermeidung, Umgebungsvariablen |
| [docs/PROJEKTPLAN.md](docs/PROJEKTPLAN.md) | Priorisierter MVP-Plan in sechs Phasen – **einzige Aufgabenliste** |
| [docs/DECISIONS.md](docs/DECISIONS.md) | Technische Entscheidungen mit Begründung und offenen Fragen |
| [CLAUDE.md](CLAUDE.md) | Regeln für KI-Agenten im Repository |

## Admin: echten Platz anlegen

```powershell
& .\scripts\hostinger\deploy.cmd                          # Code + Datenbank aktualisieren
& .\scripts\hostinger\create-admin.cmd name@beispiel.de   # Konto anlegen, Passwort wird einmal angezeigt
```

Dann `https://<domain>/MyGolfCourse/admin`:

1. **Neuer Platz** – Name, Ort, Lochzahl, Abschlagfarben, Lage per Klick auf die Karte.
2. **Bahn öffnen** – auf dem Satellitenbild Abschläge, Grün, Bäume, Bunker, Wasser setzen; Baumhöhen eintragen; speichern.
3. **Medien** – Überflug-Video, 360°-Fotos (DJI „Sphäre“, GPS wird aus dem Foto gelesen), optional Bahngrafik des Clubs.
4. **Ausrichten** – bei jedem 360°-Foto auf das Grün klicken.
5. **Veröffentlichen** (Rolle Admin).

Zustand auf dem Server prüfen: `& .\scripts\hostinger\status.cmd`

## Stack

Laravel 13 · PHP 8.3 · Blade · Plain JavaScript · Photo Sphere Viewer 5.15 + three.js (lokal in `public/vendor`) · SQLite (Staging) / MySQL · Hostinger. Geplant: Filament (Admin), Medienanbieter für Panoramen und Videos.

## Aufbau

| Bereich | Wo |
|---|---|
| Plätze, Bahnen, Abschläge | `app/Modules/Courses`, Seiten `resources/views/courses` |
| Street-View-Erkundung | `app/Modules/Explorer`, Seite `resources/views/explorer/hole.blade.php`, Viewer `public/js/explorer.js` |
| Hindernisse und Messungen | `app/Modules/Hazards` |
| Design | `public/css/tokens.css`, `public/css/app.css`, `public/css/explorer.css` |
| Demoplatz | `database/seeders/DemoCourseSeeder.php` |
| Deployment | `scripts/hostinger/` |

## Mitarbeiten

Wie beim Golf Marketplace: Feature-Branch, pushen, gegenseitig pullen, auf dem Hosting testen. `.env` niemals committen.
