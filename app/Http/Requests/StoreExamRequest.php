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
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'exam_type' => ['required', 'in:trial,exam'],
            'is_timed' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'required_if:is_timed,1', 'integer', 'min:1'],
            'start_at' => ['nullable', 'required_if:is_timed,1', 'date'],
            'end_at' => ['nullable', 'required_if:is_timed,1', 'date', 'after:start_at'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'required_if:is_timed,1', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published,closed'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_result_immediately' => ['boolean'],
            'show_correct_answers' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $isTimed = $this->boolean('is_timed');

        $this->merge([
            'is_timed' => $isTimed ? 1 : 0,
            'shuffle_questions' => $this->boolean('shuffle_questions'),
            'shuffle_options' => $this->boolean('shuffle_options'),
            'show_result_immediately' => $this->boolean('show_result_immediately'),
            'show_correct_answers' => $this->boolean('show_correct_answers'),
        ]);

        if (!$isTimed) {
            $this->merge([
                'duration_minutes' => null,
                'max_attempts' => null,
                'start_at' => null,
                'end_at' => null,
            ]);
        }
    }
}
