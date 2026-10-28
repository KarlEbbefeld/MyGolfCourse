# Medien: Aufnahme, Verarbeitung, Darstellung

Dieses Dokument behandelt die technisch schwierigsten Teile von My Golf Course: 360°-Aufnahmen, Drohnenüberflüge und interaktive Markierungen. Es beschreibt Herausforderungen und die vorgeschlagenen Lösungen für MVP und später.

Status: **Konzept**. Die Annahmen werden mit einem **Aufnahme-Pilot** (MGC-005: ein Platz, zwei bis drei Bahnen) geprüft, bevor die Medien-Module gebaut werden.

---

## 1. Drei Darstellungsmodi, eine Datengrundlage

| Modus | Was der Nutzer sieht | Medium | MVP |
|---|---|---|---|
| **Überflug** | Drohnenvideo Abschlag → Grün, mit eingeblendeten Infokarten | HLS-Video + Cue-Punkte | ✅ |
| **360°** | Frei umsehen an mehreren Positionen entlang der Bahn, wie Street View | Equirectangular-Panoramen + Verbindungen | ✅ |
| **Plan** | Ein Standbild der Bahn mit Markern (Yardage-Book-Ansicht) | Schrägluftbild hinter dem Abschlag | ✅ |
| 3D | Frei fliegen im Modell | Photogrammetrie / 3D-Tiles | ⏳ später |

**Kombination:** Alle Modi zeigen dieselben Objekte (`points_of_interest`). Der Überflug ist der Einstieg („Bahn verstehen“), 360° ist die Vertiefung („genau hinsehen“). Cue-Punkte im Video können direkt in eine 360°-Position springen („Hier umsehen“), und jede 360°-Position kann den Überflug ab der passenden Stelle starten. So entsteht ein interaktiver Kamerapfad, ohne ein echtes 3D-Modell zu bauen.

---

## 2. Aufnahme vor Ort

### 2.1 Pro Bahn (Vorschlag für den Pilot)

| Aufnahme | Anzahl | Wie |
|---|---|---|
| Überflug | 1 | 4K, 40–70 s, Start hinter dem Abschlag in ca. 10 m Höhe, steigend auf 30–50 m, konstante Geschwindigkeit, endet mit Blick über das Grün. Möglichst als **Wegpunkt-Mission** speichern → später identisch wiederholbar. |
| 360° Luft | 3–5 | Drohnen-Panoramamodus („Sphere“) oder 360°-Drohne; Positionen: über Abschlag, Landezone, Layup/Dogleg, vor dem Grün; Höhe 20–40 m |
| 360° Boden | 1–2 | 360°-Kamera auf Stativ in Augenhöhe (ca. 1,6 m) am Abschlag und am Grün – zeigt die echte **Spielerperspektive**, z. B. wie hoch ein Baum vom Abschlag aus wirkt |
| Bahnbild | 1 | Standbild hinter dem Abschlag in großer Höhe (max. 120 m), schräg nach unten, ganze Bahn sichtbar |
| Vermessung | je Objekt | Siehe § 5 |

Für jede Aufnahme: **Telemetrie aktivieren** (bei DJI „Videountertitel“/`.SRT`: Position, Höhe, Kamerawinkel je Frame), Startpunkt der Drohne notieren (relative Höhe!), Originaldateien unverändert archivieren.

### 2.2 Standardisierung

Ein festes **Aufnahmeprotokoll** (Checkliste je Bahn, MGC-005) sorgt dafür, dass alle Plätze gleich aussehen: gleiche Tageszeit (tiefe Sonne, keine harten Mittagsschatten), gleiche Flughöhen, gleiche Videolänge. Das ist für die Markenwirkung wichtiger als die Kamera.

### 2.3 Rechtliches und Organisatorisches (vor dem ersten Flug klären)

Nicht-technisch, aber projektkritisch – bitte vor dem Pilot durch eine sachkundige Stelle bestätigen lassen:

- **Drohnenbetrieb in der EU/Deutschland:** Registrierung als Betreiber beim Luftfahrt-Bundesamt, Kompetenznachweis (mind. A1/A3), Haftpflichtversicherung (in Deutschland Pflicht), Geozonen prüfen (z. B. über dipul.de: Naturschutzgebiete, Flugplatznähe, Wohngrundstücke), maximale Flughöhe 120 m, keine Flüge über unbeteiligte Personen.
- **Gewerbliche Nutzung:** Die Aufnahmen dienen einer kommerziellen Plattform – Versicherung und Kategorie entsprechend wählen.
- **Einverständnis des Golfclubs** als Grundstückseigentümer und **Vertrag über Bildrechte** (wer darf die Aufnahmen wie lange nutzen?). Feld `media_assets.copyright/license`.
- **Personen und Kennzeichen** in Aufnahmen: Flüge möglichst bei geschlossenem Platz bzw. früh morgens; erkennbare Personen/Kennzeichen vor Veröffentlichung unkenntlich machen. Feld `media_assets.privacy_reviewed_at` ist Pflicht für die Veröffentlichung.

---

## 3. Verarbeitung

### 3.1 Panoramen

**Herausforderung:** Ein 8K-Panorama (8192 × 4096) hat 10–25 MB und braucht unkomprimiert rund 130 MB Arbeitsspeicher. Viele Smartphones können nur Texturen bis 4096 px Kantenlänge darstellen. Shared Hosting kann solche Bilder nicht zuverlässig verarbeiten.

**Lösung:**
1. Verarbeitung **lokal** mit einem Werkzeug im Repo: `tools/media` (Node.js + `sharp`; Node ist auf beiden Rechnern vorhanden).
2. Das Werkzeug prüft Format (2:1), liest GPS/Zeit aus EXIF, **entfernt danach Metadaten**, erzeugt Fassungen und lädt sie zu Bunny Storage hoch:
   - `preview` 1024 × 512, stark komprimiert (sofort sichtbar, ~60 KB)
   - `pano_4k` 4096 × 2048 (Smartphones, ~1,5–3 MB)
   - `pano_8k` 8192 × 4096 (Desktop)
   - später `pano_tiles`: Kacheln für Photo Sphere Viewer (`EquirectangularTilesAdapter`), lädt nur den sichtbaren Ausschnitt in voller Auflösung
3. Anschließend meldet das Werkzeug das Asset per API an Laravel (Admin-Token), dort erscheint es zur Zuordnung im Admin.

Der Viewer wählt die Fassung nach Gerät (Bildschirmbreite, `devicePixelRatio`, WebGL-`MAX_TEXTURE_SIZE`) und zeigt immer zuerst `preview`.

### 3.2 Videos

**Herausforderung:** 4K-Drohnenvideos sind mehrere hundert MB groß, müssen in mehrere Qualitätsstufen umgewandelt werden und dürfen nicht über PHP hochgeladen werden (Upload-Limits, Zeitlimits auf Shared Hosting).

**Lösung:**
- Upload **direkt aus dem Admin-Browser zu Bunny Stream** per TUS (fortsetzbar). Laravel erzeugt nur eine kurzlebige, signierte Upload-Berechtigung.
- Bunny transkodiert in mehrere Stufen (HLS), erzeugt Vorschaubilder und meldet per **Webhook** „fertig“ → `media_assets.status = ready`.
- Wiedergabe mit eigenem Player auf Basis von `<video>` + `hls.js` (Safari spielt HLS nativ). Eigener Player statt eingebettetem Anbieter-Player, weil Cue-Karten, Bahnwechsel und Design voll kontrolliert werden müssen.
- Ton: Drohnenvideos ohne Originalton ausliefern; ein Autoplay ist dadurch stumm erlaubt, wird aber nur nach Nutzeraktion gestartet (Datenvolumen, Barrierefreiheit).

### 3.3 Archiv

Originale (RAW-Fotos, Original-Video, `.SRT`-Telemetrie) gehören nicht ins CDN, sondern in ein günstiges Archiv (externe Festplatte + Cloud-Archiv). Sie werden für spätere Nachbearbeitung, Photogrammetrie oder 3D gebraucht.

---

## 4. Interaktive Markierungen

### 4.1 Grundprinzip: Geo zuerst, manuell korrigieren

Jedes Objekt hat (wenn bekannt) eine echte Koordinate. Jede 360°-Position hat ihre Aufnahmekoordinate, Flughöhe und eine Nordausrichtung. Daraus berechnet das System, **wo** das Objekt im Panorama liegt:

```
yaw   = Peilung(Panorama → Objekt) − Nord-Korrektur des Panoramas
pitch = atan2( (Höhe Objekt + Objekthöhe) − (Bodenhöhe Panorama + Flughöhe),
               horizontale Distanz )
```

Das ergibt für alle Panoramen einer Bahn automatisch Marker (`placements.is_auto = true`). Photo Sphere Viewer unterstützt im Virtual-Tour-Plugin bereits einen GPS-Modus für Verbindungspfeile; ob Marker ebenfalls direkt per GPS gesetzt werden können, prüft der technische Spike (MGC-032).

### 4.2 Herausforderungen und Lösungen

| Herausforderung | Lösung |
|---|---|
| **Nordausrichtung** eines Drohnenpanoramas ist ungenau oder unbekannt | Kalibrierung mit einem Klick im Admin: „Klicke auf die Fahne/Grünmitte“ → aus der bekannten Grün-Koordinate wird `heading_offset_deg` berechnet. |
| **GPS-Ungenauigkeit** (Smartphone ± 3–5 m, Drohne ähnlich) | Bei 100 m Abstand sind das nur rund 2–3° Winkelfehler – meist ausreichend. In der Nähe der Aufnahmeposition größer → manuelle Korrektur per Ziehen im Admin (`is_auto = false`, wird nie automatisch überschrieben). |
| **Relative Flughöhe**: Drohnen messen Höhe relativ zum Startpunkt | Startpunkt-Höhe je Flug erfassen; Geländehöhen aus öffentlichen Höhenmodellen (in Deutschland stellen viele Länder DGM-Daten offen bereit) als Ergänzung prüfen. |
| **Objekte ohne Koordinate** | Rein manuelle Platzierung per Klick im Panorama bzw. Bahnbild; Koordinate kann später nachgetragen werden. |
| **Verdeckung** (Marker eines Objekts hinter einer Baumgruppe) | Ohne 3D-Modell nicht automatisch lösbar. Marker je Panorama einzeln aus-/einblendbar; `importance` steuert, was standardmäßig sichtbar ist. |
| **Zu viele Marker** auf dem Handy | Höchstens ~10–15 sichtbare Marker je Ansicht, Rest in der Liste; Marker als leichte HTML-Elemente mit Kurzlabel („17 m“), Details erst beim Antippen. |
| **Marker im fahrenden Video** | Positionsgenaues Mitführen erfordert die Kamerapose je Frame. **MVP:** zeitbasierte Infokarten (Cues), die zur passenden Sekunde erscheinen. **Später:** Aus `.SRT`-Telemetrie (Position, Höhe, Gimbalwinkel) die Kamerapose berechnen und Geo-Objekte ins Videobild projizieren – Daten dafür werden ab dem ersten Flug mitgespeichert. |
| **Plan-Ansicht** ohne georeferenziertes Bild | **MVP:** Schrägluftbild mit manuell gesetzten Markern (x/y relativ). **Später:** Orthofoto aus Photogrammetrie (z. B. WebODM, Open Source) → Marker automatisch aus Koordinaten. |
| **Platz verändert sich** (neuer Bunker, gefällter Baum) | Neue Aufnahme ersetzt Asset; geo-basierte Marker übertragen sich automatisch; `courses.capture_date` und Messdatum zeigen dem Nutzer die Aktualität. |

---

## 5. Vermessung von Bäumen und Hindernissen

| Methode | Typische Genauigkeit (vor dem Pilot mit eigenen Messungen bestätigen) | Einsatz |
|---|---|---|
| Laser-Entfernungsmesser mit Höhenfunktion (Forst-/Jagdgeräte) | etwa ± 0,5–1 m, abhängig von Sicht auf Spitze und Stamm | Standard für Baumhöhen |
| Neigungsmesser + Entfernung (Smartphone-App) | grob, ± 1–2 m | Schnellerfassung, Plausibilisierung |
| Drohne auf Höhe der Baumspitze, relative Höhe ablesen | grob, abhängig vom Barometer | Ergänzung bei unzugänglichen Bäumen |
| Photogrammetrie (Höhenmodell aus Luftbildern) | gut für viele Objekte auf einmal | später, wenn Orthofotos entstehen |
| GPS-Position: Smartphone / RTK-Empfänger | ± 3–5 m / Zentimeter | Position von Objekten, Abschlägen, Grün |

**Regeln für die Anzeige:**
- Immer „ca.“ und Genauigkeit: *„Baumhöhe: ca. 17 m (± 1 m, gemessen 05/2027)“*.
- Mehrere Messungen bleiben als Historie erhalten (`measurements.superseded_at`), angezeigt wird die jüngste.
- Bäume wachsen und verändern sich mit der Jahreszeit: Messdatum anzeigen, wichtige Bäume jährlich prüfen.
- Strategischer Mehrwert entsteht erst in Kombination: Höhe + Distanz vom gewählten Abschlag + Distanz bis zum Grün → *„Eiche: 17 m hoch, 182 m vom gelben Abschlag, danach noch 145 m bis Grünmitte“*.
- Erfassung vor Ort im MVP über ein einfaches Formular im Admin (auf dem Handy nutzbar). Eine eigene Erfassungs-App ist nicht nötig.

---

## 6. Darstellung im Browser (Viewer-Insel)

- `resources/js/explorer/` (TypeScript), lädt **nur auf Bahnseiten** und erst, wenn der Nutzer den Medienbereich erreicht oder anklickt.
- Photo Sphere Viewer v5 mit Plugins: Markers, Virtual Tour, Compass (optional), später Equirectangular Tiles.
- Ein Daten-Endpunkt je Bahn: `GET /api/v1/holes/{id}/explorer` (siehe [ARCHITECTURE.md](ARCHITECTURE.md) § 6).
- Bedienung: Ziehen/Wischen zum Umsehen, Pfeile/Punkte zum Positionswechsel, Tastatur (Pfeiltasten drehen, `+`/`−` zoomen, `[`/`]` Bahn wechseln), Vollbild.
- Fallback ohne WebGL oder bei Fehlern: Bahnbild + Hindernisliste (die Seite bleibt immer nutzbar).

---

## 7. Datenmengen und Kosten (Größenordnung, Preise vor MGC-006 prüfen)

Annahmen je 18-Loch-Platz: 18 Videos à 60 s, 5 Panoramen je Bahn.

| Posten | Menge je Platz |
|---|---|
| Panoramen (alle Fassungen) | 90 × ~15 MB ≈ **1,4 GB** |
| Videos (alle Qualitätsstufen) | 18 × ~300–500 MB ≈ **5–9 GB** |
| Bahnbilder, Poster | < 0,5 GB |
| **Summe Speicher** | **≈ 7–11 GB je Platz** |

Traffic je angesehener Bahn (Handy, 1080p-Video + drei 4K-Panoramen): etwa 40–50 MB. Bei 5.000 Bahnansichten im Monat ≈ 250 GB.

Bei europäischen CDN-Preisen im Bereich von etwa 1 Cent pro GB und ähnlichen Speicherpreisen liegen Speicher und Auslieferung für die ersten Plätze im **niedrigen einstelligen Euro-Bereich pro Monat**. Der Kostentreiber ist Traffic, nicht Speicher; er wächst erst mit echtem Erfolg. Hosting der Laravel-App bleibt unverändert (Hostinger).

---

## 8. Später (nicht im MVP)

- Kachel-Panoramen für schärfere Details bei kleinem Datenvolumen
- Geo-projizierte Marker im Video aus Telemetrie
- Orthofoto/Plan aus Photogrammetrie, Platzkarte mit allen Bahnen
- 3D: Photogrammetrie → 3D-Tiles (CesiumJS) oder neuere Verfahren wie Gaussian Splatting; erst evaluieren, wenn die Kernplattform validiert ist
- Offline-Paket eines Platzes für die Runde (PWA-Cache)
- „Wo bin ich?“: Standort auf der Bahn per GPS des Handys, Distanzen live
