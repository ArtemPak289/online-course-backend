<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonService
{
    /**
     * Получить уроки курса с пагинацией и сортировкой по позиции.
     *
     * @return LengthAwarePaginator<Lesson>
     */
    public function getLessonsByCourse(Course $course, ?User $user = null, int $perPage = 50): LengthAwarePaginator
    {
        // Проверка прав на просмотр уроков черновика
        if (! $course->isPublished()) {
            $canView = $user && ($user->isAdmin() || $user->id === $course->author_id);
            if (! $canView) {
                abort(403, 'Доступ к урокам черновика запрещен');
            }
        }

        return $course->lessons()->orderBy('position')->paginate($perPage);
    }

    /**
     * Получить детальную информацию об уроке.
     */
    public function getLessonDetails(Lesson $lesson, ?User $user = null): Lesson
    {
        $course = $lesson->course;

        if (! $course->isPublished()) {
            $canView = $user && ($user->isAdmin() || $user->id === $course->author_id);
            if (! $canView) {
                abort(403, 'Доступ к уроку запрещен');
            }
        }

        return $lesson->load('course');
    }

    /**
     * Создать новый урок в рамках курса.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLesson(Course $course, array $data, ?UploadedFile $file = null): Lesson
    {
        return DB::transaction(function () use ($course, $data, $file) {
            // Если позиция не указана, вычисляем следующий номер
            if (empty($data['position'])) {
                $maxPosition = $course->lessons()->max('position') ?? 0;
                $data['position'] = $maxPosition + 1;
            }

            if ($file !== null) {
                $data['file_path'] = $file->store('lessons', 'public');
            }

            return $course->lessons()->create($data);
        });
    }

    /**
     * Обновить существующий урок.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLesson(Lesson $lesson, array $data, ?UploadedFile $file = null): Lesson
    {
        return DB::transaction(function () use ($lesson, $data, $file) {
            if ($file !== null) {
                if ($lesson->file_path && Storage::disk('public')->exists($lesson->file_path)) {
                    Storage::disk('public')->delete($lesson->file_path);
                }
                $data['file_path'] = $file->store('lessons', 'public');
            }

            $lesson->update($data);

            return $lesson->fresh();
        });
    }

    /**
     * Удалить урок и его прикрепленные материалы.
     */
    public function deleteLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            if ($lesson->file_path && Storage::disk('public')->exists($lesson->file_path)) {
                Storage::disk('public')->delete($lesson->file_path);
            }

            $lesson->delete();
        });
    }

    /**
     * Скачать прикрепленный файл материалов урока.
     */
    public function downloadAttachment(Lesson $lesson): StreamedResponse
    {
        if (! $lesson->file_path || ! Storage::disk('public')->exists($lesson->file_path)) {
            abort(404, 'Файл материалов не найден');
        }

        return Storage::disk('public')->download($lesson->file_path);
    }
}
