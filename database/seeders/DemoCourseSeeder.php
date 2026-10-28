<?php

namespace Database\Seeders;

use App\Core\Support\Geo;
use App\Modules\Courses\Enums\CourseStatus;
use App\Modules\Courses\Enums\Dogleg;
use App\Modules\Courses\Enums\TeeColor;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Hole;
use App\Modules\Courses\Models\TeeSet;
use App\Modules\Explorer\Models\Flyover;
use App\Modules\Explorer\Models\Panorama;
use App\Modules\Hazards\Enums\MeasurementMethod;
use App\Modules\Hazards\Enums\MeasurementQuantity;
use App\Modules\Hazards\Enums\PoiType;
use App\Modules\Hazards\Models\PointOfInterest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Erfundener 18-Loch-Demoplatz zum Ausprobieren der Street-View-Navigation, bevor echte Aufnahmen existieren.
 * Panoramen haben keine Bild-URL → der Viewer zeichnet Platzhalter. Koordinaten sind ausgedacht.
 *
 * Wiederholbar: Der Demoplatz wird bei jedem Lauf gelöscht und neu angelegt (nur er, erkennbar an is_demo).
 * Ohne Faker, weil auf dem Server ohne Dev-Pakete installiert wird.
 */
class DemoCourseSeeder extends Seeder
{
    public const SLUG = 'demo-golfclub-lippeauen';

    private const START = [51.6790, 7.8010];

    /** Par, Länge Gelb (m), Richtungsänderung zur Vorbahn (°), Dogleg, Wasser vor dem Grün */
    private const HOLES = [
        [4, 352, 0, 'none', false],
        [4, 371, 25, 'left', false],
        [3, 158, 40, 'none', true],
        [5, 468, 15, 'right', false],
        [4, 338, 30, 'none', false],
        [4, 389, 20, 'right', false],
        [3, 172, 35, 'none', false],
        [4, 346, 10, 'left', false],
        [5, 482, 25, 'none', true],
        [4, 364, 30, 'none', false],
        [3, 145, 15, 'none', true],
        [5, 455, 20, 'left', false],
        [4, 333, 25, 'none', false],
        [4, 377, 15, 'right', false],
        [3, 186, 30, 'none', false],
        [4, 358, 10, 'none', true],
        [4, 341, 20, 'left', false],
        [5, 491, 25, 'none', false],
    ];

    private const STROKE_INDEX = [7, 3, 15, 1, 11, 5, 17, 9, 13, 8, 16, 2, 12, 4, 18, 10, 6, 14];

    public function run(): void
    {
        // Vorhandenen Demoplatz behalten (Clubmitglieder, Club-Chat, Merklisten bleiben erhalten);
        // nur neu hochgeladene Überflug-Videos verknüpfen. Komplett neu anlegen: DEMO_DATA_RESET=true in der .env.
        $existing = Course::query()->where('slug', self::SLUG)->with('holes')->first();
        if ($existing && ! config('demo.reset')) {
            foreach ($existing->holes as $hole) {
                if (! Flyover::query()->where('hole_id', $hole->id)->exists()) {
                    $this->seedFlyover($hole);
                }
            }

            return;
        }

        DB::transaction(function () {
            // Einzeln löschen, damit Course::booted() die Clubmitglieder löst
            Course::query()->where('slug', self::SLUG)->get()->each->delete();

            $course = Course::create([
                'slug' => self::SLUG,
                'name' => 'Golfclub Lippeauen (Demo)',
                'short_description' => 'Erfundener Demoplatz zum Ausprobieren der 360°-Erkundung.',
                'description' => "Dieser Platz existiert nicht. Er dient als Beispiel, bis die ersten echten Aufnahmen vorliegen.\n\n"
                    ."Jede Bahn hat fünf Aufnahmepositionen vom Abschlag bis zum Grün – zwei in Augenhöhe, drei aus der Luft. "
                    .'Die Panoramen sind gezeichnete Platzhalter; Pfeile, Grün und Hindernisse liegen aber schon an den richtigen Koordinaten.',
                'club_name' => 'Demo-Golfclub e. V.',
                'postal_code' => '59071',
                'city' => 'Hamm',
                'region' => 'Nordrhein-Westfalen',
                'country' => 'DE',
                'lat' => self::START[0],
                'lng' => self::START[1],
                'holes_count' => 18,
                'par_total' => array_sum(array_column(self::HOLES, 0)),
                'status' => CourseStatus::Published,
                'published_at' => now(),
                'capture_date' => null,
                'is_demo' => true,
            ]);

            $tees = [
                'white' => $course->teeSets()->create(['name' => 'Weiß', 'color' => TeeColor::White, 'course_rating' => 72.4, 'slope' => 133, 'sort_order' => 2]),
                'yellow' => $course->teeSets()->create(['name' => 'Gelb', 'color' => TeeColor::Yellow, 'course_rating' => 70.8, 'slope' => 129, 'sort_order' => 1]),
                'red' => $course->teeSets()->create(['name' => 'Rot', 'color' => TeeColor::Red, 'course_rating' => 72.1, 'slope' => 127, 'sort_order' => 3]),
            ];

            [$lat, $lng] = self::START;
            $bearing = 35.0;

            foreach (self::HOLES as $i => [$par, $length, $turn, $dogleg, $water]) {
                $number = $i + 1;
                $bearing = fmod($bearing + $turn, 360);
                $hole = $this->seedHole($course, $tees, $number, $par, $length, $bearing, Dogleg::from($dogleg), $water, $lat, $lng);

                // Nächster Abschlag 35 m rechts hinter dem Grün
                [$lat, $lng] = Geo::destination($hole->green_lat, $hole->green_lng, fmod($bearing + 60, 360), 35);
            }
        });
    }

    /** @param  array<string, TeeSet>  $tees */
    private function seedHole(Course $course, array $tees, int $number, int $par, int $length, float $bearing, Dogleg $dogleg, bool $water, float $teeLat, float $teeLng): Hole
    {
        // Geländehöhe leicht wellig, damit Neigungen im Viewer sichtbar werden
        $teeElevation = 62 + 4 * sin($number);
        $greenElevation = 62 + 4 * sin($number + 0.8);

        // Dogleg: Knick bei 60 % der Länge, danach 28° nach links/rechts
        $cornerShare = 0.6;
        $turn = match ($dogleg) {
            Dogleg::Left => -28,
            Dogleg::Right => 28,
            default => 0,
        };
        [$cornerLat, $cornerLng] = Geo::destination($teeLat, $teeLng, $bearing, $length * $cornerShare);
        $secondBearing = fmod($bearing + $turn + 360, 360);
        [$greenLat, $greenLng] = Geo::destination($cornerLat, $cornerLng, $secondBearing, $length * (1 - $cornerShare));

        $hole = $course->holes()->create([
            'number' => $number,
            'par' => $par,
            'stroke_index' => self::STROKE_INDEX[$number - 1],
            'dogleg' => $dogleg,
            'description' => $this->description($par, $dogleg, $water),
            'green_lat' => $greenLat,
            'green_lng' => $greenLng,
            'green_elevation_m' => round($greenElevation, 2),
            'green_depth_m' => $par === 3 ? 26 : 32,
        ]);

        // Abschläge: Gelb am Startpunkt, Weiß 20 m dahinter, Rot weiter vorne
        $redOffset = $par === 3 ? 25 : 45;
        $positions = [
            'yellow' => [$teeLat, $teeLng, $length],
            'white' => [...Geo::destination($teeLat, $teeLng, fmod($bearing + 180, 360), 20), $length + 20],
            'red' => [...Geo::destination($teeLat, $teeLng, $bearing, $redOffset), $length - $redOffset],
        ];
        foreach ($positions as $color => [$lat, $lng, $len]) {
            $hole->tees()->create([
                'tee_set_id' => $tees[$color]->id,
                'length_m' => $len,
                'lat' => $lat,
                'lng' => $lng,
                'elevation_m' => round($teeElevation, 2),
                'position_accuracy_m' => 5,
            ]);
        }

        $this->seedFlyover($hole);
        $this->seedPanoramas($hole, $par, $length, $bearing, $teeLat, $teeLng, $cornerLat, $cornerLng, $secondBearing, $teeElevation, $greenElevation);
        $this->seedHazards($hole, $par, $length, $bearing, $dogleg, $water, $teeLat, $teeLng, $cornerLat, $cornerLng, $secondBearing, $teeElevation, $greenElevation);

        return $hole;
    }

    /**
     * Überflug, falls ein Video hochgeladen wurde: public/media/demo/bahn-<nr>.mp4
     * (optional Vorschaubild bahn-<nr>.jpg). Hochladen mit scripts/hostinger/upload-media.cmd.
     */
    private function seedFlyover(Hole $hole): void
    {
        $video = "media/demo/bahn-{$hole->number}.mp4";
        if (! is_file(public_path($video))) {
            return;
        }

        $poster = "media/demo/bahn-{$hole->number}.jpg";

        Flyover::create([
            'hole_id' => $hole->id,
            'video_url' => $video,
            'poster_url' => is_file(public_path($poster)) ? $poster : null,
            'direction' => 'tee_to_green',
        ]);
    }

    private function seedPanoramas(Hole $hole, int $par, int $length, float $bearing, float $teeLat, float $teeLng, float $cornerLat, float $cornerLng, float $secondBearing, float $teeElevation, float $greenElevation): void
    {
        $spots = [
            ['Abschlag Gelb', 'ground', $teeLat, $teeLng, $teeElevation, 1.6, true],
            ['Über dem Abschlag', 'aerial', ...Geo::destination($teeLat, $teeLng, fmod($bearing + 180, 360), 15), $teeElevation, 35, false],
        ];

        if ($par > 3) {
            [$lat, $lng] = Geo::destination($teeLat, $teeLng, $bearing, $length * 0.55);
            $spots[] = ['Landezone', 'aerial', $lat, $lng, ($teeElevation + $greenElevation) / 2, 30, false];
        }

        // 60 m vor dem Grün auf der Anspiellinie, Augenhöhe kurz vor dem Grün
        [$lat, $lng] = Geo::destination($hole->green_lat, $hole->green_lng, fmod($secondBearing + 180, 360), $par === 3 ? 50 : 60);
        $spots[] = ['Vor dem Grün', 'aerial', $lat, $lng, $greenElevation, 20, false];
        [$lat, $lng] = Geo::destination($hole->green_lat, $hole->green_lng, fmod($secondBearing + 180, 360), 18);
        $spots[] = ['Am Grün', 'ground', $lat, $lng, $greenElevation, 1.6, false];

        /** @var list<Panorama> $panoramas */
        $panoramas = [];
        foreach ($spots as $order => [$label, $kind, $lat, $lng, $ground, $altitude, $isStart]) {
            $panoramas[] = Panorama::create([
                'hole_id' => $hole->id,
                'label' => $label,
                'kind' => $kind,
                'lat' => $lat,
                'lng' => $lng,
                'ground_elevation_m' => round($ground, 2),
                'altitude_m' => $altitude,
                'position_accuracy_m' => 5,
                'heading_deg' => 0,
                'default_pitch_deg' => $kind === 'aerial' ? -12 : 0,
                'sort_order' => $order,
                'is_start' => $isStart,
            ]);
        }

        // Kette in beide Richtungen + Abkürzung Abschlag ↔ „Vor dem Grün“ wie ein Sprung in Street View
        foreach ($panoramas as $index => $panorama) {
            $neighbours = array_filter([$panoramas[$index - 1] ?? null, $panoramas[$index + 1] ?? null]);
            $panorama->linkedPanoramas()->attach(array_map(fn (Panorama $p) => $p->id, $neighbours));
        }
        $approach = $panoramas[count($panoramas) - 2];
        if ($approach->id !== $panoramas[1]->id) {
            $panoramas[0]->linkedPanoramas()->attach($approach->id);
            $approach->linkedPanoramas()->attach($panoramas[0]->id);
        }
    }

    private function seedHazards(Hole $hole, int $par, int $length, float $bearing, Dogleg $dogleg, bool $water, float $teeLat, float $teeLng, float $cornerLat, float $cornerLng, float $secondBearing, float $teeElevation, float $greenElevation): void
    {
        $left = fn (float $b) => fmod($b + 270, 360);
        $right = fn (float $b) => fmod($b + 90, 360);
        $at = fn (float $share, float $side, float $offset) => Geo::destination(...[...Geo::destination($teeLat, $teeLng, $bearing, $length * $share), $side, $offset]);
        $nearGreen = fn (float $before, float $side, float $offset) => Geo::destination(...[...Geo::destination($hole->green_lat, $hole->green_lng, fmod($secondBearing + 180, 360), $before), $side, $offset]);
        $midElevation = ($teeElevation + $greenElevation) / 2;

        if ($par > 3) {
            // Strategischer Baum links im Drive-Bereich – das Beispiel aus dem Projektauftrag
            $treeHeight = 14 + ($hole->number % 5) * 2;
            $this->poi($hole, PoiType::Tree, 'Eiche links', $at(0.48, $left($bearing), 24), $midElevation, 3,
                'Ragt in die Linie über die linke Fairwayseite. Wer abkürzen will, muss den Baum überspielen.',
                [[MeasurementQuantity::Height, $treeHeight, 1.0, MeasurementMethod::LaserRangefinder]]);

            $this->poi($hole, PoiType::BunkerFairway, 'Fairwaybunker rechts', $at(0.66, $right($bearing), 17), $midElevation, 2,
                'Fängt zu lange Abschläge nach rechts ab.',
                [[MeasurementQuantity::Depth, 1.1, 0.2, MeasurementMethod::Estimate]]);
        }

        if ($dogleg !== Dogleg::None) {
            $side = $dogleg === Dogleg::Left ? $left($bearing) : $right($bearing);
            $this->poi($hole, PoiType::TreeGroup, 'Baumgruppe am Knick', Geo::destination($cornerLat, $cornerLng, $side, 20), $midElevation, 3,
                'Abkürzung über die Bäume möglich, wenn der Ball hoch genug steigt.',
                [[MeasurementQuantity::Height, 18 + ($hole->number % 3) * 2, 1.5, MeasurementMethod::LaserRangefinder]]);
        }

        if ($water) {
            $this->poi($hole, PoiType::Water, 'Teich vor dem Grün', $nearGreen(35, $left($secondBearing), 4), $greenElevation - 1, 3,
                'Liegt quer vor dem Grün. Carry bis zum sicheren Grünanfang einplanen.', []);
        }

        $this->poi($hole, PoiType::BunkerGreenside, 'Grünbunker vorne links', $nearGreen(14, $left($secondBearing), 11), $greenElevation, 2,
            'Tief, mit steiler Kante zum Grün.',
            [[MeasurementQuantity::Depth, 1.4, 0.2, MeasurementMethod::Estimate]]);
    }

    /**
     * @param  array{0: float, 1: float}  $position
     * @param  list<array{0: MeasurementQuantity, 1: float, 2: float, 3: MeasurementMethod}>  $measurements
     */
    private function poi(Hole $hole, PoiType $type, string $name, array $position, float $elevation, int $importance, string $description, array $measurements): void
    {
        $poi = PointOfInterest::create([
            'hole_id' => $hole->id,
            'type' => $type,
            'name' => $name,
            'description' => $description,
            'lat' => $position[0],
            'lng' => $position[1],
            'elevation_m' => round($elevation, 2),
            'position_accuracy_m' => 5,
            'position_source' => 'manual_map',
            'importance' => $importance,
            'status' => 'published',
        ]);

        foreach ($measurements as [$quantity, $value, $accuracy, $method]) {
            $poi->measurements()->create([
                'quantity' => $quantity,
                'value_m' => $value,
                'accuracy_m' => $accuracy,
                'method' => $method,
                'measured_at' => '2026-10-01',
                'note' => 'Demowert',
            ]);
        }
    }

    private function description(int $par, Dogleg $dogleg, bool $water): string
    {
        $text = match ($par) {
            3 => 'Kurzes Par 3 – die Schlägerwahl entscheidet.',
            5 => 'Langes Par 5, für Longhitter in zwei Schlägen erreichbar.',
            default => 'Solides Par 4 mit klarer Linie vom Abschlag.',
        };

        if ($dogleg !== Dogleg::None) {
            $text .= ' Die Bahn knickt nach '.($dogleg === Dogleg::Left ? 'links' : 'rechts').' ab; die Baumgruppe am Knick erlaubt eine Abkürzung.';
        }

        if ($water) {
            $text .= ' Wasser vor dem Grün verlangt einen sicheren Annäherungsschlag.';
        }

        return $text;
    }
}
