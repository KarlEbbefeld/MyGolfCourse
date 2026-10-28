/*
 * Bahn-Editor (Admin): Abschläge, Grün, Hindernisse und 360°-Positionen auf dem Satellitenbild setzen und verschieben,
 * Hindernisse benennen und Baumhöhen eintragen, Hindernisse auf der Bahngrafik platzieren.
 * Der gesamte Zustand geht beim Speichern als JSON im Feld „geo“ an App\Modules\Admin\Http\Controllers\HoleController.
 */
(() => {
    const stateElement = document.getElementById('editor-state');
    const mapElement = document.querySelector('[data-editor-map]');
    if (!stateElement || !mapElement) return;

    const state = JSON.parse(stateElement.textContent);
    const form = document.querySelector('[data-hole-form]');
    const geoInput = document.querySelector('[data-geo-input]');
    const hazardList = document.querySelector('[data-hazard-list]');
    const panoList = document.querySelector('[data-pano-list]');
    const toolHint = document.querySelector('[data-tool-hint]');
    const typeLabels = Object.fromEntries(state.types.map((t) => [t.value, t.label]));
    const heightTypes = ['tree', 'tree_group'];

    let keyCounter = 0;
    state.hazards.forEach((h) => { h._key = ++keyCounter; });

    let dirty = false;
    let activeTool = null;
    let planTarget = null;
    let selectedKey = null;

    const map = window.mgcMap(mapElement, state.center);
    const layers = { tees: {}, green: null, hazards: {}, panoramas: {}, line: null };

    // ---------- Hilfen ----------
    function markDirty() {
        dirty = true;
        document.querySelector('[data-dirty-hint]')?.removeAttribute('hidden');
    }

    function icon(className, html) {
        return L.divIcon({ className: '', html: `<span class="pin ${className}">${html}</span>`, iconSize: null, iconAnchor: [0, 0] });
    }

    function esc(text) {
        return String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    }

    function shortType(type) {
        return { tree: 'Baum', tree_group: 'Bäume', bunker_fairway: 'Bunker', bunker_greenside: 'Bunker', water: 'Wasser', out_of_bounds: 'Aus' }[type] ?? '•';
    }

    // ---------- Abschläge und Grün ----------
    function renderTee(tee) {
        layers.tees[tee.teeSetId]?.remove();
        delete layers.tees[tee.teeSetId];
        if (!tee.position) return;
        const marker = L.marker([tee.position.lat, tee.position.lng], {
            draggable: true,
            icon: icon(`pin--tee pin--${tee.color}`, esc(tee.name)),
            title: `Abschlag ${tee.name}`,
        }).addTo(map);
        marker.on('dragend', () => {
            tee.position = marker.getLatLng();
            markDirty();
            updateDerived();
        });
        layers.tees[tee.teeSetId] = marker;
    }

    function renderGreen() {
        layers.green?.remove();
        layers.green = null;
        if (!state.green) return;
        layers.green = L.marker([state.green.lat, state.green.lng], { draggable: true, icon: icon('pin--green', '⚑ Grün'), title: 'Grün' }).addTo(map);
        layers.green.on('dragend', () => {
            state.green = layers.green.getLatLng();
            markDirty();
            updateDerived();
        });
    }

    // Linie Abschlag → Grün und berechnete Längen neben den Eingabefeldern
    function updateDerived() {
        layers.line?.remove();
        const firstTee = state.tees.find((t) => t.position);
        if (firstTee && state.green) {
            layers.line = L.polyline([firstTee.position, state.green], { color: '#f4f1ea', weight: 2, dashArray: '6 6', opacity: 0.9 }).addTo(map);
        }
        state.tees.forEach((tee) => {
            const hint = document.querySelector(`[data-length-hint="${tee.teeSetId}"]`);
            if (!hint) return;
            hint.textContent = tee.position && state.green ? `Karte: ${Math.round(window.mgcDistance(tee.position, state.green))} m` : '';
        });
    }

    // ---------- Hindernisse ----------
    function renderHazardMarker(hazard) {
        layers.hazards[hazard._key]?.remove();
        if (hazard.lat == null || hazard.lng == null) return;
        const label = hazard.height ? `${shortType(hazard.type)} ${hazard.height} m` : shortType(hazard.type);
        const marker = L.marker([hazard.lat, hazard.lng], {
            draggable: true,
            icon: icon(`pin--hazard pin--${hazard.type}${selectedKey === hazard._key ? ' is-selected' : ''}`, esc(label)),
            title: hazard.name,
        }).addTo(map);
        marker.on('dragend', () => {
            const p = marker.getLatLng();
            hazard.lat = p.lat;
            hazard.lng = p.lng;
            markDirty();
        });
        marker.on('click', () => selectHazard(hazard._key, true));
        layers.hazards[hazard._key] = marker;
    }

    function selectHazard(key, scroll) {
        const previous = selectedKey;
        selectedKey = key;
        [previous, key].forEach((k) => {
            const hazard = state.hazards.find((h) => h._key === k);
            if (hazard) renderHazardMarker(hazard);
        });
        hazardList.querySelectorAll('.hazard-editor__item').forEach((el) => el.classList.toggle('is-selected', Number(el.dataset.key) === key));
        if (scroll) hazardList.querySelector(`[data-key="${key}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function renderHazardList() {
        document.querySelector('[data-hazard-count]').textContent = state.hazards.length ? `(${state.hazards.length})` : '';
        if (!state.hazards.length) {
            hazardList.innerHTML = '<li class="muted">Noch keine Hindernisse – Werkzeug über der Karte wählen.</li>';
            return;
        }

        hazardList.innerHTML = state.hazards.map((h) => `
            <li class="hazard-editor__item${selectedKey === h._key ? ' is-selected' : ''}" data-key="${h._key}">
                <div class="hazard-editor__row">
                    <select data-field="type">${state.types.map((t) => `<option value="${t.value}"${t.value === h.type ? ' selected' : ''}>${esc(t.label)}</option>`).join('')}</select>
                    <input data-field="name" value="${esc(h.name)}" placeholder="Name, z. B. Eiche links" maxlength="100">
                </div>
                <div class="hazard-editor__row hazard-editor__row--height"${heightTypes.includes(h.type) ? '' : ' hidden'}>
                    <label>Höhe <input data-field="height" type="number" step="0.5" min="0" max="80" value="${h.height ?? ''}"> m</label>
                    <label>± <input data-field="heightAccuracy" type="number" step="0.1" min="0" max="10" value="${h.heightAccuracy ?? ''}"> m</label>
                </div>
                <textarea data-field="description" rows="2" placeholder="Strategischer Hinweis (optional)">${esc(h.description)}</textarea>
                <div class="hazard-editor__actions">
                    <button type="button" class="link-button" data-action="locate">Auf Karte zeigen</button>
                    ${state.planImage ? `<button type="button" class="link-button" data-action="plan">${h.planX != null ? 'Auf Grafik versetzen' : 'Auf Grafik setzen'}</button>` : ''}
                    <button type="button" class="link-button link-button--danger" data-action="remove">Entfernen</button>
                </div>
            </li>`).join('');
    }

    hazardList.addEventListener('input', (event) => {
        const item = event.target.closest('[data-key]');
        const field = event.target.dataset.field;
        if (!item || !field) return;
        const hazard = state.hazards.find((h) => h._key === Number(item.dataset.key));
        const value = event.target.value;
        hazard[field] = ['height', 'heightAccuracy'].includes(field) ? (value === '' ? null : Number(value)) : value;
        if (field === 'type') {
            item.querySelector('.hazard-editor__row--height').hidden = !heightTypes.includes(value);
        }
        if (['type', 'height', 'name'].includes(field)) renderHazardMarker(hazard);
        markDirty();
    });

    hazardList.addEventListener('click', (event) => {
        const action = event.target.dataset.action;
        const item = event.target.closest('[data-key]');
        if (!item) return;
        const key = Number(item.dataset.key);
        const hazard = state.hazards.find((h) => h._key === key);
        if (!action) {
            selectHazard(key, false);
            return;
        }
        if (action === 'locate' && hazard.lat != null) {
            map.setView([hazard.lat, hazard.lng], Math.max(map.getZoom(), 18));
            selectHazard(key, false);
        }
        if (action === 'remove' && confirm(`„${hazard.name}“ entfernen?`)) {
            layers.hazards[key]?.remove();
            state.hazards = state.hazards.filter((h) => h._key !== key);
            renderHazardList();
            renderPlanMarkers();
            markDirty();
        }
        if (action === 'plan') {
            planTarget = key;
            selectHazard(key, false);
            const hint = document.querySelector('[data-plan-hint]');
            if (hint) {
                hint.hidden = false;
                hint.textContent = `Jetzt auf die Bahngrafik klicken: „${hazard.name}“`;
            }
            document.querySelector('[data-plan-editor]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // ---------- 360°-Positionen ----------
    function renderPanoramaMarker(pano, index) {
        layers.panoramas[pano.id]?.remove();
        const marker = L.marker([pano.lat, pano.lng], {
            draggable: true,
            icon: icon(`pin--pano${pano.positionSource === 'fallback' ? ' pin--warning' : ''}`, `360° ${index + 1}`),
            title: pano.label,
        }).addTo(map);
        marker.on('dragend', () => {
            const p = marker.getLatLng();
            pano.lat = p.lat;
            pano.lng = p.lng;
            pano.positionSource = 'manual';
            markDirty();
            renderPanoList();
        });
        layers.panoramas[pano.id] = marker;
    }

    function renderPanoList() {
        if (!state.panoramas.length) {
            panoList.innerHTML = '<li class="muted">Noch keine 360°-Fotos – unten hochladen.</li>';
            return;
        }
        panoList.innerHTML = state.panoramas.map((p, i) => `
            <li class="pano-editor__item" data-pano="${p.id}">
                <div class="hazard-editor__row">
                    <span class="pano-editor__number num">${i + 1}</span>
                    <input data-pano-field="label" value="${esc(p.label)}" maxlength="100" placeholder="z. B. Landezone">
                </div>
                <div class="hazard-editor__row">
                    <select data-pano-field="kind">
                        <option value="aerial"${p.kind === 'aerial' ? ' selected' : ''}>Luft</option>
                        <option value="ground"${p.kind === 'ground' ? ' selected' : ''}>Augenhöhe</option>
                    </select>
                    <label>Höhe <input data-pano-field="altitude" type="number" step="0.1" min="0" max="120" value="${p.altitude ?? ''}"> m</label>
                </div>
                <p class="pano-editor__chips">
                    ${p.positionSource === 'fallback' ? '<span class="chip chip--warning">Position fehlt – auf Karte verschieben</span>' : `<span class="chip">${p.positionSource === 'exif' ? 'GPS aus Foto' : 'Position gesetzt'}</span>`}
                    ${p.calibrated ? `<span class="chip chip--ok">ausgerichtet (${Math.round(p.heading)}°)</span>` : '<span class="chip chip--warning">nicht ausgerichtet</span>'}
                </p>
                <div class="hazard-editor__actions">
                    <button type="button" class="link-button" data-pano-action="calibrate">Ausrichten</button>
                    <button type="button" class="link-button" data-pano-action="locate">Auf Karte zeigen</button>
                    <button type="submit" class="link-button link-button--danger" form="delete-pano-${p.id}" data-pano-action="delete">Löschen</button>
                </div>
            </li>`).join('');
    }

    panoList.addEventListener('input', (event) => {
        const item = event.target.closest('[data-pano]');
        const field = event.target.dataset.panoField;
        if (!item || !field) return;
        const pano = state.panoramas.find((p) => p.id === Number(item.dataset.pano));
        pano[field] = field === 'altitude' ? (event.target.value === '' ? null : Number(event.target.value)) : event.target.value;
        markDirty();
    });

    panoList.addEventListener('click', (event) => {
        const action = event.target.dataset.panoAction;
        const item = event.target.closest('[data-pano]');
        if (!action || !item) return;
        const pano = state.panoramas.find((p) => p.id === Number(item.dataset.pano));
        if (action === 'locate') map.setView([pano.lat, pano.lng], Math.max(map.getZoom(), 18));
        if (action === 'calibrate') {
            if (!state.green) {
                alert('Zuerst das Grün auf der Karte setzen und die Bahn speichern.');
                return;
            }
            if (dirty) {
                alert('Bitte zuerst „Bahn speichern“ – die Ausrichtung braucht die gespeicherte Position von Foto und Grün.');
                return;
            }
            document.dispatchEvent(new CustomEvent('mgc:calibrate', { detail: pano }));
        }
        if (action === 'delete' && !confirm(`„${pano.label}“ mit Foto löschen?`)) event.preventDefault();
    });

    document.addEventListener('mgc:calibrated', ({ detail }) => {
        const pano = state.panoramas.find((p) => p.id === detail.id);
        if (!pano) return;
        pano.calibrated = true;
        pano.heading = detail.heading;
        renderPanoList();
    });

    // ---------- Bahngrafik ----------
    const planEditor = document.querySelector('[data-plan-editor]');
    const planImage = document.querySelector('[data-plan-image]');

    function renderPlanMarkers() {
        if (!planEditor) return;
        planEditor.querySelectorAll('.plan-pin').forEach((el) => el.remove());
        state.hazards.filter((h) => h.planX != null && h.planY != null).forEach((h) => {
            const pin = document.createElement('span');
            pin.className = `plan-pin pin--${h.type}`;
            pin.style.left = `${h.planX * 100}%`;
            pin.style.top = `${h.planY * 100}%`;
            pin.title = h.name;
            pin.textContent = h.height ? `${shortType(h.type)} ${h.height} m` : shortType(h.type);
            planEditor.append(pin);
        });
    }

    planImage?.addEventListener('click', (event) => {
        if (planTarget == null) {
            alert('Zuerst bei einem Hindernis „Auf Grafik setzen“ wählen.');
            return;
        }
        const rect = planImage.getBoundingClientRect();
        const hazard = state.hazards.find((h) => h._key === planTarget);
        hazard.planX = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);
        hazard.planY = Math.min(Math.max((event.clientY - rect.top) / rect.height, 0), 1);
        planTarget = null;
        document.querySelector('[data-plan-hint]').hidden = true;
        renderPlanMarkers();
        renderHazardList();
        markDirty();
    });

    // ---------- Werkzeuge ----------
    document.querySelectorAll('[data-tool]').forEach((button) => {
        button.addEventListener('click', () => {
            activeTool = activeTool === button.dataset.tool ? null : button.dataset.tool;
            document.querySelectorAll('[data-tool]').forEach((b) => b.classList.toggle('is-active', b.dataset.tool === activeTool));
            mapElement.classList.toggle('is-placing', Boolean(activeTool));
            toolHint.textContent = activeTool
                ? `Auf die Karte klicken, um „${button.textContent.trim()}“ zu setzen. Esc bricht ab.`
                : 'Werkzeug wählen, dann auf die Karte klicken. Punkte lassen sich verschieben.';
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeTool) document.querySelector(`[data-tool="${activeTool}"]`)?.click();
    });

    map.on('click', (event) => {
        if (!activeTool) return;
        const { lat, lng } = event.latlng;
        const [kind, value] = activeTool.split(':');

        if (kind === 'tee') {
            const tee = state.tees.find((t) => String(t.teeSetId) === value);
            tee.position = { lat, lng };
            renderTee(tee);
        } else if (kind === 'green') {
            state.green = { lat, lng };
            renderGreen();
        } else if (kind === 'hazard') {
            const hazard = { _key: ++keyCounter, id: null, type: value, name: typeLabels[value], description: '', lat, lng, height: null, heightAccuracy: null, planX: null, planY: null };
            state.hazards.push(hazard);
            renderHazardList();
            selectHazard(hazard._key, true);
            hazardList.querySelector(`[data-key="${hazard._key}"] [data-field="name"]`)?.select();
        }

        markDirty();
        updateDerived();
        // Werkzeug nach jedem Setzen beenden, damit kein Klick versehentlich etwas anlegt
        document.querySelector(`[data-tool="${activeTool}"]`)?.click();
    });

    // ---------- Speichern ----------
    form.addEventListener('submit', () => {
        geoInput.value = JSON.stringify({
            tees: state.tees.map((t) => ({ teeSetId: t.teeSetId, position: t.position ? { lat: t.position.lat, lng: t.position.lng } : null })),
            green: state.green ? { lat: state.green.lat, lng: state.green.lng } : null,
            hazards: state.hazards.map(({ _key, ...h }) => h),
            panoramas: state.panoramas.map((p) => ({ id: p.id, label: p.label, kind: p.kind, lat: p.lat, lng: p.lng, altitude: p.altitude })),
        });
        dirty = false;
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    document.querySelectorAll('[data-upload-form]').forEach((uploadForm) => {
        uploadForm.addEventListener('submit', (event) => {
            if (dirty && !confirm('Es gibt ungespeicherte Änderungen an der Karte. Trotzdem hochladen? (Änderungen gehen verloren)')) {
                event.preventDefault();
                return;
            }
            dirty = false;
            const button = uploadForm.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Wird hochgeladen …';
            }
        });
    });

    // ---------- Start ----------
    state.tees.forEach(renderTee);
    renderGreen();
    state.hazards.forEach(renderHazardMarker);
    state.panoramas.forEach(renderPanoramaMarker);
    renderHazardList();
    renderPanoList();
    renderPlanMarkers();
    updateDerived();

    // Alles Gesetzte ins Bild holen
    const points = [
        ...state.tees.filter((t) => t.position).map((t) => t.position),
        ...(state.green ? [state.green] : []),
        ...state.hazards.filter((h) => h.lat != null).map((h) => ({ lat: h.lat, lng: h.lng })),
        ...state.panoramas.map((p) => ({ lat: p.lat, lng: p.lng })),
    ];
    if (points.length >= 2) map.fitBounds(L.latLngBounds(points.map((p) => [p.lat, p.lng])), { padding: [40, 40], maxZoom: 19 });
})();
