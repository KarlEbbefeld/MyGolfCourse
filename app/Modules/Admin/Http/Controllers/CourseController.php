<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Enums\CourseStatus;
use App\Modules\Courses\Enums\TeeColor;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Plätze anlegen und bearbeiten. Beim Anlegen entstehen Abschlagfarben und alle Bahnen (Par 4 als Startwert).
 */
class CourseController extends Controller
{
    public function index(): View
    {
        return view('admin.courses.index', [
            'courses' => Course::query()->withCount('holes')->orderBy('is_demo')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.courses.form', [
            'course' => new Course(['country' => 'DE', 'holes_count' => 18, 'status' => CourseStatus::Draft]),
            'teeSets' => collect([
                ['id' => null, 'name' => 'Gelb', 'color' => 'yellow'],
                ['id' => null, 'name' => 'Rot', 'color' => 'red'],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $course = DB::transaction(function () use ($data) {
            $course = Course::create($this->courseAttributes($data) + [
                'slug' => $this->uniqueSlug($data['name']),
                'holes_count' => $data['holes_count'],
            ]);

            $this->syncTeeSets($course, $data['tee_sets'] ?? []);

            for ($number = 1; $number <= $data['holes_count']; $number++) {
                $course->holes()->create(['number' => $number, 'par' => 4, 'dogleg' => 'none']);
            }

            return $course;
        });

        return redirect()->route('admin.courses.edit', $course)
            ->with('status', 'Platz angelegt. Jetzt die Bahnen bearbeiten: Abschlag, Grün und Hindernisse auf der Karte setzen.');
    }

    public function edit(Course $course): View
    {
        $course->load(['teeSets', 'holes.tees']);

        return view('admin.courses.form', [
            'course' => $course,
            'teeSets' => $course->teeSets->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color->value]),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request, $course);

        DB::transaction(function () use ($course, $data) {
            $attributes = $this->courseAttributes($data);
            if ($course->published_at) {
                unset($attributes['published_at']);
            }
            $course->update($attributes);
            $this->syncTeeSets($course, $data['tee_sets'] ?? []);
        });

        return redirect()->route('admin.courses.edit', $course)->with('status', 'Gespeichert.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Course $course): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'club_name' => ['nullable', 'string', 'max:150'],
            'street' => ['nullable', 'string', 'max:150'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'holes_count' => [$course ? 'nullable' : 'required', 'integer', 'in:9,18,27'],
            'capture_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(CourseStatus::class)],
            'tee_sets' => ['array', 'max:6'],
            'tee_sets.*.id' => ['nullable', 'integer'],
            'tee_sets.*.name' => ['nullable', 'string', 'max:40'],
            'tee_sets.*.color' => ['nullable', Rule::enum(TeeColor::class)],
        ]);

        // Veröffentlichen darf nur die Rolle Admin (docs/ARCHITECTURE.md § 7)
        $wantsPublish = $data['status'] === CourseStatus::Published->value;
        $wasPublished = $course?->isPublished() ?? false;
        if ($wantsPublish !== $wasPublished) {
            Gate::authorize('publish-content');
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function courseAttributes(array $data): array
    {
        $attributes = collect($data)->only([
            'name', 'short_description', 'description', 'club_name', 'street', 'postal_code', 'city',
            'region', 'website_url', 'phone', 'lat', 'lng', 'capture_date', 'status',
        ])->all();

        if ($data['status'] === CourseStatus::Published->value) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }

    /** Abschlagfarben anlegen/ändern; leere Zeilen werden ignoriert. Entfernen bewusst nicht (Längen hängen daran). */
    private function syncTeeSets(Course $course, array $rows): void
    {
        $order = 1;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || empty($row['color'])) {
                continue;
            }

            $values = ['name' => $name, 'color' => $row['color'], 'sort_order' => $order++];
            $existing = ! empty($row['id']) ? $course->teeSets()->whereKey($row['id'])->first() : null;
            $existing ? $existing->update($values) : $course->teeSets()->create($values);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'platz';
        $slug = $base;
        $i = 2;
        while (Course::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
