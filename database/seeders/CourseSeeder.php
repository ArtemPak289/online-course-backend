<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Запустить сидирование курсов.
     */
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@example.com')->first();
        $admin = User::where('email', 'admin@example.com')->first();

        if (! $teacher || ! $admin) {
            $this->call(AdminUserSeeder::class);
            $teacher = User::where('email', 'teacher@example.com')->first();
            $admin = User::where('email', 'admin@example.com')->first();
        }

        $webCategory = Category::where('name', 'Веб-разработка')->first();
        $mobileCategory = Category::where('name', 'Мобильная разработка')->first();

        if (! $webCategory) {
            $this->call(CategorySeeder::class);
            $webCategory = Category::where('name', 'Веб-разработка')->first();
            $mobileCategory = Category::where('name', 'Мобильная разработка')->first();
        }

        Course::firstOrCreate(
            ['slug' => 'laravel-rest-api-mastery'],
            [
                'title' => 'Разработка REST API на Laravel',
                'description' => 'Полный курс по созданию безопасных REST API с архитектурой сервисов и аутентификацией.',
                'price' => 199.99,
                'category_id' => $webCategory->id,
                'author_id' => $teacher->id,
                'status' => CourseStatus::Published,
                'published_at' => now(),
            ]
        );

        Course::firstOrCreate(
            ['slug' => 'flutter-from-scratch'],
            [
                'title' => 'Flutter для начинающих',
                'description' => 'Изучение кроссплатформенной разработки мобильных приложений с нуля.',
                'price' => 149.50,
                'category_id' => $mobileCategory->id,
                'author_id' => $teacher->id,
                'status' => CourseStatus::Published,
                'published_at' => now(),
            ]
        );

        Course::firstOrCreate(
            ['slug' => 'draft-advanced-architecture'],
            [
                'title' => 'Архитектурные паттерны в бэкенде (Черновик)',
                'description' => 'Глубокое погружение в чистую архитектуру, CQRS и доменно-ориентированное проектирование.',
                'price' => 299.00,
                'category_id' => $webCategory->id,
                'author_id' => $teacher->id,
                'status' => CourseStatus::Draft,
                'published_at' => null,
            ]
        );
    }
}
