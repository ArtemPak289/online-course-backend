<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Определить, может ли пользователь просматривать список курсов.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Определить, может ли пользователь просматривать конкретный курс.
     */
    public function view(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $user->id === $course->author_id);
    }

    /**
     * Определить, может ли пользователь создавать курсы.
     */
    public function create(User $user): bool
    {
        // Создавать курсы могут только преподаватели или администраторы, студентам запрещено
        return $user->isAdmin() || $user->isTeacher() || $user->hasPermission('courses.create');
    }

    /**
     * Определить, может ли пользователь обновлять курс.
     */
    public function update(User $user, Course $course): bool
    {
        // Изменять курс может только его автор или администратор
        return $user->isAdmin() || $user->id === $course->author_id;
    }

    /**
     * Определить, может ли пользователь удалять курс.
     */
    public function delete(User $user, Course $course): bool
    {
        // Удалять курс может только его автор или администратор
        return $user->isAdmin() || $user->id === $course->author_id;
    }

    /**
     * Определить, может ли пользователь публиковать курс.
     */
    public function publish(User $user, Course $course): bool
    {
        // Публиковать курс может только его автор или администратор
        return $user->isAdmin() || $user->id === $course->author_id;
    }
}
