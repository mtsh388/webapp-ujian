<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Repositories\ExamRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        protected ExamRepositoryInterface $repository
    )
    {
    }

    public function getDataTable(Request $request): array
    {
        $query = $this->repository->query();

        $total = (clone $query)->count('exams.id');

        $search = trim(
            $request->input('search.value', '')
        );

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
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
            });
        }

        $filtered = (clone $query)->count('exams.id');

        $columns = [
            0 => 'id',
            1 => 'code',
            2 => 'title',
            3 => 'subject_id',
            4 => 'start_at',
            5 => 'status',
            6 => 'id',
        ];

        $columnIndex = (int)$request->input(
            'order.0.column',
            1
        );

        $orderColumn = $columns[$columnIndex] ?? 'code';

        $orderDirection = $request->input(
            'order.0.dir',
            'desc'
        );

        if (!in_array(
            $orderDirection,
            ['asc', 'desc'],
            true
        )) {
            $orderDirection = 'desc';
        }

        $start = max(
            0,
            (int)$request->input('start', 0)
        );

        $length = (int)$request->input(
            'length',
            10
        );

        if ($length < 1) {
            $length = 10;
        }

        $exams = $query
            ->orderBy(
                $orderColumn,
                $orderDirection
            )
            ->skip($start)
            ->take($length)
            ->get();

        $data = $exams
            ->values()
            ->map(function (
                $exam,
                int $index
            ) use ($start) {
                return [
                    'no' => $start + $index + 1,
                    'id' => $exam->id,
                    'code' => $exam->code,
                    'title' => $exam->title,
                    'exam_type' => $exam->exam_type
                        ?: ($exam->passing_score === null ? 'trial' : 'ujian'),
                    'has_duration' => $exam->has_duration !== null
                        ? (bool) $exam->has_duration
                        : (bool) ($exam->duration_minutes && $exam->start_at && $exam->end_at),
                    'duration_minutes' => $exam->duration_minutes,
                    'subject_name' => $exam->subject?->name,
                    'start_at' => $exam->start_at
                        ? Carbon::parse(
                            $exam->start_at
                        )->format('d M Y H:i')
                        : null,
                    'end_at' => $exam->end_at
                        ? Carbon::parse(
                            $exam->end_at
                        )->format('d M Y H:i')
                        : null,
                    'status' => $exam->status,
                ];
            })
            ->all();

        return [
            'draw' => (int)$request->input(
                'draw',
                0
            ),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }

    public function query()
    {
        return $this->repository->query();
    }

    public function paginate(
        int     $perPage = 10,
        ?string $search = null
    )
    {
        return $this->repository->paginate(
            $perPage,
            $search
        );
    }

    public function getSubjects()
    {
        return $this->repository->getActiveSubjects();
    }

    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $subject = $this
                ->getSubjects()
                ->firstWhere(
                    'id',
                    $data['subject_id']
                );

            $subjectIdentifier = $subject
                ? ($subject->topic ?: $subject->name)
                : 'UMUM';

            $subjectIdentifier = strtoupper(
                trim(
                    preg_replace(
                        '/[^A-Z0-9]+/',
                        '-',
                        strtoupper((string) $subjectIdentifier)
                    ),
                    '-'
                )
            );

            if ($subjectIdentifier === '') {
                $subjectIdentifier = 'UMUM';
            }

            $prefix = 'UJ-' . $subjectIdentifier;

            $data['code'] = $this
                ->repository
                ->generateUniqueCode($prefix);

            return $this->repository->create($data);
        });
    }

    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $exam->update($data);
            return $exam->fresh();
        });
    }

    public function getAvailableForStudent(
        int $userId,
        int $perPage = 9
    )
    {
        $student = Student::query()
            ->where('user_id', $userId)
            ->first();

        if (!$student || !$student->is_active) {
            return new LengthAwarePaginator(
                [],
                0,
                $perPage
            );
        }

        return $this->repository->getAvailableForStudent(
            $student->id,
            $perPage
        );
    }

    public function getExamWithQuestions(
        int $examId
    ): array
    {
        $exam = $this->repository->findById(
            $examId
        );

        $questions = $this
            ->repository
            ->getExamQuestions($examId);

        $excludeIds = $questions
            ->pluck('question_bank_id')
            ->filter()
            ->toArray();

        $availableQuestions = $this
            ->repository
            ->getAvailableQuestionBanks(
                $exam->subject_id,
                $excludeIds
            );

        return [
            'exam' => $exam,
            'questions' => $questions,
            'availableQuestions' => $availableQuestions,
        ];
    }

    public function addQuestionsToExam(
        int   $examId,
        array $questionBankIds
    ): void
    {
        $exam = $this->repository->findById(
            $examId
        );

        DB::transaction(function () use (
            $exam,
            $questionBankIds
        ) {
            $exam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (ExamAttempt::query()->where('exam_id', $exam->id)->exists()) {
                throw new \RuntimeException(
                    'Soal tidak dapat diubah setelah percobaan ujian dibuat.'
                );
            }

            $lastSortOrder = ExamQuestion::query()
                ->where(
                    'exam_id',
                    $exam->id
                )
                ->max('sort_order') ?? 0;

            $questionBankIds = array_values(
                array_unique(
                    array_map(
                        'intval',
                        $questionBankIds
                    )
                )
            );

            if (empty($questionBankIds)) {
                throw new \RuntimeException(
                    'Tidak ada soal yang dipilih.'
                );
            }

            $questionBanks = QuestionBank::query()
                ->with([
                    'options' => function ($query) {
                        $query->orderBy(
                            'sort_order'
                        );
                    },
                ])
                ->where(
                    'subject_id',
                    $exam->subject_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereIn(
                    'id',
                    $questionBankIds
                )
                ->get();

            if (
                $questionBanks->count() !==
                count($questionBankIds)
            ) {
                throw new \RuntimeException(
                    'Salah satu soal yang dipilih tidak ditemukan, tidak aktif, atau bukan bagian dari mata pelajaran ujian.'
                );
            }

            foreach ($questionBanks as $questionBank) {
                if (
                    in_array($questionBank->type, ['multiple_choice', 'multiple_select'], true) &&
                    $questionBank->options->isEmpty()
                ) {
                    throw new \RuntimeException(
                        "Soal {$questionBank->code} tidak memiliki pilihan jawaban."
                    );
                }

                if (
                    in_array($questionBank->type, ['multiple_choice', 'multiple_select'], true) &&
                    !$questionBank->options->contains(
                        fn($option) => (bool)$option->is_correct
                    )
                ) {
                    throw new \RuntimeException(
                        "Soal {$questionBank->code} belum memiliki jawaban yang benar."
                    );
                }

                $lastSortOrder++;

                $examQuestion = $this
                    ->repository
                    ->createExamQuestion([
                        'exam_id' => $exam->id,
                        'question_bank_id' => $questionBank->id,
                        'type' => $questionBank->type,
                        'question' => $questionBank->question,
                        'explanation' => $questionBank->explanation,
                        'score' => $questionBank->default_score,
                        'sort_order' => $lastSortOrder,
                    ]);

                foreach (
                    $questionBank->options as $option
                ) {
                    $optionText = $option->option_text;

                    if ($optionText === null) {
                        $optionText = '';
                    }

                    $optionSortOrder = (int)$option->sort_order;

                    if ($optionSortOrder < 1) {
                        $optionSortOrder = $option->id;
                    }

                    $this
                        ->repository
                        ->createExamQuestionOption([
                            'exam_question_id' => $examQuestion->id,
                            'option_key' => $option->option_key,
                            'option_text' => $optionText,
                            'is_correct' => $option->is_correct,
                            'sort_order' => $optionSortOrder,
                        ]);
                }
            }
        });
    }

    public function removeQuestionFromExam(
        int $examId,
        int $examQuestionId
    ): bool {
        return DB::transaction(function () use ($examId, $examQuestionId) {
            $exam = Exam::query()
                ->whereKey($examId)
                ->lockForUpdate()
                ->firstOrFail();

            if (ExamAttempt::query()->where('exam_id', $exam->id)->exists()) {
                throw new \RuntimeException(
                    'Soal tidak dapat diubah setelah percobaan ujian dibuat.'
                );
            }

            $question = ExamQuestion::query()
                ->where('exam_id', $exam->id)
                ->whereKey($examQuestionId)
                ->firstOrFail();

            return (bool) $question->delete();
        }, 3);
    }

    public function getExamParticipants(
        int $examId
    ): array
    {
        $exam = $this->repository->findById(
            $examId
        );

        $participants = $this
            ->repository
            ->getExamParticipants($examId);

        $availableStudents = $this
            ->repository
            ->getAvailableStudentsForExam(
                $examId
            );

        return [
            'exam' => $exam,
            'participants' => $participants,
            'availableStudents' => $availableStudents,
        ];
    }

    public function addParticipants(
        int   $examId,
        array $studentIds
    ): void
    {
        $exam = $this->repository->findById(
            $examId
        );

        DB::transaction(function () use (
            $exam,
            $studentIds
        ) {
            $studentIds = array_values(
                array_unique(
                    array_map(
                        'intval',
                        $studentIds
                    )
                )
            );

            if (empty($studentIds)) {
                throw new \RuntimeException(
                    'Tidak ada siswa yang dipilih.'
                );
            }

            $students = Student::query()
                ->whereIn(
                    'id',
                    $studentIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->get();

            if (
                $students->count() !==
                count($studentIds)
            ) {
                throw new \RuntimeException(
                    'Salah satu siswa tidak ditemukan atau tidak aktif.'
                );
            }

            $existingStudentIds = $this
                ->repository
                ->getParticipantStudentIds(
                    $exam->id,
                    $studentIds
                );

            foreach ($students as $student) {
                if (
                    in_array(
                        $student->id,
                        $existingStudentIds,
                        true
                    )
                ) {
                    continue;
                }

                $this
                    ->repository
                    ->createParticipant([
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'assigned_at' => now(),
                        'is_active' => true,
                    ]);
            }
        });
    }

    public function toggleParticipantStatus(
        int $examId,
        int $participantId
    ): bool
    {
        $participant = $this
            ->repository
            ->findParticipant(
                $examId,
                $participantId
            );

        $newStatus = !$participant->is_active;

        $this
            ->repository
            ->updateParticipantStatus(
                $participant->id,
                $newStatus
            );

        return $newStatus;
    }

    public function removeParticipant(
        int $examId,
        int $participantId
    ): bool
    {
        $participant = $this
            ->repository
            ->findParticipant(
                $examId,
                $participantId
            );

        return $this
            ->repository
            ->deleteParticipant(
                $participant->id
            );
    }
}
