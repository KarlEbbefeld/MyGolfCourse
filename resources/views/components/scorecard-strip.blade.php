@props(['course', 'holes', 'current' => null, 'tee' => null])
{{-- Scorecard-Leiste: Bahnnummer oben, Par darunter – Navigation zwischen den Bahnen (docs/DESIGN_CONCEPT.md § 3) --}}
<nav {{ $attributes->merge(['class' => 'scorecard-strip']) }} aria-label="{{ __('explorer.scorecard') }}">
    <ol class="scorecard-strip__list" data-scorecard-strip>
        @foreach ($holes as $hole)
            <li>
                <a href="{{ route('holes.show', ['course' => $course, 'number' => $hole->number] + ($tee ? ['abschlag' => $tee] : [])) }}"
                   @class(['scorecard-strip__hole', 'is-current' => $current === $hole->number])
                   @if ($current === $hole->number) aria-current="page" @endif
                   aria-label="{{ __('explorer.hole_title', ['number' => $hole->number]) }}, Par {{ $hole->par }}">
                    <span class="scorecard-strip__number num">{{ $hole->number }}</span>
                    <span class="scorecard-strip__par num">{{ $hole->par }}</span>
                </a>
            </li>
        @endforeach
    </ol>
</nav>
