<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\FilterCourseRequest;
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
     * Отобразить список курсов с поиском, фильтрацией и сортировкой.
     */
    public function index(FilterCourseRequest $request): JsonResponse
    {
        $courses = $this->courseService->getPaginatedCourses(
            $request->validated(),
            $request->user(),
            $request->integer('per_page', 15)
        );

        return response()->json($courses, Response::HTTP_OK);
    }

    /**
     * Отобразить информацию о конкретном курсе.
     */
    public function show(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('view', $course);

        $courseDetails = $this->courseService->getCourseDetails($course);

        return response()->json([
            'course' => $courseDetails,
        ], Response::HTTP_OK);
    }

    /**
     * Создать новый курс.
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->createCourse(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Курс успешно создан.',
            'course' => $course,
        ], Response::HTTP_CREATED);
    }

    /**
     * Обновить существующий курс.
     */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $updatedCourse = $this->courseService->updateCourse(
            $course,
            $request->validated()
        );

        return response()->json([
            'message' => 'Курс успешно обновлен.',
            'course' => $updatedCourse,
        ], Response::HTTP_OK);
    }

    /**
     * Удалить курс.
     */
    public function destroy(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('delete', $course);

        $this->courseService->deleteCourse($course);

        return response()->json([
            'message' => 'Курс успешно удален.',
        ], Response::HTTP_OK);
    }
}
