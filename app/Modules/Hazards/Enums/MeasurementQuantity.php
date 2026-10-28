<?php

namespace App\Modules\Hazards\Enums;

enum MeasurementQuantity: string
{
    case Height = 'height';
    case Width = 'width';
    case Depth = 'depth';
    case CarryFromTee = 'carry_from_tee';
    case DistanceToGreen = 'distance_to_green';

    public function label(): string
    {
        return __('hazards.quantities.'.$this->value);
    }
}
