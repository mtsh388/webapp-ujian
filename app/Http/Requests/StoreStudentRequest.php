<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => [
                'nullable',
                'integer',
                'exists:classes,id',
            ],
            'student_number' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                'unique:students,student_number',
            ],
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],
            'gender' => [
                'nullable',
                'string',
                'max:20',
            ],
            'birth_date' => [
                'nullable',
                'date',
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
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'akun pengguna',
            'class_id' => 'kelas',
            'student_number' => 'nomor siswa',
            'full_name' => 'nama lengkap',
            'gender' => 'jenis kelamin',
            'birth_date' => 'tanggal lahir',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'is_active' => 'status',
        ];
    }
}
