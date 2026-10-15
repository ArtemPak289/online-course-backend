<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Запустить наполнение базы данных уроками.
     */
    public function run(): void
    {
        $laravelCourse = Course::where('slug', 'laravel-rest-api-mastery')->first();

        if ($laravelCourse) {
            Lesson::firstOrCreate(
                [
                    'course_id' => $laravelCourse->id,
                    'order' => 1,
                ],
                [
                    'title' => 'Введение в архитектуру REST API',
                    'content' => 'В этом уроке рассматриваются базовые принципы REST, HTTP методы, структура запросов и статус-коды ответов.',
                    'attachment_path' => null,
                ]
            );

            Lesson::firstOrCreate(
                [
                    'course_id' => $laravelCourse->id,
                    'order' => 2,
                ],
                [
                    'title' => 'Аутентификация с помощью API токенов',
                    'content' => 'Реализация безопасной токенной авторизации без сторонних библиотек. Хеширование токенов в базе данных.',
                    'attachment_path' => null,
                ]
            );

            Lesson::firstOrCreate(
                [
                    'course_id' => $laravelCourse->id,
                    'order' => 3,
                ],
                [
                    'title' => 'Слой сервисов и валидация через Form Requests',
                    'content' => 'Разделение бизнес-логики и HTTP-контроллеров с использованием паттерна Service Layer.',
                    'attachment_path' => null,
                ]
            );
        }

        $flutterCourse = Course::where('slug', 'flutter-from-scratch')->first();

        if ($flutterCourse) {
            Lesson::firstOrCreate(
                [
                    'course_id' => $flutterCourse->id,
                    'order' => 1,
                ],
                [
                    'title' => 'Установка Flutter SDK и настройка окружения',
                    'content' => 'Пошаговое руководство по установке Flutter, Android Studio, Xcode и настройке первой рабочей среды.',
                    'attachment_path' => null,
                ]
            );
        }
    }
}
