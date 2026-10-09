<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

class LogUserRegistration
{
    /**
     * Обработать событие.
     */
    public function handle(UserRegistered $event): void
    {
        Log::info('User registered successfully', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'role' => $event->user->role?->value,
        ]);
    }
}
