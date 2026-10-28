/*
 * Kleine Helfer für alle Seiten. Ohne Build-Schritt, ohne Abhängigkeiten.
 */
(() => {
    // Konto-Menü und Burger-Menü (<details data-dropdown>): Klick daneben oder Esc schließt; immer nur eins offen
    const dropdowns = [...document.querySelectorAll('[data-dropdown]')];
    document.addEventListener('click', (event) => {
        dropdowns.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) menu.open = false;
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        dropdowns.forEach((menu) => {
            if (menu.open) {
                menu.open = false;
                menu.querySelector('summary')?.focus();
            }
        });
    });
    dropdowns.forEach((menu) => menu.addEventListener('toggle', () => {
        if (menu.open) dropdowns.forEach((other) => { if (other !== menu) other.open = false; });
    }));

    // Startseite: Kopfzeile liegt transparent über dem Film und wird beim Scrollen hell
    const overlayHeader = document.querySelector('[data-overlay-header]');
    if (overlayHeader) {
        const update = () => overlayHeader.classList.toggle('is-scrolled', window.scrollY > 40);
        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    // Hintergrund-Film: bei „weniger Bewegung“ anhalten
    const heroVideo = document.querySelector('[data-hero-video]');
    if (heroVideo && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        heroVideo.removeAttribute('autoplay');
        heroVideo.pause();
    }

    // Merken ohne Neuladen (ohne JavaScript: normales Formular)
    document.querySelectorAll('[data-favorite-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('[data-favorite-button]');
            const label = form.querySelector('[data-favorite-label]');
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                if (!response.ok) throw new Error();
                const { saved } = await response.json();
                button.setAttribute('aria-pressed', saved ? 'true' : 'false');
                if (label) label.textContent = saved ? 'Gemerkt' : 'Merken';
            } catch {
                form.submit();
            } finally {
                button.disabled = false;
            }
        });
    });

    // Bahn-Notiz: speichert automatisch 1 s nach dem Tippen und beim Verlassen des Feldes
    const noteForm = document.querySelector('[data-note-form]');
    if (noteForm) {
        const field = noteForm.querySelector('[data-note-field]');
        const status = document.querySelector('[data-note-status]');
        let timer = null;
        let lastSaved = field.value;

        const save = async () => {
            clearTimeout(timer);
            if (field.value === lastSaved) return;
            const value = field.value;
            if (status) status.textContent = 'speichert …';
            try {
                const response = await fetch(noteForm.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(noteForm),
                });
                if (!response.ok) throw new Error();
                const result = await response.json();
                lastSaved = value;
                if (status) status.textContent = `· gespeichert ${result.at}`;
            } catch {
                if (status) status.textContent = '· nicht gespeichert – bitte erneut versuchen';
            }
        };

        field.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(save, 1000);
        });
        field.addEventListener('blur', save);
    }

    // Scorecard-Tabelle: ganze Zeile anklickbar (der Link in der ersten Spalte bleibt für Tastatur und Screenreader)
    document.querySelectorAll('.scorecard__row[data-href]').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('a')) return;
            window.location.href = row.dataset.href;
        });
    });

    // Abschlagwahl wechselt sofort
    document.querySelectorAll('[data-autosubmit]').forEach((input) => {
        input.addEventListener('change', () => input.form?.submit());
    });

    // Bahnseite: Umschalten zwischen 360° und Überflug
    const modeButtons = document.querySelectorAll('[data-mode]');
    modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const mode = button.dataset.mode;
            modeButtons.forEach((b) => {
                const active = b === button;
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-mode-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.modePanel !== mode;
            });
            const video = document.querySelector('[data-flyover]');
            if (video && mode !== 'flyover') video.pause();
            // Der 360°-Viewer misst seine Größe neu, wenn er wieder sichtbar wird
            if (mode === 'explore') window.dispatchEvent(new Event('resize'));
        });
    });

    // Plan-Modus: Marker auf der Bahngrafik → Eintrag in der Hindernisliste hervorheben
    document.querySelectorAll('[data-plan-poi]').forEach((pin) => {
        pin.addEventListener('click', () => {
            const item = document.querySelector(`.hazard[data-poi="${pin.dataset.planPoi}"]`);
            if (!item) return;
            document.querySelectorAll('.hazard.is-highlighted').forEach((el) => el.classList.remove('is-highlighted'));
            item.classList.add('is-highlighted');
            item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    // Vollbild im Überflug-Modus: Video selbst; im 360°-Modus übernimmt das explorer.js
    document.querySelector('[data-fullscreen]')?.addEventListener('click', () => {
        const video = document.querySelector('[data-mode-panel="flyover"]:not([hidden]) [data-flyover]');
        if (video?.requestFullscreen) video.requestFullscreen();
    });

    // Aktuelle Bahn in der Scorecard-Leiste sichtbar machen (Handy: Leiste ist wischbar)
    document.querySelector('.scorecard-strip__hole.is-current')?.scrollIntoView({ block: 'nearest', inline: 'center' });

    // Tastatur: [ und ] bzw. Bild-auf/-ab wechseln die Bahn (Pfeiltasten gehören dem 360°-Viewer)
    document.addEventListener('keydown', (event) => {
        if (event.altKey || event.ctrlKey || event.metaKey) return;
        if (event.target.closest('input, select, textarea, [contenteditable]')) return;
        const direction = { '[': 'previous', ']': 'next', PageUp: 'previous', PageDown: 'next' }[event.key];
        const link = direction && document.querySelector(`[data-hotkey="${direction}"]`);
        if (link) {
            event.preventDefault();
            window.location.href = link.href;
        }
    });
})();
