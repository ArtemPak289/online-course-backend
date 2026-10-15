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
     * Тест: любой пользователь (включая гостя) может просматривать список уроков опубликованного курса.
     */
    public function test_guest_can_view_lessons_of_published_course(): void
    {
        $course = Course::factory()->published()->create();
        Lesson::factory()->create(['course_id' => $course->id, 'order' => 1, 'title' => 'Урок 1']);
        Lesson::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => 'Урок 2']);

        $response = $this->getJson("/api/courses/{$course->id}/lessons");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'lessons')
            ->assertJsonFragment(['title' => 'Урок 1'])
            ->assertJsonFragment(['title' => 'Урок 2']);
    }

    /**
     * Тест: гость или сторонний студент не могут просматривать уроки курса в статусе черновика.
     */
    public function test_guest_or_student_cannot_view_lessons_of_draft_course(): void
    {
        $course = Course::factory()->draft()->create();
        Lesson::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->student()->create();

        // Гость получает 403
        $this->getJson("/api/courses/{$course->id}/lessons")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Студент получает 403
        $this->actingAsApi($student)
            ->getJson("/api/courses/{$course->id}/lessons")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: автор курса и администратор могут просматривать уроки черновика.
     */
    public function test_author_and_admin_can_view_lessons_of_draft_course(): void
    {
        $author = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $course = Course::factory()->draft()->create(['author_id' => $author->id]);
        Lesson::factory()->create(['course_id' => $course->id, 'title' => 'Черновой урок']);

        // Автор видит уроки своего черновика
        $this->actingAsApi($author)
            ->getJson("/api/courses/{$course->id}/lessons")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => 'Черновой урок']);

        // Администратор видит уроки любого черновика
        $this->actingAsApi($admin)
            ->getJson("/api/courses/{$course->id}/lessons")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => 'Черновой урок']);
    }

    /**
     * Тест: получение детальной информации об отдельном уроке.
     */
    public function test_can_view_single_lesson_details(): void
    {
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'title' => 'Детальный урок',
            'content' => 'Полный текст этого урока.',
        ]);

        $response = $this->getJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => 'Детальный урок',
                    'content' => 'Полный текст этого урока.',
                ],
            ]);
    }

    /**
     * Тест: автор курса может создать урок.
     */
    public function test_course_author_can_create_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);

        $payload = [
            'title' => 'Новый практический урок',
            'content' => 'Инструкция по написанию кода.',
            'order' => 1,
        ];

        $response = $this->actingAsApi($teacher)
            ->postJson("/api/courses/{$course->id}/lessons", $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Урок успешно создан',
                'lesson' => [
                    'title' => 'Новый практический урок',
                    'order' => 1,
                    'course_id' => $course->id,
                ],
            ]);

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Новый практический урок',
            'order' => 1,
        ]);
    }

    /**
     * Тест: автор курса может создать урок с загрузкой файла материалов.
     */
    public function test_course_author_can_create_lesson_with_attachment(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);

        $file = UploadedFile::fake()->create('materials.pdf', 300, 'application/pdf');

        $response = $this->actingAsApi($teacher)
            ->post("/api/courses/{$course->id}/lessons", [
                'title' => 'Урок с файлом материалов',
                'content' => 'Скачайте прикрепленный файл.',
                'attachment' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_CREATED);

        $lesson = Lesson::where('title', 'Урок с файлом материалов')->first();
        $this->assertNotNull($lesson);
        $this->assertNotNull($lesson->attachment_path);
        Storage::disk('public')->assertExists($lesson->attachment_path);
    }

    /**
     * Тест: администратор может создать урок в любом курсе.
     */
    public function test_admin_can_create_lesson_in_any_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $response = $this->actingAsApi($admin)
            ->postJson("/api/courses/{$course->id}/lessons", [
                'title' => 'Урок от администратора',
                'content' => 'Текст урока.',
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Урок от администратора',
        ]);
    }

    /**
     * Тест: студент не имеет права создавать уроки.
     */
    public function test_student_cannot_create_lesson(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();

        $response = $this->actingAsApi($student)
            ->postJson("/api/courses/{$course->id}/lessons", [
                'title' => 'Попытка студента создать урок',
                'content' => 'Текст урока.',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: другой преподаватель не может добавлять уроки в чужой курс.
     */
    public function test_other_teacher_cannot_create_lesson_in_foreign_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $course = Course::factory()->create(['author_id' => $owner->id]);

        $response = $this->actingAsApi($otherTeacher)
            ->postJson("/api/courses/{$course->id}/lessons", [
                'title' => 'Чужой урок',
                'content' => 'Текст чужого урока.',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: автор курса может обновить урок.
     */
    public function test_course_author_can_update_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id, 'title' => 'Исходный заголовок']);

        $response = $this->actingAsApi($teacher)
            ->putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Обновленный заголовок',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Урок успешно обновлен',
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => 'Обновленный заголовок',
                ],
            ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Обновленный заголовок',
        ]);
    }

    /**
     * Тест: администратор может обновить урок в любом курсе.
     */
    public function test_admin_can_update_lesson(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create(['title' => 'Старый урок']);

        $response = $this->actingAsApi($admin)
            ->putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Урок обновлен администратором',
            ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Урок обновлен администратором',
        ]);
    }

    /**
     * Тест: студент или чужой преподаватель не могут изменять урок.
     */
    public function test_other_users_cannot_update_lesson(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $course = Course::factory()->create(['author_id' => $owner->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->actingAsApi($otherTeacher)
            ->putJson("/api/lessons/{$lesson->id}", ['title' => 'Попытка изменения'])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAsApi($student)
            ->putJson("/api/lessons/{$lesson->id}", ['title' => 'Попытка изменения'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: автор курса может удалить урок.
     */
    public function test_course_author_can_delete_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAsApi($teacher)
            ->deleteJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Урок успешно удален']);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    /**
     * Тест: администратор может удалить урок.
     */
    public function test_admin_can_delete_lesson(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $response = $this->actingAsApi($admin)
            ->deleteJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    /**
     * Тест: студент или чужой преподаватель не могут удалить урок.
     */
    public function test_other_users_cannot_delete_lesson(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $course = Course::factory()->create(['author_id' => $owner->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->actingAsApi($otherTeacher)
            ->deleteJson("/api/lessons/{$lesson->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAsApi($student)
            ->deleteJson("/api/lessons/{$lesson->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: скачивание файла материалов урока.
     */
    public function test_download_attachment_success(): void
    {
        Storage::fake('public');

        $course = Course::factory()->published()->create();
        $file = UploadedFile::fake()->create('cheatsheet.pdf', 150);
        $path = $file->store('attachments/lessons', 'public');

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => $path,
        ]);

        $response = $this->get("/api/lessons/{$lesson->id}/attachment");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('content-disposition');
    }

    /**
     * Тест: при попытке скачивания отсутствующих материалов возвращается 404.
     */
    public function test_download_attachment_returns_404_when_missing(): void
    {
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => null,
        ]);

        $response = $this->getJson("/api/lessons/{$lesson->id}/attachment");

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(['message' => 'Файл материалов отсутствует для данного урока']);
    }
}
