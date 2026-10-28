<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Определение состояния курса по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'author_id' => User::factory()->teacher(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 500),
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    /**
     * Состояние: черновик курса.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Состояние: опубликованный курс.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
