<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
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
