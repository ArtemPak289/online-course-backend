<?php

namespace Tests\Unit\Services;

use App\Events\CoursePurchased;
use App\Jobs\SendPaymentNotification;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseService = new PurchaseService;
    }

    /**
     * Тест: создание покупки опубликованного курса генерирует payment_id и payment_url.
     */
    public function test_create_purchase_generates_payment_data(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create(['price' => 59.90]);

        $purchase = $this->purchaseService->createPurchase($student, $course);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 59.90,
            'status' => 'pending',
        ]);

        $this->assertNotNull($purchase->payment_id);
        $this->assertNotNull($purchase->payment_url);
    }

    /**
     * Тест: попытка покупки неопубликованного курса выбрасывает 422 ошибку.
     */
    public function test_create_purchase_fails_on_draft_course(): void
    {
        $this->expectException(HttpException::class);

        $student = User::factory()->student()->create();
        $course = Course::factory()->draft()->create();

        $this->purchaseService->createPurchase($student, $course);
    }

    /**
     * Тест: успешная обработка webhook изменяет статус на paid, диспатчит событие и очередь уведомления.
     */
    public function test_handle_webhook_updates_status_and_dispatches_event_and_job(): void
    {
        Event::fake([CoursePurchased::class]);
        Queue::fake([SendPaymentNotification::class]);

        $purchase = Purchase::factory()->create([
            'payment_id' => 'pay_test_123',
            'status' => 'pending',
        ]);

        $payload = [
            'payment_id' => 'pay_test_123',
            'status' => 'paid',
        ];

        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, config('app.key'));

        $result = $this->purchaseService->handleWebhook($raw, $signature, $payload);

        $this->assertFalse($result['idempotent']);
        $this->assertEquals('paid', $result['purchase']->status);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'paid',
        ]);

        Event::assertDispatched(CoursePurchased::class);
        Queue::assertPushed(SendPaymentNotification::class);
    }

    /**
     * Тест: webhook с неверной HMAC подписью выбрасывает 401 ошибку.
     */
    public function test_handle_webhook_fails_on_invalid_signature(): void
    {
        $this->expectException(HttpException::class);

        $payload = ['payment_id' => 'pay_invalid', 'status' => 'paid'];
        $raw = json_encode($payload);

        $this->purchaseService->handleWebhook($raw, 'invalid_signature_hash', $payload);
    }

    /**
     * Тест: проверка идемпотентности при повторном вызове webhook.
     */
    public function test_handle_webhook_is_idempotent(): void
    {
        Event::fake([CoursePurchased::class]);

        $purchase = Purchase::factory()->paid()->create([
            'payment_id' => 'pay_idempotent_123',
        ]);

        $payload = [
            'payment_id' => 'pay_idempotent_123',
            'status' => 'paid',
        ];

        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, config('app.key'));

        $result = $this->purchaseService->handleWebhook($raw, $signature, $payload);

        $this->assertTrue($result['idempotent']);
        Event::assertNotDispatched(CoursePurchased::class);
    }

    /**
     * Тест: проверка наличия доступа к курсу через hasPurchasedCourse.
     */
    public function test_has_purchased_course_checks_correctly(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();

        $this->assertFalse($this->purchaseService->hasPurchasedCourse($student, $course));

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->assertTrue($this->purchaseService->hasPurchasedCourse($student, $course));
    }
}
