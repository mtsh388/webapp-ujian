<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionOptionMedia extends Model
{
    protected $fillable = [
        'question_option_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'source',
        'external_url',
        'file_size',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function option()
    {
        return $this->belongsTo(
            QuestionOption::class,
            'question_option_id'
        );
    }
}
