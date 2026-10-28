<x-layout :title="'Yardage Book · '.$course->name" body-class="page-yardage">
    <section class="page-head yardage-head">
        <div class="container">
            <p class="eyebrow">Mein Yardage Book</p>
            <h1 class="page-head__title">{{ $course->name }}</h1>
            <p class="page-head__lead">
                @if ($teeSet) Abschlag {{ $teeSet->name }} · @endif Längen in {{ \App\Core\Support\Units::label() }}
                · Stand {{ now()->format('d.m.Y') }}
            </p>
            <div class="yardage-toolbar no-print">
                @if ($course->teeSets->count() > 1)
                    <form method="get" class="yardage-toolbar__tees">
                        @foreach ($course->teeSets as $option)
                            <button type="submit" name="abschlag" value="{{ $option->color->value }}" @class(['button', 'button--secondary', 'is-active' => $teeSet?->id === $option->id])>
                                <span class="tee-dot tee-dot--{{ $option->color->value }}"></span>{{ $option->name }}
                            </button>
                        @endforeach
                    </form>
                @endif
                <button type="button" class="button button--primary" onclick="window.print()">Drucken / als PDF speichern</button>
                <a class="button button--secondary" href="{{ route('courses.show', $course) }}">Zurück zum Platz</a>
            </div>
            @if ($bag->isEmpty())
                <p class="notice no-print" style="margin-top: var(--space-6)">Tipp: Mit eingetragenen Schlägerdistanzen steht bei jeder Bahn dein Spielplan. <a href="{{ route('account.settings') }}#bag">Bag einrichten</a></p>
            @endif
        </div>
    </section>

    <section class="section section--tight">
        <div class="container yardage-grid">
            @foreach ($holes as $item)
                @php
                    $hole = $item['hole'];
                    $tee = $item['tee'];
                    $explorer = $item['explorer'];
                    $plan = $tee ? $bag->plan($tee->length_m, $hole->par) : [];
                @endphp
                <article class="yardage-card">
                    <header class="yardage-card__head">
                        <span class="yardage-card__number num">{{ $hole->number }}</span>
                        <div>
                            <p class="yardage-card__facts num">Par {{ $hole->par }} @if ($tee) · {{ distance($tee->length_m) }} @endif @if ($hole->stroke_index) · HCP {{ $hole->stroke_index }} @endif</p>
                            @if ($hole->dogleg->value !== 'none')<p class="muted yardage-card__sub">{{ $hole->dogleg->label() }}</p>@endif
                        </div>
                    </header>

                    @if ($band = $explorer->distanceBand())
                        <table class="yardage-table num">
                            @foreach ($band as $stop)
                                <tr>
                                    <td>{{ $stop['label'] }}@if ($stop['poi'] && $stop['poi']->height()) <span class="muted">({{ $stop['poi']->height()->short() }})</span>@endif</td>
                                    <td>{{ distance($stop['distance_m']) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @elseif ($explorer->pois->isNotEmpty())
                        <ul class="yardage-list">
                            @foreach ($explorer->pois as $poi)
                                <li>{{ $poi->name }}@if ($poi->height()) ({{ $poi->height()->short() }})@endif</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($plan)
                        <p class="yardage-card__plan"><strong>Plan:</strong>
                            {{ collect($plan)->map(fn ($s) => $s['label'])->join(' → ') }}
                            @if (end($plan)['remaining'] > 5) <span class="muted">(Rest {{ distance(end($plan)['remaining']) }})</span>@endif
                        </p>
                    @endif

                    @if ($notes->has($hole->id))
                        <p class="yardage-card__note">{{ $notes[$hole->id] }}</p>
                    @else
                        <div class="yardage-card__lines" aria-hidden="true"></div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</x-layout>
