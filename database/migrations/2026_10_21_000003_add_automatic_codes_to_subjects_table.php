<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('name');
            });
        }

        $subjects = DB::table('subjects')->orderBy('id')->get(['id', 'name', 'education_level', 'grade_level', 'topic', 'code']);
        $usedCodes = [];

        foreach ($subjects as $subject) {
            if (is_string($subject->code) && trim($subject->code) !== '') {
                $usedCodes[$subject->code] = true;
            }
        }

        foreach ($subjects as $subject) {
            if (is_string($subject->code) && trim($subject->code) !== '') {
                continue;
            }

            $level = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($subject->education_level ?? ''))) ?: 'UMUM';
            $grade = (string) ($subject->grade_level ?? 0);
            $normalizedName = strtoupper(Str::ascii((string) ($subject->name ?: $subject->topic ?: 'TOPIK')));
            $words = preg_split('/\s+/', trim($normalizedName)) ?: [];
            $words = array_values(array_filter($words, fn ($word) => $word !== '' && preg_match('/[A-Z0-9]/', $word)));

            if (count($words) > 1) {
                $abbreviation = substr(implode('', array_map(fn ($word) => substr($word, 0, 1), $words)), 0, 5);
            } else {
                $abbreviation = substr($words[0] ?? 'TOP', 0, 3);
            }

            $abbreviation = preg_replace('/[^A-Z0-9]/', '', $abbreviation) ?: 'TOP';
            $prefix = $level . '-' . $grade . '-' . $abbreviation;
            $number = 1;

            do {
                $code = $prefix . '-' . str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
            } while (isset($usedCodes[$code]));

            DB::table('subjects')->where('id', $subject->id)->update(['code' => $code]);
            $usedCodes[$code] = true;
        }

        $indexes = Schema::getIndexes('subjects');
        $hasUniqueCodeIndex = collect($indexes)->contains(function (array $index) {
            return ($index['type'] ?? '') === 'unique' && ($index['columns'] ?? []) === ['code'];
        });

        if (! $hasUniqueCodeIndex) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique('code', 'subjects_code_unique');
            });
        }
    }

    public function down(): void
    {
    }
};
