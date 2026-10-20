<?php

namespace App\Repositories;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;

class ExamAttemptRepository implements ExamAttemptRepositoryInterface
{
    public function findById(int $id): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->with([
                'exam',
                'student',
                'answers.examQuestion',
                'answers.selectedOption',
            ])
            ->find($id);
    }

    public function findForStudent(
        int $id,
        int $studentId
    ): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('id', $id)
            ->where('student_id', $studentId)
            ->with([
                'exam',
                'student',
                'answers.examQuestion',
                'answers.selectedOption',
            ])
            ->first();
    }

    public function getActiveAttempt(
        int $examId,
        int $studentId
    ): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();
    }

    public function getLatestAttempt(
        int $examId,
        int $studentId
    ): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->latest('id')
            ->first();
    }

    public function getAttemptCount(
        int $examId,
        int $studentId
    ): int
    {
        return ExamAttempt::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->count();
    }

    public function create(array $data): ExamAttempt
    {
        return ExamAttempt::create($data);
    }

    public function update(
        ExamAttempt $attempt,
        array       $data
    ): bool
    {
        return $attempt->update($data);
    }

    public function saveAnswer(array $data): ExamAnswer
    {
        return ExamAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $data['exam_attempt_id'],
                'exam_question_id' => $data['exam_question_id'],
            ],
            [
                'selected_option_id' => $data['selected_option_id'] ?? null,
                'selected_option_ids' => $data['selected_option_ids'] ?? null,
                'is_correct' => $data['is_correct'] ?? null,
                'score' => $data['score'] ?? 0,
                'answered_at' => $data['answered_at'] ?? now(),
            ]
        );
    }

    public function getAnswer(
        int $attemptId,
        int $examQuestionId
    ): ?ExamAnswer
    {
        return ExamAnswer::query()
            ->where('exam_attempt_id', $attemptId)
            ->where('exam_question_id', $examQuestionId)
            ->first();
    }

    public function getAnswers(int $attemptId)
    {
        return ExamAnswer::query()
            ->where('exam_attempt_id', $attemptId)
            ->with([
                'examQuestion',
                'selectedOption',
            ])
            ->get()
            ->keyBy('exam_question_id');
    }

    public function calculateResult(
        ExamAttempt $attempt
    ): array
    {
        $attempt->loadMissing('exam');

        $questionIds = collect($attempt->question_order);

        if ($questionIds->isEmpty()) {
            $questionIds = $attempt->exam
                ->questions()
                ->pluck('id');
        }

        $questionIds = $questionIds
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $answers = ExamAnswer::query()
            ->where('exam_attempt_id', $attempt->id)
            ->whereIn('exam_question_id', $questionIds)
            ->get()
            ->keyBy('exam_question_id');

        $correctAnswers = 0;
        $wrongAnswers = 0;
        $unanswered = 0;
        $totalScore = 0.0;

        foreach ($questionIds as $questionId) {
            $answer = $answers->get($questionId);

            if (!$answer || $answer->selected_option_id === null) {
                $unanswered++;
                continue;
            }

            if ($answer->is_correct === true) {
                $correctAnswers++;
                $totalScore += (float) $answer->score;
            } else {
                $wrongAnswers++;
            }
        }

        $totalQuestions = $questionIds->count();

        $score = $totalQuestions > 0
            ? ($correctAnswers / $totalQuestions) * 100
            : 0;

        return [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'total_score' => round($totalScore, 2),
            'score' => round($score, 2),
        ];
    }
}
