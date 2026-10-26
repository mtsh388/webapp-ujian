<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptApproval;
use App\Models\ExamQuestion;
use App\Models\Student;
use App\Repositories\ExamAttemptRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;
use RuntimeException;

class ExamAttemptService
{
    public function __construct(
        protected ExamAttemptRepositoryInterface $repository
    )
    {
    }

    public function start(
        Exam $exam,
        Student $student
    ): ExamAttempt {
        return DB::transaction(function () use ($exam, $student) {
            $lockedStudent = Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedStudent->is_active) {
                throw new RuntimeException(
                    'Akun siswa tidak aktif.'
                );
            }

            $lockedExam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExam->status !== 'published') {
                throw new RuntimeException(
                    'Ujian belum dipublikasikan.'
                );
            }

            $now = now();

            if ($lockedExam->start_at && $now->lt($lockedExam->start_at)) {
                throw new RuntimeException(
                    'Ujian belum dimulai.'
                );
            }

            if ($lockedExam->end_at && $now->gte($lockedExam->end_at)) {
                throw new RuntimeException(
                    'Waktu ujian telah berakhir.'
                );
            }

            $isActiveParticipant = $lockedExam->participants()
                ->where('student_id', $lockedStudent->id)
                ->where('is_active', true)
                ->exists();

            if (!$isActiveParticipant) {
                throw new RuntimeException(
                    'Anda tidak terdaftar sebagai peserta aktif ujian ini.'
                );
            }

            $existingAttempt = $this->repository->getActiveAttempt(
                $lockedExam->id,
                $lockedStudent->id
            );

            if ($existingAttempt) {
                return $existingAttempt;
            }

            $attemptCount = $this->repository->getAttemptCount(
                $lockedExam->id,
                $lockedStudent->id
            );

            if ($lockedExam->schedule_mode === 'duration_without_schedule' && $attemptCount === 1) {
                $approved = ExamAttemptApproval::query()
                    ->where('exam_id', $lockedExam->id)
                    ->where('student_id', $lockedStudent->id)
                    ->where('status', 'approved')
                    ->exists();

                if (! $approved) {
                    throw new RuntimeException('Percobaan kedua untuk ujian berdurasi tanpa jadwal harus disetujui Admin terlebih dahulu.');
                }
            }

            if (
                $lockedExam->max_attempts !== null &&
                $attemptCount >= $lockedExam->max_attempts
            ) {
                throw new RuntimeException(
                    'Anda sudah mencapai batas maksimal percobaan ujian.'
                );
            }

            $totalQuestions = $lockedExam->questions()->count();

            if ($totalQuestions === 0) {
                throw new RuntimeException(
                    'Ujian belum memiliki soal.'
                );
            }

            $attempt = $this->repository->create([
                'exam_id' => $lockedExam->id,
                'student_id' => $lockedStudent->id,
                'attempt_number' => $attemptCount + 1,
                'started_at' => $now,
                'total_questions' => $totalQuestions,
                'correct_answers' => 0,
                'wrong_answers' => 0,
                'unanswered' => $totalQuestions,
                'total_score' => 0,
                'score' => 0,
                'duration_seconds' => 0,
                'status' => 'in_progress',
                'passed' => false,
            ]);

            $this->initializeAttemptOrder($attempt);

            return $attempt->fresh();
        }, 3);
    }

    public function isExpired(ExamAttempt $attempt): bool
    {
        if ($attempt->status !== 'in_progress') {
            return false;
        }

        if (!$attempt->started_at) {
            return false;
        }

        $attempt->loadMissing('exam');

        if (!$attempt->exam->has_duration) {
            return false;
        }

        $endTime = $this->getAttemptDeadline($attempt);

        return now()->greaterThanOrEqualTo($endTime);
    }

    public function getAttemptForPage(
        ExamAttempt $attempt,
        int         $questionNumber
    ): array
    {
        $attempt->load([
            'exam',
            'answers',
        ]);

        $this->initializeAttemptOrder($attempt);
        $attempt->refresh();

        $questions = $attempt->exam
            ->questions()
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        $questionsById = $questions->keyBy('id');

        $questions = collect($attempt->question_order)
            ->map(fn($questionId) => $questionsById->get($questionId))
            ->filter()
            ->values();

        foreach ($questions as $question) {
            $optionIds = $attempt->option_order[(string)$question->id]
                ?? $attempt->option_order[$question->id]
                ?? [];

            $optionsById = $question->options->keyBy('id');

            $orderedOptions = collect($optionIds)
                ->map(fn($optionId) => $optionsById->get($optionId))
                ->filter()
                ->values();

            $question->setRelation('options', $orderedOptions);
        }

        $totalQuestions = $questions->count();

        if ($totalQuestions === 0) {
            throw new RuntimeException(
                'Ujian belum memiliki soal.'
            );
        }

        $questionNumber = min(
            max($questionNumber, 1),
            $totalQuestions
        );

        $currentQuestion = $questions->get(
            $questionNumber - 1
        );

        $answers = $attempt->answers
            ->keyBy('exam_question_id');

        $currentAnswer = $answers->get(
            $currentQuestion->id
        );

        $remainingSeconds = $this->getRemainingSeconds(
            $attempt
        );

        return [
            'attempt' => $attempt,
            'questions' => $questions,
            'currentQuestion' => $currentQuestion,
            'currentNumber' => $questionNumber,
            'totalQuestions' => $totalQuestions,
            'answers' => $answers,
            'currentAnswer' => $currentAnswer,
            'remainingSeconds' => $remainingSeconds,
        ];
    }

    protected function initializeAttemptOrder(
        ExamAttempt $attempt
    ): void
    {
        DB::transaction(function () use ($attempt) {
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedAttempt->question_order !== null &&
                $lockedAttempt->option_order !== null
            ) {
                return;
            }

            $exam = $lockedAttempt->exam()->firstOrFail();

            $questions = $exam->questions()
                ->with('options')
                ->orderBy('sort_order')
                ->get();

            $questionOrder = $lockedAttempt->question_order;

            if ($questionOrder === null) {
                $questionOrder = $questions
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->all();

                if ($exam->shuffle_questions) {
                    shuffle($questionOrder);
                }
            }

            $optionOrder = $lockedAttempt->option_order;

            if ($optionOrder === null) {
                $optionOrder = [];

                foreach ($questions as $question) {
                    $optionIds = $question->options
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();

                    if ($exam->shuffle_options && $question->type !== 'statement_true_false') {
                        shuffle($optionIds);
                    }

                    $optionOrder[(string) $question->id] = $optionIds;
                }
            }

            $lockedAttempt->forceFill([
                'question_order' => $questionOrder,
                'option_order' => $optionOrder,
            ])->save();
        }, 3);
    }

    public function getRemainingSeconds(
        ExamAttempt $attempt
    ): int
    {
        if (!$attempt->started_at) {
            return 0;
        }

        $attempt->loadMissing('exam');

        if (!$attempt->exam->has_duration) {
            return -1;
        }

        $endTime = $this->getAttemptDeadline($attempt);

        return max(
            0,
            (int) now()->diffInSeconds($endTime, false)
        );
    }

    protected function getAttemptDeadline(ExamAttempt $attempt): \Illuminate\Support\Carbon
    {
        $attempt->loadMissing('exam');

        $deadline = $attempt->started_at
            ->copy()
            ->addMinutes((int) ($attempt->exam->duration_minutes ?? 0));

        if ($attempt->exam->end_at && $attempt->exam->end_at->lt($deadline)) {
            return $attempt->exam->end_at->copy();
        }

        return $deadline;
    }

    public function answer(
        ExamAttempt $attempt,
        int $examQuestionId,
        ?int $selectedOptionId,
        ?array $selectedOptionIds = null
    ): ExamAnswer
    {
        $answer = DB::transaction(function () use (
            $attempt,
            $examQuestionId,
            $selectedOptionId,
            $selectedOptionIds
        ) {
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->status !== 'in_progress') {
                throw new RuntimeException(
                    'Ujian sudah tidak dapat dikerjakan.'
                );
            }

            if ($this->isExpired($lockedAttempt)) {
                $this->submit($lockedAttempt, true);

                return null;
            }

            return $this->answerLocked($lockedAttempt, $examQuestionId, $selectedOptionId, $selectedOptionIds);
        }, 3);

        if (!$answer) {
            throw new RuntimeException(
                'Waktu ujian telah habis.'
            );
        }

        return $answer;
    }

    protected function answerLocked(
        ExamAttempt $attempt,
        int $examQuestionId,
        ?int $selectedOptionId,
        ?array $selectedOptionIds = null
    ): ExamAnswer
    {
        $attempt->loadMissing('exam');

        if ($attempt->status !== 'in_progress') {
            throw new RuntimeException(
                'Ujian sudah tidak dapat dikerjakan.'
            );
        }

        if ($attempt->exam->status !== 'published') {
            throw new RuntimeException(
                'Ujian sudah ditutup dan jawaban tidak dapat diubah.'
            );
        }

        $this->initializeAttemptOrder($attempt);
        $attempt->refresh();
        $attempt->loadMissing('exam');

        $examQuestion = ExamQuestion::query()
            ->where('id', $examQuestionId)
            ->where('exam_id', $attempt->exam_id)
            ->with('options')
            ->first();

        if (!$examQuestion) {
            throw new RuntimeException(
                'Soal tidak ditemukan dalam ujian ini.'
            );
        }

        $questionOrder = array_map(
            'intval',
            $attempt->question_order ?? []
        );

        if (!in_array((int) $examQuestion->id, $questionOrder, true)) {
            throw new RuntimeException(
                'Soal ini tidak termasuk dalam percobaan ujian Anda.'
            );
        }

        $selectedOption = null;
        $selectedOptionIds = array_values(array_unique(array_map('intval', $selectedOptionIds ?? [])));
        $isCorrect = null;
        $score = 0;

        if ($examQuestion->type === 'statement_true_false') {
            if ($selectedOptionId !== null && empty($selectedOptionIds)) {
                $selectedOptionIds = [$selectedOptionId];
            }

            $validOptionIds = $examQuestion->options->pluck('id')->map(fn ($id) => (int) $id)->all();

            if (array_diff($selectedOptionIds, $validOptionIds)) {
                throw new RuntimeException('Pilihan jawaban tidak valid.');
            }

            $optionOrder = array_map('intval', $attempt->option_order[(string) $examQuestion->id] ?? $attempt->option_order[$examQuestion->id] ?? []);

            if (array_diff($selectedOptionIds, $optionOrder)) {
                throw new RuntimeException('Pilihan jawaban tidak termasuk dalam percobaan ujian Anda.');
            }

            $statementGroups = $examQuestion->options
                ->groupBy(fn ($option) => preg_replace('/[TF]$/', '', (string) $option->option_key));

            $selectedOptions = $examQuestion->options
                ->whereIn('id', $selectedOptionIds)
                ->values();

            $selectedGroups = $selectedOptions
                ->groupBy(fn ($option) => preg_replace('/[TF]$/', '', (string) $option->option_key));

            foreach ($selectedGroups as $groupOptions) {
                if ($groupOptions->count() > 1) {
                    throw new RuntimeException('Pilih Benar atau Salah satu kali untuk setiap pernyataan.');
                }
            }

            $correctCount = 0;

            foreach ($statementGroups as $groupKey => $groupOptions) {
                $selectedInGroup = $selectedGroups->get($groupKey, collect())->first();

                if ($selectedInGroup && $selectedInGroup->is_correct) {
                    $correctCount++;
                }
            }

            $statementCount = $statementGroups->count();
            $isCorrect = $statementCount > 0
                && $selectedOptions->count() === $statementCount
                && $correctCount === $statementCount;
            $score = $statementCount > 0
                ? round(((float) $examQuestion->score * $correctCount) / $statementCount, 2)
                : 0;
            $selectedOption = $selectedOptions->first();
        } elseif ($examQuestion->type === 'multiple_select') {
            if ($selectedOptionId !== null && empty($selectedOptionIds)) $selectedOptionIds = [$selectedOptionId];
            $validOptionIds = $examQuestion->options->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_diff($selectedOptionIds, $validOptionIds)) throw new RuntimeException('Pilihan jawaban tidak valid.');
            $optionOrder = array_map('intval', $attempt->option_order[(string) $examQuestion->id] ?? $attempt->option_order[$examQuestion->id] ?? []);
            if (array_diff($selectedOptionIds, $optionOrder)) throw new RuntimeException('Pilihan jawaban tidak termasuk dalam percobaan ujian Anda.');
            $correctIds = $examQuestion->options->filter(fn ($option) => (bool) $option->is_correct)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submittedIds = $selectedOptionIds;
            sort($submittedIds);
            $isCorrect = !empty($submittedIds) && $submittedIds === $correctIds;
            $score = $isCorrect ? (float) $examQuestion->score : 0;
            $selectedOption = $examQuestion->options->firstWhere('id', $selectedOptionIds[0] ?? null);
        } elseif ($selectedOptionId !== null) {
            $selectedOption = $examQuestion->options
                ->firstWhere('id', $selectedOptionId);

            if (!$selectedOption) {
                throw new RuntimeException(
                    'Pilihan jawaban tidak valid.'
                );
            }

            $optionOrder = $attempt->option_order[(string) $examQuestion->id]
                ?? $attempt->option_order[$examQuestion->id]
                ?? [];

            if (!in_array((int) $selectedOption->id, array_map('intval', $optionOrder), true)) {
                throw new RuntimeException(
                    'Pilihan jawaban ini tidak termasuk dalam percobaan ujian Anda.'
                );
            }
        }

        if (!in_array($examQuestion->type, ['multiple_select', 'statement_true_false'], true)) {
            $isCorrect = $selectedOption ? (bool) $selectedOption->is_correct : null;
            $score = $isCorrect ? (float) $examQuestion->score : 0;
            $selectedOptionIds = $selectedOption ? [(int) $selectedOption->id] : [];
        } elseif (empty($selectedOptionIds)) {
            $isCorrect = null;
            $score = 0;
        }

        $answer = $this->repository->saveAnswer([
            'exam_attempt_id' => $attempt->id,
            'exam_question_id' => $examQuestion->id,
            'selected_option_id' => $selectedOption?->id,
            'selected_option_ids' => $selectedOptionIds,
            'is_correct' => $isCorrect,
            'score' => $score,
            'answered_at' => now(),
        ]);

        $this->updateProgress($attempt);

        return $answer;
    }

    protected function updateProgress(
        ExamAttempt $attempt
    ): void
    {
        $result = $this->repository->calculateResult($attempt);

        $this->repository->update($attempt, [
            'total_questions' => $result['total_questions'],
            'correct_answers' => $result['correct_answers'],
            'wrong_answers' => $result['wrong_answers'],
            'unanswered' => $result['unanswered'],
            'total_score' => $result['total_score'],
            'score' => $result['score'],
        ]);
    }

    public function submit(
        ExamAttempt $attempt,
        bool        $expired = false
    ): ExamAttempt
    {
        $submittedAttempt = DB::transaction(function () use (
            $attempt,
            $expired
        ) {
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedAttempt->status === 'submitted' ||
                $lockedAttempt->status === 'expired'
            ) {
                return $lockedAttempt;
            }

            if ($lockedAttempt->status !== 'in_progress') {
                throw new RuntimeException(
                    'Status percobaan ujian tidak valid.'
                );
            }

            $lockedAttempt->load('exam');

            if (!$expired && $this->isExpired($lockedAttempt)) {
                $expired = true;
            }

            $result = $this->repository->calculateResult(
                $lockedAttempt
            );

            $score = $result['score'];
            $passed = $lockedAttempt->exam->passing_score !== null
                ? $score >= (float) $lockedAttempt->exam->passing_score
                : null;

            $now = now();
            $elapsedSeconds = $lockedAttempt->started_at
                ? max(0, (int) $lockedAttempt->started_at->diffInSeconds($now, false))
                : 0;
            $durationSeconds = $lockedAttempt->exam->has_duration
                ? min($elapsedSeconds, max(0, (int) $lockedAttempt->exam->duration_minutes * 60))
                : $elapsedSeconds;

            $lockedAttempt->update([
                'submitted_at' => $now,
                'total_questions' => $result['total_questions'],
                'correct_answers' => $result['correct_answers'],
                'wrong_answers' => $result['wrong_answers'],
                'unanswered' => $result['unanswered'],
                'total_score' => $result['total_score'],
                'score' => $score,
                'duration_seconds' => $durationSeconds,
                'status' => $expired ? 'expired' : 'submitted',
                'passed' => $passed,
            ]);

            return $lockedAttempt->fresh();
        }, 3);

        $this->sendResultToParents($submittedAttempt);

        return $submittedAttempt;
    }

    private function sendResultToParents(ExamAttempt $attempt): void
    {
        if ($attempt->parent_result_sent_at) {
            return;
        }

        $attempt->loadMissing(['exam', 'student.parents.user']);

        $emails = $attempt->student?->parents
            ?->pluck('user.email')
            ->filter()
            ->unique()
            ->values()
            ->all() ?? [];

        if ($emails === []) {
            return;
        }

        $studentName = $attempt->student?->full_name ?: 'Siswa';
        $examTitle = $attempt->exam?->title ?: 'Ujian';
        $score = number_format((float) $attempt->score, 2, ',', '.');
        $resultStatus = $attempt->passed === null
            ? 'Nilai kelulusan belum ditentukan.'
            : ($attempt->passed ? 'Status: Lulus.' : 'Status: Belum lulus.');

        try {
            Mail::raw(
                "Yth. Orang Tua/Wali dari {$studentName},\n\nHasil ujian siswa telah tersedia.\nUjian: {$examTitle}\nNama Siswa: {$studentName}\nPercobaan: {$attempt->attempt_number}\nNilai: {$score}\n{$resultStatus}\nWaktu pengumpulan: " . ($attempt->submitted_at?->format('d-m-Y H:i') ?? '-') . "\n\nSalam,\nQSEE",
                function ($message) use ($emails, $studentName, $examTitle) {
                    $message->to($emails)->subject("QSEE - Hasil Ujian {$studentName}: {$examTitle}");
                }
            );

            $attempt->forceFill(['parent_result_sent_at' => now()])->save();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
