<?php

namespace App\Listeners;

use App\Events\CoursePublished;
use Illuminate\Support\Facades\Log;

class LogCoursePublished
{
    /**
     * Обработать событие публикации курса.
     */
    public function handle(CoursePublished $event): void
    {
        Log::info('Course published successfully', [
            'course_id' => $event->course->id,
            'title' => $event->course->title,
            'author_id' => $event->course->author_id,
            'published_at' => $event->course->published_at?->toIso8601String(),
        ]);
    }
}
