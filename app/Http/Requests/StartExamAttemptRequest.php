<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [];
    }
}
