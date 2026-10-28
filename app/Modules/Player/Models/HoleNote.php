<?php

namespace App\Modules\Player\Models;

use App\Models\User;
use App\Modules\Courses\Models\Hole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Private Notiz eines Nutzers zu einer Bahn („mein Yardage Book“). Nur für den Nutzer selbst sichtbar.
 */
class HoleNote extends Model
{
    protected $fillable = ['user_id', 'hole_id', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }
}
