<?php

namespace App\Modules\Hazards\Models;

use App\Modules\Courses\Models\Hole;
use App\Modules\Hazards\Enums\MeasurementQuantity;
use App\Modules\Hazards\Enums\PoiType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointOfInterest extends Model
{
    protected $table = 'points_of_interest';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => PoiType::class,
            'lat' => 'float',
            'lng' => 'float',
            'elevation_m' => 'float',
            'position_accuracy_m' => 'float',
            'geometry' => 'array',
            'importance' => 'integer',
            'plan_x' => 'float',
            'plan_y' => 'float',
        ];
    }

    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    /** @param  Builder<PointOfInterest>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    public function hasPosition(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    public function hasPlanPosition(): bool
    {
        return $this->plan_x !== null && $this->plan_y !== null;
    }

    /** Jüngste gültige Messung einer Größe (setzt geladene measurements voraus). */
    public function currentMeasurement(MeasurementQuantity $quantity): ?Measurement
    {
        return $this->measurements
            ->filter(fn (Measurement $m) => $m->quantity === $quantity && $m->superseded_at === null)
            ->sortByDesc(fn (Measurement $m) => $m->measured_at?->timestamp ?? 0)
            ->first();
    }

    public function height(): ?Measurement
    {
        return $this->currentMeasurement(MeasurementQuantity::Height);
    }
}
