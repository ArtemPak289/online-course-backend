<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\CreateLessonRequest;
use App\Http\Requests\Lesson\UpdateLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonController extends Controller
{
    public function __construct(
        protected LessonService $lessonService
    ) {}

    /**
     * Получить список всех уроков курса.
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('viewAny', [Lesson::class, $course]);

        $lessons = $this->lessonService->getLessonsByCourse($course);

        return response()->json([
            'lessons' => $lessons,
        ], Response::HTTP_OK);
    }

    /**
     * Получить детальную информацию об уроке.
     */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('view', $lesson);

        return response()->json([
            'lesson' => $lesson->load('course'),
        ], Response::HTTP_OK);
    }

    /**
     * Создать новый урок в курсе.
     */
    public function store(CreateLessonRequest $request, Course $course): JsonResponse
    {
        $lesson = $this->lessonService->createLesson(
            $course,
            $request->validated(),
            $request->file('attachment')
        );

        return response()->json([
            'message' => 'Урок успешно создан',
            'lesson' => $lesson,
        ], Response::HTTP_CREATED);
    }

    /**
     * Обновить существующий урок.
     */
    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $updatedLesson = $this->lessonService->updateLesson(
            $lesson,
            $request->validated(),
            $request->file('attachment')
        );

        return response()->json([
            'message' => 'Урок успешно обновлен',
            'lesson' => $updatedLesson,
        ], Response::HTTP_OK);
    }

    /**
     * Удалить урок.
     */
    public function destroy(Request $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('delete', $lesson);

        $this->lessonService->deleteLesson($lesson);

        return response()->json([
            'message' => 'Урок успешно удален',
        ], Response::HTTP_OK);
    }

    /**
     * Скачать файл дополнительных материалов урока.
     */
    public function downloadAttachment(Request $request, Lesson $lesson): StreamedResponse|JsonResponse
    {
        Gate::authorize('downloadAttachment', $lesson);

        if (! $lesson->attachment_path || ! Storage::disk('public')->exists($lesson->attachment_path)) {
            return response()->json([
                'message' => 'Файл материалов отсутствует для данного урока',
            ], Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('public')->download($lesson->attachment_path);
    }
}
