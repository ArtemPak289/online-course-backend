<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\LessonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonServiceTest extends TestCase
{
    use RefreshDatabase;

    private LessonService $lessonService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lessonService = new LessonService;
    }

    /**
     * Тест: получение всех уроков курса в правильном порядке.
     */
    public function test_get_lessons_by_course_returns_ordered_collection(): void
    {
        $course = Course::factory()->create();

        Lesson::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => 'Урок 2']);
        Lesson::factory()->create(['course_id' => $course->id, 'order' => 1, 'title' => 'Урок 1']);

        $lessons = $this->lessonService->getLessonsByCourse($course);

        $this->assertCount(2, $lessons);
        $this->assertEquals('Урок 1', $lessons->first()->title);
        $this->assertEquals('Урок 2', $lessons->last()->title);
    }

    /**
     * Тест: при создании урока без указания order номер вычисляется автоматически.
     */
    public function test_create_lesson_auto_increments_order_when_not_provided(): void
    {
        $course = Course::factory()->create();

        Lesson::factory()->create(['course_id' => $course->id, 'order' => 5]);

        $lesson = $this->lessonService->createLesson($course, [
            'title' => 'Новый урок',
            'content' => 'Текст урока',
        ]);

        $this->assertEquals(6, $lesson->order);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Новый урок',
            'order' => 6,
        ]);
    }

    /**
     * Тест: создание урока с загрузкой файла материалов.
     */
    public function test_create_lesson_with_attachment_file(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $file = UploadedFile::fake()->create('guide.pdf', 500, 'application/pdf');

        $lesson = $this->lessonService->createLesson($course, [
            'title' => 'Урок с файлом',
            'content' => 'Инструкция к файлу',
            'order' => 1,
        ], $file);

        $this->assertNotNull($lesson->attachment_path);
        Storage::disk('public')->assertExists($lesson->attachment_path);
    }

    /**
     * Тест: обновление урока и замена файла материалов.
     */
    public function test_update_lesson_updates_attributes_and_replaces_attachment(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $oldFile = UploadedFile::fake()->create('old.pdf', 100);
        $oldPath = $oldFile->store('attachments/lessons', 'public');

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'title' => 'Старое название',
            'attachment_path' => $oldPath,
        ]);

        Storage::disk('public')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->create('new.pdf', 200);

        $updatedLesson = $this->lessonService->updateLesson($lesson, [
            'title' => 'Новое название',
        ], $newFile);

        $this->assertEquals('Новое название', $updatedLesson->title);
        $this->assertNotEquals($oldPath, $updatedLesson->attachment_path);

        // Старый файл удален, новый сохранен
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updatedLesson->attachment_path);
    }

    /**
     * Тест: удаление урока удаляет запись и прикрепленный файл.
     */
    public function test_delete_lesson_removes_record_and_file(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $file = UploadedFile::fake()->create('delete-me.pdf', 100);
        $path = $file->store('attachments/lessons', 'public');

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $this->lessonService->deleteLesson($lesson);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
