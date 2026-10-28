<?php

namespace App\Listeners;

use App\Events\CoursePurchased;
use Illuminate\Support\Facades\Log;

class LogCoursePurchased
{
    /**
     * Обработать событие покупки курса и записать в системный лог.
     */
    public function handle(CoursePurchased $event): void
    {
        Log::info('Курс успешно приобретен', [
            'purchase_id' => $event->purchase->id,
            'user_id' => $event->purchase->user_id,
            'course_id' => $event->purchase->course_id,
            'amount' => $event->purchase->amount,
            'payment_id' => $event->purchase->payment_id,
        ]);
    }
}
