<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionMedia extends Model
{
    protected $fillable = [
        'question_bank_id',
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

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }
}
