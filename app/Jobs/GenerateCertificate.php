<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Создать новый экземпляр задачи генерации сертификата.
     */
    public function __construct(
        public int $userId,
        public int $courseId
    ) {}

    /**
     * Выполнить задачу генерации сертификата в фоновой очереди.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        $course = Course::find($this->courseId);

        if (! $user || ! $course) {
            Log::warning('Не удалось сформировать сертификат: пользователь или курс не найден', [
                'user_id' => $this->userId,
                'course_id' => $this->courseId,
            ]);

            return;
        }

        $path = "certificates/{$this->userId}-{$this->courseId}.pdf";

        // Генерация валидного бинарного содержимого PDF-документа
        $title = preg_replace('/[^A-Za-z0-9 .\\-]/', '', $course->title) ?: 'Online Course';
        $userName = preg_replace('/[^A-Za-z0-9 .\\-]/', '', $user->name) ?: 'Student';

        $text = "Certificate of Completion - Student: {$userName} - Course: {$title}";
        $stream = "BT /F1 16 Tf 72 720 Td ({$text}) Tj ET";
        $streamLen = strlen($stream);

        $pdf = "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj\n"
            ."4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            ."5 0 obj<</Length {$streamLen}>>stream\n{$stream}\nendstream endobj\n"
            ."trailer<</Root 1 0 R>>\n%%EOF";

        Storage::disk('local')->put($path, $pdf);

        Certificate::updateOrCreate(
            [
                'user_id' => $this->userId,
                'course_id' => $this->courseId,
            ],
            [
                'file_path' => $path,
            ]
        );

        Log::info('PDF сертификат успешно сгенерирован в фоновой очереди', [
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'file_path' => $path,
        ]);
    }
}
