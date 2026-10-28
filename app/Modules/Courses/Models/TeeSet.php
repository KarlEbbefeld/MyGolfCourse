<?php

namespace App\Modules\Courses\Models;

use App\Modules\Courses\Enums\TeeColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeeSet extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'color' => TeeColor::class,
            'course_rating' => 'float',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function holeTees(): HasMany
    {
        return $this->hasMany(HoleTee::class);
    }
}
