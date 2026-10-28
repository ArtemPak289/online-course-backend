<?php

namespace App\Events;

use App\Models\Course;
use App\Models\User;

class CourseCompleted
{
    public function __construct(public Course $course, public User $user) {}
}
