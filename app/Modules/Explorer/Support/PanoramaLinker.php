<?php

namespace App\Modules\Explorer\Support;

use App\Core\Support\Geo;
use App\Modules\Courses\Models\Hole;
use App\Modules\Explorer\Models\Panorama;
use Illuminate\Support\Facades\DB;

/**
 * Verbindet die 360°-Positionen einer Bahn automatisch zu einem Weg vom Abschlag zum Grün (Street View):
 * Reihenfolge nach Anteil an der Strecke Abschlag → Grün, jede Position mit Vorgänger und Nachfolger verbunden.
 * Ohne Grün-Koordinate wird nach Entfernung zum ersten Abschlag sortiert.
 */
class PanoramaLinker
{
    public static function relink(Hole $hole): void
    {
        $hole->loadMissing('tees');
        $panoramas = Panorama::query()->where('hole_id', $hole->id)->get();
        if ($panoramas->isEmpty()) {
            return;
        }

        $tee = $hole->tees->first(fn ($t) => $t->hasPosition());

        $sorted = $panoramas->sortBy(function (Panorama $p) use ($hole, $tee) {
            if ($hole->hasGreenPosition()) {
                $toGreen = Geo::distance($p->lat, $p->lng, $hole->green_lat, $hole->green_lng);
                $fromTee = $tee ? Geo::distance($tee->lat, $tee->lng, $p->lat, $p->lng) : 0;

                return $tee ? $fromTee / max($fromTee + $toGreen, 1) : -$toGreen;
            }

            return $tee ? Geo::distance($tee->lat, $tee->lng, $p->lat, $p->lng) : $p->id;
        })->values();

        DB::transaction(function () use ($sorted) {
            DB::table('panorama_links')->whereIn('from_panorama_id', $sorted->pluck('id'))->delete();

            foreach ($sorted as $index => $panorama) {
                $panorama->update(['sort_order' => $index, 'is_start' => $index === 0]);

                $neighbours = array_filter([$sorted[$index - 1] ?? null, $sorted[$index + 1] ?? null]);
                $panorama->linkedPanoramas()->attach(array_map(fn (Panorama $p) => $p->id, $neighbours));
            }
        });
    }
}
