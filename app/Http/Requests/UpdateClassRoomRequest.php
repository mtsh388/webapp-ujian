<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'education_level' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
            'grade_level' => ['required', 'integer', Rule::in([4, 5, 6, 7, 8, 9, 10, 11, 12])],
            'registration_type' => ['required', Rule::in(['trial', 'paid'])],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $level = $this->input('education_level');
            $grade = (int) $this->input('grade_level');

            $allowedGrades = match ($level) {
                'SD' => [4, 5, 6],
                'SMP' => [7, 8, 9],
                'SMA' => [10, 11, 12],
                default => [],
            };

            if (!in_array($grade, $allowedGrades, true)) {
                $validator->errors()->add('grade_level', 'Kelas tidak sesuai dengan jenjang pendidikan.');
            }
        });
    }
}
