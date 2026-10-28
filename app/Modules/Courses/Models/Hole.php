<?php

namespace App\Modules\Courses\Models;

use App\Modules\Courses\Enums\Dogleg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Bahn. Aufnahmen (Explorer) und Hindernisse (Hazards) hängen sich per hole_id an,
 * das Modul Courses kennt sie bewusst nicht (docs/ARCHITECTURE.md § 4).
 */
class Hole extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'dogleg' => Dogleg::class,
            'green_lat' => 'float',
            'green_lng' => 'float',
            'green_elevation_m' => 'float',
            'green_depth_m' => 'float',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tees(): HasMany
    {
        return $this->hasMany(HoleTee::class);
    }

    public function teeFor(?TeeSet $teeSet): ?HoleTee
    {
        if (! $teeSet) {
            return null;
        }

        return $this->tees->firstWhere('tee_set_id', $teeSet->id);
    }

    public function hasGreenPosition(): bool
    {
        return $this->green_lat !== null && $this->green_lng !== null;
    }
}
