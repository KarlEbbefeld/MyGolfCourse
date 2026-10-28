/*
 * Chat (Direkt- und Club-Chat), Muster aus dem Golf Marketplace:
 * - nur der geöffnete Chat fragt neue Nachrichten ab, nur bei sichtbarem Tab
 * - Abstand wächst ohne neue Nachrichten von 3 s auf 15 s, nach Aktivität zurück auf 3 s
 * - Server liefert nur Nachrichten mit ID > zuletzt bekannter ID
 * - Texte werden per textContent eingefügt (nie HTML aus Nutzereingaben)
 */
(() => {
    const chat = document.querySelector('[data-chat]');
    if (!chat) return;

    const MIN_DELAY = 3000;
    const MAX_DELAY = 15000;
    const list = chat.querySelector('[data-chat-messages]');
    const form = chat.querySelector('[data-chat-form]');
    const input = chat.querySelector('[data-chat-input]');
    const isClub = chat.dataset.club === '1';
    const csrf = form?.querySelector('input[name="_token"]')?.value ?? '';

    let lastId = Number(chat.dataset.lastId || 0);
    let partnerRead = Number(chat.dataset.partnerRead || 0);
    let delay = MIN_DELAY;
    let timer = null;
    let inFlight = false;

    const scrollToEnd = () => { list.scrollTop = list.scrollHeight; };

    function render(message) {
        const item = document.createElement('li');
        item.className = 'chat-msg' + (message.mine ? ' chat-msg--mine' : '') + (message.hidden ? ' chat-msg--hidden' : '');
        item.dataset.messageId = message.id;
        item.dataset.day = message.day;
        item.dataset.sender = message.sender;

        if (!message.mine && isClub) {
            const sender = document.createElement('span');
            sender.className = 'chat-msg__sender';
            if (message.senderUrl) {
                const link = document.createElement('a');
                link.href = message.senderUrl;
                link.textContent = message.sender;
                sender.appendChild(link);
            } else {
                sender.textContent = message.sender;
            }
            item.appendChild(sender);
        }

        const bubble = document.createElement('div');
        bubble.className = 'chat-msg__bubble';
        const body = document.createElement('p');
        body.className = 'chat-msg__body';
        body.textContent = message.body;
        const meta = document.createElement('span');
        meta.className = 'chat-msg__meta';
        const time = document.createElement('time');
        time.textContent = String(message.time).slice(-5);
        meta.appendChild(time);
        bubble.append(body, meta);
        item.appendChild(bubble);

        if (!message.mine && !message.hidden) {
            const report = document.createElement('button');
            report.type = 'button';
            report.className = 'chat-msg__report';
            report.dataset.report = message.id;
            report.textContent = 'Melden';
            report.setAttribute('aria-label', 'Nachricht melden');
            item.appendChild(report);
        }

        return item;
    }

    // Tagestrenner („Heute“, „27.10.2026“) und Gruppierung aufeinanderfolgender Nachrichten derselben Person
    function decorate() {
        list.querySelectorAll('.chat__day').forEach((el) => el.remove());
        const items = [...list.querySelectorAll('.chat-msg')];
        const today = new Date();
        const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        let previousDay = null;

        items.forEach((item, index) => {
            const day = item.dataset.day;
            if (day && day !== previousDay) {
                const divider = document.createElement('li');
                divider.className = 'chat__day';
                const label = document.createElement('span');
                const [y, m, d] = day.split('-');
                label.textContent = day === todayKey ? 'Heute' : `${d}.${m}.${y}`;
                divider.appendChild(label);
                list.insertBefore(divider, item);
                previousDay = day;
            }
            const prev = items[index - 1];
            const sameAsPrev = prev && prev.dataset.sender === item.dataset.sender && prev.dataset.day === item.dataset.day;
            item.classList.toggle('chat-msg--continued', Boolean(sameAsPrev));
        });

        markRead();
    }

    // Direktchat: „Gelesen“ unter der letzten eigenen Nachricht, die das Gegenüber gesehen hat
    function markRead() {
        if (isClub) return;
        list.querySelectorAll('.chat-msg__read').forEach((el) => el.remove());
        const mine = [...list.querySelectorAll('.chat-msg--mine')];
        const last = mine.at(-1);
        if (!last) return;
        const status = document.createElement('span');
        status.className = 'chat-msg__read';
        status.textContent = Number(last.dataset.messageId) <= partnerRead ? 'Gelesen' : 'Gesendet';
        last.appendChild(status);
    }

    function append(messages) {
        const fresh = messages.filter((m) => m.id > lastId && !list.querySelector(`[data-message-id="${m.id}"]`));
        if (!fresh.length) return 0;
        list.querySelector('[data-chat-empty]')?.remove();
        const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 120;
        fresh.forEach((m) => list.appendChild(render(m)));
        lastId = Math.max(lastId, ...fresh.map((m) => m.id));
        decorate();
        if (nearBottom || fresh.some((m) => m.mine)) scrollToEnd();
        return fresh.length;
    }

    async function poll() {
        if (inFlight || document.hidden) return schedule();
        inFlight = true;
        try {
            const response = await fetch(`${chat.dataset.pollUrl}?after=${lastId}`, { headers: { Accept: 'application/json' } });
            if (response.ok) {
                const data = await response.json();
                const count = append(data.messages || []);
                if (data.partnerReadUpTo !== partnerRead) {
                    partnerRead = data.partnerReadUpTo || 0;
                    markRead();
                }
                delay = count ? MIN_DELAY : Math.min(delay * 1.5, MAX_DELAY);
            }
        } catch {
            delay = MAX_DELAY;
        } finally {
            inFlight = false;
            schedule();
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(poll, delay);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            delay = MIN_DELAY;
            poll();
        }
    });

    // Senden ohne Neuladen
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch(chat.dataset.sendUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
                body: JSON.stringify({ body: text }),
            });
            if (response.status === 429) {
                alert('Du schreibst gerade sehr schnell. Bitte kurz warten.');
                return;
            }
            if (!response.ok) throw new Error();
            const data = await response.json();
            input.value = '';
            input.style.height = '';
            append([data.message]);
            delay = MIN_DELAY;
            schedule();
        } catch {
            alert('Nachricht konnte nicht gesendet werden. Bitte erneut versuchen.');
        } finally {
            button.disabled = false;
            input.focus();
        }
    });

    // Enter sendet, Umschalt+Enter macht eine neue Zeile; Feld wächst mit
    input?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
    input?.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 160)}px`;
    });

    // Melden
    const dialog = document.querySelector('[data-report-dialog]');
    const reportForm = document.querySelector('[data-report-form]');
    list.addEventListener('click', (event) => {
        const id = event.target.closest('[data-report]')?.dataset.report;
        if (!id || !dialog) return;
        reportForm.action = chat.dataset.reportUrl.replace('__ID__', id);
        dialog.showModal();
    });
    document.querySelector('[data-report-cancel]')?.addEventListener('click', () => dialog.close());

    decorate();
    scrollToEnd();
    schedule();
})();
