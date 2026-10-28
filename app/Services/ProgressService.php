<?php

namespace App\Services;

use App\Events\CourseCompleted;
use App\Jobs\GenerateCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressService
{
    /**
     * Отметить урок как завершённый студентом и проверить завершение всего курса.
     *
     * @return array{progress: LessonProgress, completed: bool, total_lessons: int, completed_lessons: int, percentage: float}
     */
    public function completeLesson(User $user, Lesson $lesson): array
    {
        $course = $lesson->course;

        // Проверка: студент должен оплатить курс (или быть админом / автором)
        $hasAccess = $user->isAdmin() || $user->id === $course->author_id ||
            $course->purchases()->where('user_id', $user->id)->where('status', 'paid')->exists();

        if (! $hasAccess) {
            abort(403, 'Для прохождения уроков необходимо приобрести курс');
        }

        return DB::transaction(function () use ($user, $lesson, $course) {
            $progress = LessonProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'completed_at' => now(),
                ]
            );

            $lessonIds = $course->lessons()->pluck('id');
            $total = $lessonIds->count();

            $completedCount = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->whereNotNull('completed_at')
                ->count();

            $isCompleted = $total > 0 && $completedCount >= $total;
            $percentage = $total > 0 ? round(($completedCount / $total) * 100, 2) : 0;

            if ($isCompleted) {
                // Доменное событие завершения курса
                event(new CourseCompleted($course, $user));

                // Фоновая задача генерации сертификата в очереди
                GenerateCertificate::dispatch($user->id, $course->id);
            }

            return [
                'progress' => $progress,
                'completed' => $isCompleted,
                'total_lessons' => $total,
                'completed_lessons' => $completedCount,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Получить подробный прогресс студента по курсу.
     *
     * @return array{total_lessons: int, completed_lessons: int, percentage: float, completed_lesson_ids: array<int>, is_completed: bool}
     */
    public function getCourseProgress(User $user, Course $course): array
    {
        $lessonIds = $course->lessons()->pluck('id');
        $total = $lessonIds->count();

        $completedLessonIds = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        $completedCount = count($completedLessonIds);
        $percentage = $total > 0 ? round(($completedCount / $total) * 100, 2) : 0;

        return [
            'total_lessons' => $total,
            'completed_lessons' => $completedCount,
            'percentage' => $percentage,
            'completed_lesson_ids' => $completedLessonIds,
            'is_completed' => $total > 0 && $completedCount >= $total,
        ];
    }

    /**
     * Получить модель сертификата пользователя за пройденный курс.
     */
    public function getCertificate(User $user, Course $course): Certificate
    {
        return Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Скачать сгенерированный PDF-сертификат.
     */
    public function downloadCertificate(User $user, Course $course): StreamedResponse
    {
        $certificate = $this->getCertificate($user, $course);

        if (! Storage::disk('local')->exists($certificate->file_path)) {
            abort(404, 'Файл сертификата ещё формируется или отсутствует');
        }

        return Storage::disk('local')->download(
            $certificate->file_path,
            "certificate-{$course->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
