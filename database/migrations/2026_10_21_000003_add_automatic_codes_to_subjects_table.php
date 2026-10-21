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

        $duplicateCodes = DB::table('subjects')
            ->selectRaw('UPPER(TRIM(code)) as normalized_code, COUNT(*) as total')
            ->whereNotNull('code')
            ->whereRaw("TRIM(code) <> ''")
            ->groupByRaw('UPPER(TRIM(code))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_code');

        if ($duplicateCodes->isNotEmpty()) {
            throw new RuntimeException(
                'Migration kode mata pelajaran dihentikan karena kode lama duplikat: '
                . $duplicateCodes->implode(', ')
            );
        }

        $subjects = DB::table('subjects')
            ->orderBy('id')
            ->get(['id', 'name', 'education_level', 'grade_level', 'topic', 'code']);

        $usedCodes = [];

        foreach ($subjects as $subject) {
            if (is_string($subject->code) && trim($subject->code) !== '') {
                $usedCodes[strtoupper(trim($subject->code))] = true;
            }
        }

        foreach ($subjects as $subject) {
            if (is_string($subject->code) && trim($subject->code) !== '') {
                continue;
            }

            $level = $this->normalizeLevel($subject->education_level);
            $grade = $this->normalizeGrade($subject->grade_level);
            $topic = (string) ($subject->name ?: $subject->topic ?: 'TOPIK');
            $prefix = $level . '-' . $grade . '-' . $this->abbreviation($topic);
            $number = 1;

            do {
                $code = $prefix . '-' . str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
                $normalizedCode = strtoupper($code);
            } while (isset($usedCodes[$normalizedCode]));

            DB::table('subjects')
                ->where('id', $subject->id)
                ->update(['code' => $code]);

            $usedCodes[$normalizedCode] = true;
        }

        $duplicateCodes = DB::table('subjects')
            ->selectRaw('UPPER(TRIM(code)) as normalized_code, COUNT(*) as total')
            ->whereNotNull('code')
            ->whereRaw("TRIM(code) <> ''")
            ->groupByRaw('UPPER(TRIM(code))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_code');

        if ($duplicateCodes->isNotEmpty()) {
            throw new RuntimeException(
                'Migration kode mata pelajaran dihentikan karena ditemukan kode duplikat: '
                . $duplicateCodes->implode(', ')
            );
        }

        $indexes = Schema::getIndexes('subjects');
        $hasUniqueCodeIndex = collect($indexes)->contains(function (array $index) {
            return ($index['type'] ?? '') === 'unique'
                && ($index['columns'] ?? []) === ['code'];
        });

        if (! $hasUniqueCodeIndex) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique('code', 'subjects_code_unique');
            });
        }
    }

    private function normalizeLevel(?string $level): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $level))) ?: 'UMUM';
    }

    private function normalizeGrade(mixed $grade): string
    {
        return is_numeric($grade) && (int) $grade > 0
            ? (string) (int) $grade
            : '0';
    }

    private function abbreviation(string $topic): string
    {
        $normalized = strtoupper(Str::ascii(trim($topic)));
        $words = preg_split('/[^A-Z0-9]+/', $normalized) ?: [];
        $words = array_values(array_filter($words, fn (string $word) => $word !== ''));

        if (count($words) > 1) {
            $value = substr(implode('', array_map(
                fn (string $word) => substr($word, 0, 1),
                $words
            )), 0, 5);
        } else {
            $value = substr($words[0] ?? 'TOP', 0, 3);
        }

        return preg_replace('/[^A-Z0-9]/', '', $value) ?: 'TOP';
    }

    public function down(): void
    {
    }
};
