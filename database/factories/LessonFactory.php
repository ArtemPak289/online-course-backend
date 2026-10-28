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
    protected $model = Lesson::class;

    private static int $positionSequence = 1;

    /**
     * Определение состояния урока по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(3, true),
            'position' => self::$positionSequence++,
            'file_path' => null,
        ];
    }

    /**
     * Указать позицию урока.
     */
    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
