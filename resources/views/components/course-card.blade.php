@props(['course'])
<article class="course-card">
    <a class="course-card__link" href="{{ route('courses.show', $course) }}">
        <div class="course-card__media">
            @if ($course->hero_image_url)
                <img src="{{ \App\Modules\Explorer\Support\HoleExplorer::mediaUrl($course->hero_image_url) }}" alt="" loading="lazy" decoding="async">
            @else
                <x-course-diagram :course="$course" />
            @endif
            <span class="course-card__badges">
                @if ($course->is_demo)
                    <span class="badge badge--dark">Demo</span>
                @endif
            </span>
        </div>
        <div class="course-card__body">
            <h3 class="course-card__title">{{ $course->name }}</h3>
            <p class="course-card__location">
                <x-icon name="pin" :size="15" /> {{ $course->location() ?: $course->region }}@if ($course->region && $course->location()) · {{ $course->region }}@endif
            </p>
            <p class="course-card__facts num">
                {{ $course->holes_count }} Bahnen
                @if ($course->par_total) · Par {{ $course->par_total }} @endif
            </p>
        </div>
    </a>
</article>
