<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentClassHistoryService
{
    public function changeClass(
        Student $student,
        ?int $classId,
        string $reason,
        ?Carbon $effectiveDate = null,
        bool $enforceProgression = false
    ): Student {
        if ($classId === null) {
            return $student->refresh()->load('classRoom');
        }

        $effectiveDate = ($effectiveDate ?? now())->copy()->startOfDay();

        return DB::transaction(function () use ($student, $classId, $reason, $effectiveDate, $enforceProgression): Student {
            $lockedStudent = Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            $targetClass = ClassRoom::query()
                ->whereKey($classId)
                ->where('is_active', true)
                ->first();

            if (! $targetClass) {
                throw ValidationException::withMessages([
                    'class_id' => 'Kelas tujuan tidak ditemukan atau tidak aktif.',
                ]);
            }

            if (
                $lockedStudent->registration_type
                && $lockedStudent->registration_type !== $targetClass->registration_type
            ) {
                throw ValidationException::withMessages([
                    'class_id' => 'Jenis pendaftaran kelas tujuan harus sama dengan jenis pendaftaran siswa.',
                ]);
            }

            $currentClass = $lockedStudent->classRoom()->first();

            if ($enforceProgression && $currentClass && (int) $currentClass->id !== (int) $targetClass->id) {
                $sameGrade = $currentClass->education_level === $targetClass->education_level
                    && (int) $currentClass->grade_level === (int) $targetClass->grade_level;

                [$nextLevel, $nextGrade] = match ([$currentClass->education_level, (int) $currentClass->grade_level]) {
                    ['SD', 6] => ['SMP', 7],
                    ['SMP', 9] => ['SMA', 10],
                    ['SMA', 12] => [null, null],
                    default => [$currentClass->education_level, (int) $currentClass->grade_level + 1],
                };

                $nextGradeAllowed = $nextLevel !== null
                    && $targetClass->education_level === $nextLevel
                    && (int) $targetClass->grade_level === $nextGrade;

                if (! $sameGrade && ! $nextGradeAllowed) {
                    throw ValidationException::withMessages([
                        'class_id' => 'Perpindahan kelas melalui profil hanya diizinkan ke kelas paralel pada tingkat yang sama atau satu tingkat di atasnya.',
                    ]);
                }
            }

            if ((int) $lockedStudent->class_id === (int) $targetClass->id) {
                $this->ensureCurrentHistory($lockedStudent, $targetClass, $reason, $effectiveDate);

                return $lockedStudent->refresh()->load('classRoom');
            }

            if ($currentClass) {
                $this->closeCurrentHistory($lockedStudent, $currentClass, $reason, $effectiveDate);
            }

            $lockedStudent->forceFill([
                'class_id' => $targetClass->id,
                'education_level' => $targetClass->education_level,
            ])->save();

            $lockedStudent->classHistories()->create([
                'class_id' => $targetClass->id,
                'education_level' => $targetClass->education_level,
                'grade_level' => $targetClass->grade_level,
                'started_at' => $effectiveDate->toDateString(),
                'ended_at' => null,
                'change_reason' => $reason,
            ]);

            return $lockedStudent->refresh()->load('classRoom');
        }, 3);
    }

    public function closeCurrentClass(
        Student $student,
        string $reason,
        ?Carbon $effectiveDate = null
    ): Student {
        $effectiveDate = ($effectiveDate ?? now())->copy()->startOfDay();

        return DB::transaction(function () use ($student, $reason, $effectiveDate): Student {
            $lockedStudent = Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentClass = $lockedStudent->classRoom()->first();

            if ($currentClass) {
                $this->closeCurrentHistory($lockedStudent, $currentClass, $reason, $effectiveDate);
            }

            return $lockedStudent->refresh()->load('classRoom');
        }, 3);
    }

    private function ensureCurrentHistory(
        Student $student,
        ClassRoom $class,
        string $reason,
        Carbon $effectiveDate
    ): void {
        $activeHistory = $student->classHistories()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($activeHistory && (int) $activeHistory->class_id === (int) $class->id) {
            return;
        }

        if ($activeHistory) {
            $activeHistory->forceFill([
                'ended_at' => $effectiveDate->copy()->subDay()->toDateString(),
            ])->save();
        }

        $student->classHistories()->create([
            'class_id' => $class->id,
            'education_level' => $class->education_level,
            'grade_level' => $class->grade_level,
            'started_at' => $this->inferCurrentClassStart($student, $class, $effectiveDate),
            'ended_at' => null,
            'change_reason' => $reason,
        ]);
    }

    private function closeCurrentHistory(
        Student $student,
        ClassRoom $class,
        string $reason,
        Carbon $effectiveDate
    ): void {
        $activeHistory = $student->classHistories()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($activeHistory && (int) $activeHistory->class_id === (int) $class->id) {
            $activeHistory->forceFill([
                'ended_at' => $effectiveDate->copy()->subDay()->toDateString(),
            ])->save();

            return;
        }

        if ($activeHistory) {
            $activeHistory->forceFill([
                'ended_at' => $effectiveDate->copy()->subDay()->toDateString(),
            ])->save();
        }

        $student->classHistories()->create([
            'class_id' => $class->id,
            'education_level' => $class->education_level,
            'grade_level' => $class->grade_level,
            'started_at' => $this->inferCurrentClassStart($student, $class, $effectiveDate),
            'ended_at' => $effectiveDate->copy()->subDay()->toDateString(),
            'change_reason' => $reason,
        ]);
    }

    private function inferCurrentClassStart(
        Student $student,
        ClassRoom $class,
        Carbon $fallback
    ): string {
        $latestHistory = $student->classHistories()
            ->latest('started_at')
            ->first();

        if ($latestHistory && (int) $latestHistory->class_id === (int) $class->id) {
            return $latestHistory->started_at->toDateString();
        }

        if ($latestHistory?->ended_at) {
            return Carbon::parse($latestHistory->ended_at)->addDay()->toDateString();
        }

        return $student->created_at?->toDateString() ?? $fallback->toDateString();
    }
}
