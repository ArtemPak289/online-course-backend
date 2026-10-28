<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Наполнить базу данных базовыми категориями курсов.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Веб-разработка',
                'description' => 'Курсы по созданию современных веб-приложений, REST API, микросервисов и баз данных.',
            ],
            [
                'name' => 'Мобильная разработка',
                'description' => 'Создание мобильных приложений для iOS и Android с использованием современных фреймворков.',
            ],
            [
                'name' => 'DevOps и Архитектура',
                'description' => 'Контейнеризация, CI/CD пайплайны, оркестрация и проектирование масштабируемых систем.',
            ],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
