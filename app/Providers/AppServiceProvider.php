<?php

namespace App\Providers;

use App\Events\UserRegistered;
use App\Listeners\LogUserRegistration;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(
            User::class,
            UserPolicy::class
        );

        Event::listen(
            UserRegistered::class,
            LogUserRegistration::class
        );
    }
}
