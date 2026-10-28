<?php

namespace App\Modules\Player\Models;

use App\Models\User;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\TeeSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gespielte Runde im persönlichen Runden-Tagebuch.
 */
class Round extends Model
{
    protected $fillable = ['user_id', 'course_id', 'tee_set_id', 'played_on', 'holes_played', 'score', 'note'];

    protected function casts(): array
    {
        return [
            'played_on' => 'date',
            'holes_played' => 'integer',
            'score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teeSet(): BelongsTo
    {
        return $this->belongsTo(TeeSet::class);
    }
}
