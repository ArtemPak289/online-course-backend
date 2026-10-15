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

    public function test_anyone_can_view_categories_list(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description'],
                ],
                'total',
            ]);
    }

    public function test_anyone_can_view_single_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Web Development',
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'category' => [
                    'id' => $category->id,
                    'name' => 'Web Development',
                ],
            ]);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAsApi($admin)
            ->postJson('/api/categories', [
                'name' => 'Cloud Computing',
                'description' => 'AWS, Azure, and GCP courses.',
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Категория успешно создана.',
                'category' => [
                    'name' => 'Cloud Computing',
                    'slug' => 'cloud-computing',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Cloud Computing',
            'slug' => 'cloud-computing',
        ]);
    }

    public function test_teacher_and_student_cannot_create_category(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $this->actingAsApi($teacher)
            ->postJson('/api/categories', ['name' => 'Teacher Cat'])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAsApi($student)
            ->postJson('/api/categories', ['name' => 'Student Cat'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAsApi($admin)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Updated Category',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Категория успешно обновлена.',
                'category' => [
                    'name' => 'Updated Category',
                    'slug' => 'updated-category',
                ],
            ]);

        $this->assertSame('Updated Category', $category->fresh()->name);
    }

    public function test_non_admin_cannot_update_category(): void
    {
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $this->actingAsApi($teacher)
            ->putJson("/api/categories/{$category->id}", ['name' => 'Hacked'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($admin)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Категория успешно удалена.',
            ]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_delete_category(): void
    {
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAsApi($student)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_category_validation_requires_unique_name(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Unique Name']);

        $response = $this->actingAsApi($admin)
            ->postJson('/api/categories', [
                'name' => 'Unique Name',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }
}
