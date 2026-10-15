<?php

namespace Tests\Feature\Course;

use App\Enums\CourseStatus;
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

    public function test_teacher_can_create_course(): void
    {
        Event::fake([CoursePublished::class]);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $payload = [
            'title' => 'Building APIs with Laravel',
            'description' => 'A comprehensive course on Laravel REST APIs.',
            'price' => 99.99,
            'category_id' => $category->id,
            'status' => CourseStatus::Published->value,
        ];

        $response = $this->actingAsApi($teacher)
            ->postJson('/api/courses', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Курс успешно создан.',
                'course' => [
                    'title' => 'Building APIs with Laravel',
                    'price' => '99.99',
                    'category_id' => $category->id,
                    'author_id' => $teacher->id,
                    'status' => CourseStatus::Published->value,
                ],
            ]);

        $this->assertDatabaseHas('courses', [
            'title' => 'Building APIs with Laravel',
            'author_id' => $teacher->id,
            'category_id' => $category->id,
            'status' => CourseStatus::Published->value,
        ]);

        Event::assertDispatched(CoursePublished::class);
    }

    public function test_admin_can_create_course(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($admin)
            ->postJson('/api/courses', [
                'title' => 'Admin Course',
                'description' => 'Created by admin.',
                'price' => 50.00,
                'category_id' => $category->id,
                'status' => CourseStatus::Draft->value,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('courses', [
            'title' => 'Admin Course',
            'author_id' => $admin->id,
        ]);
    }

    public function test_student_cannot_create_course(): void
    {
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($student)
            ->postJson('/api/courses', [
                'title' => 'Student Course',
                'description' => 'Should fail.',
                'price' => 10.00,
                'category_id' => $category->id,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_author_can_update_their_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id, 'title' => 'Initial Title']);

        $response = $this->actingAsApi($teacher)
            ->putJson("/api/courses/{$course->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Курс успешно обновлен.',
                'course' => [
                    'id' => $course->id,
                    'title' => 'Updated Title',
                ],
            ]);

        $this->assertSame('Updated Title', $course->fresh()->title);
    }

    public function test_admin_can_update_any_course(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);

        $response = $this->actingAsApi($admin)
            ->putJson("/api/courses/{$course->id}", [
                'title' => 'Admin Overwrite',
            ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame('Admin Overwrite', $course->fresh()->title);
    }

    public function test_other_teacher_cannot_update_course(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacherA->id]);

        $response = $this->actingAsApi($teacherB)
            ->putJson("/api/courses/{$course->id}", [
                'title' => 'Unauthorized Update',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_author_can_delete_their_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacher->id]);

        $response = $this->actingAsApi($teacher)
            ->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Курс успешно удален.',
            ]);

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_other_teacher_cannot_delete_course(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $course = Course::factory()->create(['author_id' => $teacherA->id]);

        $response = $this->actingAsApi($teacherB)
            ->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_admin_can_delete_any_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $response = $this->actingAsApi($admin)
            ->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_course_validation_errors(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAsApi($teacher)
            ->postJson('/api/courses', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['title', 'description', 'price', 'category_id']);
    }
}
