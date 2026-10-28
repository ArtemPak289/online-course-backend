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
     * Тест: получение уроков курса в правильном порядке.
     */
    public function test_get_lessons_by_course_returns_ordered(): void
    {
        $course = Course::factory()->published()->create();

        Lesson::factory()->create(['course_id' => $course->id, 'position' => 2, 'title' => 'Урок 2']);
        Lesson::factory()->create(['course_id' => $course->id, 'position' => 1, 'title' => 'Урок 1']);

        $lessons = $this->lessonService->getLessonsByCourse($course);

        $this->assertCount(2, $lessons->items());
        $this->assertEquals('Урок 1', $lessons->items()[0]->title);
        $this->assertEquals('Урок 2', $lessons->items()[1]->title);
    }

    /**
     * Тест: создание урока с автовычислением позиции.
     */
    public function test_create_lesson_auto_increments_position(): void
    {
        $course = Course::factory()->create();

        Lesson::factory()->create(['course_id' => $course->id, 'position' => 3]);

        $lesson = $this->lessonService->createLesson($course, [
            'title' => 'Новый урок',
            'content' => 'Содержание урока',
        ]);

        $this->assertEquals(4, $lesson->position);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Новый урок',
            'position' => 4,
        ]);
    }

    /**
     * Тест: создание урока с прикреплением файла.
     */
    public function test_create_lesson_with_file_upload(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $file = UploadedFile::fake()->create('materials.pdf', 500);

        $lesson = $this->lessonService->createLesson($course, [
            'title' => 'Урок с файлом',
            'content' => 'Описание материалов',
        ], $file);

        $this->assertNotNull($lesson->file_path);
        Storage::disk('public')->assertExists($lesson->file_path);
    }

    /**
     * Тест: обновление урока и замена файла.
     */
    public function test_update_lesson_replaces_old_file(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $oldFile = UploadedFile::fake()->create('old.pdf', 200);
        $oldPath = $oldFile->store('lessons', 'public');

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'file_path' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->create('new.pdf', 300);

        $updated = $this->lessonService->updateLesson($lesson, [
            'title' => 'Обновленный урок',
        ], $newFile);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updated->file_path);
    }

    /**
     * Тест: удаление урока и удаление прикрепленного файла.
     */
    public function test_delete_lesson_removes_file_from_storage(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $file = UploadedFile::fake()->create('delete.pdf', 100);
        $path = $file->store('lessons', 'public');

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'file_path' => $path,
        ]);

        $this->lessonService->deleteLesson($lesson);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
