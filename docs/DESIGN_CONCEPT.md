# Design- und UX-Konzept – My Golf Course

Status: **Konzept**. Farben und Schriften sind begründete Vorschläge; endgültig festgelegt werden sie im Prototyp (MGC-004) **mit echten Aufnahmen aus dem Pilot**, nicht mit Stockfotos.

---

## 1. Leitidee: das digitale Yardage Book

Golfer kennen das **Yardage Book**: ein kleines Heft mit präzisen Bahnzeichnungen, Distanzen, Höhenangaben und handschriftlichen Notizen. Es ist das ehrlichste Werkzeug zur Rundenvorbereitung – sachlich, genau, persönlich.

My Golf Course übersetzt dieses Werkzeug ins Digitale und ersetzt die Zeichnung durch echte Aufnahmen:

- **Bild zuerst.** Die Aufnahmen sind der Inhalt. Die Oberfläche tritt zurück wie ein guter Caddie – da, wenn man sie braucht.
- **Präzision als Ästhetik.** Zahlen sind gestalterisches Element: Distanzen, Höhen, Par in einer klaren Zahlenschrift, feine Linien, Maßketten und Messmarken wie auf einem Vermessungsplan.
- **Ruhe.** Wenige Farben, viel Raum, keine dekorativen Animationen. Nichts lenkt von der Bahn ab.

Was wir bewusst vermeiden: Verläufe und Glas-Effekte, generische Hero-Sektion mit Stockfoto und drei Icon-Kacheln, Golfklischees (Karo, Wappen, Goldschrift), knalliges Grün auf Weiß, Scroll-Animationen.

---

## 2. Markenelemente (Vorschlag)

### Farben

| Token | Name | Wert (Vorschlag) | Verwendung |
|---|---|---|---|
| `--color-paper` | Kreide | `#F4F1EA` | Seitenhintergrund – warmes Papierweiß statt reinem Weiß (Yardage-Book-Papier) |
| `--color-surface` | Weiß | `#FFFFFF` | Karten, Panels |
| `--color-ink` | Graphit | `#1C1F1D` | Text, dunkle Viewer-Oberfläche |
| `--color-ink-muted` | Schiefer | `#5E6661` | Sekundärtext |
| `--color-line` | Bleistift | `#D9D4C8` | Linien, Rahmen, Maßketten |
| `--color-brand` | Nadelwald | `#23443A` | Marke, Links, aktive Zustände – sparsam |
| `--color-signal` | Fahne | `#E4572E` | Messmarken, aktuelle Bahn, Fokus im Viewer – sichtbar auf Grün, Blau und Sand |
| `--color-sand` | Bunker | `#D8C8A4` | Akzentflächen, Hervorhebung von Hindernissen |
| `--color-water` | Teich | `#3E6E8E` | Wasserhindernisse |

Objekttypen werden nie **nur** über Farbe unterschieden, sondern immer zusätzlich über Symbol und Text (Barrierefreiheit).

### Schriften (alle Open Font License, lokal ausgeliefert)

| Rolle | Vorschlag | Warum | Alternative für den Prototyp |
|---|---|---|---|
| Überschriften | **Fraunces** (variabel) | Weiche, eigenständige Serif – Tradition und Scorecard-Gefühl, ohne altbacken zu wirken | Newsreader |
| Text und Bedienung | **Instrument Sans** | Klar, kompakt, gut lesbar auf kleinen Displays, weniger verbreitet als Inter | Hanken Grotesk |
| Zahlen und Messwerte | **IBM Plex Mono** (Ziffern) | Tabellarische Ziffern, technische Präzision – Distanzen stehen sauber untereinander | JetBrains Mono |

### Bildsprache

- Ausschließlich echte Aufnahmen der Plätze, keine Stockfotos.
- Einheitliche Lichtstimmung (tiefe Sonne, lange Schatten), einheitliche Flughöhen – ergibt sich aus dem Aufnahmeprotokoll ([MEDIA_PIPELINE.md](MEDIA_PIPELINE.md) § 2.2).
- Text auf Bildern nur mit ruhigem Verlauf am Rand oder auf eigener Fläche.

---

## 3. Signatur-Komponenten

Diese wiederkehrenden Elemente machen die Plattform unverwechselbar:

**Scorecard-Leiste** – Navigation zwischen den Bahnen im Format einer Scorecard (Bahnnummer oben, Par darunter). Aktuelle Bahn hervorgehoben. Desktop: Leiste unter dem Medienbereich; Handy: horizontal wischbar, unten fixiert.

```
 ┌───┬───┬───┬───┬───┬───┬───┬───┬───┐
 │ 1 │ 2 │ 3 │▌4▐│ 5 │ 6 │ 7 │ 8 │ 9 │ …
 │ 4 │ 5 │ 3 │ 4 │ 4 │ 3 │ 5 │ 4 │ 4 │
 └───┴───┴───┴───┴───┴───┴───┴───┴───┘
```

**Messmarke** – Marker in Panorama, Bahnbild und Video: kleiner Punkt, feine Führungslinie, Etikett mit Zahl in Mono-Schrift. Antippen öffnet die Details.

```
        ┌─────────┐
        │ ▲ 17 m  │   ← Typ-Symbol + Leitwert
        └────┬────┘
             │
             ●
```

**Distanzband** – Die Bahn als lineare Maßkette vom gewählten Abschlag bis zum Grün, Objekte als Markierungen. Zugleich barrierefreie Textalternative zum Viewer und das, was ein Yardage Book im Kern ausmacht.

```
 Abschlag Gelb ──── 182 m ──── Eiche (17 m) ──── 98 m ──── Bunker re. ──── 47 m ──── Grün
```

**Faktenzeile** – `Par 4 · 362 m · HCP 7 · Dogleg links` in Mono-Ziffern, immer an derselben Stelle.

**Modusschalter** – Segmentierter Schalter über dem Medienbereich: `Überflug | 360° | Plan`. Nicht verfügbare Modi erscheinen nicht.

---

## 4. Seiten und Abläufe

### 4.1 Startseite
- Volle Breite: ein ruhiges, echtes Luftbild (Standbild; Video nur nach Klick bzw. nie bei „weniger Bewegung“).
- Eine Zeile Haltung, z. B. *„Kenne die Bahn, bevor du abschlägst.“* – plus Suchfeld (Platzname, Ort).
- Darunter ausgewählte Plätze als große Bildkarten. Kein Feature-Kachel-Raster.

### 4.2 Platzübersicht (angelehnt an Reise- und Immobilienportale)
- Liste großer Karten: Luftbild, Name, Ort, `18 Bahnen · Par 72`, Kennzeichen für verfügbare Modi (360°, Überflug).
- Filter: Region, 9/18 Löcher, Medien verfügbar. Suche nach Name/Ort.
- Kartenansicht als Umschalter (Should, nicht MVP-kritisch; Kartendienst ohne automatische Datenübertragung an Dritte).

### 4.3 Platzdetailseite
1. Großes Luftbild (ca. 60 % der Bildschirmhöhe), Name, Ort, Faktenzeile.
2. Primäre Aktion **„Bahn 1 erkunden“**.
3. **Scorecard-Tabelle** aller Bahnen (Nr., Par, HCP, Längen je Abschlagfarbe) – jede Zeile führt zur Bahn. Sie ist Übersicht und Navigation zugleich.
4. Beschreibung, Kontakt, Website des Clubs, Aufnahmedatum („Aufgenommen im Mai 2027“).
5. Merken (Herz) für angemeldete Nutzer; Gäste werden zur Anmeldung eingeladen, ohne die Seite zu verlassen.

### 4.4 Bahnseite (Kernerlebnis)

**Desktop**
```
┌──────────────────────────────────────────────┬──────────────────┐
│                                              │ Bahn 4 · Par 4   │
│        Medienbereich (Überflug/360°/Plan)    │ 362 m · HCP 7    │
│        Modusschalter oben links              │ Abschlag: [Gelb▾]│
│        Vollbild oben rechts                  │──────────────────│
│                                              │ Distanzband      │
│                                              │──────────────────│
│                                              │ Hindernisse      │
│                                              │ ▲ Eiche  17 m    │
│                                              │ ◼ Bunker re.     │
│                                              │ ≈ Teich          │
├──────────────────────────────────────────────┴──────────────────┤
│ Scorecard-Leiste  1 2 3 [4] 5 6 7 8 9 | 10 … 18          ‹  ›   │
└─────────────────────────────────────────────────────────────────┘
```

**Smartphone**
- Medienbereich oben (ca. 60 % Höhe), Modusschalter als Overlay, Vollbild-Knopf.
- Darunter scrollbar: Faktenzeile, Abschlagwahl, Distanzband, Hindernisliste, Beschreibung.
- Scorecard-Leiste unten fixiert; Wischen über die Leiste wechselt Bahnen.
- Im Vollbild: Hindernisse als herausziehbares Bottom-Sheet.

**Interaktion zwischen Liste und Viewer:** Antippen eines Hindernisses in der Liste dreht das Panorama dorthin bzw. springt im Überflug an die passende Stelle. Antippen einer Messmarke hebt den Listeneintrag hervor.

### 4.5 Konto (einfach)
Anmeldung/Registrierung, Merkliste, zuletzt angesehen, Einstellungen (Meter/Yards, Standard-Abschlagfarbe, Sprache), Daten herunterladen, Konto löschen.

### 4.6 Admin
Filament mit Markenfarben, funktional statt gestaltet. Eigene Seite zum Setzen und Korrigieren von Markern direkt im Panorama.

---

## 5. Bewegung

- Nur zweckgebunden: Bahnwechsel als kurze Überblendung (200–300 ms), Panorama dreht weich zu einem gewählten Hindernis, Bottom-Sheet gleitet.
- Keine Scroll-Effekte, keine automatisch startenden Videos mit Ton, kein Lade-Intro.
- `prefers-reduced-motion`: keine Kamerafahrten, Überflug startet nie automatisch, Übergänge ohne Bewegung.

---

## 6. Barrierefreiheit (Ziel: WCAG 2.2 AA)

- Alle Informationen aus dem Viewer existieren auch als Text (Distanzband, Hindernisliste).
- Viewer per Tastatur bedienbar; Fokus sichtbar (Signalfarbe); Bedienelemente beschriftet.
- Mindestens 44 × 44 px Trefferfläche auf Touch-Geräten, Text mindestens 16 px auf dem Handy.
- Kontraste geprüft – besonders Text auf Bildern und Marker-Etiketten.
- Videos haben Bedienelemente, Pause jederzeit; Cue-Texte sind auch außerhalb des Videos lesbar.

---

## 7. Sprache und Ton

- Kurz, sachlich, golfkundig, nicht werblich. Fachbegriffe (Carry, Dogleg, Layup) mit kurzer Erklärung beim ersten Auftreten.
- Ansprache **„du“ oder „Sie“** ist eine Markenentscheidung → offene Frage, im Prototyp festlegen.

---

## 8. Leistungsbudget

| Seite | Ziel (Mittelklasse-Smartphone, 4G) |
|---|---|
| Start, Übersicht, Platzdetail | Largest Contentful Paint < 2,5 s; < 60 KB JavaScript |
| Bahnseite | Poster sichtbar < 2,5 s; Viewer interaktiv < 4 s; Panorama-Vorschau sofort, volle Auflösung nachgeladen |
| Bilder | AVIF/WebP mit JPEG-Fallback, responsive Größen, Lazy Loading unterhalb des sichtbaren Bereichs |

---

## 9. Vorgehen (MGC-004)

1. Moodboard aus eigenen Pilotaufnahmen, Yardage-Book-Beispielen, topografischen Karten.
2. Klickbarer Prototyp für **drei Schlüsselansichten** – Platzdetail, Bahnseite (Desktop + Handy), Platzübersicht – in Figma oder als statische HTML-Seiten.
3. Gemeinsame Entscheidung zu Farben, Schriften, Ansprache. Erst dann werden Tokens und Komponenten gebaut.
4. Änderungen am Designsystem danach nur in eigenen, kleinen PRs mit Screenshots.

Das vermeidet die Revert-Schleifen, die im Golf Marketplace beim Startseiten-Hero entstanden sind.
