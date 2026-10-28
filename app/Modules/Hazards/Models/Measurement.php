<?php

namespace App\Modules\Hazards\Models;

use App\Modules\Hazards\Enums\MeasurementMethod;
use App\Modules\Hazards\Enums\MeasurementQuantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => MeasurementQuantity::class,
            'method' => MeasurementMethod::class,
            'value_m' => 'float',
            'accuracy_m' => 'float',
            'measured_at' => 'date',
            'superseded_at' => 'datetime',
        ];
    }

    public function pointOfInterest(): BelongsTo
    {
        return $this->belongsTo(PointOfInterest::class);
    }

    /** Kurzform für Marker-Etiketten: „17 m“. */
    public function short(): string
    {
        return self::meters($this->value_m);
    }

    /** „ca. 17 m (± 1 m, gemessen 05/2027)“ */
    public function display(): string
    {
        $text = __('hazards.approx', ['value' => self::meters($this->value_m)]);

        $details = array_filter([
            $this->accuracy_m ? '± '.self::meters($this->accuracy_m) : null,
            $this->measured_at ? __('hazards.measured', ['date' => $this->measured_at->format('m/Y')]) : null,
        ]);

        return $details ? $text.' ('.implode(', ', $details).')' : $text;
    }

    /** 17 m, 0,5 m – deutsche Schreibweise, ganze Meter ohne Nachkommastelle. */
    public static function meters(float $value): string
    {
        $decimals = abs($value - round($value)) < 0.05 ? 0 : 1;

        return number_format($value, $decimals, ',', '.').' m';
    }
}
