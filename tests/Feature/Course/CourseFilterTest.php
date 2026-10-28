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

    /**
     * Тест: поиск курсов по названию (включая регистронезависимый поиск).
     */
    public function test_search_courses_by_title(): void
    {
        Course::factory()->published()->create(['title' => 'Learn Laravel REST API']);
        Course::factory()->published()->create(['title' => 'Learn Python Django']);

        $response = $this->getJson('/api/courses?search=laravel');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => 'Learn Laravel REST API'])
            ->assertJsonMissing(['title' => 'Learn Python Django']);
    }

    /**
     * Тест: фильтрация курсов по статусу для преподавателя.
     */
    public function test_teacher_can_filter_courses_by_status(): void
    {
        $teacher = User::factory()->teacher()->create();
        $draft = Course::factory()->draft()->create(['author_id' => $teacher->id, 'title' => 'Draft Course']);
        $published = Course::factory()->published()->create(['author_id' => $teacher->id, 'title' => 'Published Course']);

        $response = $this->actingAsApi($teacher)
            ->getJson('/api/courses?status=draft');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['id' => $draft->id])
            ->assertJsonMissing(['id' => $published->id]);
    }

    /**
     * Тест: фильтрация по категории.
     */
    public function test_filter_courses_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        $c1 = Course::factory()->published()->create(['category_id' => $cat1->id]);
        $c2 = Course::factory()->published()->create(['category_id' => $cat2->id]);

        $response = $this->getJson("/api/courses?category_id={$cat1->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => $c1->title])
            ->assertJsonMissing(['title' => $c2->title]);
    }

    /**
     * Тест: сортировка по стоимости.
     */
    public function test_sort_courses_by_price(): void
    {
        $c1 = Course::factory()->published()->create(['price' => 10]);
        $c2 = Course::factory()->published()->create(['price' => 100]);

        $response = $this->getJson('/api/courses?sort=price&direction=desc');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($c2->id, $response->json('data.0.id'));
    }

    /**
     * Тест: гость не видит черновики курсов, а автор видит свой черновик.
     */
    public function test_draft_visibility_controls(): void
    {
        $author = User::factory()->teacher()->create();
        $draft = Course::factory()->draft()->create(['author_id' => $author->id]);

        // Гость получает 404
        $this->getJson("/api/courses/{$draft->id}")
            ->assertStatus(Response::HTTP_NOT_FOUND);

        // Автор черновика видит его
        $this->actingAsApi($author)
            ->getJson("/api/courses/{$draft->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['id' => $draft->id]);
    }
}
