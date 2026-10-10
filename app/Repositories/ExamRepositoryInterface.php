<?php

namespace App\Repositories;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamQuestionOption;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ExamRepositoryInterface
{
    public function create(array $data): Exam;

    public function getActiveSubjects(): Collection;

    public function generateUniqueCode(string $prefix): string;

    public function paginate(
        int     $perPage = 10,
        ?string $search = null
    ): LengthAwarePaginator;

    public function getAvailableForStudent(
        int $studentId,
        int $perPage = 9
    ): LengthAwarePaginator;

    public function query(): Builder;

    public function findById(int $id): Exam;

    public function getExamQuestions(
        int $examId
    ): Collection;

    public function getAvailableQuestionBanks(
        int   $subjectId,
        array $excludeQuestionBankIds
    ): Collection;

    public function createExamQuestion(
        array $data
    ): ExamQuestion;

    public function createExamQuestionOption(
        array $data
    ): ExamQuestionOption;

    public function deleteExamQuestion(
        int $examQuestionId
    ): bool;

    public function getExamParticipants(
        int $examId
    ): Collection;

    public function getAvailableStudentsForExam(
        int $examId
    ): Collection;

    public function getParticipantStudentIds(
        int   $examId,
        array $studentIds
    ): array;

    public function createParticipant(
        array $data
    ): ExamParticipant;

    public function findParticipant(
        int $examId,
        int $participantId
    ): ExamParticipant;

    public function updateParticipantStatus(
        int  $participantId,
        bool $isActive
    ): bool;

    public function deleteParticipant(
        int $participantId
    ): bool;
}
