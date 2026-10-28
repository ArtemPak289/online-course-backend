<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Http\Requests\Lesson\UpdateLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $lessons = $this->lessonService->getLessonsByCourse(
            $course,
            $request->user(),
            $request->integer('per_page', 50)
        );

        return response()->json($lessons, Response::HTTP_OK);
    }

    /**
     * Получить детальную информацию об уроке.
     */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        $lessonDetails = $this->lessonService->getLessonDetails($lesson, $request->user());

        return response()->json([
            'lesson' => $lessonDetails,
        ], Response::HTTP_OK);
    }

    /**
     * Создать новый урок в курсе (автор курса или администратор).
     */
    public function store(StoreLessonRequest $request, Course $course): JsonResponse
    {
        $lesson = $this->lessonService->createLesson(
            $course,
            $request->validated(),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Урок успешно создан',
            'lesson' => $lesson,
        ], Response::HTTP_CREATED);
    }

    /**
     * Обновить урок (автор курса или администратор).
     */
    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $updatedLesson = $this->lessonService->updateLesson(
            $lesson,
            $request->validated(),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Урок успешно обновлен',
            'lesson' => $updatedLesson,
        ], Response::HTTP_OK);
    }

    /**
     * Удалить урок (автор курса или администратор).
     */
    public function destroy(Lesson $lesson): JsonResponse
    {
        Gate::authorize('delete', $lesson);

        $this->lessonService->deleteLesson($lesson);

        return response()->json([
            'message' => 'Урок успешно удален',
        ], Response::HTTP_OK);
    }

    /**
     * Скачать прикрепленный файл материалов урока.
     */
    public function download(Lesson $lesson): StreamedResponse
    {
        return $this->lessonService->downloadAttachment($lesson);
    }
}
