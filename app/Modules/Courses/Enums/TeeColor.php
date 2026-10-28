<?php

namespace App\Modules\Courses\Enums;

/**
 * Abschlagfarben. Der Wert ist zugleich URL-Parameter (?abschlag=yellow) und CSS-Modifier (.tee-dot--yellow).
 */
enum TeeColor: string
{
    case White = 'white';
    case Yellow = 'yellow';
    case Blue = 'blue';
    case Red = 'red';
    case Orange = 'orange';
    case Black = 'black';
    case Green = 'green';
    case Other = 'other';

    public function label(): string
    {
        return __('courses.tee_colors.'.$this->value);
    }
}
