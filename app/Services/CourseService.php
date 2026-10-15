<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Events\CoursePublished;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService
{
    /**
     * Получить список курсов с поиском, фильтрацией, сортировкой и пагинацией.
     *
     * @param  array{search?: string, category_id?: int, author_id?: int, sort_by?: string, sort_order?: string}  $filters
     */
    public function getPaginatedCourses(array $filters = [], ?User $currentUser = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['category', 'author:id,name,email']);

        // Проверка видимости: неавторизованные пользователи и студенты видят только опубликованные курсы
        if (! $currentUser || ! $currentUser->isAdmin()) {
            $query->where(function ($q) use ($currentUser) {
                $q->where('status', CourseStatus::Published->value);

                // Преподаватели могут также видеть свои собственные черновики
                if ($currentUser) {
                    $q->orWhere('author_id', $currentUser->id);
                }
            });
        }

        // Поиск по названию курса
        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        // Фильтрация по категории
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Фильтрация по преподавателю (автору)
        if (! empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        // Сортировка по стоимости или дате создания
        $sortOrder = strtolower($filters['sort_order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        if (isset($filters['sort_by']) && $filters['sort_by'] === 'price') {
            $query->orderBy('price', $sortOrder);
        } elseif (isset($filters['sort_by']) && $filters['sort_by'] === 'created_at') {
            $query->orderBy('created_at', $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    /**
     * Получить детальную информацию о курсе.
     */
    public function getCourseDetails(Course $course): Course
    {
        return $course->load(['category', 'author:id,name,email']);
    }

    /**
     * Создать новый курс.
     *
     * @param  array{title: string, description: string, price: float, category_id: int, status?: string}  $data
     */
    public function createCourse(User $author, array $data): Course
    {
        $status = $data['status'] ?? CourseStatus::Draft->value;
        $isPublished = $status === CourseStatus::Published->value;

        $course = Course::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'author_id' => $author->id,
            'status' => $status,
            'published_at' => $isPublished ? now() : null,
        ]);

        if ($isPublished) {
            event(new CoursePublished($course));
        }

        return $course->load(['category', 'author:id,name,email']);
    }

    /**
     * Обновить существующий курс.
     *
     * @param  array{title?: string, description?: string, price?: float, category_id?: int, status?: string}  $data
     */
    public function updateCourse(Course $course, array $data): Course
    {
        $wasPublished = $course->isPublished();
        $willBePublished = isset($data['status']) && $data['status'] === CourseStatus::Published->value;

        if (! $wasPublished && $willBePublished) {
            $data['published_at'] = now();
        }

        $course->update($data);

        if (! $wasPublished && $willBePublished) {
            event(new CoursePublished($course));
        }

        return $course->fresh(['category', 'author:id,name,email']);
    }

    /**
     * Удалить курс.
     */
    public function deleteCourse(Course $course): void
    {
        $course->delete();
    }
}
