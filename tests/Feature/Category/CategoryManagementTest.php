<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: любой пользователь (гость) может просматривать список категорий.
     */
    public function test_anyone_can_view_categories_list(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data', 'current_page']);
    }

    /**
     * Тест: просмотр конкретной категории.
     */
    public function test_anyone_can_view_single_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => $category->name]);
    }

    /**
     * Тест: администратор может создать категорию.
     */
    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAsApi($admin)
            ->postJson('/api/categories', [
                'name' => 'Backend Development',
                'description' => 'PHP, Go, Node.js',
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['name' => 'Backend Development']);

        $this->assertDatabaseHas('categories', ['name' => 'Backend Development']);
    }

    /**
     * Тест: студент и преподаватель не могут создавать категории.
     */
    public function test_student_and_teacher_cannot_create_category(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAsApi($student)
            ->postJson('/api/categories', ['name' => 'Test'])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAsApi($teacher)
            ->postJson('/api/categories', ['name' => 'Test'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: администратор может обновить категорию.
     */
    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($admin)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Updated Category',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Updated Category']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Category']);
    }

    /**
     * Тест: администратор может удалить категорию.
     */
    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($admin)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * Тест: валидация уникальности названия категории.
     */
    public function test_category_validation_requires_unique_name(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Existing Name']);

        $response = $this->actingAsApi($admin)
            ->postJson('/api/categories', ['name' => 'Existing Name']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }
}
