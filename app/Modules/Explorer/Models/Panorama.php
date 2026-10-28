<?php

namespace App\Modules\Explorer\Models;

use App\Modules\Courses\Models\Hole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eine 360°-Aufnahmeposition auf einer Bahn – ein „Knoten“ der Street-View-Navigation.
 */
class Panorama extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'ground_elevation_m' => 'float',
            'altitude_m' => 'float',
            'position_accuracy_m' => 'float',
            'heading_deg' => 'float',
            'default_yaw_deg' => 'float',
            'default_pitch_deg' => 'float',
            'is_start' => 'boolean',
            'heading_calibrated' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }

    /** Positionen, zu denen man von hier aus „weitergehen“ kann (Pfeile im Viewer). */
    public function linkedPanoramas(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'panorama_links', 'from_panorama_id', 'to_panorama_id')
            ->withTimestamps();
    }

    /** Absolute Kamerahöhe (Boden + Flug- bzw. Stativhöhe), für Pfeile und Marker im GPS-Modus. */
    public function cameraElevation(): float
    {
        return $this->ground_elevation_m + $this->altitude_m;
    }
}
