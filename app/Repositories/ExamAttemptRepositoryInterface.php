<?php

namespace App\Repositories;

use App\Models\ExamAttempt;

interface ExamAttemptRepositoryInterface
{
    public function findById(int $id): ?ExamAttempt;

    public function findForStudent(
        int $id,
        int $studentId
    ): ?ExamAttempt;

    public function getActiveAttempt(
        int $examId,
        int $studentId
    ): ?ExamAttempt;

    public function getLatestAttempt(
        int $examId,
        int $studentId
    ): ?ExamAttempt;

    public function getAttemptCount(
        int $examId,
        int $studentId
    ): int;

    public function create(array $data): ExamAttempt;

    public function update(
        ExamAttempt $attempt,
        array       $data
    ): bool;

    public function saveAnswer(array $data): \App\Models\ExamAnswer;

    public function getAnswer(
        int $attemptId,
        int $examQuestionId
    ): ?\App\Models\ExamAnswer;

    public function getAnswers(
        int $attemptId
    );

    public function calculateResult(
        ExamAttempt $attempt
    ): array;
}
