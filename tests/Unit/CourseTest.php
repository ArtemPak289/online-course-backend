<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_belongs_to_category_and_author(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->teacher()->create();

        $course = Course::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        $this->assertTrue($course->category->is($category));
        $this->assertTrue($course->author->is($author));
    }

    public function test_course_status_helpers(): void
    {
        $publishedCourse = Course::factory()->published()->create();
        $draftCourse = Course::factory()->draft()->create();

        $this->assertTrue($publishedCourse->isPublished());
        $this->assertFalse($publishedCourse->isDraft());

        $this->assertTrue($draftCourse->isDraft());
        $this->assertFalse($draftCourse->isPublished());
    }

    public function test_course_scopes(): void
    {
        Course::factory()->published()->count(2)->create();
        Course::factory()->draft()->count(3)->create();

        $this->assertSame(2, Course::published()->count());
        $this->assertSame(3, Course::draft()->count());
    }
}
