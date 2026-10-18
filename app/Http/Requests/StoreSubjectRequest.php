<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
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
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where(fn ($query) => $query->where('education_level', $this->input('education_level'))->where('is_active', true))],
            'topic' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama mata pelajaran',
            'education_level' => 'jenjang pendidikan',
            'topic' => 'topik',
            'class_id' => 'kelas',
            'description' => 'deskripsi',
            'is_active' => 'status',
        ];
    }
}
