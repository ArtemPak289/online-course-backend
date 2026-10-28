<?php

namespace Tests\Feature\Purchase;

use App\Events\CoursePurchased;
use App\Jobs\SendPaymentNotification;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: студент может инициировать покупку опубликованного курса.
     */
    public function test_student_can_initiate_purchase(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create(['price' => 150]);

        $response = $this->actingAsApi($student)
            ->postJson("/api/courses/{$course->id}/purchase");

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'message',
                'purchase' => [
                    'id',
                    'user_id',
                    'course_id',
                    'amount',
                    'payment_id',
                    'payment_url',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('purchases', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Тест: студент не может купить курс в статусе черновика.
     */
    public function test_student_cannot_purchase_draft_course(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->draft()->create();

        $response = $this->actingAsApi($student)
            ->postJson("/api/courses/{$course->id}/purchase");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Тест: webhook с валидной HMAC подписью успешно переводит покупку в paid.
     */
    public function test_webhook_payment_updates_purchase_and_dispatches_event(): void
    {
        Event::fake([CoursePurchased::class]);
        Queue::fake([SendPaymentNotification::class]);

        $purchase = Purchase::factory()->create([
            'payment_id' => 'pay_webhook_test',
            'status' => 'pending',
        ]);

        $payload = [
            'payment_id' => 'pay_webhook_test',
            'status' => 'paid',
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, config('app.key'));

        $response = $this->withHeaders([
            'X-Webhook-Signature' => $signature,
        ])->postJson('/api/payments/webhook', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'paid',
        ]);

        Event::assertDispatched(CoursePurchased::class);
        Queue::assertPushed(SendPaymentNotification::class);
    }

    /**
     * Тест: webhook с недействительной подписью возвращает 401.
     */
    public function test_webhook_with_invalid_signature_returns_401(): void
    {
        $payload = [
            'payment_id' => 'pay_invalid',
            'status' => 'paid',
        ];

        $response = $this->withHeaders([
            'X-Webhook-Signature' => 'fake_signature_hash',
        ])->postJson('/api/payments/webhook', $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Тест: студент может просмотреть список своих покупок.
     */
    public function test_student_can_view_own_purchases(): void
    {
        $student = User::factory()->student()->create();
        Purchase::factory()->count(2)->paid()->create(['user_id' => $student->id]);

        $response = $this->actingAsApi($student)
            ->getJson('/api/purchases');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data']);
    }
}
