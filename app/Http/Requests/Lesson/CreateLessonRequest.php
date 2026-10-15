<?php

namespace App\Http\Requests\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateLessonRequest extends FormRequest
{
    /**
     * Проверка прав пользователя на создание урока.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');

        if (! $course instanceof Course) {
            $course = Course::find($course);
        }

        if (! $course) {
            return false;
        }

        return $this->user()?->can('create', [Lesson::class, $course]) ?? false;
    }

    /**
     * Правила валидации для создания урока.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'order' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'attachment' => ['sometimes', 'nullable', 'file', 'max:10240'],
        ];
    }
}
