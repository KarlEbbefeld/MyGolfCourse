/*
 * Street-View-Erkundung einer Bahn (Modul Explorer).
 *
 * Photo Sphere Viewer + Virtual-Tour-Plugin im GPS-Modus: Jede Aufnahmeposition ist ein Knoten mit Koordinate.
 * Pfeile zu den Nachbarpositionen und die Marker (Bäume, Bunker, Grün) berechnet das Plugin aus den
 * Koordinaten – Richtung aus der Peilung, Neigung aus dem Höhenunterschied. Blickrichtung 0 = Norden.
 *
 * Daten kommen aus <script id="explorer-data"> (App\Modules\Explorer\Support\HoleExplorer::toViewerArray).
 * Positionen ohne echte Aufnahme bekommen ein gezeichnetes Platzhalter-Panorama.
 */
import { Viewer } from '@photo-sphere-viewer/core';
import { MarkersPlugin } from '@photo-sphere-viewer/markers-plugin';
import { VirtualTourPlugin } from '@photo-sphere-viewer/virtual-tour-plugin';

/*
 * Kalibrierung echter Aufnahmen: heading_deg = Himmelsrichtung der Bildmitte (0 = Norden).
 * Photo Sphere Viewer zeigt einen Bildpunkt mit Blickwinkel y bei y − pan (Rotation um die Y-Achse,
 * siehe sphericalCoordsToVector3 + setSphereCorrection). Damit die Bildmitte bei der Peilung H liegt: pan = −H.
 * Gleiche Formel im Admin (public/js/admin/hole-editor.js → Kalibrierung).
 */
const HEADING_SIGN = -1;

const container = document.querySelector('[data-viewer]');
const dataElement = document.getElementById('explorer-data');

if (container && dataElement) {
    start(JSON.parse(dataElement.textContent)).catch((error) => {
        console.error(error);
        setStatus('Die Ansicht konnte nicht geladen werden.');
    });
}

async function start(data) {
    if (!hasWebGL()) {
        setStatus('Dein Browser kann die 360°-Ansicht nicht darstellen. Alle Informationen zur Bahn findest du unten.');
        return;
    }

    const nodes = await Promise.all(data.nodes.map(toTourNode));
    const startNode = nodes.find((node) => node.id === data.startNodeId) ?? nodes[0];

    const viewer = new Viewer({
        container,
        loadingTxt: 'Ansicht wird geladen …',
        defaultYaw: degToRad(startNode.data.defaultYawDeg ?? 0),
        defaultPitch: degToRad(startNode.data.defaultPitchDeg ?? 0),
        defaultZoomLvl: 30,
        navbar: ['zoom', 'caption', 'fullscreen'],
        mousewheelCtrlKey: false,
        lang: {
            zoom: 'Zoom',
            zoomOut: 'Herauszoomen',
            zoomIn: 'Hineinzoomen',
            moveUp: 'Nach oben',
            moveDown: 'Nach unten',
            moveLeft: 'Nach links',
            moveRight: 'Nach rechts',
            description: 'Beschreibung',
            download: 'Herunterladen',
            fullscreen: 'Vollbild',
            loading: 'Wird geladen …',
            menu: 'Menü',
            close: 'Schließen',
            twoFingers: 'Mit zwei Fingern bewegen',
            ctrlZoom: 'Strg + Mausrad zum Zoomen',
            loadError: 'Das Panorama konnte nicht geladen werden',
            webglError: 'Dein Browser unterstützt kein WebGL',
        },
        plugins: [
            [MarkersPlugin, {}],
            [VirtualTourPlugin, {
                positionMode: 'gps',
                renderMode: '3d',
                nodes,
                startNodeId: startNode.id,
                preload: true,
                transitionOptions: { showLoader: false, effect: 'fade', speed: '20rpm', rotation: true },
            }],
        ],
    });

    container.querySelector('[data-viewer-status]')?.remove();

    const markers = viewer.getPlugin(MarkersPlugin);
    const tour = viewer.getPlugin(VirtualTourPlugin);

    // Beim ersten Laden Richtung Grün schauen; danach dreht die Übergangsanimation in Laufrichtung
    let firstNode = true;
    tour.addEventListener('node-changed', ({ node }) => {
        if (firstNode) {
            firstNode = false;
            viewer.rotate({ yaw: degToRad(node.data.defaultYawDeg ?? 0), pitch: degToRad(node.data.defaultPitchDeg ?? 0) });
        }
    });

    // Marker angeklickt → passenden Eintrag in der Hindernisliste hervorheben
    markers.addEventListener('select-marker', ({ marker }) => {
        highlightHazard(marker.data?.poiId);
    });

    // „In der Ansicht zeigen“ in der Liste → Panorama zum Marker drehen
    document.querySelectorAll('[data-focus-marker]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.focusMarker;
            try {
                markers.gotoMarker(id, '10rpm').then(() => markers.showMarkerTooltip(id));
                container.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'nearest' });
            } catch {
                // Marker in dieser Position nicht vorhanden (z. B. ohne Koordinate)
            }
        });
    });

    // Aus dem Überflug „360° hier umsehen“ (public/js/flyover.js) → zur passenden Position springen
    document.addEventListener('mgc:goto-node', ({ detail }) => {
        if (!detail?.nodeId) return;
        // Der Viewer war im Überflug-Modus ausgeblendet und muss seine Größe neu messen
        requestAnimationFrame(() => {
            viewer.autoSize();
            tour.setCurrentNode(detail.nodeId).catch(() => {});
        });
    });

    // Vollbild nur im 360°-Modus; im Überflug-Modus macht das public/js/app.js mit dem Video
    document.querySelector('[data-fullscreen]')?.addEventListener('click', () => {
        if (!container.closest('[data-mode-panel]')?.hidden) viewer.toggleFullscreen();
    });
}

/** Datensatz aus Laravel → Knoten für das Virtual-Tour-Plugin. */
async function toTourNode(node) {
    const isPlaceholder = !node.panorama;
    // Handys: 4096-px-Fassung, weil viele Geräte keine 8K-Texturen darstellen können
    const small = window.matchMedia('(max-width: 64rem)').matches;
    const image = small && node.panoramaMobile ? node.panoramaMobile : node.panorama;

    return {
        id: node.id,
        name: node.name,
        caption: node.caption,
        panorama: isPlaceholder ? await placeholderPanorama(node.placeholder ?? {}) : image,
        gps: node.gps,
        // Platzhalter sind nach Norden gezeichnet; echte Aufnahmen werden um ihre Kalibrierung gedreht
        sphereCorrection: isPlaceholder ? undefined : { pan: HEADING_SIGN * degToRad(node.headingDeg ?? 0) },
        links: node.links,
        markers: node.markers.map(toMarker),
        data: {
            defaultYawDeg: node.defaultYawDeg,
            defaultPitchDeg: node.defaultPitchDeg,
        },
    };
}

/** Messmarke: Etikett mit Wert, feine Führungslinie, Punkt an der Koordinate (docs/DESIGN_CONCEPT.md § 3). */
function toMarker(marker) {
    const label = marker.label ? `<span class="mark__value">${escapeHtml(marker.label)}</span>` : '';
    const details = marker.details.map((line) => `<p>${escapeHtml(line)}</p>`).join('');

    return {
        id: marker.id,
        gps: marker.gps,
        html: `<span class="mark mark--${marker.type}">`
            + `<span class="mark__tag">${icon(marker.icon)}${label}</span>`
            + '<span class="mark__stem"></span><span class="mark__dot"></span></span>',
        anchor: 'bottom center',
        zIndex: marker.type === 'green' ? 20 : 10,
        tooltip: {
            content: `<div class="mark-tooltip"><p class="mark-tooltip__type">${escapeHtml(marker.typeLabel)}</p>`
                + `<p class="mark-tooltip__title">${escapeHtml(marker.title)}</p>${details}</div>`,
            position: 'top center',
            trigger: 'click',
        },
        data: { poiId: marker.poiId },
    };
}

function icon(name) {
    const paths = {
        tree: '<path d="M12 3c-3.3 0-6 2.6-6 5.9 0 1.2.4 2.3 1 3.2A4.5 4.5 0 0 0 10.5 19H12m0-16c3.3 0 6 2.6 6 5.9 0 1.2-.4 2.3-1 3.2A4.5 4.5 0 0 1 13.5 19H12m0-16v18"/>',
        bunker: '<path d="M3 15c2.5-4 6-6 9-6s6.5 2 9 6c-3 2-6 3-9 3s-6-1-9-3Z"/>',
        water: '<path d="M3 9c1.5 1.2 3 1.2 4.5 0s3-1.2 4.5 0 3 1.2 4.5 0 3-1.2 4.5 0M3 15c1.5 1.2 3 1.2 4.5 0s3-1.2 4.5 0 3 1.2 4.5 0 3-1.2 4.5 0"/>',
        oob: '<path d="M5 4v16M19 4v16M5 8h14M5 16h14"/>',
        flag: '<path d="M7 21V3l10 4-10 4"/>',
        point: '<circle cx="12" cy="12" r="4"/>',
    };

    return `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] ?? paths.point}</svg>`;
}

/*
 * Platzhalter-Panorama (equirektangular, 2:1), solange es keine echte Aufnahme gibt:
 * Himmel, Rasen, Baumreihe am Horizont, Fairway in Richtung Grün, Fahne am Grün.
 * Bildmitte = Norden, x wächst im Uhrzeigersinn (wie die Peilung).
 */
async function placeholderPanorama(hints) {
    const width = window.matchMedia('(max-width: 48rem)').matches ? 2048 : 4096;
    const height = width / 2;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    const xOf = (bearingDeg) => (((bearingDeg / 360) + 0.5) % 1 + 1) % 1 * width;
    const yOf = (pitchDeg) => (0.5 - pitchDeg / 180) * height;
    const horizon = yOf(0);
    const cameraHeight = Math.max(hints.cameraHeightM ?? 1.6, 0.5);
    // Peilung auf -180…180 bringen; „xu“ rechnet ohne Umbruch an der Bildnaht (Süden), gezeichnet wird dreifach versetzt
    const greenBearing = ((((hints.greenBearingDeg ?? 0) + 180) % 360) + 360) % 360 - 180;
    const greenDistance = hints.greenDistanceM ?? 150;
    const xu = (bearingDeg) => ((bearingDeg / 360) + 0.5) * width;

    // Himmel
    const sky = ctx.createLinearGradient(0, 0, 0, horizon);
    sky.addColorStop(0, '#5f8fb8');
    sky.addColorStop(0.75, '#a9c7dc');
    sky.addColorStop(1, '#dfe8e6');
    ctx.fillStyle = sky;
    ctx.fillRect(0, 0, width, horizon);

    // Rasen: am Horizont dunstig, unter der Kamera kräftig
    const grass = ctx.createLinearGradient(0, horizon, 0, height);
    grass.addColorStop(0, '#8fa784');
    grass.addColorStop(0.15, '#5f8a4c');
    grass.addColorStop(1, '#3d6a32');
    ctx.fillStyle = grass;
    ctx.fillRect(0, horizon, width, height - horizon);

    // Fairway: 30 m breites Band Richtung Grün, nach hinten schmaler (Perspektive)
    const steps = 60;
    const side = [];
    for (let i = 0; i <= steps; i++) {
        const distance = 2 + (greenDistance - 2) * (i / steps);
        const pitch = -Math.atan2(cameraHeight, distance) * 180 / Math.PI;
        const half = Math.atan2(15, distance) * 180 / Math.PI;
        side.push([pitch, half]);
    }
    const greenGroundPitch = -Math.atan2(cameraHeight, greenDistance) * 180 / Math.PI;
    const greenHalf = Math.atan2(14, greenDistance) * 180 / Math.PI;
    const flagTop = yOf(hints.greenPitchDeg ?? 1);

    for (const shift of [-width, 0, width]) {
        ctx.save();
        ctx.translate(shift, 0);

        ctx.fillStyle = 'rgba(160, 200, 120, 0.55)';
        ctx.beginPath();
        side.forEach(([pitch, half], i) => {
            const x = xu(greenBearing - half);
            if (i === 0) ctx.moveTo(x, yOf(pitch));
            else ctx.lineTo(x, yOf(pitch));
        });
        [...side].reverse().forEach(([pitch, half]) => ctx.lineTo(xu(greenBearing + half), yOf(pitch)));
        ctx.closePath();
        ctx.fill();

        // Grün und Fahne
        const poleX = xu(greenBearing);
        ctx.fillStyle = '#7fbf5a';
        ctx.beginPath();
        ctx.ellipse(poleX, yOf(greenGroundPitch), Math.max(greenHalf / 360 * width, 6), Math.max(height * 0.004, 3), 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#f4f1ea';
        ctx.lineWidth = Math.max(width / 2048, 1.5);
        ctx.beginPath();
        ctx.moveTo(poleX, yOf(greenGroundPitch));
        ctx.lineTo(poleX, flagTop);
        ctx.stroke();
        ctx.fillStyle = '#e4572e';
        ctx.beginPath();
        ctx.moveTo(poleX, flagTop);
        ctx.lineTo(poleX + width * 0.006, flagTop + height * 0.004);
        ctx.lineTo(poleX, flagTop + height * 0.008);
        ctx.closePath();
        ctx.fill();

        ctx.restore();
    }

    // Baumreihe am Horizont (deterministisch, ohne Zufall), mit Lücke in Spielrichtung
    ctx.fillStyle = '#2f4a2b';
    for (let deg = 0; deg < 360; deg += 0.5) {
        const offset = Math.abs(((deg - greenBearing + 540) % 360) - 180);
        if (offset < 6) continue;
        const crown = 1.2 + 1.1 * Math.abs(Math.sin(deg * 0.21) + 0.6 * Math.sin(deg * 0.57 + 1.3));
        const top = yOf(crown * (cameraHeight > 10 ? 0.4 : 1));
        ctx.fillRect(xOf(deg), top, width / 720 + 1, horizon - top + 2);
    }

    // Himmelsrichtungen und Hinweis „Platzhalter“ (hinter der Kamera, stört den Blick zum Grün nicht)
    ctx.fillStyle = 'rgba(28, 31, 29, 0.55)';
    ctx.textAlign = 'center';
    ctx.font = `600 ${Math.round(height * 0.02)}px "IBM Plex Mono", monospace`;
    [['N', 0], ['O', 90], ['S', 180], ['W', 270]].forEach(([letter, bearing]) => {
        ctx.fillText(letter, xOf(bearing), yOf(14));
    });
    ctx.font = `500 ${Math.round(height * 0.026)}px "Instrument Sans", sans-serif`;
    ctx.fillStyle = 'rgba(28, 31, 29, 0.6)';
    ctx.fillText(`Platzhalter · ${hints.label ?? ''}`, xOf(greenBearing + 180), yOf(22));

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.86));

    return URL.createObjectURL(blob);
}

function highlightHazard(poiId) {
    document.querySelectorAll('.hazard.is-highlighted').forEach((el) => el.classList.remove('is-highlighted'));
    if (!poiId) return;
    const item = document.querySelector(`.hazard[data-poi="${poiId}"]`);
    if (!item) return;
    item.classList.add('is-highlighted');
    item.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'nearest' });
}

function setStatus(text) {
    let status = container.querySelector('[data-viewer-status]');
    if (!status) {
        status = document.createElement('p');
        status.className = 'viewer__status';
        status.dataset.viewerStatus = '';
        container.append(status);
    }
    status.textContent = text;
}

function hasWebGL() {
    try {
        const canvas = document.createElement('canvas');
        return Boolean(canvas.getContext('webgl2') || canvas.getContext('webgl'));
    } catch {
        return false;
    }
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function degToRad(deg) {
    return (deg * Math.PI) / 180;
}

function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}
