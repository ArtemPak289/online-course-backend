<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CourseFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_search_courses_by_title(): void
    {
        Course::factory()->published()->create(['title' => 'Vue 3 Essentials']);
        Course::factory()->published()->create(['title' => 'React from Scratch']);

        $response = $this->getJson('/api/courses?search=Vue');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(1, $response->json('total'));
        $this->assertSame('Vue 3 Essentials', $response->json('data.0.title'));
    }

    public function test_anyone_can_filter_courses_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        Course::factory()->published()->count(2)->create(['category_id' => $cat1->id]);
        Course::factory()->published()->count(3)->create(['category_id' => $cat2->id]);

        $response = $this->getJson("/api/courses?category_id={$cat2->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(3, $response->json('total'));
    }

    public function test_anyone_can_filter_courses_by_teacher(): void
    {
        $teacher1 = User::factory()->teacher()->create();
        $teacher2 = User::factory()->teacher()->create();

        Course::factory()->published()->count(2)->create(['author_id' => $teacher1->id]);
        Course::factory()->published()->count(1)->create(['author_id' => $teacher2->id]);

        $response = $this->getJson("/api/courses?author_id={$teacher1->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(2, $response->json('total'));
    }

    public function test_anyone_can_sort_courses_by_price(): void
    {
        Course::factory()->published()->create(['title' => 'Expensive', 'price' => 300.00]);
        Course::factory()->published()->create(['title' => 'Cheap', 'price' => 20.00]);
        Course::factory()->published()->create(['title' => 'Medium', 'price' => 100.00]);

        $responseAsc = $this->getJson('/api/courses?sort_by=price&sort_order=asc');
        $responseAsc->assertStatus(Response::HTTP_OK);
        $this->assertSame('Cheap', $responseAsc->json('data.0.title'));
        $this->assertSame('Expensive', $responseAsc->json('data.2.title'));

        $responseDesc = $this->getJson('/api/courses?sort_by=price&sort_order=desc');
        $responseDesc->assertStatus(Response::HTTP_OK);
        $this->assertSame('Expensive', $responseDesc->json('data.0.title'));
        $this->assertSame('Cheap', $responseDesc->json('data.2.title'));
    }

    public function test_anyone_can_sort_courses_by_created_at(): void
    {
        $oldCourse = Course::factory()->published()->create(['title' => 'Old', 'created_at' => now()->subDays(5)]);
        $newCourse = Course::factory()->published()->create(['title' => 'New', 'created_at' => now()]);

        $response = $this->getJson('/api/courses?sort_by=created_at&sort_order=desc');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame('New', $response->json('data.0.title'));
        $this->assertSame('Old', $response->json('data.1.title'));
    }

    public function test_guest_cannot_view_draft_course_detail(): void
    {
        $draft = Course::factory()->draft()->create();

        $response = $this->getJson("/api/courses/{$draft->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_author_can_view_own_draft_course_detail(): void
    {
        $teacher = User::factory()->teacher()->create();
        $draft = Course::factory()->draft()->create(['author_id' => $teacher->id]);

        $response = $this->actingAsApi($teacher)
            ->getJson("/api/courses/{$draft->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'course' => [
                    'id' => $draft->id,
                ],
            ]);
    }

    public function test_admin_can_view_draft_course_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Course::factory()->draft()->create();

        $response = $this->actingAsApi($admin)
            ->getJson("/api/courses/{$draft->id}");

        $response->assertStatus(Response::HTTP_OK);
    }
}
