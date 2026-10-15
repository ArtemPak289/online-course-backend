<?php

namespace App\Events;

use App\Models\Course;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoursePublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Создать новый экземпляр события публикации курса.
     */
    public function __construct(public Course $course) {}
}
