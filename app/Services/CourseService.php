<?php

namespace App\Services;

use App\Events\CoursePublished;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * Получить список курсов с пагинацией, поиском, фильтрацией и сортировкой.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Course>
     */
    public function getPaginatedCourses(array $filters, ?User $user = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['category', 'author']);

        // Для неавторизованных пользователей или обычных студентов отображаются только опубликованные курсы
        $isPrivileged = $user && ($user->isAdmin() || $user->isTeacher());

        if (! $isPrivileged) {
            $query->where('status', 'published');
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Поиск по названию курса (регистронезависимый через PostgreSQL ILIKE)
        if (! empty($filters['search'])) {
            $query->where('title', 'ilike', '%'.$filters['search'].'%');
        }

        // Фильтрация по категории
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Фильтрация по автору (преподавателю)
        if (! empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        // Сортировка по стоимости или дате создания
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortField, ['price', 'created_at'], true)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Получить подробную информацию о курсе.
     */
    public function getCourseDetails(Course $course, ?User $user = null): Course
    {
        $canViewDraft = $user && ($user->isAdmin() || $user->id === $course->author_id);

        if (! $course->isPublished() && ! $canViewDraft) {
            abort(404, 'Курс не найден или ещё не опубликован');
        }

        return $course->load(['lessons' => function ($query) {
            $query->orderBy('position');
        }, 'category', 'author']);
    }

    /**
     * Создать новый курс.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCourse(User $author, array $data): Course
    {
        return DB::transaction(function () use ($author, $data) {
            $data['author_id'] = $author->id;

            $status = $data['status'] ?? 'draft';
            if ($status === 'published') {
                $data['published_at'] = now();
            }

            $course = Course::create($data);

            if ($course->isPublished()) {
                event(new CoursePublished($course));
            }

            return $course->load(['category', 'author']);
        });
    }

    /**
     * Обновить информацию о курсе.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            $wasPublished = $course->isPublished();

            if (isset($data['status']) && $data['status'] === 'published' && ! $wasPublished) {
                $data['published_at'] = now();
            }

            $course->update($data);

            if (! $wasPublished && $course->isPublished()) {
                event(new CoursePublished($course));
            }

            return $course->fresh(['category', 'author']);
        });
    }

    /**
     * Удалить курс.
     */
    public function deleteCourse(Course $course): void
    {
        $course->delete();
    }
}
