<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_courses(): void
    {
        $category = Category::factory()->create();
        Course::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->courses);
        $this->assertInstanceOf(Course::class, $category->courses->first());
    }

    public function test_category_generates_slug_automatically(): void
    {
        $category = Category::create([
            'name' => 'Backend Development',
        ]);

        $this->assertSame('backend-development', $category->slug);
    }
}
