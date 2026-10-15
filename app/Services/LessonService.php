<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LessonService
{
    /**
     * Получить список всех уроков курса, отсортированных по порядку.
     *
     * @return Collection<int, Lesson>
     */
    public function getLessonsByCourse(Course $course): Collection
    {
        return $course->lessons()->orderBy('order')->get();
    }

    /**
     * Создать новый урок в курсе.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLesson(Course $course, array $data, ?UploadedFile $attachment = null): Lesson
    {
        return DB::transaction(function () use ($course, $data, $attachment) {
            // Если порядковый номер не указан, ставим его следующим после максимального
            if (empty($data['order'])) {
                $maxOrder = $course->lessons()->max('order') ?? 0;
                $data['order'] = $maxOrder + 1;
            }

            // Загрузка файла дополнительных материалов, если он передан
            if ($attachment !== null) {
                $data['attachment_path'] = $attachment->store('attachments/lessons', 'public');
            }

            return $course->lessons()->create($data);
        });
    }

    /**
     * Обновить существующий урок.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLesson(Lesson $lesson, array $data, ?UploadedFile $attachment = null): Lesson
    {
        return DB::transaction(function () use ($lesson, $data, $attachment) {
            if ($attachment !== null) {
                // Удаляем старый файл, если он существовал
                if ($lesson->attachment_path && Storage::disk('public')->exists($lesson->attachment_path)) {
                    Storage::disk('public')->delete($lesson->attachment_path);
                }

                $data['attachment_path'] = $attachment->store('attachments/lessons', 'public');
            }

            $lesson->update($data);

            return $lesson->fresh();
        });
    }

    /**
     * Удалить урок и привязанный файл материалов.
     */
    public function deleteLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            if ($lesson->attachment_path && Storage::disk('public')->exists($lesson->attachment_path)) {
                Storage::disk('public')->delete($lesson->attachment_path);
            }

            $lesson->delete();
        });
    }
}
