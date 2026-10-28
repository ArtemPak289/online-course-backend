<?php

namespace Tests\Feature\Course;

use App\Events\CoursePublished;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: преподаватель может создать курс.
     */
    public function test_teacher_can_create_course(): void
    {
        Event::fake([CoursePublished::class]);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($teacher)
            ->postJson('/api/courses', [
                'title' => 'Building Web APIs',
                'description' => 'Complete guide.',
                'price' => 99.99,
                'category_id' => $category->id,
                'status' => 'published',
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['title' => 'Building Web APIs']);

        $this->assertDatabaseHas('courses', [
            'title' => 'Building Web APIs',
            'author_id' => $teacher->id,
            'status' => 'published',
        ]);

        Event::assertDispatched(CoursePublished::class);
    }

    /**
     * Тест: студент не имеет прав на создание курса.
     */
    public function test_student_cannot_create_course(): void
    {
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($student)
            ->postJson('/api/courses', [
                'title' => 'Attempt by Student',
                'description' => 'Desc',
                'price' => 50,
                'category_id' => $category->id,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: автор курса может обновить свой курс.
     */
    public function test_author_can_update_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);

        $response = $this->actingAsApi($teacher)
            ->putJson("/api/courses/{$course->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Updated Title']);
    }

    /**
     * Тест: сторонний преподаватель не может изменять чужой курс.
     */
    public function test_other_teacher_cannot_update_course(): void
    {
        $author = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $course = Course::factory()->create(['author_id' => $author->id]);

        $response = $this->actingAsApi($otherTeacher)
            ->putJson("/api/courses/{$course->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: автор или администратор могут удалить курс.
     */
    public function test_author_and_admin_can_delete_course(): void
    {
        $author = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $course1 = Course::factory()->create(['author_id' => $author->id]);
        $course2 = Course::factory()->create();

        // Автор удаляет свой курс
        $this->actingAsApi($author)
            ->deleteJson("/api/courses/{$course1->id}")
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseMissing('courses', ['id' => $course1->id]);

        // Администратор удаляет любой курс
        $this->actingAsApi($admin)
            ->deleteJson("/api/courses/{$course2->id}")
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseMissing('courses', ['id' => $course2->id]);
    }
}
