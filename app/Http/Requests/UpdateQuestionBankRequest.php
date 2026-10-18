<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $question = $this->route('question');

        $questionBankId = is_object($question)
            ? $question->id
            : $question;

        return [
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],

            'type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                ]),
            ],

            'question' => [
                'required',
                'string',
            ],

            'explanation' => [
                'nullable',
                'string',
            ],

            'difficulty' => [
                'required',
                Rule::in([
                    'easy',
                    'medium',
                    'hard',
                ]),
            ],

            'default_score' => [
                'required',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'options' => [
                'required',
                'array',
                'min:2',
            ],

            'options.*.id' => [
                'nullable',
                'integer',
                'exists:question_options,id',
            ],

            'options.*.option_key' => [
                'required',
                'string',
                'max:10',
            ],

            'options.*.option_text' => [
                'nullable',
                'string',
            ],

            'options.*.is_correct' => [
                'nullable',
                'boolean',
            ],

            'options.*.media' => [
                'nullable',
                'array',
            ],

            'options.*.media.*.type' => [
                'required',
                Rule::in([
                    'image',
                ]),
            ],

            'options.*.media.*.source' => [
                'required',
                Rule::in([
                    'upload',
                ]),
            ],

            'options.*.media.*.file' => [
                'nullable',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp',
            ],

            'question_media' => [
                'nullable',
                'array',
            ],

            'question_media.*.type' => [
                'required',
                Rule::in([
                    'image',
                    'video',
                    'audio',
                    'document',
                ]),
            ],

            'question_media.*.source' => [
                'required',
                Rule::in([
                    'upload',
                    'youtube',
                    'vimeo',
                    'external',
                ]),
            ],

            'question_media.*.file' => [
                'nullable',
                'file',
                'max:51200',
                'mimes:jpg,jpeg,png,webp,mp4,webm,mov,mp3,wav,ogg,pdf',
            ],

            'question_media.*.external_url' => [
                'nullable',
                'url',
                'max:500',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $options = $this->input('options', []);

            if ($type === 'multiple_choice') {
                $correctCount = collect($options)
                    ->filter(
                        fn($option) => !empty($option['is_correct'])
                    )
                    ->count();

                if ($correctCount !== 1) {
                    $validator->errors()->add(
                        'options',
                        'Soal pilihan ganda harus memiliki tepat satu jawaban benar.'
                    );
                }
            }

            if ($type === 'true_false') {
                if (count($options) !== 2) {
                    $validator->errors()->add(
                        'options',
                        'Soal benar/salah harus memiliki dua pilihan jawaban.'
                    );
                }

                $correctCount = collect($options)
                    ->filter(
                        fn($option) => !empty($option['is_correct'])
                    )
                    ->count();

                if ($correctCount !== 1) {
                    $validator->errors()->add(
                        'options',
                        'Soal benar/salah harus memiliki tepat satu jawaban benar.'
                    );
                }
            }

            foreach ($options as $index => $option) {
                $text = trim($option['option_text'] ?? '');
                $media = $option['media'] ?? [];

                $hasMedia = collect($media)->contains(
                    fn($item) => !empty($item['file'])
                );

                if ($text === '' && !$hasMedia) {
                    $validator->errors()->add(
                        "options.{$index}.option_text",
                        'Pilihan jawaban harus memiliki teks atau gambar.'
                    );
                }
            }

            foreach ($options as $index => $option) {
                foreach ($option['media'] ?? [] as $mediaIndex => $media) {
                    if (
                        ($media['source'] ?? 'upload') === 'upload' &&
                        empty($media['file'])
                    ) {
                        $validator->errors()->add(
                            "options.{$index}.media.{$mediaIndex}.file",
                            'File gambar pilihan jawaban wajib dipilih.'
                        );
                    }
                }
            }

            foreach ($this->input('question_media', []) as $index => $media) {
                $source = $media['source'] ?? 'upload';

                if (
                    $source === 'upload' &&
                    empty($media['file'])
                ) {
                    $validator->errors()->add(
                        "question_media.{$index}.file",
                        'File media wajib dipilih.'
                    );
                }

                if (
                    $source !== 'upload' &&
                    empty($media['external_url'])
                ) {
                    $validator->errors()->add(
                        "question_media.{$index}.external_url",
                        'URL media wajib diisi.'
                    );
                }
            }
        });
    }
}
