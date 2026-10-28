<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Администратор имеет полный доступ ко всем действиям с уроками.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Просмотр списка уроков курса.
     */
    public function viewAny(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $course->author_id === $user->id);
    }

    /**
     * Просмотр отдельного урока.
     */
    public function view(?User $user, Lesson $lesson): bool
    {
        if ($lesson->course->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $lesson->course->author_id === $user->id);
    }

    /**
     * Создание урока в курсе (только автор курса или администратор).
     */
    public function create(User $user, Course|Lesson $target): bool
    {
        $course = $target instanceof Course ? $target : $target->course;

        return $course && ($user->isAdmin() || ($user->isTeacher() && $course->author_id === $user->id));
    }

    /**
     * Обновление урока (только автор курса или администратор).
     */
    public function update(User $user, Lesson $lesson): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $lesson->course->author_id === $user->id);
    }

    /**
     * Удаление урока (только автор курса или администратор).
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
