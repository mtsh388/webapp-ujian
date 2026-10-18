<?php

namespace App\Services;

use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Repositories\QuestionBankRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class QuestionBankService
{
    public function __construct(
        protected QuestionBankRepositoryInterface $repository
    )
    {
    }

    public function getDataTable(Request $request): array
    {
        $query = $this->repository->query();

        $total = (clone $query)
            ->count('question_banks.id');

        $search = trim(
            $request->input(
                'search.value',
                ''
            )
        );

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where(
                    'question_banks.code',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'question_banks.question',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if ($request->filled('subject_id')) {
            $query->where(
                'question_banks.subject_id',
                $request->integer('subject_id')
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'question_banks.type',
                $request->string('type')->toString()
            );
        }

        if ($request->filled('difficulty')) {
            $query->where(
                'question_banks.difficulty',
                $request->string('difficulty')->toString()
            );
        }

        $filtered = (clone $query)
            ->count('question_banks.id');

        $orderColumnIndex = $request->integer(
            'order.0.column',
            1
        );

        $orderDirection = strtolower(
            $request->input(
                'order.0.dir',
                'desc'
            )
        );

        if (!in_array(
            $orderDirection,
            ['asc', 'desc'],
            true
        )) {
            $orderDirection = 'desc';
        }

        $orderColumns = [
            0 => 'question_banks.id',
            1 => 'question_banks.code',
            2 => 'question_banks.question',
            3 => 'question_banks.subject_id',
            4 => 'question_banks.type',
            5 => 'question_banks.difficulty',
            6 => 'question_banks.id',
            7 => 'question_banks.is_active',
            8 => 'question_banks.id',
        ];

        $orderColumn = $orderColumns[$orderColumnIndex] ?? 'question_banks.code';

        $query->orderBy(
            $orderColumn,
            $orderDirection
        );

        $start = max(
            0,
            $request->integer(
                'start',
                0
            )
        );

        $length = $request->integer(
            'length',
            10
        );

        if ($length !== -1) {
            $length = max(
                1,
                min($length, 100)
            );

            $query
                ->skip($start)
                ->take($length);
        }

        $questions = $query->get();

        $data = $questions
            ->values()
            ->map(function (
                QuestionBank $question,
                int          $index
            ) use ($start) {
                $questionText = Str::limit(
                    trim(
                        strip_tags(
                            html_entity_decode(
                                $question->question ?? '',
                                ENT_QUOTES | ENT_HTML5,
                                'UTF-8'
                            )
                        )
                    ),
                    100
                );

                $difficultyClass = match (
                $question->difficulty
                ) {
                    'easy' => 'success',
                    'medium' => 'warning',
                    'hard' => 'danger',
                    default => 'secondary',
                };

                $difficultyLabel = match (
                $question->difficulty
                ) {
                    'easy' => 'Mudah',
                    'medium' => 'Sedang',
                    'hard' => 'Sulit',
                    default => ucfirst(
                        $question->difficulty
                    ),
                };

                $typeLabel =
                    $question->type === 'multiple_choice'
                        ? '<span class="badge bg-label-primary">Pilihan Ganda</span>'
                        : '<span class="badge bg-label-info">Benar / Salah</span>';

                $mediaCount = $question->media->count();

                $mediaLabel = $mediaCount > 0
                    ? '<span class="badge bg-label-secondary">' .
                    $mediaCount .
                    ' media</span>'
                    : '<span class="text-muted">-</span>';

                $statusLabel = $question->is_active
                    ? '<span class="badge bg-label-success">Aktif</span>'
                    : '<span class="badge bg-label-secondary">Nonaktif</span>';

                $showUrl = route(
                    'questions.show',
                    $question->id
                );

                $editUrl = route(
                    'questions.edit',
                    $question->id
                );

                $deleteUrl = route(
                    'questions.destroy',
                    $question->id
                );

                $action = '
                <div class="d-flex align-items-center gap-1">
                    <a
                        href="' . $showUrl . '"
                        class="btn btn-sm btn-icon btn-outline-info"
                        title="Detail">
                        <i class="bx bx-show"></i>
                    </a>

                    <a
                        href="' . $editUrl . '"
                        class="btn btn-sm btn-icon btn-outline-primary"
                        title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>

                    <form
                        action="' . $deleteUrl . '"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm(\'Hapus bank soal ini?\')">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '

                        <button
                            type="submit"
                            class="btn btn-sm btn-icon btn-outline-danger"
                            title="Hapus">
                            <i class="bx bx-trash"></i>
                        </button>
                    </form>
                </div>
            ';

                return [
                    'DT_RowIndex' =>
                        $start + $index + 1,

                    'code' =>
                        '<span class="fw-semibold">' .
                        e($question->code) .
                        '</span>',

                    'question' =>
                        '<div class="question-preview">' .
                        e($questionText) .
                        '</div>',

                    'subject_name' =>
                        $question->subject
                            ? e($question->subject->name)
                            : '<span class="text-muted">-</span>',

                    'type' => $typeLabel,

                    'difficulty' =>
                        '<span class="badge bg-label-' .
                        $difficultyClass .
                        '">' .
                        e($difficultyLabel) .
                        '</span>',

                    'media_count' => $mediaLabel,

                    'is_active' => $statusLabel,

                    'action' => $action,
                ];
            })
            ->values()
            ->all();

        return [
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }


    public function paginate(
        int     $perPage = 10,
        ?string $search = null,
        ?int    $subjectId = null,
        ?string $type = null,
        ?string $difficulty = null
    )
    {
        return $this->repository->paginate(
            $perPage,
            $search,
            $subjectId,
            $type,
            $difficulty
        );
    }

    public function find(int $id): QuestionBank
    {
        return $this->repository->findById($id);
    }

    public function getSubjects()
    {
        return $this->repository->getSubjects();
    }

    public function create(array $data): QuestionBank
    {
        return DB::transaction(function () use ($data) {
            $options = $data['options'] ?? [];
            $questionMedia = $data['question_media'] ?? [];

            unset(
                $data['options'],
                $data['question_media']
            );

            $data['code'] = $this->generateCodeForSubject((int) $data['subject_id']);
            $questionBank = $this->repository->create($data);

            $this->syncOptions(
                $questionBank,
                $options
            );

            $this->storeQuestionMedia(
                $questionBank,
                $questionMedia
            );

            return $this->repository->findById(
                $questionBank->id
            );
        });
    }

    public function update(
        QuestionBank $questionBank,
        array        $data
    ): QuestionBank
    {
        return DB::transaction(function () use (
            $questionBank,
            $data
        ) {
            $options = $data['options'] ?? [];
            $questionMedia = $data['question_media'] ?? [];

            unset(
                $data['options'],
                $data['question_media']
            );

            if ((int) $data['subject_id'] !== (int) $questionBank->subject_id) {
                $data['code'] = $this->generateCodeForSubject((int) $data['subject_id']);
            } else {
                unset($data['code']);
            }

            $this->repository->update(
                $questionBank,
                $data
            );

            $this->syncOptions(
                $questionBank,
                $options
            );

            if (!empty($questionMedia)) {
                $this->storeQuestionMedia(
                    $questionBank,
                    $questionMedia
                );
            }

            return $this->repository->findById(
                $questionBank->id
            );
        });
    }

    protected function generateCodeForSubject(int $subjectId): string
    {
        $subject = Subject::query()->findOrFail($subjectId);
        $prefix = Str::upper(Str::slug(implode('-', array_filter([
            $subject->name,
            $subject->education_level,
            $subject->topic,
        ])), '-'));
        $prefix = substr($prefix !== '' ? $prefix : 'SOAL', 0, 90);
        $number = 1;

        do {
            $code = $prefix . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $number++;
        } while (QuestionBank::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function delete(QuestionBank $questionBank): bool
    {
        return DB::transaction(function () use (
            $questionBank
        ) {
            $questionBank->load([
                'media',
                'options.media',
            ]);

            foreach ($questionBank->media as $media) {
                $this->deleteMediaFile($media);
            }

            foreach ($questionBank->options as $option) {
                foreach ($option->media as $media) {
                    $this->deleteMediaFile($media);
                }
            }

            return $this->repository->delete(
                $questionBank
            );
        });
    }

    public function deleteQuestionMedia($media): bool
    {
        return DB::transaction(function () use ($media) {
            $this->deleteMediaFile($media);

            return $media->delete();
        });
    }

    public function deleteOptionMedia($media): bool
    {
        return DB::transaction(function () use ($media) {
            $this->deleteMediaFile($media);

            return $media->delete();
        });
    }

    protected function syncOptions(
        QuestionBank $questionBank,
        array        $options
    ): void
    {
        $existingIds = collect($options)
            ->pluck('id')
            ->filter()
            ->values();

        $optionsToDelete = $questionBank
            ->options()
            ->when(
                $existingIds->isNotEmpty(),
                fn($query) => $query->whereNotIn(
                    'id',
                    $existingIds
                )
            )
            ->with('media')
            ->get();

        foreach ($optionsToDelete as $option) {
            foreach ($option->media as $media) {
                $this->deleteMediaFile($media);
            }

            $option->delete();
        }

        foreach ($options as $index => $optionData) {
            $optionId = $optionData['id'] ?? null;

            unset($optionData['id']);

            $option = $questionBank
                ->options()
                ->updateOrCreate(
                    [
                        'id' => $optionId,
                    ],
                    [
                        'option_key' => $optionData['option_key'],
                        'option_text' => $optionData['option_text'] ?? null,
                        'is_correct' => (bool)(
                            $optionData['is_correct'] ?? false
                        ),
                        'sort_order' => $index,
                    ]
                );

            $media = $optionData['media'] ?? [];

            if (!empty($media)) {
                $this->storeOptionMedia(
                    $option,
                    $media
                );
            }
        }
    }

    protected function storeQuestionMedia(
        QuestionBank $questionBank,
        array        $mediaItems
    ): void
    {
        foreach ($mediaItems as $index => $media) {
            if (
                !isset($media['file']) &&
                empty($media['external_url'])
            ) {
                continue;
            }

            $media['sort_order'] = $media['sort_order']
                ?? $index;

            $this->storeMedia(
                $questionBank,
                null,
                $media
            );
        }
    }

    protected function storeOptionMedia(
        QuestionOption $option,
        array          $mediaItems
    ): void
    {
        foreach ($mediaItems as $index => $media) {
            if (
                !isset($media['file']) &&
                empty($media['external_url'])
            ) {
                continue;
            }

            $media['sort_order'] = $media['sort_order']
                ?? $index;

            $this->storeMedia(
                null,
                $option,
                $media
            );
        }
    }

    protected function storeMedia(
        ?QuestionBank   $questionBank,
        ?QuestionOption $option,
        array           $media
    ): void
    {
        $file = $media['file'] ?? null;

        $source = $media['source'] ?? 'upload';

        $type = $media['type'] ?? 'image';

        $data = [
            'type' => $type,
            'disk' => 'public',
            'path' => null,
            'original_name' => null,
            'mime_type' => null,
            'source' => $source,
            'external_url' => null,
            'file_size' => null,
            'sort_order' => $media['sort_order'] ?? 0,
        ];

        if ($file instanceof UploadedFile) {
            $directory = $questionBank
                ? 'question-banks/' . $questionBank->id
                : 'question-options/' . $option->id;

            $filename = Str::uuid()
                . '.'
                . $file->getClientOriginalExtension();

            $path = $file->storeAs(
                $directory,
                $filename,
                'public'
            );

            $data['path'] = $path;
            $data['original_name'] =
                $file->getClientOriginalName();
            $data['mime_type'] =
                $file->getMimeType();
            $data['file_size'] =
                $file->getSize();
        } else {
            $data['external_url'] =
                $media['external_url'] ?? null;
        }

        if ($questionBank) {
            $questionBank
                ->media()
                ->create($data);
        }

        if ($option) {
            $option
                ->media()
                ->create($data);
        }
    }

    protected function deleteMediaFile($media): void
    {
        if (
            $media->source === 'upload' &&
            $media->path &&
            Storage::disk($media->disk)->exists(
                $media->path
            )
        ) {
            Storage::disk($media->disk)->delete(
                $media->path
            );
        }
    }
}
