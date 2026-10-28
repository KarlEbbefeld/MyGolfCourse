<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Core\Support\Geo;
use App\Http\Controllers\Controller;
use App\Modules\Courses\Enums\Dogleg;
use App\Modules\Courses\Models\Hole;
use App\Modules\Explorer\Models\Flyover;
use App\Modules\Explorer\Models\Panorama;
use App\Modules\Explorer\Support\PanoramaLinker;
use App\Modules\Hazards\Enums\MeasurementMethod;
use App\Modules\Hazards\Enums\MeasurementQuantity;
use App\Modules\Hazards\Enums\PoiType;
use App\Modules\Hazards\Models\PointOfInterest;
use App\Modules\Media\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bahn-Editor: Stammdaten + alles, was auf der Satellitenkarte gesetzt wird (Abschläge, Grün, Hindernisse,
 * Positionen der 360°-Aufnahmen). Die Karte (public/js/admin/hole-editor.js) schickt ihren Zustand als JSON im Feld „geo“.
 */
class HoleController extends Controller
{
    public function edit(Hole $hole): View
    {
        $hole->load(['course.teeSets', 'course.holes', 'tees']);
        $course = $hole->course;

        $panoramas = Panorama::query()->where('hole_id', $hole->id)->orderBy('sort_order')->get();
        $pois = PointOfInterest::query()->where('hole_id', $hole->id)->with('measurements')->orderBy('id')->get();

        return view('admin.holes.edit', [
            'hole' => $hole,
            'course' => $course,
            'teeSets' => $course->teeSets,
            'panoramas' => $panoramas,
            'flyover' => Flyover::query()->where('hole_id', $hole->id)->latest('id')->first(),
            'uploadLimit' => MediaStore::uploadLimit(),
            'editorState' => [
                'center' => $this->mapCenter($hole),
                'tees' => $course->teeSets->map(fn ($teeSet) => [
                    'teeSetId' => $teeSet->id,
                    'name' => $teeSet->name,
                    'color' => $teeSet->color->value,
                    'position' => ($t = $hole->teeFor($teeSet))?->hasPosition() ? ['lat' => $t->lat, 'lng' => $t->lng] : null,
                ])->values(),
                'green' => $hole->hasGreenPosition() ? ['lat' => $hole->green_lat, 'lng' => $hole->green_lng] : null,
                'hazards' => $pois->map(fn (PointOfInterest $poi) => [
                    'id' => $poi->id,
                    'type' => $poi->type->value,
                    'name' => $poi->name,
                    'description' => $poi->description,
                    'lat' => $poi->lat,
                    'lng' => $poi->lng,
                    'height' => $poi->height()?->value_m,
                    'heightAccuracy' => $poi->height()?->accuracy_m,
                    'planX' => $poi->plan_x,
                    'planY' => $poi->plan_y,
                ])->values(),
                'panoramas' => $panoramas->map(fn (Panorama $p) => [
                    'id' => $p->id,
                    'label' => $p->label,
                    'kind' => $p->kind,
                    'lat' => $p->lat,
                    'lng' => $p->lng,
                    'altitude' => $p->altitude_m,
                    'positionSource' => $p->position_source,
                ])->values(),
                'types' => collect(PoiType::cases())->map(fn (PoiType $t) => ['value' => $t->value, 'label' => $t->label(), 'icon' => $t->icon()])->values(),
            ],
        ]);
    }

    public function update(Request $request, Hole $hole): RedirectResponse
    {
        $hole->load(['course.teeSets', 'tees']);

        $data = $request->validate([
            'par' => ['required', 'integer', 'between:3,6'],
            'stroke_index' => ['nullable', 'integer', 'between:1,27'],
            'name' => ['nullable', 'string', 'max:100'],
            'dogleg' => ['required', Rule::enum(Dogleg::class)],
            'description' => ['nullable', 'string', 'max:3000'],
            'lengths' => ['array'],
            'lengths.*' => ['nullable', 'numeric', 'between:30,800'],
            'geo' => ['required', 'json'],
        ]);

        $geo = json_decode($data['geo'], true);
        $this->validateGeo($geo);

        DB::transaction(function () use ($hole, $data, $geo, $request) {
            $hole->update([
                'par' => $data['par'],
                'stroke_index' => $data['stroke_index'],
                'name' => $data['name'],
                'dogleg' => $data['dogleg'],
                'description' => $data['description'],
                'green_lat' => $geo['green']['lat'] ?? null,
                'green_lng' => $geo['green']['lng'] ?? null,
            ]);

            $this->syncTees($hole, $data['lengths'] ?? [], $geo['tees'] ?? []);
            $this->syncHazards($hole, $geo['hazards'] ?? [], $request->user()->id);
            $this->syncPanoramaPositions($hole, $geo['panoramas'] ?? []);
        });

        PanoramaLinker::relink($hole->fresh());

        return redirect()->route('admin.holes.edit', $hole)->with('status', 'Bahn '.$hole->number.' gespeichert.');
    }

    private function validateGeo(mixed $geo): void
    {
        $point = fn ($p) => $p === null || (is_array($p) && is_numeric($p['lat'] ?? null) && is_numeric($p['lng'] ?? null)
            && abs($p['lat']) <= 90 && abs($p['lng']) <= 180);

        $valid = is_array($geo)
            && $point($geo['green'] ?? null)
            && collect($geo['tees'] ?? [])->every(fn ($t) => is_array($t) && $point($t['position'] ?? null))
            && collect($geo['hazards'] ?? [])->every(fn ($h) => is_array($h) && PoiType::tryFrom((string) ($h['type'] ?? '')) !== null)
            && collect($geo['panoramas'] ?? [])->every(fn ($p) => is_array($p) && $point(['lat' => $p['lat'] ?? null, 'lng' => $p['lng'] ?? null]));

        if (! $valid) {
            throw ValidationException::withMessages(['geo' => 'Die Kartendaten sind unvollständig. Bitte Seite neu laden und erneut versuchen.']);
        }
    }

    /** Abschläge je Farbe: Position von der Karte, Länge aus dem Formular (leer = aus Koordinaten berechnet). */
    private function syncTees(Hole $hole, array $lengths, array $tees): void
    {
        foreach ($hole->course->teeSets as $teeSet) {
            $position = collect($tees)->firstWhere('teeSetId', $teeSet->id)['position'] ?? null;
            $length = $lengths[$teeSet->id] ?? null;

            if ($length === null && $position && $hole->green_lat !== null) {
                $length = round(Geo::distance($position['lat'], $position['lng'], $hole->green_lat, $hole->green_lng));
            }

            $existing = $hole->teeFor($teeSet);
            if ($length === null && ! $position) {
                $existing?->delete();

                continue;
            }

            $values = [
                'length_m' => $length ?? 0,
                'lat' => $position['lat'] ?? null,
                'lng' => $position['lng'] ?? null,
                'position_accuracy_m' => $position ? 3 : null,
            ];
            $existing ? $existing->update($values) : $hole->tees()->create($values + ['tee_set_id' => $teeSet->id]);
        }
    }

    /** Hindernisse: neue anlegen, geänderte aktualisieren, entfernte löschen; Höhe als Messung mit Historie. */
    private function syncHazards(Hole $hole, array $hazards, int $userId): void
    {
        $existing = PointOfInterest::query()->where('hole_id', $hole->id)->with('measurements')->get()->keyBy('id');
        $keep = [];

        foreach ($hazards as $row) {
            $type = PoiType::from($row['type']);
            $values = [
                'type' => $type,
                'name' => trim((string) ($row['name'] ?? '')) ?: $type->label(),
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'lat' => is_numeric($row['lat'] ?? null) ? $row['lat'] : null,
                'lng' => is_numeric($row['lng'] ?? null) ? $row['lng'] : null,
                'plan_x' => is_numeric($row['planX'] ?? null) ? min(max($row['planX'], 0), 1) : null,
                'plan_y' => is_numeric($row['planY'] ?? null) ? min(max($row['planY'], 0), 1) : null,
                'position_source' => 'manual_map',
                'position_accuracy_m' => 3,
                'status' => 'published',
            ];

            $poi = isset($row['id']) ? $existing->get($row['id']) : null;
            if ($poi) {
                $poi->update($values);
            } else {
                $poi = PointOfInterest::create($values + ['hole_id' => $hole->id]);
                $poi->setRelation('measurements', collect());
            }
            $keep[] = $poi->id;

            $this->syncHeight($poi, $row['height'] ?? null, $row['heightAccuracy'] ?? null, $userId);
        }

        $existing->except($keep)->each(fn (PointOfInterest $poi) => $poi->delete());
    }

    private function syncHeight(PointOfInterest $poi, mixed $height, mixed $accuracy, int $userId): void
    {
        $current = $poi->relationLoaded('measurements') ? $poi->height() : null;
        $height = is_numeric($height) && $height > 0 ? round((float) $height, 1) : null;
        $accuracy = is_numeric($accuracy) && $accuracy > 0 ? round((float) $accuracy, 1) : null;

        if ($current && $height !== null && abs($current->value_m - $height) < 0.05 && $current->accuracy_m == $accuracy) {
            return;
        }

        // Alte Messung bleibt als Historie erhalten
        $current?->update(['superseded_at' => now()]);

        if ($height !== null) {
            $poi->measurements()->create([
                'quantity' => MeasurementQuantity::Height,
                'value_m' => $height,
                'accuracy_m' => $accuracy,
                'method' => MeasurementMethod::LaserRangefinder,
                'measured_at' => now()->toDateString(),
                'measured_by' => $userId,
            ]);
        }
    }

    /** Positionen und Beschriftung der hochgeladenen 360°-Aufnahmen (Hochladen/Löschen: PanoramaController). */
    private function syncPanoramaPositions(Hole $hole, array $rows): void
    {
        $panoramas = Panorama::query()->where('hole_id', $hole->id)->get()->keyBy('id');

        foreach ($rows as $row) {
            $panorama = $panoramas->get($row['id'] ?? 0);
            if (! $panorama) {
                continue;
            }

            $moved = abs($panorama->lat - (float) $row['lat']) > 0.0000005 || abs($panorama->lng - (float) $row['lng']) > 0.0000005;
            $panorama->update([
                'label' => trim((string) ($row['label'] ?? '')) ?: $panorama->label,
                'kind' => in_array($row['kind'] ?? null, ['aerial', 'ground'], true) ? $row['kind'] : $panorama->kind,
                'altitude_m' => is_numeric($row['altitude'] ?? null) ? max(0, min(120, (float) $row['altitude'])) : $panorama->altitude_m,
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'position_source' => $moved ? 'manual' : $panorama->position_source,
            ]);
        }
    }

    /** @return array{lat: float, lng: float, zoom: int} */
    private function mapCenter(Hole $hole): array
    {
        if ($hole->hasGreenPosition()) {
            return ['lat' => $hole->green_lat, 'lng' => $hole->green_lng, 'zoom' => 17];
        }

        $tee = $hole->tees->first(fn ($t) => $t->hasPosition());
        if ($tee) {
            return ['lat' => $tee->lat, 'lng' => $tee->lng, 'zoom' => 17];
        }

        // Nachbarbahn als Anhaltspunkt, sonst Platz, sonst Deutschland
        $neighbour = $hole->course->holes->first(fn (Hole $h) => $h->hasGreenPosition() && abs($h->number - $hole->number) <= 2);
        if ($neighbour) {
            return ['lat' => $neighbour->green_lat, 'lng' => $neighbour->green_lng, 'zoom' => 16];
        }
        if ($hole->course->lat !== null) {
            return ['lat' => $hole->course->lat, 'lng' => $hole->course->lng, 'zoom' => 16];
        }

        return ['lat' => 51.16, 'lng' => 10.45, 'zoom' => 6];
    }
}
