<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Core\Support\Geo;
use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Hole;
use App\Modules\Explorer\Models\Panorama;
use App\Modules\Explorer\Support\PanoramaLinker;
use App\Modules\Media\MediaStore;
use App\Modules\Media\PanoramaImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 360°-Aufnahmen einer Bahn: hochladen (GPS und Flughöhe aus dem Foto), Himmelsrichtung kalibrieren, löschen.
 */
class PanoramaController extends Controller
{
    public function store(Request $request, Hole $hole): RedirectResponse
    {
        $request->validate([
            'panoramas' => ['required', 'array', 'max:10'],
            'panoramas.*' => ['file', 'mimes:jpg,jpeg', 'max:61440'],
            'kind' => ['required', 'in:aerial,ground'],
        ], [
            'panoramas.required' => 'Bitte mindestens ein 360°-Foto auswählen.',
            'panoramas.*.mimes' => 'Nur JPG-Dateien (360°-Panorama, Seitenverhältnis 2:1).',
            'panoramas.*.max' => 'Ein Foto darf höchstens 60 MB groß sein.',
        ]);

        $hole->load(['course', 'tees']);
        $kind = $request->input('kind');
        $count = Panorama::query()->where('hole_id', $hole->id)->count();
        $warnings = [];

        foreach ($request->file('panoramas') as $index => $file) {
            $original = $file->getClientOriginalName();
            $path = MediaStore::store($file, MediaStore::holeDirectory($hole).'/360', 'pano');
            $meta = PanoramaImage::metadata(public_path($path));

            if ($meta['width'] && $meta['height'] && abs($meta['width'] / $meta['height'] - 2) > 0.05) {
                $warnings[] = "„{$original}“ ist kein 360°-Panorama im Format 2:1 ({$meta['width']}×{$meta['height']}) – die Ansicht wird verzerrt.";
            }

            [$lat, $lng, $source] = $meta['lat'] !== null
                ? [$meta['lat'], $meta['lng'], 'exif']
                : [...$this->fallbackPosition($hole, $index), 'fallback'];

            if ($source === 'fallback') {
                $warnings[] = "„{$original}“ hat keine GPS-Daten – Position bitte auf der Karte verschieben (gelb markiert).";
            }

            Panorama::create([
                'hole_id' => $hole->id,
                'label' => 'Position '.($count + $index + 1),
                'image_url' => $path,
                'image_mobile_url' => PanoramaImage::makeMobileVariant($path),
                'kind' => $kind,
                'lat' => $lat,
                'lng' => $lng,
                // Gelände als eben angenommen: Höhe = Flughöhe über dem Startpunkt
                'ground_elevation_m' => 0,
                'altitude_m' => $meta['relative_altitude'] ?? ($kind === 'ground' ? 1.6 : 30),
                'position_accuracy_m' => $source === 'exif' ? 5 : null,
                'position_source' => $source,
                'heading_deg' => 0,
                'heading_calibrated' => false,
                'default_pitch_deg' => $kind === 'aerial' ? -12 : 0,
                'sort_order' => $count + $index,
                'captured_at' => $meta['captured_at'],
            ]);
        }

        PanoramaLinker::relink($hole);

        $message = count($request->file('panoramas')).' 360°-Foto(s) hochgeladen. Als Nächstes jede Position kalibrieren: „Ausrichten“ → auf das Grün klicken.';

        return redirect()->route('admin.holes.edit', $hole)->with('status', $message)->with('warnings', $warnings);
    }

    /**
     * Ausrichtung: Im unkorrigierten Panorama wurde auf die Grünmitte geklickt (Blickwinkel yaw_deg).
     * Photo Sphere Viewer zeigt einen Bildpunkt y bei y − pan, im Viewer gilt pan = −heading (public/js/explorer.js).
     * Das Grün soll bei seiner Peilung B erscheinen: yaw + heading = B → heading = B − yaw.
     */
    public function calibrate(Request $request, Panorama $panorama): JsonResponse
    {
        $data = $request->validate(['yaw_deg' => ['required', 'numeric', 'between:-360,360']]);
        $hole = $panorama->loadMissing('hole')->hole;

        if (! $hole->hasGreenPosition()) {
            return response()->json(['message' => 'Zuerst das Grün auf der Karte setzen und speichern.'], 422);
        }

        $bearing = Geo::bearing($panorama->lat, $panorama->lng, $hole->green_lat, $hole->green_lng);
        $heading = fmod($bearing - (float) $data['yaw_deg'] + 720, 360);

        $panorama->update(['heading_deg' => round($heading, 2), 'heading_calibrated' => true]);

        return response()->json([
            'message' => 'Ausgerichtet: Bildmitte zeigt nach '.round($heading).'°.',
            'heading' => round($heading, 2),
        ]);
    }

    public function destroy(Panorama $panorama): RedirectResponse
    {
        $hole = $panorama->loadMissing('hole')->hole;
        MediaStore::delete($panorama->image_url);
        MediaStore::delete($panorama->image_mobile_url);
        $panorama->delete();

        PanoramaLinker::relink($hole);

        return redirect()->route('admin.holes.edit', $hole)->with('status', '360°-Position gelöscht.');
    }

    /**
     * Ohne GPS: zwischen Abschlag und Grün verteilen (bzw. Platzmitte), damit die Position sichtbar ist und verschoben werden kann.
     *
     * @return array{0: float, 1: float}
     */
    private function fallbackPosition(Hole $hole, int $index): array
    {
        $tee = $hole->tees->first(fn ($t) => $t->hasPosition());
        if ($tee && $hole->hasGreenPosition()) {
            $share = min(0.2 + 0.2 * $index, 0.95);

            return [$tee->lat + ($hole->green_lat - $tee->lat) * $share, $tee->lng + ($hole->green_lng - $tee->lng) * $share];
        }

        if ($hole->hasGreenPosition()) {
            return [$hole->green_lat, $hole->green_lng];
        }

        return [(float) ($hole->course->lat ?? 51.16), (float) ($hole->course->lng ?? 10.45)];
    }
}
