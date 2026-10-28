@props(['course', 'aspect' => 0.625, 'numbers' => true])
@php($diagram = \App\Modules\Courses\Support\CourseDiagram::for($course, $aspect))
{{-- Platzgrafik aus Koordinaten (Yardage-Book-Stil); ohne Koordinaten eine ruhige Fläche --}}
<div {{ $attributes->merge(['class' => 'course-diagram']) }} aria-hidden="true">
    @if ($diagram)
        <svg viewBox="0 0 {{ $diagram['width'] }} {{ $diagram['height'] }}" preserveAspectRatio="xMidYMid slice">
            <defs>
                <pattern id="grid-{{ $course->id }}" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M40 0H0V40" fill="none" class="course-diagram__grid" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid-{{ $course->id }})" />
            @foreach ($diagram['holes'] as $h)
                <line x1="{{ $h['x1'] }}" y1="{{ $h['y1'] }}" x2="{{ $h['x2'] }}" y2="{{ $h['y2'] }}" class="course-diagram__fairway" />
            @endforeach
            @foreach ($diagram['holes'] as $h)
                <line x1="{{ $h['x1'] }}" y1="{{ $h['y1'] }}" x2="{{ $h['x2'] }}" y2="{{ $h['y2'] }}" class="course-diagram__line" />
                <circle cx="{{ $h['x2'] }}" cy="{{ $h['y2'] }}" r="9" class="course-diagram__green" />
                <circle cx="{{ $h['x1'] }}" cy="{{ $h['y1'] }}" r="4" class="course-diagram__tee" />
                @if ($numbers)
                    <text x="{{ $h['x1'] }}" y="{{ $h['y1'] - 12 }}" class="course-diagram__number">{{ $h['number'] }}</text>
                @endif
            @endforeach
        </svg>
    @endif
</div>
