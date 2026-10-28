<?php

namespace App\Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoleTee extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'length_m' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'elevation_m' => 'float',
            'position_accuracy_m' => 'float',
        ];
    }

    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }

    public function teeSet(): BelongsTo
    {
        return $this->belongsTo(TeeSet::class);
    }

    public function hasPosition(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }
}
