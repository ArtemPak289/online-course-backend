<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Запустить сидирование категорий.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Веб-разработка',
                'description' => 'Курсы по созданию сайтов и веб-приложений (PHP, Laravel, JavaScript, HTML/CSS).',
            ],
            [
                'name' => 'Мобильная разработка',
                'description' => 'Создание приложений для iOS и Android (Flutter, React Native, Swift, Kotlin).',
            ],
            [
                'name' => 'Data Science & Аналитика',
                'description' => 'Анализ данных, машинное обучение и искусственный интеллект (Python, SQL, Pandas).',
            ],
            [
                'name' => 'Дизайн & UX/UI',
                'description' => 'Интерфейсы, прототипирование и графический дизайн (Figma, Adobe).',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                ]
            );
        }
    }
}
