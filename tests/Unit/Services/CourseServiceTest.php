<?php

namespace Tests\Unit\Services;

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

    /**
     * Тест: создание черновика курса.
     */
    public function test_create_course_as_draft(): void
    {
        Event::fake([CoursePublished::class]);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $course = $this->courseService->createCourse($teacher, [
            'title' => 'Golang Mastery',
            'description' => 'Complete Golang guide.',
            'price' => 89.99,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Golang Mastery',
            'author_id' => $teacher->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        Event::assertNotDispatched(CoursePublished::class);
    }

    /**
     * Тест: создание опубликованного курса инициирует событие CoursePublished.
     */
    public function test_create_course_as_published_dispatches_event(): void
    {
        Event::fake([CoursePublished::class]);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $course = $this->courseService->createCourse($teacher, [
            'title' => 'Laravel REST API',
            'description' => 'Deep dive into APIs.',
            'price' => 129.99,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->assertEquals('published', $course->status);
        $this->assertNotNull($course->published_at);
        Event::assertDispatched(CoursePublished::class);
    }

    /**
     * Тест: перевод курса из черновика в опубликованный инициирует событие CoursePublished.
     */
    public function test_update_course_to_published_dispatches_event(): void
    {
        Event::fake([CoursePublished::class]);

        $course = Course::factory()->draft()->create();

        $this->courseService->updateCourse($course, [
            'status' => 'published',
        ]);

        Event::assertDispatched(CoursePublished::class);
    }

    /**
     * Тест: фильтрация и поиск курсов.
     */
    public function test_get_paginated_courses_filters_by_search_category_and_author(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->teacher()->create();

        Course::factory()->published()->create([
            'title' => 'Target Course Title',
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        Course::factory()->published()->create([
            'title' => 'Other Course Title',
        ]);

        $results = $this->courseService->getPaginatedCourses([
            'search' => 'Target',
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        $this->assertCount(1, $results->items());
        $this->assertEquals('Target Course Title', $results->items()[0]->title);
    }

    /**
     * Тест: черновики скрыты от неавторизованных пользователей и обычных студентов.
     */
    public function test_draft_courses_hidden_from_guests_and_students(): void
    {
        Course::factory()->published()->create(['title' => 'Published 1']);
        Course::factory()->draft()->create(['title' => 'Draft 1']);

        $guestResults = $this->courseService->getPaginatedCourses([]);
        $this->assertCount(1, $guestResults->items());
        $this->assertEquals('Published 1', $guestResults->items()[0]->title);

        $student = User::factory()->student()->create();
        $studentResults = $this->courseService->getPaginatedCourses([], $student);
        $this->assertCount(1, $studentResults->items());
    }
}
