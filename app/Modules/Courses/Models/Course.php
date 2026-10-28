<?php

namespace App\Modules\Courses\Models;

use App\Modules\Courses\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
            'capture_date' => 'date',
            'is_demo' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Clubzugehörigkeit hat keinen Fremdschlüssel (SQLite) → beim Löschen eines Platzes Mitglieder lösen
        static::deleting(fn (Course $course) => \Illuminate\Support\Facades\DB::table('users')->where('club_id', $course->id)->update(['club_id' => null]));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function holes(): HasMany
    {
        return $this->hasMany(Hole::class)->orderBy('number');
    }

    public function teeSets(): HasMany
    {
        return $this->hasMany(TeeSet::class)->orderBy('sort_order');
    }

    /** @param  Builder<Course>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', CourseStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    /** „Golfplatz Musterwald, 59071 Hamm“ – für Karten und Kopfzeilen. */
    public function location(): string
    {
        return trim(implode(' ', array_filter([$this->postal_code, $this->city])));
    }

    /** Standard-Abschlag für Längenangaben: erster in der Sortierung (meist Gelb). */
    public function defaultTeeSet(): ?TeeSet
    {
        return $this->teeSets->first();
    }
}
