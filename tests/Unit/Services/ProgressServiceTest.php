<?php

namespace Tests\Unit\Services;

use App\Events\CourseCompleted;
use App\Jobs\GenerateCertificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $progressService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->progressService = new ProgressService;
    }

    /**
     * Тест: отметка урока пройденным сохраняет запись в базе.
     */
    public function test_complete_lesson_records_progress(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $result = $this->progressService->completeLesson($student, $lesson);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertTrue($result['completed']);
        $this->assertEquals(100, $result['percentage']);
    }

    /**
     * Тест: отметка урока без оплаты курса выбрасывает 403 ошибку.
     */
    public function test_complete_lesson_fails_if_not_purchased(): void
    {
        $this->expectException(HttpException::class);

        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->progressService->completeLesson($student, $lesson);
    }

    /**
     * Тест: завершение всех уроков курса диспатчит CourseCompleted и задачу GenerateCertificate.
     */
    public function test_completing_all_lessons_dispatches_event_and_job(): void
    {
        Event::fake([CourseCompleted::class]);
        Queue::fake([GenerateCertificate::class]);

        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();

        $lesson1 = Lesson::factory()->create(['course_id' => $course->id, 'position' => 1]);
        $lesson2 = Lesson::factory()->create(['course_id' => $course->id, 'position' => 2]);

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // Завершаем первый урок (курс ещё не завершен)
        $res1 = $this->progressService->completeLesson($student, $lesson1);
        $this->assertFalse($res1['completed']);
        $this->assertEquals(50, $res1['percentage']);
        Event::assertNotDispatched(CourseCompleted::class);

        // Завершаем второй урок (курс полностью завершен)
        $res2 = $this->progressService->completeLesson($student, $lesson2);
        $this->assertTrue($res2['completed']);
        $this->assertEquals(100, $res2['percentage']);

        Event::assertDispatched(CourseCompleted::class);
        Queue::assertPushed(GenerateCertificate::class);
    }

    /**
     * Тест: расчет общего прогресса прохождения курса.
     */
    public function test_get_course_progress(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();

        $lesson1 = Lesson::factory()->create(['course_id' => $course->id, 'position' => 1]);
        Lesson::factory()->create(['course_id' => $course->id, 'position' => 2]);

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->progressService->completeLesson($student, $lesson1);

        $progress = $this->progressService->getCourseProgress($student, $course);

        $this->assertEquals(2, $progress['total_lessons']);
        $this->assertEquals(1, $progress['completed_lessons']);
        $this->assertEquals(50, $progress['percentage']);
        $this->assertFalse($progress['is_completed']);
    }
}
