<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->randomFloat(2, 10, 500),
            'category_id' => Category::factory(),
            'author_id' => User::factory()->teacher(),
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ];
    }

    /**
     * Указать, что курс находится в статусе черновика.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Указать, что курс опубликован.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }
}
