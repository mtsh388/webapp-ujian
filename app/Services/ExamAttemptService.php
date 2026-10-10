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
        Exam    $exam,
        Student $student
    ): ExamAttempt
    {
        if ($exam->status !== 'published') {
            throw new RuntimeException(
                'Ujian belum dipublikasikan.'
            );
        }

        $now = now();

        if (
            $exam->start_at &&
            $now->lt($exam->start_at)
        ) {
            throw new RuntimeException(
                'Ujian belum dimulai.'
            );
        }

        if (
            $exam->end_at &&
            $now->gt($exam->end_at)
        ) {
            throw new RuntimeException(
                'Waktu ujian telah berakhir.'
            );
        }

        $existingAttempt = $this->repository->getActiveAttempt(
            $exam->id,
            $student->id
        );

        if ($existingAttempt) {
            return $existingAttempt;
        }

        $attemptCount = $this->repository->getAttemptCount(
            $exam->id,
            $student->id
        );

        if (
            $exam->max_attempts !== null &&
            $attemptCount >= $exam->max_attempts
        ) {
            throw new RuntimeException(
                'Anda sudah mencapai batas maksimal percobaan ujian.'
            );
        }

        $totalQuestions = $exam->questions()->count();

        if ($totalQuestions === 0) {
            throw new RuntimeException(
                'Ujian belum memiliki soal.'
            );
        }

        return $this->repository->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => $attemptCount + 1,
            'started_at' => now(),
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
    }

    public function isExpired(ExamAttempt $attempt): bool
    {
        if ($attempt->status !== 'in_progress') {
            return false;
        }

        if (!$attempt->started_at) {
            return false;
        }

        $durationSeconds =
            (int)$attempt->exam->duration_minutes * 60;

        $endTime = $attempt->started_at
            ->copy()
            ->addSeconds($durationSeconds);

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

        $questions = $attempt->exam
            ->questions()
            ->with('options')
            ->orderBy('sort_order')
            ->get();

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

    public function getRemainingSeconds(
        ExamAttempt $attempt
    ): int
    {
        if (!$attempt->started_at) {
            return 0;
        }

        $durationSeconds =
            (int)$attempt->exam->duration_minutes * 60;

        $endTime = $attempt->started_at
            ->copy()
            ->addSeconds($durationSeconds);

        return max(
            0,
            now()->diffInSeconds($endTime, false)
        );
    }

    public function answer(
        ExamAttempt $attempt,
        int         $examQuestionId,
        ?int        $selectedOptionId
    ): ExamAnswer
    {
        if ($attempt->status !== 'in_progress') {
            throw new RuntimeException(
                'Ujian sudah tidak dapat dikerjakan.'
            );
        }

        if ($this->isExpired($attempt)) {
            $this->submit($attempt, true);

            throw new RuntimeException(
                'Waktu ujian telah habis.'
            );
        }

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

        $selectedOption = null;

        if ($selectedOptionId !== null) {
            $selectedOption = $examQuestion->options
                ->firstWhere('id', $selectedOptionId);

            if (!$selectedOption) {
                throw new RuntimeException(
                    'Pilihan jawaban tidak valid.'
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
        $answers = $this->repository->getAnswers(
            $attempt->id
        );

        $correct = $answers
            ->where('is_correct', true)
            ->count();

        $wrong = $answers
            ->filter(
                fn($answer) => $answer->selected_option_id !== null &&
                    $answer->is_correct === false
            )
            ->count();

        $answered = $answers
            ->filter(
                fn($answer) => $answer->selected_option_id !== null
            )
            ->count();

        $totalQuestions = $attempt->total_questions;

        $unanswered = max(
            0,
            $totalQuestions - $answered
        );

        $totalScore = (float)$answers->sum(
            fn($answer) => (float)$answer->score
        );

        $score = $totalQuestions > 0
            ? ($correct / $totalQuestions) * 100
            : 0;

        $this->repository->update($attempt, [
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'unanswered' => $unanswered,
            'total_score' => $totalScore,
            'score' => $score,
        ]);
    }

    public function submit(
        ExamAttempt $attempt,
        bool        $expired = false
    ): ExamAttempt
    {
        if (
            $attempt->status === 'submitted' ||
            $attempt->status === 'expired'
        ) {
            return $attempt;
        }

        return DB::transaction(function () use (
            $attempt,
            $expired
        ) {
            $result = $this->repository->calculateResult(
                $attempt
            );

            $score = $result['score'];

            $passed = $score >=
                (float)$attempt->exam->passing_score;

            $durationSeconds = $attempt->started_at
                ? $attempt->started_at->diffInSeconds(now())
                : 0;

            $this->repository->update($attempt, [
                'submitted_at' => now(),
                'total_questions' =>
                    $result['total_questions'],
                'correct_answers' =>
                    $result['correct_answers'],
                'wrong_answers' =>
                    $result['wrong_answers'],
                'unanswered' =>
                    $result['unanswered'],
                'total_score' =>
                    $result['total_score'],
                'score' => $score,
                'duration_seconds' => $durationSeconds,
                'status' => $expired
                    ? 'expired'
                    : 'submitted',
                'passed' => $passed,
            ]);

            return $attempt->fresh();
        });
    }
}
