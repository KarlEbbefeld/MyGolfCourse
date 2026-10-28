<?php

namespace App\Modules\Courses\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __('courses.status.'.$this->value);
    }
}
