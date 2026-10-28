<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use Illuminate\Support\Facades\Log;

class LogCourseCompleted
{
    /**
     * Обработать событие успешного завершения курса пользователем.
     */
    public function handle(CourseCompleted $event): void
    {
        Log::info('Курс успешно пройден студентом', [
            'course_id' => $event->course->id,
            'title' => $event->course->title,
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
        ]);
    }
}
