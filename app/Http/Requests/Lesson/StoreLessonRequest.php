<?php

namespace App\Http\Requests\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    /**
     * Проверка прав доступа: только автор курса или администратор могут добавлять уроки.
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

        $lesson = new Lesson(['course_id' => $course->id]);
        $lesson->setRelation('course', $course);

        return $this->user()?->can('create', $lesson) ?? false;
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
            'position' => ['sometimes', 'integer', 'min:1'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
