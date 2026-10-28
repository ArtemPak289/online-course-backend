<?php

namespace App\Http\Requests\Lesson;

use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    /**
     * Проверка прав доступа: только автор курса или администратор могут обновлять урок.
     */
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');

        if (! $lesson instanceof Lesson) {
            $lesson = Lesson::find($lesson);
        }

        return $lesson ? ($this->user()?->can('update', $lesson) ?? false) : false;
    }

    /**
     * Правила валидации для обновления урока.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'position' => ['sometimes', 'integer', 'min:1'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
