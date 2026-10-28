/*
 * Ausrichten einer 360°-Aufnahme (Admin): Panorama ohne Korrektur anzeigen, Nutzer klickt auf die Grünmitte.
 * Der angeklickte Blickwinkel geht an den Server, der daraus die Himmelsrichtung der Bildmitte berechnet
 * (App\Modules\Admin\Http\Controllers\PanoramaController::calibrate, Formel dort und in public/js/explorer.js).
 */
import { Viewer } from '@photo-sphere-viewer/core';

const dialog = document.querySelector('[data-calibrate-dialog]');
const container = document.querySelector('[data-calibrate-viewer]');
const status = document.querySelector('[data-calibrate-status]');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

let viewer = null;
let current = null;
let busy = false;

document.addEventListener('mgc:calibrate', ({ detail }) => {
    if (!detail?.image) {
        alert('Für diese Position gibt es kein Foto.');
        return;
    }
    current = detail;
    dialog.querySelector('[data-calibrate-title]').textContent = detail.label;
    status.textContent = 'Panorama wird geladen …';
    dialog.showModal();

    if (!viewer) {
        viewer = new Viewer({
            container,
            panorama: detail.image,
            navbar: ['zoom', 'fullscreen'],
            defaultZoomLvl: 40,
            loadingTxt: 'Wird geladen …',
        });
        viewer.addEventListener('ready', () => { status.textContent = 'Jetzt auf die Grünmitte bzw. die Fahne klicken.'; }, { once: true });
        viewer.addEventListener('click', ({ data }) => save(data.yaw));
    } else {
        // Ohne Korrektur laden, damit der Klick den Winkel im Bild liefert
        viewer.setPanorama(detail.image, { sphereCorrection: { pan: 0 }, position: { yaw: 0, pitch: 0 }, transition: false })
            .then(() => { status.textContent = 'Jetzt auf die Grünmitte bzw. die Fahne klicken.'; });
    }
});

async function save(yawRad) {
    if (!current || busy) return;
    busy = true;
    const yawDeg = (yawRad * 180) / Math.PI;
    status.textContent = 'Wird gespeichert …';

    try {
        const response = await fetch(current.calibrateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ yaw_deg: yawDeg }),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || 'Speichern fehlgeschlagen.');

        status.textContent = `${result.message} – Fenster kann geschlossen werden.`;
        document.dispatchEvent(new CustomEvent('mgc:calibrated', { detail: { id: current.id, heading: result.heading } }));
        setTimeout(() => dialog.close(), 1200);
    } catch (error) {
        status.textContent = error.message;
    } finally {
        busy = false;
    }
}

dialog?.querySelector('[data-calibrate-close]')?.addEventListener('click', () => dialog.close());
