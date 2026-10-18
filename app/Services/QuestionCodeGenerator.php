<?php

namespace App\Services;

use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Support\Str;

class QuestionCodeGenerator
{
    public function generate(Subject $subject): string
    {
        $subjectPart = substr(
            Str::upper(Str::slug($subject->name, '-')),
            0,
            18
        );

        $levelPart = Str::upper($subject->education_level ?: 'UMUM');

        $gradePart = $subject->grade_level
            ? 'K' . (string) $subject->grade_level
            : 'KX';

        $topicPart = substr(
            Str::upper(Str::slug($subject->topic ?: 'UMUM', '-')),
            0,
            30
        );

        $prefix = trim(
            implode('-', array_filter([
                $subjectPart ?: 'MAPEL',
                $levelPart,
                $gradePart,
                $topicPart ?: 'TOPIK',
            ])),
            '-'
        );

        $codes = QuestionBank::withTrashed()
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code');

        $lastNumber = 0;

        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $code, $matches)) {
                $lastNumber = max($lastNumber, (int) $matches[1]);
            }
        }

        do {
            $candidate = $prefix . '-' . str_pad(
                (string) ++$lastNumber,
                3,
                '0',
                STR_PAD_LEFT
            );
        } while (QuestionBank::withTrashed()->where('code', $candidate)->exists());

        return $candidate;
    }
}
