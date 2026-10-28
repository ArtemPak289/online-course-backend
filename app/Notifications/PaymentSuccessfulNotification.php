<?php

namespace App\Notifications;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessfulNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Purchase $purchase) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Course purchase confirmed')
            ->line("Your purchase of {$this->purchase->course->title} was successful.")
            ->line('You can now access the course lessons.');
    }
}
