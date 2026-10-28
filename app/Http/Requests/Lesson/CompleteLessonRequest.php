<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;

class CompleteLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() || $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [];
    }
}
