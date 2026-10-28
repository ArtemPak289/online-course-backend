<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Наполнить базу данных уроками для курсов.
     */
    public function run(): void
    {
        $laravelCourse = Course::where('title', 'Разработка REST API на Laravel')->first();

        if ($laravelCourse) {
            Lesson::firstOrCreate(
                [
                    'course_id' => $laravelCourse->id,
                    'position' => 1,
                ],
                [
                    'title' => 'Введение в архитектуру REST API',
                    'content' => 'В этом уроке рассматриваются базовые принципы REST, HTTP методы, структура запросов и статус-коды ответов.',
                    'file_path' => null,
                ]
            );

            Lesson::firstOrCreate(
                [
                    'course_id' => $laravelCourse->id,
                    'position' => 2,
                ],
                [
                    'title' => 'Аутентификация с помощью API токенов',
                    'content' => 'Реализация безопасной токенной авторизации без сторонних библиотек. Хеширование токенов в базе данных.',
                    'file_path' => null,
                ]
            );

            Lesson::firstOrCreate(
                [
                    'course_id' => $laravelCourse->id,
                    'position' => 3,
                ],
                [
                    'title' => 'Слой сервисов и валидация через Form Requests',
                    'content' => 'Разделение бизнес-логики и HTTP-контроллеров с использованием паттерна Service Layer.',
                    'file_path' => null,
                ]
            );
        }
    }
}
