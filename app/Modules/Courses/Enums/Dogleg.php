<?php

namespace App\Modules\Courses\Enums;

enum Dogleg: string
{
    case None = 'none';
    case Left = 'left';
    case Right = 'right';
    case Double = 'double';

    public function label(): string
    {
        return __('courses.dogleg.'.$this->value);
    }
}
