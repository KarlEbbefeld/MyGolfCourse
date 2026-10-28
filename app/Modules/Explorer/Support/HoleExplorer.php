<?php

namespace App\Modules\Explorer\Support;

use App\Core\Support\Geo;
use App\Core\Support\Units;
use App\Modules\Courses\Models\Hole;
use App\Modules\Courses\Models\TeeSet;
use App\Modules\Explorer\Models\Flyover;
use App\Modules\Explorer\Models\Panorama;
use App\Modules\Hazards\Enums\PoiType;
use App\Modules\Hazards\Models\Measurement;
use App\Modules\Hazards\Models\PointOfInterest;
use Illuminate\Support\Collection;

/**
 * Führt Bahn, 360°-Positionen und Hindernisse zusammen – für die Bahnseite (Distanzband, Liste)
 * und den Viewer (JSON unter /api/v1/holes/{hole}/explorer, siehe public/js/explorer.js).
 *
 * Im Viewer läuft die Street-View-Navigation im GPS-Modus von Photo Sphere Viewer:
 * Pfeile zu den Nachbarpositionen und Marker der Hindernisse werden aus den Koordinaten berechnet
 * (Peilung = Richtung, Höhenunterschied = Neigung). Darum geben wir hier nur Koordinaten aus, keine Bildwinkel.
 */
class HoleExplorer
{
    /** Höhe der Fahne über dem Grün, damit der Grün-Marker sichtbar über dem Boden schwebt. */
    private const FLAG_HEIGHT_M = 2.2;

    /** @var Collection<int, Panorama> */
    public readonly Collection $panoramas;

    /** @var Collection<int, PointOfInterest> */
    public readonly Collection $pois;

    public readonly ?Flyover $flyover;

    public function __construct(public readonly Hole $hole, public readonly ?TeeSet $teeSet)
    {
        $this->flyover = Flyover::query()->where('hole_id', $hole->id)->latest('id')->first();

        $this->panoramas = Panorama::query()
            ->where('hole_id', $hole->id)
            ->with('linkedPanoramas')
            ->orderBy('sort_order')
            ->get();

        $this->pois = PointOfInterest::query()
            ->where('hole_id', $hole->id)
            ->published()
            ->with('measurements')
            ->orderByDesc('importance')
            ->get();
    }

    public function hasPanoramas(): bool
    {
        return $this->panoramas->isNotEmpty();
    }

    public function hasFlyover(): bool
    {
        return $this->flyover !== null;
    }

    public function startPanorama(): ?Panorama
    {
        return $this->panoramas->firstWhere('is_start', true) ?? $this->panoramas->first();
    }

    /**
     * Distanzband: Abschlag → Objekte (nach Entfernung) → Grün.
     *
     * @return list<array{label: string, distance_m: float, segment_m: float, poi: ?PointOfInterest, kind: string}>
     */
    public function distanceBand(): array
    {
        $tee = $this->hole->teeFor($this->teeSet);
        if (! $tee?->hasPosition() || ! $this->hole->hasGreenPosition()) {
            return [];
        }

        $items = $this->pois
            ->filter(fn (PointOfInterest $poi) => $poi->hasPosition())
            ->map(fn (PointOfInterest $poi) => [
                'label' => $poi->name,
                'distance_m' => Geo::distance($tee->lat, $tee->lng, $poi->lat, $poi->lng),
                'poi' => $poi,
                'kind' => 'poi',
            ])
            ->sortBy('distance_m')
            ->values()
            ->all();

        $items[] = [
            'label' => __('explorer.green'),
            'distance_m' => Geo::distance($tee->lat, $tee->lng, $this->hole->green_lat, $this->hole->green_lng),
            'poi' => null,
            'kind' => 'green',
        ];

        $previous = 0.0;
        foreach ($items as &$item) {
            $item['segment_m'] = max(0, $item['distance_m'] - $previous);
            $previous = $item['distance_m'];
        }

        return $items;
    }

    /** Berechnete Distanz vom gewählten Abschlag zu einem Objekt (null ohne Koordinaten). */
    public function distanceFromTee(PointOfInterest $poi): ?float
    {
        $tee = $this->hole->teeFor($this->teeSet);
        if (! $tee?->hasPosition() || ! $poi->hasPosition()) {
            return null;
        }

        return Geo::distance($tee->lat, $tee->lng, $poi->lat, $poi->lng);
    }

    /** Berechnete Distanz von einem Objekt bis zur Grünmitte. */
    public function distanceToGreen(PointOfInterest $poi): ?float
    {
        if (! $poi->hasPosition() || ! $this->hole->hasGreenPosition()) {
            return null;
        }

        return Geo::distance($poi->lat, $poi->lng, $this->hole->green_lat, $this->hole->green_lng);
    }

    /**
     * Daten für den Viewer. Koordinaten im Format von Photo Sphere Viewer: [lng, lat, höhe].
     *
     * @return array<string, mixed>
     */
    public function toViewerArray(): array
    {
        $markers = $this->markers();
        $start = $this->startPanorama();

        return [
            'hole' => [
                'id' => $this->hole->id,
                'number' => $this->hole->number,
                'par' => $this->hole->par,
            ],
            'startNodeId' => $start ? $this->nodeId($start) : null,
            'flyover' => $this->flyover ? [
                'src' => $this->flyover->videoSrc(),
                'poster' => $this->flyover->posterSrc(),
                'direction' => $this->flyover->direction,
                ...$this->flyoverTimeline(),
            ] : null,
            'nodes' => $this->panoramas->map(fn (Panorama $p) => [
                'id' => $this->nodeId($p),
                'name' => $p->label,
                'caption' => __('explorer.caption', ['number' => $this->hole->number, 'label' => $p->label]),
                'panorama' => self::mediaUrl($p->image_url),
                'panoramaMobile' => self::mediaUrl($p->image_mobile_url),
                'gps' => [$p->lng, $p->lat, round($p->cameraElevation(), 2)],
                'headingDeg' => $p->heading_deg,
                'defaultYawDeg' => $p->default_yaw_deg ?? $this->bearingToGreen($p),
                'defaultPitchDeg' => $p->default_pitch_deg,
                'kind' => $p->kind,
                'placeholder' => $p->image_url ? null : $this->placeholderHints($p),
                'links' => $p->linkedPanoramas->map(fn (Panorama $target) => [
                    'nodeId' => $this->nodeId($target),
                    'gps' => [$target->lng, $target->lat, round($target->cameraElevation(), 2)],
                ])->values()->all(),
                'markers' => $markers,
            ])->values()->all(),
        ];
    }

    /**
     * Haltepunkte und Hinweise im Überflug, ohne von Hand gepflegte Zeiten:
     * Jede Position bekommt ihren Anteil an der Strecke Abschlag → Grün (0 … 1).
     * Der Player rechnet daraus die Sekunde im Video (Anteil × Videolänge) – passt bei gleichmäßigem Flugtempo.
     *
     * @return array{stops: list<array<string, mixed>>, cues: list<array<string, mixed>>}
     */
    private function flyoverTimeline(): array
    {
        $tee = $this->hole->teeFor($this->teeSet);
        if (! $tee?->hasPosition() || ! $this->hole->hasGreenPosition()) {
            return ['stops' => [], 'cues' => []];
        }

        // Anteil = Weg vom Abschlag / (Weg vom Abschlag + Rest bis Grün) – funktioniert auch bei Doglegs
        $share = function (float $lat, float $lng) use ($tee): float {
            $fromTee = Geo::distance($tee->lat, $tee->lng, $lat, $lng);
            $toGreen = Geo::distance($lat, $lng, $this->hole->green_lat, $this->hole->green_lng);

            return round($fromTee / max($fromTee + $toGreen, 1), 4);
        };

        $stops = [];
        foreach ($this->panoramas->sortBy(fn (Panorama $p) => $share($p->lat, $p->lng)) as $panorama) {
            $fraction = $share($panorama->lat, $panorama->lng);
            // Positionen dicht beieinander (z. B. Abschlag am Boden und darüber) sind ein Haltepunkt
            if ($stops !== [] && $fraction - end($stops)['fraction'] < 0.06) {
                continue;
            }
            $stops[] = [
                'fraction' => $stops === [] ? 0.0 : $fraction,
                'label' => $panorama->label,
                'nodeId' => $this->nodeId($panorama),
                'distance' => Units::distance(Geo::distance($tee->lat, $tee->lng, $panorama->lat, $panorama->lng)),
            ];
        }
        if ($stops === [] || end($stops)['fraction'] < 0.94) {
            $stops[] = ['fraction' => 1.0, 'label' => __('explorer.green'), 'nodeId' => null, 'distance' => null];
        }

        $cues = $this->pois
            ->filter(fn (PointOfInterest $poi) => $poi->hasPosition())
            ->map(fn (PointOfInterest $poi) => [
                'fraction' => $share($poi->lat, $poi->lng),
                'poiId' => $poi->id,
                'icon' => $poi->type->icon(),
                'typeLabel' => $poi->type->label(),
                'title' => $poi->name,
                'details' => $this->poiDetails($poi),
            ])
            ->sortBy('fraction')
            ->values()
            ->all();

        return ['stops' => $stops, 'cues' => $cues];
    }

    /** @return list<string> Höhe, Distanz vom Abschlag, Rest bis Grün */
    private function poiDetails(PointOfInterest $poi): array
    {
        $height = $poi->height();
        $fromTee = $this->distanceFromTee($poi);
        $toGreen = $this->distanceToGreen($poi);

        return array_values(array_filter([
            $height ? __('hazards.quantities.height').': '.$height->display() : null,
            $fromTee !== null ? __('explorer.from_tee', ['distance' => Units::distance($fromTee)]) : null,
            $toGreen !== null ? __('explorer.to_green', ['distance' => Units::distance($toGreen)]) : null,
        ]));
    }

    /** Pfade relativ zu public/ (hochgeladene Medien) → volle Adresse mit Versionsnummer; https-Adressen bleiben. */
    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : asset_v($path);
    }

    /** Hindernisse mit Lage auf der Bahngrafik (Modus „Plan“). */
    public function planPois(): Collection
    {
        return $this->pois->filter(fn (PointOfInterest $poi) => $poi->hasPlanPosition())->values();
    }

    private function nodeId(Panorama $panorama): string
    {
        return 'p'.$panorama->id;
    }

    private function bearingToGreen(Panorama $panorama): ?float
    {
        if (! $this->hole->hasGreenPosition()) {
            return null;
        }

        return round(Geo::bearing($panorama->lat, $panorama->lng, $this->hole->green_lat, $this->hole->green_lng), 1);
    }

    /**
     * Hinweise für das gezeichnete Platzhalter-Panorama (solange keine echte Aufnahme existiert):
     * Richtung und Entfernung zum Grün, damit Fahne und Fairway an der richtigen Stelle liegen.
     *
     * @return array<string, mixed>
     */
    private function placeholderHints(Panorama $panorama): array
    {
        if (! $this->hole->hasGreenPosition()) {
            return ['label' => $panorama->label];
        }

        $distance = Geo::distance($panorama->lat, $panorama->lng, $this->hole->green_lat, $this->hole->green_lng);
        $dz = (($this->hole->green_elevation_m ?? 0) + self::FLAG_HEIGHT_M) - $panorama->cameraElevation();

        return [
            'label' => $panorama->label,
            'greenBearingDeg' => round(Geo::bearing($panorama->lat, $panorama->lng, $this->hole->green_lat, $this->hole->green_lng), 2),
            'greenPitchDeg' => round(rad2deg(atan2($dz, max($distance, 1))), 2),
            'greenDistanceM' => round($distance),
            'cameraHeightM' => $panorama->altitude_m,
        ];
    }

    /**
     * Marker für alle Positionen (im GPS-Modus berechnet der Viewer die Lage je Position selbst).
     *
     * @return list<array<string, mixed>>
     */
    private function markers(): array
    {
        $markers = $this->pois
            ->filter(fn (PointOfInterest $poi) => $poi->hasPosition())
            ->map(function (PointOfInterest $poi) {
                $height = $poi->height();
                // Bäume: Marker an der Spitze – dort, wo der Ball drüber muss
                $top = ($poi->elevation_m ?? 0) + (in_array($poi->type, [PoiType::Tree, PoiType::TreeGroup], true) && $height ? $height->value_m : 0.5);

                return [
                    'id' => 'poi-'.$poi->id,
                    'poiId' => $poi->id,
                    'type' => $poi->type->value,
                    'icon' => $poi->type->icon(),
                    'title' => $poi->name,
                    'typeLabel' => $poi->type->label(),
                    'label' => $height?->short(),
                    'details' => $this->poiDetails($poi),
                    'gps' => [$poi->lng, $poi->lat, round($top, 2)],
                ];
            })
            ->values()
            ->all();

        if ($this->hole->hasGreenPosition()) {
            $markers[] = [
                'id' => 'green',
                'poiId' => null,
                'type' => 'green',
                'icon' => 'flag',
                'title' => __('explorer.green'),
                'typeLabel' => __('explorer.green'),
                'label' => null,
                'details' => [],
                'gps' => [$this->hole->green_lng, $this->hole->green_lat, round(($this->hole->green_elevation_m ?? 0) + self::FLAG_HEIGHT_M, 2)],
            ];
        }

        return $markers;
    }
}
