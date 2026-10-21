<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'exam_question_id' => [
                'required',
                'integer',
                'exists:exam_questions,id',
            ],
            'selected_option_id' => ['nullable', 'integer', 'exists:exam_question_options,id'],
            'selected_option_ids' => ['sometimes', 'array'],
            'selected_option_ids.*' => ['integer', 'distinct', 'exists:exam_question_options,id'],
        ];
    }
}
