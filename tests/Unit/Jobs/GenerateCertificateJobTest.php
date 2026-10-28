<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateCertificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateCertificateJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: выполнение задачи GenerateCertificate создает PDF файл и запись в таблице certificates.
     */
    public function test_generate_certificate_job_creates_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->student()->create(['name' => 'John Doe']);
        $course = Course::factory()->create(['title' => 'Laravel Masterclass']);

        $job = new GenerateCertificate($user->id, $course->id);
        $job->handle();

        $expectedPath = "certificates/{$user->id}-{$course->id}.pdf";

        Storage::disk('local')->assertExists($expectedPath);

        $this->assertDatabaseHas('certificates', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'file_path' => $expectedPath,
        ]);
    }
}
