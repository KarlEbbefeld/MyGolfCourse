/*
 * Zähler in Kopfzeile, Konto-Menü und Menüleiste (Nachrichten, Mitteilungen) ohne Neuladen aktualisieren
 * (Muster aus dem Golf Marketplace):
 * - fragt alle 15 s ab, nur bei sichtbarem Tab; beim Zurückkehren zum Tab sofort
 * - Glocke (und roter Punkt am Konto) nur bei ungelesenen Mitteilungen; bei neuen bimmelt sie erneut
 * - ohne JavaScript bleiben die beim Seitenaufruf gerenderten Zähler stehen
 */
(() => {
    const INTERVAL = 15000;
    const root = document.querySelector('[data-badges-url]');
    if (!root) return;

    const url = root.dataset.badgesUrl;
    let last = { messages: null, notifications: null };
    let timer = null;

    function setBadge(link, count) {
        let badge = link.querySelector('.count-badge');
        if (count <= 0) {
            badge?.remove();
            return;
        }
        if (!badge) {
            badge = document.createElement('span');
            badge.className = link.closest('.header-account__list') ? 'count-badge' : 'count-badge count-badge--header';
            link.append(badge);
        }
        badge.textContent = count > 99 ? '99+' : String(count);
    }

    function setShown(el, count, previous) {
        el.hidden = count <= 0;
        if (count > 0 && previous !== null && count > previous) {
            el.classList.remove('is-ringing');
            void el.offsetWidth; // Animation neu starten
            el.classList.add('is-ringing');
        }
    }

    async function poll() {
        clearTimeout(timer);
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (response.ok) {
                const counts = await response.json();
                document.querySelectorAll('[data-badge]').forEach((link) => setBadge(link, counts[link.dataset.badge] ?? 0));
                document.querySelectorAll('[data-badge-show]').forEach((el) => setShown(el, counts[el.dataset.badgeShow] ?? 0, last[el.dataset.badgeShow]));
                last = counts;
            }
        } catch {
            // Netzwerkfehler: nächster Versuch im nächsten Intervall
        }
        if (document.visibilityState === 'visible') timer = setTimeout(poll, INTERVAL);
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') poll();
        else clearTimeout(timer);
    });

    timer = setTimeout(poll, INTERVAL);
})();
