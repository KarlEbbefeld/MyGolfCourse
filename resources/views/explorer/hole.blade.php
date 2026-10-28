@php
    $teeParam = $teeSet?->color->value;
    $band = $explorer->distanceBand();
    $viewerData = $explorer->toViewerArray();
    $hasPlaceholders = collect($viewerData['nodes'])->contains(fn ($node) => $node['panorama'] === null);
    $holeTitle = __('explorer.hole_title', ['number' => $hole->number]);
    // Ohne 360°-Positionen, aber mit Video: direkt im Überflug starten
    $startMode = ! $explorer->hasPanoramas() && $explorer->hasFlyover() ? 'flyover' : 'explore';
@endphp
<x-layout :title="$holeTitle.' · '.$course->name" :description="$hole->description ?? $course->name" body-class="page-hole" no-footer>
    <x-slot:head>
        <link rel="stylesheet" href="{{ asset_v('vendor/psv/core/index.css') }}">
        <link rel="stylesheet" href="{{ asset_v('vendor/psv/markers-plugin/index.css') }}">
        <link rel="stylesheet" href="{{ asset_v('vendor/psv/virtual-tour-plugin/index.css') }}">
        <link rel="stylesheet" href="{{ asset_v('css/explorer.css') }}">
        {{-- Bibliotheken liegen lokal in public/vendor (kein CDN, keine Datenübertragung an Dritte) --}}
        <script type="importmap">{!! json_encode(['imports' => [
            'three' => asset('vendor/three/three.module.js'),
            '@photo-sphere-viewer/core' => asset('vendor/psv/core/index.module.js'),
            '@photo-sphere-viewer/markers-plugin' => asset('vendor/psv/markers-plugin/index.module.js'),
            '@photo-sphere-viewer/virtual-tour-plugin' => asset('vendor/psv/virtual-tour-plugin/index.module.js'),
        ]], JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:head>

    <div class="hole-layout">
        <section class="hole-stage" aria-label="{{ __('explorer.modes.explore') }}">
            <div class="hole-stage__bar">
                <a class="stage-button" href="{{ route('courses.show', $course) }}" aria-label="{{ __('explorer.overview') }}">
                    <x-icon name="arrow-left" :size="18" />
                    <span class="stage-button__text">{{ $course->name }}</span>
                </a>
                <div class="mode-switch" role="group" aria-label="{{ __('explorer.modes.label') }}">
                    <button type="button" @class(['mode-switch__option', 'is-active' => $startMode === 'explore']) data-mode="explore" aria-pressed="{{ $startMode === 'explore' ? 'true' : 'false' }}">{{ __('explorer.modes.explore') }}</button>
                    @if ($explorer->hasFlyover())
                        <button type="button" @class(['mode-switch__option', 'is-active' => $startMode === 'flyover']) data-mode="flyover" aria-pressed="{{ $startMode === 'flyover' ? 'true' : 'false' }}">{{ __('explorer.modes.flyover') }}</button>
                    @else
                        <button type="button" class="mode-switch__option" aria-pressed="false" disabled title="{{ __('explorer.flyover_soon') }}">{{ __('explorer.modes.flyover') }}</button>
                    @endif
                    @if ($hole->plan_image_url)
                        <button type="button" class="mode-switch__option" data-mode="plan" aria-pressed="false">{{ __('explorer.modes.plan') }}</button>
                    @endif
                </div>
                <button type="button" class="stage-button" data-fullscreen aria-label="{{ __('explorer.fullscreen') }}">
                    <x-icon name="expand" :size="18" />
                </button>
            </div>

            <div class="stage-panel" data-mode-panel="explore" @if ($startMode !== 'explore') hidden @endif>
                @if ($explorer->hasPanoramas())
                    <div class="viewer" id="viewer" data-viewer>
                        <p class="viewer__status" data-viewer-status>{{ __('explorer.loading') }}</p>
                    </div>
                    <p class="viewer__help">{{ __('explorer.help') }}</p>
                @else
                    <div class="viewer viewer--empty">
                        <p class="viewer__status">{{ __('explorer.no_panoramas') }}</p>
                    </div>
                @endif
            </div>

            @if ($hole->plan_image_url)
                <div class="stage-panel stage-panel--plan" data-mode-panel="plan" hidden>
                    <figure class="plan">
                        <div class="plan__canvas">
                            <img src="{{ \App\Modules\Explorer\Support\HoleExplorer::mediaUrl($hole->plan_image_url) }}" alt="Bahngrafik {{ $holeTitle }}" loading="lazy">
                            @foreach ($explorer->planPois() as $poi)
                                <button type="button" class="plan__pin mark mark--{{ $poi->type->value }}" style="left: {{ $poi->plan_x * 100 }}%; top: {{ $poi->plan_y * 100 }}%"
                                        data-plan-poi="{{ $poi->id }}" title="{{ $poi->name }}">
                                    <span class="mark__tag"><x-icon :name="$poi->type->icon()" :size="14" />@if ($poi->height()) {{ $poi->height()->short() }} @endif</span>
                                </button>
                            @endforeach
                        </div>
                    </figure>
                </div>
            @endif

            @if ($explorer->hasFlyover())
                <div class="stage-panel" data-mode-panel="flyover" @if ($startMode !== 'flyover') hidden @endif>
                    {{-- Ohne Ton, erst nach Klick (Datenvolumen, docs/MEDIA_PIPELINE.md § 3.2) --}}
                    <video class="flyover" controls playsinline muted preload="metadata" data-flyover
                           @if ($explorer->flyover->posterSrc()) poster="{{ $explorer->flyover->posterSrc() }}" @endif>
                        <source src="{{ $explorer->flyover->videoSrc() }}" type="video/mp4">
                    </video>
                </div>
            @endif
        </section>

        <aside class="hole-panel">
            <header class="hole-panel__head">
                <p class="eyebrow">{{ $course->name }}</p>
                <h1 class="hole-panel__title">{{ $holeTitle }}</h1>
                <p class="fact-line num">
                    <span>Par {{ $hole->par }}</span>
                    @if ($tee) <span>{{ distance($tee->length_m) }}</span> @endif
                    @if ($hole->stroke_index) <span>HCP {{ $hole->stroke_index }}</span> @endif
                    @if ($hole->dogleg->value !== 'none') <span>{{ $hole->dogleg->label() }}</span> @endif
                </p>
            </header>

            @if ($course->teeSets->count() > 1)
                <form class="tee-picker" method="get" action="{{ route('holes.show', ['course' => $course, 'number' => $hole->number]) }}">
                    <fieldset>
                        <legend class="panel-label">{{ __('explorer.choose_tee') }}</legend>
                        <div class="tee-picker__options">
                            @foreach ($course->teeSets as $option)
                                <label class="tee-picker__option">
                                    <input type="radio" name="abschlag" value="{{ $option->color->value }}" @checked($teeSet?->id === $option->id) data-autosubmit>
                                    <span><span class="tee-dot tee-dot--{{ $option->color->value }}" aria-hidden="true"></span>{{ $option->name }}
                                        @if ($hole->teeFor($option)) <span class="num muted">{{ distance($hole->teeFor($option)->length_m) }}</span> @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <noscript><button class="button button--secondary" type="submit">OK</button></noscript>
                    </fieldset>
                </form>
            @endif

            @if ($hasPlaceholders && $explorer->hasPanoramas())
                <p class="notice">{{ __('explorer.placeholder_notice') }}</p>
            @endif

            @if ($hole->description)
                <p class="hole-panel__description">{{ $hole->description }}</p>
            @endif

            {{-- Persönlicher Spielplan aus „Mein Bag“ (nur mit Konto) --}}
            @auth
                <section class="panel-section">
                    <h2 class="panel-label">Dein Spielplan</h2>
                    @if ($plan)
                        <ol class="game-plan">
                            @foreach ($plan as $i => $step)
                                <li class="game-plan__step">
                                    <span class="game-plan__shot num">{{ $i + 1 }}</span>
                                    <span><strong>{{ $step['label'] }}</strong> <span class="num muted">{{ distance($step['carry']) }}</span></span>
                                    <span class="game-plan__rest num">{{ $step['remaining'] > 5 ? 'Rest '.distance($step['remaining']) : 'am Grün' }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <p class="panel-hint">Aus deinen Carry-Distanzen, ohne Roll und Wind. <a href="{{ route('account.settings') }}#bag">Bag ändern</a></p>
                    @else
                        <p class="muted">Trag deine Schlägerdistanzen ein – dann zeigen wir dir hier, mit welchen Schlägern du die Bahn spielst und wo deine Bälle landen.</p>
                        <a class="button button--secondary" href="{{ route('account.settings') }}#bag">Mein Bag einrichten</a>
                    @endif
                </section>
            @else
                <aside class="member-cta">
                    <p class="member-cta__title">Mehr aus jeder Bahn holen</p>
                    <p>Mit kostenlosem Konto: dein Spielplan aus deinen Schlägerdistanzen, welche Hindernisse du erreichst, eigene Notizen je Bahn und dein Yardage Book zum Ausdrucken.</p>
                    <div class="member-cta__actions">
                        <a class="button button--primary" href="{{ route('register') }}">Konto erstellen</a>
                        <a class="button button--secondary" href="{{ route('login') }}">Anmelden</a>
                    </div>
                </aside>
            @endauth

            @if ($band)
                <section class="panel-section">
                    <h2 class="panel-label">{{ __('explorer.distance_band') }}</h2>
                    <ol class="distance-band">
                        <li class="distance-band__stop distance-band__stop--tee">
                            <span class="distance-band__name"><span class="tee-dot tee-dot--{{ $teeParam }}" aria-hidden="true"></span>{{ __('explorer.tee') }} {{ $teeSet->name }}</span>
                        </li>
                        @foreach ($band as $item)
                            <li class="distance-band__segment num">{{ distance($item['segment_m']) }}</li>
                            <li class="distance-band__stop distance-band__stop--{{ $item['kind'] }}">
                                <span class="distance-band__name">
                                    @if ($item['poi'])
                                        <x-icon :name="$item['poi']->type->icon()" :size="16" />
                                        {{ $item['label'] }}
                                        @if ($item['poi']->height()) <span class="muted">({{ $item['poi']->height()->short() }} hoch)</span> @endif
                                    @else
                                        <x-icon name="flag" :size="16" /> {{ $item['label'] }}
                                    @endif
                                </span>
                                <span class="distance-band__total num">{{ distance($item['distance_m']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="panel-hint">{{ __('explorer.distance_band_hint') }}</p>
                </section>
            @endif

            <section class="panel-section">
                <h2 class="panel-label">{{ __('explorer.hazards') }}</h2>
                @if ($explorer->pois->isEmpty())
                    <p class="muted">{{ __('explorer.no_hazards') }}</p>
                @else
                    <ul class="hazard-list">
                        @foreach ($explorer->pois as $poi)
                            <li class="hazard" data-poi="{{ $poi->id }}">
                                <span class="hazard__icon hazard__icon--{{ $poi->type->icon() }}"><x-icon :name="$poi->type->icon()" :size="18" /></span>
                                <div class="hazard__body">
                                    <p class="hazard__title">{{ $poi->name }} <span class="hazard__type">{{ $poi->type->label() }}</span></p>
                                    @if ($poi->height())
                                        <p class="hazard__value">{{ __('hazards.quantities.height') }}: <strong class="num">{{ $poi->height()->display() }}</strong></p>
                                    @endif
                                    @if (($fromTee = $explorer->distanceFromTee($poi)) !== null)
                                        <p class="hazard__meta num">{{ __('explorer.from_tee', ['distance' => distance($fromTee)]) }} · {{ __('explorer.to_green', ['distance' => distance($explorer->distanceToGreen($poi))]) }}</p>
                                        @unless ($bag->isEmpty())
                                            @php($reach = $bag->atDistance($fromTee))
                                            @if ($reach['lands'])
                                                <p class="hazard__bag hazard__bag--risk">Achtung: {{ collect($reach['lands'])->map(fn ($c) => $bag->describe($c))->join(', ') }} landet hier</p>
                                            @endif
                                            @if ($reach['carries'])
                                                <p class="hazard__bag">Sicher drüber ab {{ $bag->describe($reach['carries']) }}</p>
                                            @endif
                                        @endunless
                                    @endif
                                    @if ($poi->description)
                                        <p class="hazard__description">{{ $poi->description }}</p>
                                    @endif
                                    @if ($poi->hasPosition() && $explorer->hasPanoramas())
                                        <button type="button" class="link-button" data-focus-marker="poi-{{ $poi->id }}">{{ __('explorer.show_in_view') }}</button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @auth
                <section class="panel-section">
                    <h2 class="panel-label">Meine Notiz <span class="note-status" data-note-status></span></h2>
                    <form method="post" action="{{ route('notes.update', $hole) }}" data-note-form>
                        @csrf
                        @method('put')
                        <textarea class="note-field" name="body" rows="4" maxlength="2000"
                                  placeholder="z. B. Abschlag links halten, Grün fällt nach hinten ab …" data-note-field>{{ $note }}</textarea>
                        <noscript><button class="button button--secondary" type="submit">Notiz speichern</button></noscript>
                    </form>
                    <p class="panel-hint">Nur für dich sichtbar. Speichert automatisch. Alle Notizen im <a href="{{ route('yardage-book', $course) }}">Yardage Book</a>.</p>
                </section>
            @endauth

            <nav class="hole-pager" aria-label="Bahnen">
                @if ($previous)
                    <a class="button button--secondary" href="{{ route('holes.show', ['course' => $course, 'number' => $previous->number] + ($teeParam ? ['abschlag' => $teeParam] : [])) }}" rel="prev" data-hotkey="previous">
                        <x-icon name="arrow-left" :size="16" /> {{ __('explorer.hole_title', ['number' => $previous->number]) }}
                    </a>
                @else
                    <span></span>
                @endif
                @if ($next)
                    <a class="button button--primary" href="{{ route('holes.show', ['course' => $course, 'number' => $next->number] + ($teeParam ? ['abschlag' => $teeParam] : [])) }}" rel="next" data-hotkey="next">
                        {{ __('explorer.hole_title', ['number' => $next->number]) }} <x-icon name="arrow-right" :size="16" />
                    </a>
                @endif
            </nav>
        </aside>

        <x-scorecard-strip class="hole-layout__strip" :course="$course" :holes="$course->holes" :current="$hole->number" :tee="$teeParam" />
    </div>

    <x-slot:scripts>
        <script type="application/json" id="explorer-data">{!! json_encode($viewerData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @if ($explorer->hasFlyover())
            <script src="{{ asset_v('js/flyover.js') }}" defer></script>
        @endif
        @if ($explorer->hasPanoramas())
            <script type="module" src="{{ asset_v('js/explorer.js') }}"></script>
        @endif
    </x-slot:scripts>
</x-layout>
