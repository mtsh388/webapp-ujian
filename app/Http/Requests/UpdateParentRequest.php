<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $parent = $this->route('parent');

        return [
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'students' => [
                'nullable',
                'array',
            ],
            'students.*' => [
                'integer',
                'exists:students,id',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'akun pengguna',
            'full_name' => 'nama lengkap',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'is_active' => 'status',
            'students' => 'siswa',
        ];
    }
}
