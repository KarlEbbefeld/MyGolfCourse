@php
    $isNew = ! $course->exists;
    $colors = \App\Modules\Courses\Enums\TeeColor::cases();
    $teeRows = collect(old('tee_sets', $teeSets->all()))->pad(max(4, $teeSets->count() + 1), ['id' => null, 'name' => '', 'color' => '']);
@endphp
<x-admin-layout :title="$isNew ? 'Neuer Platz' : $course->name">
    <x-slot:head>
        <link rel="stylesheet" href="{{ asset_v('vendor/leaflet/leaflet.css') }}">
    </x-slot:head>

    <div class="admin-head">
        <div>
            <p class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Plätze</a> /</p>
            <h1>{{ $isNew ? 'Neuer Platz' : $course->name }}</h1>
        </div>
        @if (! $isNew && $course->isPublished())
            <a class="button button--secondary" href="{{ route('courses.show', $course) }}" target="_blank" rel="noopener">Öffentliche Seite ↗</a>
        @endif
    </div>

    @unless ($isNew)
        <section class="admin-card">
            <h2>Bahnen</h2>
            <p class="muted">Bahn öffnen, um Abschlag, Grün, Hindernisse und Medien zu pflegen.</p>
            <ol class="hole-grid">
                @foreach ($course->holes as $hole)
                    @php
                        $ready = $hole->hasGreenPosition() && $hole->tees->contains(fn ($t) => $t->hasPosition());
                    @endphp
                    <li>
                        <a @class(['hole-tile', 'is-ready' => $ready]) href="{{ route('admin.holes.edit', $hole) }}">
                            <span class="hole-tile__number num">{{ $hole->number }}</span>
                            <span class="hole-tile__par num">Par {{ $hole->par }}</span>
                            <span class="hole-tile__state">{{ $ready ? 'Karte ✓' : 'offen' }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endunless

    <form class="admin-card admin-form" method="post" action="{{ $isNew ? route('admin.courses.store') : route('admin.courses.update', $course) }}">
        @csrf
        @unless ($isNew) @method('put') @endunless

        <h2>Stammdaten</h2>
        <div class="form-grid">
            <label class="field field--wide"><span>Name *</span>
                <input name="name" value="{{ old('name', $course->name) }}" required maxlength="150" placeholder="Golfclub Beispielstadt"></label>
            <label class="field"><span>Club (falls abweichend)</span>
                <input name="club_name" value="{{ old('club_name', $course->club_name) }}" maxlength="150"></label>
            <label class="field"><span>Website</span>
                <input name="website_url" type="url" value="{{ old('website_url', $course->website_url) }}" placeholder="https://"></label>
            <label class="field"><span>Straße</span>
                <input name="street" value="{{ old('street', $course->street) }}"></label>
            <label class="field"><span>PLZ</span>
                <input name="postal_code" value="{{ old('postal_code', $course->postal_code) }}"></label>
            <label class="field"><span>Ort</span>
                <input name="city" value="{{ old('city', $course->city) }}"></label>
            <label class="field"><span>Region / Bundesland</span>
                <input name="region" value="{{ old('region', $course->region) }}" placeholder="Nordrhein-Westfalen"></label>
            <label class="field"><span>Telefon</span>
                <input name="phone" value="{{ old('phone', $course->phone) }}"></label>
            @if ($isNew)
                <label class="field"><span>Lochzahl *</span>
                    <select name="holes_count">
                        @foreach ([9, 18, 27] as $count)
                            <option value="{{ $count }}" @selected((int) old('holes_count', $course->holes_count) === $count)>{{ $count }}</option>
                        @endforeach
                    </select></label>
            @endif
            <label class="field"><span>Aufgenommen am</span>
                <input name="capture_date" type="date" value="{{ old('capture_date', $course->capture_date?->format('Y-m-d')) }}"></label>
            <label class="field"><span>Status</span>
                <select name="status">
                    @foreach (\App\Modules\Courses\Enums\CourseStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $course->status?->value) === $status->value)
                            @disabled($status->value === 'published' && ! auth()->user()->role->canPublish() && ! $course->isPublished())>{{ $status->label() }}</option>
                    @endforeach
                </select></label>
            <label class="field field--wide"><span>Kurzbeschreibung (Karten, Suchmaschinen)</span>
                <input name="short_description" value="{{ old('short_description', $course->short_description) }}" maxlength="300"></label>
            <label class="field field--wide"><span>Beschreibung (Absätze mit Leerzeile trennen)</span>
                <textarea name="description" rows="5">{{ old('description', $course->description) }}</textarea></label>
        </div>

        <h2>Lage des Platzes</h2>
        <p class="muted">Ort suchen oder auf der Karte auf das Clubhaus klicken. Dient als Startpunkt für den Bahn-Editor.</p>
        <div class="map-search">
            <input type="search" placeholder="Ort oder Golfclub suchen …" data-map-search>
            <button type="button" class="button button--secondary" data-map-search-button>Suchen</button>
        </div>
        <div class="course-map" data-course-map
             data-lat="{{ old('lat', $course->lat) }}" data-lng="{{ old('lng', $course->lng) }}"></div>
        <input type="hidden" name="lat" value="{{ old('lat', $course->lat) }}" data-lat-input>
        <input type="hidden" name="lng" value="{{ old('lng', $course->lng) }}" data-lng-input>

        <h2>Abschlagfarben</h2>
        <p class="muted">Reihenfolge = Anzeige; die erste Farbe ist der Standard. Leere Zeilen werden ignoriert.</p>
        <div class="tee-rows">
            @foreach ($teeRows as $i => $row)
                <div class="tee-row">
                    <input type="hidden" name="tee_sets[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                    <input name="tee_sets[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="z. B. Gelb" maxlength="40">
                    <select name="tee_sets[{{ $i }}][color]">
                        <option value="">Farbe …</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color->value }}" @selected(($row['color'] ?? '') === $color->value)>{{ $color->label() }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>

        <div class="admin-form__actions">
            <button class="button button--primary" type="submit">{{ $isNew ? 'Platz anlegen' : 'Speichern' }}</button>
        </div>
    </form>

    <x-slot:scripts>
        <script src="{{ asset_v('vendor/leaflet/leaflet.js') }}"></script>
        <script src="{{ asset_v('js/admin/map-tiles.js') }}"></script>
        <script src="{{ asset_v('js/admin/course-map.js') }}"></script>
    </x-slot:scripts>
</x-admin-layout>
