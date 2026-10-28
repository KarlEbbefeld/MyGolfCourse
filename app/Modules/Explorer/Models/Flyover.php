<?php

namespace App\Modules\Explorer\Models;

use App\Modules\Courses\Models\Hole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Drohnenüberflug einer Bahn (Abschlag → Grün).
 */
class Flyover extends Model
{
    protected $guarded = ['id'];

    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }

    /** Abspielbare Adresse: Pfade relativ zu public/ werden mit Versionsnummer ausgeliefert. */
    public function videoSrc(): string
    {
        return Str::startsWith($this->video_url, ['http://', 'https://']) ? $this->video_url : asset_v($this->video_url);
    }

    public function posterSrc(): ?string
    {
        if (! $this->poster_url) {
            return null;
        }

        return Str::startsWith($this->poster_url, ['http://', 'https://']) ? $this->poster_url : asset_v($this->poster_url);
    }
}
