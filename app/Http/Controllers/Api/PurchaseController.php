<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\CompleteLessonRequest;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\WebhookRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\ProgressService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected ProgressService $progressService
    ) {}

    /**
     * Инициировать покупку курса студентом.
     */
    public function store(StorePurchaseRequest $request, Course $course): JsonResponse
    {
        $purchase = $this->purchaseService->createPurchase($request->user(), $course);

        return response()->json([
            'message' => 'Покупка успешно создана. Перейдите по ссылке для завершения оплаты.',
            'purchase' => $purchase,
        ], Response::HTTP_CREATED);
    }

    /**
     * Обработать входящий webhook от платежной системы.
     */
    public function webhook(WebhookRequest $request): JsonResponse
    {
        $signature = $request->header('X-Webhook-Signature', '');
        $rawPayload = $request->getContent();

        $result = $this->purchaseService->handleWebhook(
            $rawPayload,
            $signature,
            $request->validated()
        );

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Получить список всех покупок текущего пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $purchases = $this->purchaseService->getUserPurchases(
            $request->user(),
            $request->integer('per_page', 15)
        );

        return response()->json($purchases, Response::HTTP_OK);
    }

    /**
     * Отметить урок как завершённый студентом.
     */
    public function completeLesson(CompleteLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $result = $this->progressService->completeLesson($request->user(), $lesson);

        return response()->json([
            'message' => 'Прогресс успешно сохранен',
            'progress' => $result['progress'],
            'completed' => $result['completed'],
            'percentage' => $result['percentage'],
            'total_lessons' => $result['total_lessons'],
            'completed_lessons' => $result['completed_lessons'],
        ], Response::HTTP_OK);
    }

    /**
     * Получить общий прогресс прохождения курса.
     */
    public function progress(Request $request, Course $course): JsonResponse
    {
        $progress = $this->progressService->getCourseProgress($request->user(), $course);

        return response()->json($progress, Response::HTTP_OK);
    }

    /**
     * Скачать сертификат об окончании курса.
     */
    public function certificate(Request $request, Course $course): StreamedResponse
    {
        return $this->progressService->downloadCertificate($request->user(), $course);
    }
}
