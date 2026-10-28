<?php

namespace Tests\Feature\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LessonManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: просмотр списка уроков опубликованного курса.
     */
    public function test_anyone_can_view_lessons_of_published_course(): void
    {
        $course = Course::factory()->published()->create();
        Lesson::factory()->count(2)->create(['course_id' => $course->id]);

        $response = $this->getJson("/api/courses/{$course->id}/lessons");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data']);
    }

    /**
     * Тест: автор курса может создать урок с загрузкой файла.
     */
    public function test_author_can_create_lesson_with_attachment(): void
    {
        Storage::fake('public');

        $author = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $author->id]);

        $file = UploadedFile::fake()->create('cheat_sheet.pdf', 300);

        $response = $this->actingAsApi($author)
            ->post("/api/courses/{$course->id}/lessons", [
                'title' => 'Урок 1: Архитектура',
                'content' => 'Текст урока',
                'position' => 1,
                'file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['title' => 'Урок 1: Архитектура']);

        $lesson = Lesson::where('title', 'Урок 1: Архитектура')->first();
        $this->assertNotNull($lesson);
        $this->assertNotNull($lesson->file_path);
        Storage::disk('public')->assertExists($lesson->file_path);
    }

    /**
     * Тест: автор курса может обновить урок.
     */
    public function test_author_can_update_lesson(): void
    {
        $author = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $author->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id, 'title' => 'Старое название']);

        $response = $this->actingAsApi($author)
            ->putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Новое название',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => 'Новое название']);

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'Новое название']);
    }

    /**
     * Тест: автор курса может удалить урок.
     */
    public function test_author_can_delete_lesson(): void
    {
        $author = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $author->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAsApi($author)
            ->deleteJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    /**
     * Тест: скачивание прикрепленного файла материалов.
     */
    public function test_download_lesson_attachment(): void
    {
        Storage::fake('public');

        $course = Course::factory()->published()->create();
        $file = UploadedFile::fake()->create('materials.pdf', 100);
        $path = $file->store('lessons', 'public');

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'file_path' => $path,
        ]);

        $response = $this->get("/api/lessons/{$lesson->id}/download");

        $response->assertStatus(Response::HTTP_OK);
    }
}
