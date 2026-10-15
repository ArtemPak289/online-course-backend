<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = new CategoryService;
    }

    public function test_create_category(): void
    {
        $category = $this->categoryService->createCategory([
            'name' => 'Data Science',
            'description' => 'Machine learning and big data courses.',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Data Science',
            'slug' => 'data-science',
        ]);
    }

    public function test_update_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Old Category',
            'slug' => 'old-category',
        ]);

        $updated = $this->categoryService->updateCategory($category, [
            'name' => 'New Category Name',
            'description' => 'Updated description',
        ]);

        $this->assertSame('New Category Name', $updated->name);
        $this->assertSame('new-category-name', $updated->slug);
    }

    public function test_delete_category(): void
    {
        $category = Category::factory()->create();

        $this->categoryService->deleteCategory($category);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_get_paginated_categories(): void
    {
        Category::factory()->count(5)->create();

        $result = $this->categoryService->getPaginatedCategories(3);

        $this->assertSame(5, $result->total());
        $this->assertCount(3, $result->items());
    }
}
