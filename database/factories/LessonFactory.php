<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Определение состояния модели Lesson по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(3, true),
            'order' => 1,
            'attachment_path' => null,
        ];
    }

    /**
     * Указать порядковый номер урока.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Указать путь к прикрепленному файлу.
     */
    public function withAttachment(string $path = 'attachments/lessons/sample.pdf'): static
    {
        return $this->state(fn (array $attributes) => [
            'attachment_path' => $path,
        ]);
    }
}
