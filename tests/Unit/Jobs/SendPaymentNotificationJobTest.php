<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendPaymentNotification;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\PaymentSuccessfulNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPaymentNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: выполнение задачи отправляет уведомление пользователю.
     */
    public function test_send_payment_notification_sends_notification_to_user(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $purchase = Purchase::factory()->paid()->create(['user_id' => $student->id]);

        $job = new SendPaymentNotification($purchase->id);
        $job->handle();

        Notification::assertSentTo($student, PaymentSuccessfulNotification::class);
    }
}
