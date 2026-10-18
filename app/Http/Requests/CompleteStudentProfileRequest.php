<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Siswa') ?? false;
    }

    public function rules(): array
    {
        $level = $this->input('education_level');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'school_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'education_level' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->where(
                    fn ($query) => $query
                        ->where('education_level', $level)
                        ->where('is_active', true)
                ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'school_name' => 'nama sekolah',
            'phone' => 'nomor HP',
            'education_level' => 'jenjang pendidikan',
            'class_id' => 'kelas',
        ];
    }
}
