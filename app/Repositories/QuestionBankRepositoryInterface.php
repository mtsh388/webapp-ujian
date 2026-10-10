<?php

namespace App\Repositories;

use App\Models\QuestionBank;
use App\Models\QuestionMedia;
use App\Models\QuestionOption;
use App\Models\QuestionOptionMedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface QuestionBankRepositoryInterface
{
    public function paginate(
        int     $perPage = 10,
        ?string $search = null,
        ?int    $subjectId = null,
        ?string $type = null,
        ?string $difficulty = null
    ): LengthAwarePaginator;

    public function query(): Builder;

    public function findById(int $id): QuestionBank;

    public function create(array $data): QuestionBank;

    public function createOption(array $data): QuestionOption;

    public function createQuestionMedia(array $data): QuestionMedia;

    public function createOptionMedia(array $data): QuestionOptionMedia;

    public function update(
        QuestionBank $questionBank,
        array        $data
    ): QuestionBank;

    public function delete(QuestionBank $questionBank): bool;

    public function getSubjects(): Collection;

    public function getLastCode(string $prefix): ?string;

    public function getActiveForExam(): Collection;

    public function getForExam(
        ?int    $subjectId = null,
        ?string $type = null,
        ?string $difficulty = null,
        ?string $search = null
    ): Collection;

    public function findActiveWithOptions(int $id);
}
