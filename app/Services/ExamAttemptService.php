<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Student;
use App\Repositories\ExamAttemptRepositoryInterface;
use Illuminate\Support\Facades\DB;
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

                    if ($exam->shuffle_options) {
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
            ->addMinutes((int) $attempt->exam->duration_minutes);

        if ($attempt->exam->end_at && $attempt->exam->end_at->lt($deadline)) {
            return $attempt->exam->end_at->copy();
        }

        return $deadline;
    }

    public function answer(
        ExamAttempt $attempt,
        int         $examQuestionId,
        ?int        $selectedOptionId
    ): ExamAnswer
    {
        $answer = DB::transaction(function () use (
            $attempt,
            $examQuestionId,
            $selectedOptionId
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

            return $this->answerLocked(
                $lockedAttempt,
                $examQuestionId,
                $selectedOptionId
            );
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
        int         $examQuestionId,
        ?int        $selectedOptionId
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

        if ($selectedOptionId !== null) {
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

        $isCorrect = $selectedOption
            ? (bool)$selectedOption->is_correct
            : null;

        $score = $isCorrect
            ? (float)$examQuestion->score
            : 0;

        $answer = $this->repository->saveAnswer([
            'exam_attempt_id' => $attempt->id,
            'exam_question_id' => $examQuestion->id,
            'selected_option_id' => $selectedOption?->id,
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
        return DB::transaction(function () use (
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
            $passed = $score >= (float) $lockedAttempt->exam->passing_score;

            $now = now();
            $durationSeconds = $lockedAttempt->started_at
                ? min(
                    (int) $lockedAttempt->started_at->diffInSeconds($now),
                    max(0, (int) $lockedAttempt->exam->duration_minutes * 60)
                )
                : 0;

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
    }
}
