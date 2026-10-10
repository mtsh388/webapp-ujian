<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user()) {
            return false;
        }

        if ($this->isMethod('post')) {
            return $this->user()->can('exam.create');
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->user()->can('exam.update');
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'subject_id' => [
                'required',
                'exists:subjects,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'duration_minutes' => [
                'required',
                'integer',
                'min:1',
            ],
            'start_at' => [
                'required',
                'date',
            ],
            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],
            'passing_score' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'max_attempts' => [
                'required',
                'integer',
                'min:1',
            ],
            'status' => [
                'required',
                'in:draft,published,closed',
            ],
            'shuffle_questions' => [
                'boolean',
            ],
            'shuffle_options' => [
                'boolean',
            ],
            'show_result_immediately' => [
                'boolean',
            ],
            'show_correct_answers' => [
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shuffle_questions' => $this->boolean('shuffle_questions'),
            'shuffle_options' => $this->boolean('shuffle_options'),
            'show_result_immediately' => $this->boolean('show_result_immediately'),
            'show_correct_answers' => $this->boolean('show_correct_answers'),
        ]);
    }
}
