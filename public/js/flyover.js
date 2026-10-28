/*
 * Überflug mit Haltepunkten (Modul Explorer): Das Drohnenvideo hält an den Positionen der Bahn an
 * (Abschlag, Landezone, vor dem Grün …) – Durchklicken wie in Street View, nur in Flugrichtung.
 * Hindernisse erscheinen als Karte, wenn die Drohne an ihnen vorbeifliegt.
 *
 * Zeiten werden nicht gepflegt, sondern berechnet: Anteil an der Strecke Abschlag → Grün (fraction, vom Server)
 * × Videolänge. Passt, wenn die Drohne mit gleichmäßigem Tempo vom Abschlag zum Grün fliegt.
 */
(() => {
    const video = document.querySelector('[data-flyover]');
    const dataElement = document.getElementById('explorer-data');
    if (!video || !dataElement) return;

    const flyover = JSON.parse(dataElement.textContent).flyover ?? {};
    const stops = flyover.stops ?? [];
    const cues = flyover.cues ?? [];
    const panel = video.closest('[data-mode-panel]');

    // Eigene Steuerung statt der Browser-Leiste
    video.removeAttribute('controls');

    const ui = document.createElement('div');
    ui.className = 'flyover-ui';
    ui.innerHTML = `
        <div class="flyover-card" data-card hidden></div>
        <div class="flyover-stop" data-stop hidden>
            <p class="flyover-stop__eyebrow" data-stop-step></p>
            <p class="flyover-stop__label" data-stop-label></p>
            <p class="flyover-stop__distance num" data-stop-distance></p>
            <div class="flyover-stop__actions">
                <button type="button" class="button button--primary" data-continue></button>
                <button type="button" class="button button--ghost" data-look>360° hier umsehen</button>
            </div>
        </div>
        <div class="flyover-bar">
            <button type="button" class="flyover-play" data-play aria-label="Abspielen">${playIcon()}</button>
            <div class="flyover-track" data-track role="slider" tabindex="0" aria-label="Position im Überflug" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="flyover-track__progress" data-progress></div>
            </div>
            <label class="flyover-toggle"><input type="checkbox" data-stop-mode checked> Anhalten</label>
        </div>
        <ol class="flyover-chips" data-chips></ol>
    `;
    panel.append(ui);

    const $ = (selector) => ui.querySelector(selector);
    const card = $('[data-card]');
    const stopBox = $('[data-stop]');
    const playButton = $('[data-play]');
    const track = $('[data-track]');
    const progress = $('[data-progress]');
    const stopMode = $('[data-stop-mode]');
    const chips = $('[data-chips]');

    let duration = 0;
    let stopTimes = [];
    let passed = new Set();
    let currentStop = null;

    const timeOf = (fraction) => Math.min(Math.max(fraction * duration, 0), Math.max(duration - 0.05, 0));

    function setup() {
        duration = video.duration || 0;
        if (!duration || !Number.isFinite(duration)) return;

        stopTimes = stops.map((stop) => timeOf(stop.fraction));

        // Markierungen auf der Zeitleiste: Haltepunkte (Strich) und Hindernisse (Punkt)
        track.querySelectorAll('.flyover-track__tick').forEach((el) => el.remove());
        stops.forEach((stop) => addTick('stop', stop.fraction, stop.label));
        cues.forEach((cue) => addTick('cue', cue.fraction, cue.title));

        chips.innerHTML = '';
        stops.forEach((stop, index) => {
            const li = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'flyover-chip';
            button.textContent = stop.label;
            button.addEventListener('click', () => goToStop(index));
            li.append(button);
            chips.append(li);
        });

        showStop(0);
    }

    function addTick(kind, fraction, title) {
        const tick = document.createElement('span');
        tick.className = `flyover-track__tick flyover-track__tick--${kind}`;
        tick.style.left = `${Math.min(Math.max(fraction, 0), 1) * 100}%`;
        tick.title = title;
        track.append(tick);
    }

    function showStop(index) {
        const stop = stops[index];
        if (!stop) return;
        currentStop = index;
        const isLast = index === stops.length - 1;

        $('[data-stop-step]').textContent = `Haltepunkt ${index + 1} von ${stops.length}`;
        $('[data-stop-label]').textContent = stop.label;
        $('[data-stop-distance]').textContent = stop.distance ? `${stop.distance} vom Abschlag` : '';
        $('[data-continue]').textContent = index === 0 ? 'Überflug starten' : isLast ? 'Nochmal ansehen' : 'Weiterfliegen';
        $('[data-look]').hidden = !stop.nodeId || !document.querySelector('[data-mode="explore"]');
        stopBox.hidden = false;
        markChip(index);
        updateCard();
    }

    function hideStop() {
        stopBox.hidden = true;
        currentStop = null;
    }

    function markChip(index) {
        chips.querySelectorAll('.flyover-chip').forEach((chip, i) => chip.classList.toggle('is-current', i === index));
    }

    function goToStop(index) {
        video.pause();
        video.currentTime = stopTimes[index] ?? 0;
        passed = new Set(stopTimes.map((t, i) => i).filter((i) => i <= index));
        showStop(index);
    }

    function play() {
        if (currentStop === stops.length - 1 && video.currentTime >= duration - 0.2) {
            video.currentTime = 0;
            passed = new Set([0]);
        }
        hideStop();
        video.play().catch(() => {});
    }

    // Aktives Hindernis: das nächste innerhalb von ± 2,5 s (bei kurzen Videos weniger)
    function updateCard() {
        if (!duration) return;
        const range = Math.min(2.5, duration * 0.06);
        let best = null;
        cues.forEach((cue) => {
            const distance = Math.abs(timeOf(cue.fraction) - video.currentTime);
            if (distance <= range && (!best || distance < best.distance)) best = { cue, distance };
        });

        if (!best) {
            card.hidden = true;
            return;
        }
        const { cue } = best;
        card.innerHTML = `<p class="flyover-card__type">${escapeHtml(cue.typeLabel)}</p>`
            + `<p class="flyover-card__title">${escapeHtml(cue.title)}</p>`
            + cue.details.map((line) => `<p>${escapeHtml(line)}</p>`).join('');
        card.dataset.poi = cue.poiId;
        card.hidden = false;
    }

    video.addEventListener('timeupdate', () => {
        if (!duration) return;
        progress.style.width = `${(video.currentTime / duration) * 100}%`;
        track.setAttribute('aria-valuenow', String(Math.round((video.currentTime / duration) * 100)));

        // Haltepunkt erreicht → anhalten (der erste liegt bei 0 und gilt als Start)
        if (!video.paused && stopMode.checked) {
            const index = stopTimes.findIndex((t, i) => i > 0 && !passed.has(i) && video.currentTime >= t - 0.1);
            if (index > 0) {
                passed.add(index);
                video.pause();
                video.currentTime = stopTimes[index];
                showStop(index);
            }
        }
        updateCard();
    });

    video.addEventListener('seeked', () => {
        if (!video.paused) passed = new Set(stopTimes.map((t, i) => i).filter((i) => stopTimes[i] <= video.currentTime + 0.1));
        updateCard();
    });
    video.addEventListener('play', () => { playButton.innerHTML = pauseIcon(); playButton.setAttribute('aria-label', 'Pause'); });
    video.addEventListener('pause', () => { playButton.innerHTML = playIcon(); playButton.setAttribute('aria-label', 'Abspielen'); });
    video.addEventListener('ended', () => showStop(stops.length - 1));

    playButton.addEventListener('click', () => (video.paused ? play() : video.pause()));
    video.addEventListener('click', () => (video.paused ? play() : video.pause()));
    $('[data-continue]').addEventListener('click', play);

    $('[data-look]').addEventListener('click', () => {
        const stop = stops[currentStop];
        if (!stop?.nodeId) return;
        video.pause();
        document.querySelector('[data-mode="explore"]')?.click();
        document.dispatchEvent(new CustomEvent('mgc:goto-node', { detail: { nodeId: stop.nodeId } }));
    });

    // Klick auf die Zeitleiste springt an die Stelle
    track.addEventListener('click', (event) => {
        const rect = track.getBoundingClientRect();
        const fraction = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);
        video.currentTime = fraction * duration;
        passed = new Set(stopTimes.map((t, i) => i).filter((i) => stopTimes[i] <= video.currentTime + 0.1));
        hideStop();
        updateCard();
    });
    track.addEventListener('keydown', (event) => {
        const step = { ArrowRight: 2, ArrowLeft: -2 }[event.key];
        if (!step) return;
        event.preventDefault();
        video.currentTime = Math.min(Math.max(video.currentTime + step, 0), duration);
    });

    // Karte antippen → Eintrag in der Hindernisliste hervorheben
    card.addEventListener('click', () => {
        const item = document.querySelector(`.hazard[data-poi="${card.dataset.poi}"]`);
        if (!item) return;
        document.querySelectorAll('.hazard.is-highlighted').forEach((el) => el.classList.remove('is-highlighted'));
        item.classList.add('is-highlighted');
        item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    if (video.readyState >= 1) setup();
    else video.addEventListener('loadedmetadata', setup, { once: true });

    function playIcon() {
        return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M7 4.5v15l12-7.5z" fill="currentColor"/></svg>';
    }

    function pauseIcon() {
        return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M7 4h3.5v16H7zM13.5 4H17v16h-3.5z" fill="currentColor"/></svg>';
    }

    function escapeHtml(text) {
        return String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    }
})();
