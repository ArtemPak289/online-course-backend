<?php

namespace App\Jobs;

use App\Models\Purchase;
use App\Notifications\PaymentSuccessfulNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Создать новый экземпляр задачи отправки уведомления об оплате.
     */
    public function __construct(
        public int $purchaseId
    ) {}

    /**
     * Выполнить задачу отправки уведомления в фоновой очереди.
     */
    public function handle(): void
    {
        $purchase = Purchase::with(['user', 'course'])->find($this->purchaseId);

        if (! $purchase || ! $purchase->user) {
            Log::warning('Не удалось отправить уведомление: покупка или пользователь не найден', [
                'purchase_id' => $this->purchaseId,
            ]);

            return;
        }

        $purchase->user->notify(new PaymentSuccessfulNotification($purchase));

        Log::info('Уведомление об успешной оплате курса отправлено', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user->id,
            'email' => $purchase->user->email,
        ]);
    }
}
