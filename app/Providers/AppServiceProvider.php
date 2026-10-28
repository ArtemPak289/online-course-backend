<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use App\Policies\UserPolicy;
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
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
    }
}
