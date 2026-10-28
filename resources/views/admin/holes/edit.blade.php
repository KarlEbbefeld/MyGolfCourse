@php
    $state = $editorState;
    $state['panoramas'] = $panoramas->map(fn ($p) => [
        'id' => $p->id,
        'label' => $p->label,
        'kind' => $p->kind,
        'lat' => $p->lat,
        'lng' => $p->lng,
        'altitude' => $p->altitude_m,
        'positionSource' => $p->position_source,
        'calibrated' => $p->heading_calibrated,
        'heading' => $p->heading_deg,
        'image' => $p->image_mobile_url ? asset_v($p->image_mobile_url) : ($p->image_url ? asset_v($p->image_url) : null),
        'calibrateUrl' => route('admin.panoramas.calibrate', $p),
    ])->values();
    $state['planImage'] = $hole->plan_image_url ? asset_v($hole->plan_image_url) : null;
    $previousHole = $course->holes->firstWhere('number', $hole->number - 1);
    $nextHole = $course->holes->firstWhere('number', $hole->number + 1);
@endphp
<x-admin-layout :title="'Bahn '.$hole->number.' · '.$course->name" wide>
    <x-slot:head>
        <link rel="stylesheet" href="{{ asset_v('vendor/leaflet/leaflet.css') }}">
        <link rel="stylesheet" href="{{ asset_v('vendor/psv/core/index.css') }}">
        <script type="importmap">{!! json_encode(['imports' => [
            'three' => asset('vendor/three/three.module.js'),
            '@photo-sphere-viewer/core' => asset('vendor/psv/core/index.module.js'),
        ]], JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:head>

    <div class="admin-head">
        <div>
            <p class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Plätze</a> / <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->name }}</a> /</p>
            <h1>Bahn {{ $hole->number }}</h1>
        </div>
        <div class="admin-head__actions">
            @if ($previousHole) <a class="button button--secondary" href="{{ route('admin.holes.edit', $previousHole) }}">← Bahn {{ $previousHole->number }}</a> @endif
            @if ($nextHole) <a class="button button--secondary" href="{{ route('admin.holes.edit', $nextHole) }}">Bahn {{ $nextHole->number }} →</a> @endif
            @if ($course->isPublished())
                <a class="button button--secondary" href="{{ route('holes.show', ['course' => $course, 'number' => $hole->number]) }}" target="_blank" rel="noopener">Ansehen ↗</a>
            @endif
        </div>
    </div>

    <div class="editor">
        {{-- Karte --}}
        <section class="editor__map-wrap">
            <div class="editor-tools" data-tools>
                <span class="editor-tools__label">Setzen:</span>
                @foreach ($teeSets as $teeSet)
                    <button type="button" class="tool" data-tool="tee:{{ $teeSet->id }}"><span class="tee-dot tee-dot--{{ $teeSet->color->value }}"></span>Abschlag {{ $teeSet->name }}</button>
                @endforeach
                <button type="button" class="tool" data-tool="green">⚑ Grün</button>
                <span class="editor-tools__sep"></span>
                <button type="button" class="tool" data-tool="hazard:tree">Baum</button>
                <button type="button" class="tool" data-tool="hazard:tree_group">Baumgruppe</button>
                <button type="button" class="tool" data-tool="hazard:bunker_fairway">Fairwaybunker</button>
                <button type="button" class="tool" data-tool="hazard:bunker_greenside">Grünbunker</button>
                <button type="button" class="tool" data-tool="hazard:water">Wasser</button>
                <button type="button" class="tool" data-tool="hazard:out_of_bounds">Aus</button>
                <button type="button" class="tool" data-tool="hazard:other">Sonstiges</button>
            </div>
            <div class="editor__map" data-editor-map></div>
            <p class="editor__hint" data-tool-hint>Werkzeug wählen, dann auf die Karte klicken. Punkte lassen sich verschieben.</p>
        </section>

        {{-- Seitenleiste --}}
        <div class="editor__side">
            <form id="hole-form" method="post" action="{{ route('admin.holes.update', $hole) }}" class="admin-card admin-form" data-hole-form>
                @csrf
                @method('put')
                <input type="hidden" name="geo" data-geo-input>

                <h2>Bahndaten</h2>
                <div class="form-grid form-grid--compact">
                    <label class="field"><span>Par</span>
                        <select name="par">@foreach ([3, 4, 5, 6] as $par)<option value="{{ $par }}" @selected(old('par', $hole->par) == $par)>{{ $par }}</option>@endforeach</select></label>
                    <label class="field"><span>HCP</span>
                        <input name="stroke_index" type="number" min="1" max="27" value="{{ old('stroke_index', $hole->stroke_index) }}"></label>
                    <label class="field"><span>Verlauf</span>
                        <select name="dogleg">
                            @foreach (\App\Modules\Courses\Enums\Dogleg::cases() as $dogleg)
                                <option value="{{ $dogleg->value }}" @selected(old('dogleg', $hole->dogleg->value) === $dogleg->value)>{{ $dogleg->label() }}</option>
                            @endforeach
                        </select></label>
                    <label class="field"><span>Name (optional)</span>
                        <input name="name" value="{{ old('name', $hole->name) }}" maxlength="100"></label>
                </div>

                <h3>Längen (m)</h3>
                <div class="form-grid form-grid--compact">
                    @foreach ($teeSets as $teeSet)
                        <label class="field"><span><span class="tee-dot tee-dot--{{ $teeSet->color->value }}"></span>{{ $teeSet->name }}</span>
                            <input name="lengths[{{ $teeSet->id }}]" type="number" step="1" min="30" max="800"
                                   value="{{ old('lengths.'.$teeSet->id, $hole->teeFor($teeSet)?->length_m ? round($hole->teeFor($teeSet)->length_m) : '') }}"
                                   placeholder="aus Karte">
                            <small class="muted" data-length-hint="{{ $teeSet->id }}"></small></label>
                    @endforeach
                </div>

                <label class="field field--wide"><span>Beschreibung / Strategie</span>
                    <textarea name="description" rows="3">{{ old('description', $hole->description) }}</textarea></label>

                <h2>Hindernisse <small class="muted" data-hazard-count></small></h2>
                <p class="muted">Mit den Werkzeugen über der Karte setzen. Baumhöhe in Metern, Genauigkeit ± m.</p>
                <ol class="hazard-editor" data-hazard-list></ol>

                <h2>360°-Positionen</h2>
                <p class="muted">Werden automatisch vom Abschlag zum Grün verbunden. Auf der Karte verschiebbar.</p>
                <ol class="pano-editor" data-pano-list></ol>

                <div class="admin-form__actions admin-form__actions--sticky">
                    <button class="button button--primary" type="submit">Bahn speichern</button>
                    <span class="muted" data-dirty-hint hidden>Ungespeicherte Änderungen</span>
                </div>
            </form>

            {{-- Uploads: eigene Formulare (Formulare dürfen nicht verschachtelt sein) --}}
            <section class="admin-card">
                <h2>360°-Fotos hochladen</h2>
                <p class="muted">JPG im Format 2:1, z. B. DJI-Panoramamodus „Sphäre“. GPS und Flughöhe werden aus dem Foto gelesen. Vorher Bahn speichern!</p>
                <form method="post" action="{{ route('admin.panoramas.store', $hole) }}" enctype="multipart/form-data" class="upload-form" data-upload-form>
                    @csrf
                    <input type="file" name="panoramas[]" accept="image/jpeg" multiple required>
                    <select name="kind">
                        <option value="aerial">Aus der Luft (Drohne)</option>
                        <option value="ground">Augenhöhe (360°-Kamera)</option>
                    </select>
                    <button class="button button--secondary" type="submit">Hochladen</button>
                </form>
            </section>

            <section class="admin-card">
                <h2>Überflug-Video</h2>
                @if ($flyover)
                    <video class="admin-video" src="{{ $flyover->videoSrc() }}" controls muted preload="metadata"></video>
                    <form method="post" action="{{ route('admin.flyovers.destroy', $hole) }}" onsubmit="return confirm('Video wirklich entfernen?')">
                        @csrf @method('delete')
                        <button class="link-button link-button--danger" type="submit">Video entfernen</button>
                    </form>
                @endif
                <p class="muted">MP4 (H.264), ideal 1080p ohne Ton. Gleichmäßig vom Abschlag zum Grün fliegen – dann passen die Haltepunkte.</p>
                <form method="post" action="{{ route('admin.flyovers.store', $hole) }}" enctype="multipart/form-data" class="upload-form" data-upload-form>
                    @csrf
                    <label class="field"><span>Video</span><input type="file" name="video" accept="video/mp4,video/quicktime" required></label>
                    <label class="field"><span>Vorschaubild (optional)</span><input type="file" name="poster" accept="image/*"></label>
                    <button class="button button--secondary" type="submit">{{ $flyover ? 'Video ersetzen' : 'Video hochladen' }}</button>
                </form>
            </section>

            <section class="admin-card">
                <h2>Bahngrafik</h2>
                <p class="muted">Die Grafik des Clubs (Scorecard/Yardage-Book). Danach bei jedem Hindernis „Auf Grafik setzen“ und auf die Stelle klicken.</p>
                @if ($hole->plan_image_url)
                    <div class="plan-editor" data-plan-editor>
                        <img src="{{ asset_v($hole->plan_image_url) }}" alt="Bahngrafik Bahn {{ $hole->number }}" data-plan-image>
                    </div>
                    <p class="editor__hint" data-plan-hint hidden></p>
                    <form method="post" action="{{ route('admin.plan.destroy', $hole) }}" onsubmit="return confirm('Bahngrafik entfernen?')">
                        @csrf @method('delete')
                        <button class="link-button link-button--danger" type="submit">Grafik entfernen</button>
                    </form>
                @endif
                <form method="post" action="{{ route('admin.plan.store', $hole) }}" enctype="multipart/form-data" class="upload-form" data-upload-form>
                    @csrf
                    <input type="file" name="plan" accept="image/jpeg,image/png,image/webp" required>
                    <button class="button button--secondary" type="submit">{{ $hole->plan_image_url ? 'Grafik ersetzen' : 'Grafik hochladen' }}</button>
                </form>
            </section>

            <p class="muted admin-limits">Upload-Grenzen des Servers: {{ $uploadLimit }}. Größere Dateien kommen nicht an – dann verkleinern.</p>

            {{-- Löschformulare für 360°-Positionen (Knöpfe in der Liste verweisen per form-Attribut hierher) --}}
            @foreach ($panoramas as $panorama)
                <form id="delete-pano-{{ $panorama->id }}" method="post" action="{{ route('admin.panoramas.destroy', $panorama) }}" hidden>
                    @csrf @method('delete')
                </form>
            @endforeach
        </div>
    </div>

    <dialog class="calibrate" data-calibrate-dialog>
        <div class="calibrate__head">
            <h2>Ausrichten: <span data-calibrate-title></span></h2>
            <button type="button" class="link-button" data-calibrate-close>Schließen</button>
        </div>
        <p class="muted">Im Panorama umsehen und genau auf die <strong>Grünmitte bzw. die Fahne</strong> klicken. Daraus berechnet das System die Himmelsrichtung.</p>
        <div class="calibrate__viewer" data-calibrate-viewer></div>
        <p class="calibrate__status" data-calibrate-status></p>
    </dialog>

    <x-slot:scripts>
        <script type="application/json" id="editor-state">{!! json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        <script src="{{ asset_v('vendor/leaflet/leaflet.js') }}"></script>
        <script src="{{ asset_v('js/admin/map-tiles.js') }}"></script>
        <script src="{{ asset_v('js/admin/hole-editor.js') }}"></script>
        <script type="module" src="{{ asset_v('js/admin/calibrate.js') }}"></script>
    </x-slot:scripts>
</x-admin-layout>
