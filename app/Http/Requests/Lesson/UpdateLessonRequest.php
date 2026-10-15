<?php

namespace App\Http\Requests\Lesson;

use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    /**
     * Проверка прав пользователя на обновление урока.
     */
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');

        if (! $lesson instanceof Lesson) {
            $lesson = Lesson::find($lesson);
        }

        if (! $lesson) {
            return false;
        }

        return $this->user()?->can('update', $lesson) ?? false;
    }

    /**
     * Правила валидации для обновления урока.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'order' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'attachment' => ['sometimes', 'nullable', 'file', 'max:10240'],
        ];
    }
}
