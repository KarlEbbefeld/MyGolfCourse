<?php

namespace App\Modules\Hazards\Enums;

enum MeasurementMethod: string
{
    case LaserRangefinder = 'laser_rangefinder';
    case Clinometer = 'clinometer';
    case DroneAltitude = 'drone_altitude';
    case Photogrammetry = 'photogrammetry';
    case Estimate = 'estimate';

    public function label(): string
    {
        return __('hazards.methods.'.$this->value);
    }
}
