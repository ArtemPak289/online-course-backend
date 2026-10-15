<?php

namespace App\Providers;

use App\Events\CoursePublished;
use App\Events\UserRegistered;
use App\Listeners\LogCoursePublished;
use App\Listeners\LogUserRegistration;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CoursePolicy;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);

        Event::listen(
            UserRegistered::class,
            LogUserRegistration::class
        );

        Event::listen(
            CoursePublished::class,
            LogCoursePublished::class
        );
    }
}
