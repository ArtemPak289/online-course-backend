<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\IndexCourseRequest;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CourseController extends Controller
{
    public function __construct(
        protected CourseService $courseService
    ) {}

    /**
     * Получить список курсов с пагинацией, фильтрами, поиском и сортировкой.
     */
    public function index(IndexCourseRequest $request): JsonResponse
    {
        $courses = $this->courseService->getPaginatedCourses(
            $request->validated(),
            $request->user(),
            $request->integer('per_page', 15)
        );

        return response()->json($courses, Response::HTTP_OK);
    }

    /**
     * Создать новый курс (преподаватель или администратор).
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->createCourse(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Курс успешно создан',
            'course' => $course,
        ], Response::HTTP_CREATED);
    }

    /**
     * Получить подробную информацию о конкретном курсе.
     */
    public function show(Request $request, Course $course): JsonResponse
    {
        $courseDetails = $this->courseService->getCourseDetails($course, $request->user());

        return response()->json([
            'course' => $courseDetails,
        ], Response::HTTP_OK);
    }

    /**
     * Обновить существующий курс (только автор курса или администратор).
     */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $updatedCourse = $this->courseService->updateCourse($course, $request->validated());

        return response()->json([
            'message' => 'Курс успешно обновлен',
            'course' => $updatedCourse,
        ], Response::HTTP_OK);
    }

    /**
     * Удалить курс (только автор курса или администратор).
     */
    public function destroy(Course $course): JsonResponse
    {
        Gate::authorize('delete', $course);

        $this->courseService->deleteCourse($course);

        return response()->json([
            'message' => 'Курс успешно удален',
        ], Response::HTTP_OK);
    }
}
