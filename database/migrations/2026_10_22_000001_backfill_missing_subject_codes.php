<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $subjects = DB::table('subjects')
            ->where(function ($query) {
                $query->whereNull('code')
                    ->orWhereRaw("TRIM(code) = ''");
            })
            ->orderBy('id')
            ->get(['id', 'name', 'education_level', 'grade_level', 'topic']);

        $usedCodes = [];

        foreach (DB::table('subjects')->whereNotNull('code')->whereRaw("TRIM(code) <> ''")->pluck('code') as $existingCode) {
            $usedCodes[strtoupper(trim((string) $existingCode))] = true;
        }

        foreach ($subjects as $subject) {
            $level = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $subject->education_level))) ?: 'UMUM';
            $grade = is_numeric($subject->grade_level) && (int) $subject->grade_level > 0
                ? (string) (int) $subject->grade_level
                : '0';
            $name = (string) ($subject->name ?: $subject->topic ?: 'TOPIK');
            $normalized = strtoupper(Str::ascii(trim($name)));
            $words = preg_split('/[^A-Z0-9]+/', $normalized) ?: [];
            $words = array_values(array_filter($words, fn (string $word) => $word !== ''));

            if (count($words) > 1) {
                $abbreviation = substr(implode('', array_map(
                    fn (string $word) => substr($word, 0, 1),
                    $words
                )), 0, 5);
            } else {
                $abbreviation = substr($words[0] ?? 'TOP', 0, 3);
            }

            $abbreviation = preg_replace('/[^A-Z0-9]/', '', $abbreviation) ?: 'TOP';
            $prefix = $level . '-' . $grade . '-' . $abbreviation;
            $number = 1;

            do {
                $code = $prefix . '-' . str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
                $normalizedCode = strtoupper($code);
            } while (isset($usedCodes[$normalizedCode]));

            DB::table('subjects')->where('id', $subject->id)->update(['code' => $code]);
            $usedCodes[$normalizedCode] = true;
        }
    }

    public function down(): void
    {
    }
};
