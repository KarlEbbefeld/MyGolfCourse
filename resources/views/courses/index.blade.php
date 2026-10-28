<x-layout title="Golfplätze" description="Alle Golfplätze auf My Golf Course – mit Drohnenüberflug, 360°-Ansicht und vermessenen Hindernissen.">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Golfplätze</p>
            <h1 class="page-head__title">Finde deinen nächsten Platz</h1>
            <p class="page-head__lead">Wähle einen Platz und erkunde jede Bahn – vom Abschlag bis zum Grün.</p>

            <form class="filters" action="{{ route('courses.index') }}" method="get" role="search">
                <div class="search-field search-field--compact">
                    <label class="visually-hidden" for="course-search">Platz, Ort oder Club</label>
                    <x-icon name="search" :size="18" />
                    <input id="course-search" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Platz, Ort oder Club">
                </div>
                @if ($regions->isNotEmpty())
                    <label class="visually-hidden" for="course-region">Region</label>
                    <select id="course-region" name="region" class="select">
                        <option value="">Alle Regionen</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region }}" @selected(($filters['region'] ?? null) === $region)>{{ $region }}</option>
                        @endforeach
                    </select>
                @endif
                <label class="visually-hidden" for="course-holes">Löcher</label>
                <select id="course-holes" name="loecher" class="select">
                    <option value="">Alle Platzgrößen</option>
                    @foreach ([9, 18, 27] as $count)
                        <option value="{{ $count }}" @selected((int) ($filters['loecher'] ?? 0) === $count)>{{ $count }} Löcher</option>
                    @endforeach
                </select>
                <button class="button button--primary" type="submit">Suchen</button>
            </form>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <p class="result-count num">{{ trans_choice('courses.index.count', $courses->total(), ['count' => $courses->total()]) }}</p>

            @if ($courses->isEmpty())
                <div class="empty-state">
                    <p>Keine Plätze gefunden. Versuch es mit einem anderen Ort oder ohne Filter.</p>
                    <a class="button button--secondary" href="{{ route('courses.index') }}">Filter zurücksetzen</a>
                </div>
            @else
                <div class="course-grid">
                    @foreach ($courses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
                <div class="pagination">{{ $courses->links('partials.pagination') }}</div>
            @endif
        </div>
    </section>
</x-layout>
