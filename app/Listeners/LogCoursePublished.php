<?php

namespace App\Listeners;

use App\Events\CoursePublished;
use Illuminate\Support\Facades\Log;

class LogCoursePublished
{
    /**
     * Обработать событие публикации курса и записать в системный лог.
     */
    public function handle(CoursePublished $event): void
    {
        Log::info('Курс успешно опубликован', [
            'course_id' => $event->course->id,
            'title' => $event->course->title,
            'author_id' => $event->course->author_id,
            'published_at' => $event->course->published_at,
        ]);
    }
}
