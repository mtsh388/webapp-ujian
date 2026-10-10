<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkQuestionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],
            'difficulty' => [
                'required',
                'in:easy,medium,hard',
            ],
            'default_score' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'file' => [
                'required',
                'file',
                'mimes:docx',
                'max:10240',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'subject_id' => 'mata pelajaran',
            'difficulty' => 'tingkat kesulitan',
            'default_score' => 'nilai default',
            'file' => 'file Word',
        ];
    }
}
