<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запуск миграции для создания таблицы уроков.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete(); // При удалении курса удаляются все его уроки
            $table->string('title');
            $table->text('content'); // Текст урока
            $table->unsignedInteger('order')->default(1); // Порядковый номер урока в курсе
            $table->string('attachment_path')->nullable(); // Путь к файлу с дополнительными материалами
            $table->timestamps();

            // Составной индекс для быстрой сортировки уроков конкретного курса
            $table->index(['course_id', 'order']);
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
