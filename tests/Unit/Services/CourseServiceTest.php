<?php

namespace Tests\Unit\Services;

use App\Enums\CourseStatus;
use App\Events\CoursePublished;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    private CourseService $courseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courseService = new CourseService;
    }

    public function test_create_course_as_draft(): void
    {
        Event::fake([CoursePublished::class]);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $course = $this->courseService->createCourse($teacher, [
            'title' => 'Learn Go Programming',
            'description' => 'Complete guide to Go.',
            'price' => 99.99,
            'category_id' => $category->id,
            'status' => CourseStatus::Draft->value,
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Learn Go Programming',
            'author_id' => $teacher->id,
            'status' => CourseStatus::Draft->value,
        ]);
        $this->assertNull($course->published_at);

        Event::assertNotDispatched(CoursePublished::class);
    }

    public function test_create_course_as_published_dispatches_event(): void
    {
        Event::fake([CoursePublished::class]);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $course = $this->courseService->createCourse($teacher, [
            'title' => 'Vue 3 Deep Dive',
            'description' => 'Comprehensive Vue 3 tutorial.',
            'price' => 120.00,
            'category_id' => $category->id,
            'status' => CourseStatus::Published->value,
        ]);

        $this->assertSame(CourseStatus::Published, $course->status);
        $this->assertNotNull($course->published_at);

        Event::assertDispatched(CoursePublished::class, function ($event) use ($course) {
            return $event->course->id === $course->id;
        });
    }

    public function test_update_course_to_published_dispatches_event(): void
    {
        Event::fake([CoursePublished::class]);

        $course = Course::factory()->draft()->create();

        $updated = $this->courseService->updateCourse($course, [
            'status' => CourseStatus::Published->value,
        ]);

        $this->assertSame(CourseStatus::Published, $updated->status);
        $this->assertNotNull($updated->published_at);

        Event::assertDispatched(CoursePublished::class);
    }

    public function test_delete_course(): void
    {
        $course = Course::factory()->create();

        $this->courseService->deleteCourse($course);

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_get_paginated_courses_filters_by_search_category_author(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();

        Course::factory()->published()->create([
            'title' => 'Mastering Laravel',
            'category_id' => $categoryA->id,
            'author_id' => $teacherA->id,
            'price' => 200,
        ]);

        Course::factory()->published()->create([
            'title' => 'Python for AI',
            'category_id' => $categoryB->id,
            'author_id' => $teacherB->id,
            'price' => 100,
        ]);

        // Search
        $searchResult = $this->courseService->getPaginatedCourses(['search' => 'Laravel']);
        $this->assertSame(1, $searchResult->total());
        $this->assertSame('Mastering Laravel', $searchResult->items()[0]->title);

        // Filter by category
        $catResult = $this->courseService->getPaginatedCourses(['category_id' => $categoryB->id]);
        $this->assertSame(1, $catResult->total());

        // Filter by author
        $authorResult = $this->courseService->getPaginatedCourses(['author_id' => $teacherA->id]);
        $this->assertSame(1, $authorResult->total());
    }

    public function test_draft_courses_hidden_from_guests_and_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        Course::factory()->published()->create(['author_id' => $teacher->id]);
        Course::factory()->draft()->create(['author_id' => $teacher->id]);

        $student = User::factory()->student()->create();

        // Guest sees only published
        $guestCourses = $this->courseService->getPaginatedCourses([], null);
        $this->assertSame(1, $guestCourses->total());

        // Student sees only published
        $studentCourses = $this->courseService->getPaginatedCourses([], $student);
        $this->assertSame(1, $studentCourses->total());

        // Author teacher sees their own drafts too
        $teacherCourses = $this->courseService->getPaginatedCourses([], $teacher);
        $this->assertSame(2, $teacherCourses->total());

        // Admin sees all courses
        $admin = User::factory()->admin()->create();
        $adminCourses = $this->courseService->getPaginatedCourses([], $admin);
        $this->assertSame(2, $adminCourses->total());
    }
}
