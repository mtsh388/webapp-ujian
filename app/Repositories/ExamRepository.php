<?php

namespace App\Repositories;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamQuestionOption;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExamRepository implements ExamRepositoryInterface
{
    public function query(): Builder
    {
        return Exam::query()
            ->with('subject');
    }

    public function create(array $data): Exam
    {
        return Exam::create($data);
    }

    public function getActiveSubjects(): Collection
    {
        return Subject::query()
            ->with('classRoom')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function generateUniqueCode(
        string $prefix
    ): string
    {
        $lastExam = Exam::query()
            ->where(
                'code',
                'like',
                $prefix . '-%'
            )
            ->orderByDesc('id')
            ->first();

        $number = $lastExam
            ? (int)substr(
                $lastExam->code,
                strrpos(
                    $lastExam->code,
                    '-'
                ) + 1
            ) + 1
            : 1;

        return $prefix . '-' . str_pad(
                $number,
                3,
                '0',
                STR_PAD_LEFT
            );
    }

    public function paginate(
        int     $perPage = 10,
        ?string $search = null
    ): LengthAwarePaginator
    {
        return Exam::query()
            ->with('subject')
            ->when(
                $search,
                function (
                    $query,
                    $search
                ) {
                    $query->where(
                        function ($query) use ($search) {
                            $query
                                ->where(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAvailableForStudent(
        int $studentId,
        int $perPage = 9
    ): LengthAwarePaginator
    {
        return Exam::query()
            ->with('subject')
            ->select('exams.*')
            ->join(
                'exam_participants',
                'exams.id',
                '=',
                'exam_participants.exam_id'
            )
            ->where(
                'exam_participants.student_id',
                $studentId
            )
            ->where(
                'exam_participants.is_active',
                true
            )
            ->where(
                'exams.status',
                'published'
            )
            ->orderBy(
                'exams.start_at',
                'desc'
            )
            ->paginate($perPage);
    }

    public function findById(int $id): Exam
    {
        return Exam::query()
            ->with('subject')
            ->findOrFail($id);
    }

    public function getExamQuestions(
        int $examId
    ): Collection
    {
        return ExamQuestion::query()
            ->where(
                'exam_id',
                $examId
            )
            ->with([
                'options' => function ($query) {
                    $query->orderBy(
                        'sort_order'
                    );
                },
            ])
            ->orderBy('sort_order')
            ->get();
    }

    public function getAvailableQuestionBanks(
        int   $subjectId,
        array $excludeQuestionBankIds
    ): Collection
    {
        return QuestionBank::query()
            ->where(
                'subject_id',
                $subjectId
            )
            ->where(
                'is_active',
                true
            )
            ->whereNotIn(
                'id',
                $excludeQuestionBankIds
            )
            ->with([
                'options' => function ($query) {
                    $query->orderBy(
                        'sort_order'
                    );
                },
            ])
            ->latest()
            ->get();
    }

    public function createExamQuestion(
        array $data
    ): ExamQuestion
    {
        return ExamQuestion::create($data);
    }

    public function createExamQuestionOption(
        array $data
    ): ExamQuestionOption
    {
        return ExamQuestionOption::create($data);
    }

    public function deleteExamQuestion(
        int $examQuestionId
    ): bool
    {
        return ExamQuestion::query()
                ->where(
                    'id',
                    $examQuestionId
                )
                ->delete() > 0;
    }

    public function getExamParticipants(
        int $examId
    ): Collection
    {
        return ExamParticipant::query()
            ->where(
                'exam_id',
                $examId
            )
            ->with([
                'student' => function ($query) {
                    $query->with([
                        'classRoom',
                        'user',
                    ]);
                },
            ])
            ->orderByDesc('assigned_at')
            ->get();
    }

    public function getAvailableStudentsForExam(
        int $examId
    ): Collection
    {
        return Student::query()
            ->where(
                'students.is_active',
                true
            )
            ->whereNotExists(
                function ($query) use ($examId) {
                    $query
                        ->selectRaw('1')
                        ->from(
                            'exam_participants'
                        )
                        ->whereColumn(
                            'exam_participants.student_id',
                            'students.id'
                        )
                        ->where(
                            'exam_participants.exam_id',
                            $examId
                        );
                }
            )
            ->with([
                'classRoom',
                'user',
            ])
            ->orderBy(
                'full_name'
            )
            ->get();
    }

    public function getParticipantStudentIds(
        int   $examId,
        array $studentIds
    ): array
    {
        return ExamParticipant::query()
            ->where(
                'exam_id',
                $examId
            )
            ->whereIn(
                'student_id',
                $studentIds
            )
            ->pluck('student_id')
            ->map(
                fn($id) => (int)$id
            )
            ->toArray();
    }

    public function createParticipant(
        array $data
    ): ExamParticipant
    {
        return ExamParticipant::create(
            $data
        );
    }

    public function findParticipant(
        int $examId,
        int $participantId
    ): ExamParticipant
    {
        return ExamParticipant::query()
            ->where(
                'exam_id',
                $examId
            )
            ->where(
                'id',
                $participantId
            )
            ->firstOrFail();
    }

    public function updateParticipantStatus(
        int  $participantId,
        bool $isActive
    ): bool
    {
        return ExamParticipant::query()
                ->where(
                    'id',
                    $participantId
                )
                ->update([
                    'is_active' => $isActive,
                ]) > 0;
    }

    public function deleteParticipant(
        int $participantId
    ): bool
    {
        return ExamParticipant::query()
                ->where(
                    'id',
                    $participantId
                )
                ->delete() > 0;
    }
}
