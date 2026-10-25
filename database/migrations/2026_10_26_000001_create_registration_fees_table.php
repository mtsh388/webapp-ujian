<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_fees', function (Blueprint $table): void {
            $table->id();
            $table->string('education_level', 10);
            $table->unsignedTinyInteger('grade_level');
            $table->unsignedBigInteger('amount');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['education_level', 'grade_level']);
            $table->index(['is_active', 'education_level', 'grade_level']);
        });

        $now = now();
        $fees = [];

        foreach ([4, 5, 6] as $grade) {
            $fees[] = ['education_level' => 'SD', 'grade_level' => $grade, 'amount' => 200000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        foreach ([7, 8, 9] as $grade) {
            $fees[] = ['education_level' => 'SMP', 'grade_level' => $grade, 'amount' => 300000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        foreach ([10, 11, 12] as $grade) {
            $fees[] = ['education_level' => 'SMA', 'grade_level' => $grade, 'amount' => $grade === 12 ? 500000 : 400000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('registration_fees')->insert($fees);
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_fees');
    }
};
