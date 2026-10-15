<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Определить, может ли пользователь просматривать список уроков курса.
     */
    public function viewAny(?User $user, Course $course): bool
    {
        // Если курс опубликован — список уроков доступен всем
        if ($course->isPublished()) {
            return true;
        }

        // Если курс в черновике — только автор или администратор
        return $user !== null && ($user->isAdmin() || $user->id === $course->author_id);
    }

    /**
     * Определить, может ли пользователь просматривать конкретный урок.
     */
    public function view(?User $user, Lesson $lesson): bool
    {
        $course = $lesson->course;

        if ($course->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $user->id === $course->author_id);
    }

    /**
     * Определить, может ли пользователь создавать уроки в курсе.
     */
    public function create(User $user, Course $course): bool
    {
        // Добавлять уроки может только автор курса или администратор
        return $user->isAdmin() || $user->id === $course->author_id;
    }

    /**
     * Определить, может ли пользователь обновлять урок.
     */
    public function update(User $user, Lesson $lesson): bool
    {
        // Изменять урок может только автор курса или администратор
        return $user->isAdmin() || $user->id === $lesson->course->author_id;
    }

    /**
     * Определить, может ли пользователь удалять урок.
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        // Удалять урок может только автор курса или администратор
        return $user->isAdmin() || $user->id === $lesson->course->author_id;
    }

    /**
     * Определить, может ли пользователь скачать материалы урока.
     */
    public function downloadAttachment(?User $user, Lesson $lesson): bool
    {
        return $this->view($user, $lesson);
    }
}
