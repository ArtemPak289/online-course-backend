<?php

namespace App\Events;

use App\Models\Course;

class CoursePublished
{
    public function __construct(public Course $course) {}
}
