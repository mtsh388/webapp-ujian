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
        DB::table('subjects')
            ->whereNull('topic')
            ->orWhere('topic', '')
            ->update(['topic' => DB::raw('name')]);

        if (Schema::hasColumn('subjects', 'code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropUnique('subjects_code_unique');
                $table->dropColumn('code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('subjects', 'code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('name');
            });
        }

        $subjects = DB::table('subjects')
            ->orderBy('id')
            ->get(['id', 'name', 'topic']);

        $usedCodes = [];

        foreach ($subjects as $subject) {
            $base = Str::upper(Str::slug($subject->topic ?: $subject->name, '-'));
            $base = substr($base !== '' ? $base : 'TOPIK', 0, 50);
            $candidate = $base;
            $suffix = 2;

            while (isset($usedCodes[$candidate])) {
                $ending = '-' . $suffix++;
                $candidate = substr($base, 0, 50 - strlen($ending)) . $ending;
            }

            $usedCodes[$candidate] = true;

            DB::table('subjects')
                ->where('id', $subject->id)
                ->update(['code' => $candidate]);
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 50)->nullable(false)->change();
            $table->unique('code', 'subjects_code_unique');
        });
    }
};
