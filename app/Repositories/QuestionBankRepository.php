<?php

namespace App\Repositories;

use App\Models\QuestionBank;
use App\Models\QuestionMedia;
use App\Models\QuestionOption;
use App\Models\QuestionOptionMedia;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class QuestionBankRepository implements QuestionBankRepositoryInterface
{
    public function query(): Builder
    {
        return QuestionBank::query()
            ->with([
                'subject',
                'media',
            ]);
    }

    public function paginate(
        int     $perPage = 10,
        ?string $search = null,
        ?int    $subjectId = null,
        ?string $type = null,
        ?string $difficulty = null
    ): LengthAwarePaginator
    {
        return $this->query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where(
                        'code',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'question',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->when($subjectId, function ($query, $subjectId) {
                $query->where(
                    'subject_id',
                    $subjectId
                );
            })
            ->when($type, function ($query, $type) {
                $query->where(
                    'type',
                    $type
                );
            })
            ->when($difficulty, function ($query, $difficulty) {
                $query->where(
                    'difficulty',
                    $difficulty
                );
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): QuestionBank
    {
        return QuestionBank::with([
            'subject',
            'options.media',
            'media',
        ])->findOrFail($id);
    }

    public function create(array $data): QuestionBank
    {
        return QuestionBank::create($data);
    }

    public function createOption(array $data): QuestionOption
    {
        return QuestionOption::create($data);
    }

    public function createQuestionMedia(array $data): QuestionMedia
    {
        return QuestionMedia::create($data);
    }

    public function createOptionMedia(array $data): QuestionOptionMedia
    {
        return QuestionOptionMedia::create($data);
    }

    public function update(
        QuestionBank $questionBank,
        array        $data
    ): QuestionBank
    {
        $questionBank->update($data);

        return $questionBank->refresh();
    }

    public function delete(QuestionBank $questionBank): bool
    {
        return $questionBank->delete();
    }

    public function getSubjects(): Collection
    {
        return Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getLastCode(string $prefix): ?string
    {
        return QuestionBank::query()
            ->where(
                'code',
                'like',
                $prefix . '-%'
            )
            ->orderByDesc('id')
            ->value('code');
    }

    public function getActiveForExam(): Collection
    {
        return QuestionBank::query()
            ->where('is_active', true)
            ->with([
                'subject',
                'options',
                'media',
            ])
            ->latest()
            ->get();
    }

    public function getForExam(
        ?int    $subjectId = null,
        ?string $type = null,
        ?string $difficulty = null,
        ?string $search = null
    ): Collection
    {
        return QuestionBank::query()
            ->with([
                'subject',
                'options' => fn($query) => $query->orderBy('sort_order'),
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when(
                $subjectId,
                fn($query) => $query->where(
                    'subject_id',
                    $subjectId
                )
            )
            ->when(
                $type,
                fn($query) => $query->where(
                    'type',
                    $type
                )
            )
            ->when(
                $difficulty,
                fn($query) => $query->where(
                    'difficulty',
                    $difficulty
                )
            )
            ->when(
                $search,
                fn($query) => $query->where(function ($query) use ($search) {
                    $query->where(
                        'code',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'question',
                        'like',
                        "%{$search}%"
                    );
                })
            )
            ->orderBy('id')
            ->get();
    }

    public function findActiveWithOptions(int $id)
    {
        return QuestionBank::query()
            ->with([
                'options' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'options.media',
                'media',
            ])
            ->where('is_active', true)
            ->findOrFail($id);
    }
}
