<?php

namespace Tests\Feature\Progress;

use App\Events\CourseCompleted;
use App\Jobs\GenerateCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CourseProgressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: студент, оплативший курс, может отметить урок завершённым.
     */
    public function test_student_with_paid_course_can_complete_lesson(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAsApi($student)
            ->postJson("/api/lessons/{$lesson->id}/complete");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'completed' => true,
                'percentage' => 100,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    /**
     * Тест: студент без оплаты курса не может отмечать уроки завершёнными.
     */
    public function test_student_without_paid_course_cannot_complete_lesson(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAsApi($student)
            ->postJson("/api/lessons/{$lesson->id}/complete");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Тест: завершение последнего урока курса генерирует событие и запускает генерацию сертификата.
     */
    public function test_completing_course_triggers_event_and_certificate_job(): void
    {
        Event::fake([CourseCompleted::class]);
        Queue::fake([GenerateCertificate::class]);

        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();

        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAsApi($student)
            ->postJson("/api/lessons/{$lesson->id}/complete");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['completed' => true]);

        Event::assertDispatched(CourseCompleted::class);
        Queue::assertPushed(GenerateCertificate::class);
    }

    /**
     * Тест: просмотр текущего прогресса курса.
     */
    public function test_can_view_course_progress(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();

        Lesson::factory()->create(['course_id' => $course->id, 'position' => 1]);
        Lesson::factory()->create(['course_id' => $course->id, 'position' => 2]);

        Purchase::factory()->paid()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAsApi($student)
            ->getJson("/api/courses/{$course->id}/progress");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'total_lessons' => 2,
                'completed_lessons' => 0,
                'percentage' => 0,
                'is_completed' => false,
            ]);
    }

    /**
     * Тест: скачивание сформированного сертификата курса.
     */
    public function test_download_certificate(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $course = Course::factory()->published()->create();

        $certPath = "certificates/{$student->id}-{$course->id}.pdf";
        Storage::disk('local')->put($certPath, '%PDF-1.4 sample content');

        Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'file_path' => $certPath,
        ]);

        $response = $this->actingAsApi($student)
            ->get("/api/courses/{$course->id}/certificate");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
