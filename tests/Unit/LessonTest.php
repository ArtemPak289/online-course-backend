<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: урок принадлежит курсу.
     */
    public function test_lesson_belongs_to_a_course(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->assertInstanceOf(Course::class, $lesson->course);
        $this->assertEquals($course->id, $lesson->course->id);
    }

    /**
     * Тест: курс возвращает уроки, упорядоченные по возрастанию поля order.
     */
    public function test_course_lessons_are_ordered_by_order_field(): void
    {
        $course = Course::factory()->create();

        $lesson3 = Lesson::factory()->create(['course_id' => $course->id, 'order' => 3]);
        $lesson1 = Lesson::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['course_id' => $course->id, 'order' => 2]);

        $lessons = $course->lessons;

        $this->assertCount(3, $lessons);
        $this->assertEquals($lesson1->id, $lessons[0]->id);
        $this->assertEquals($lesson2->id, $lessons[1]->id);
        $this->assertEquals($lesson3->id, $lessons[2]->id);
    }

    /**
     * Тест: генерация URL файла материалов при наличии прикрепленного файла.
     */
    public function test_attachment_url_attribute(): void
    {
        $lessonWithoutFile = Lesson::factory()->create(['attachment_path' => null]);
        $this->assertNull($lessonWithoutFile->attachment_url);

        $lessonWithFile = Lesson::factory()->create(['attachment_path' => 'attachments/lessons/test.pdf']);
        $this->assertNotNull($lessonWithFile->attachment_url);
        $this->assertStringContainsString('attachments/lessons/test.pdf', $lessonWithFile->attachment_url);
    }
}
