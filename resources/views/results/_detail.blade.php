@extends('template.app')

@section('title', 'Hasil Ujian')

@section('content')
    <style>
        .result-score {
            font-size: 2.75rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .result-stat {
            height: 100%;
            padding: 1rem;
            border-radius: 0.75rem;
        }

        .result-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            font-size: 1.35rem;
        }

        .question-card {
            scroll-margin-top: 100px;
        }

        .question-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            font-weight: 600;
        }

        .question-content img,
        .option-content img,
        .explanation-content img {
            max-width: 100%;
            height: auto;
        }

        .question-content table,
        .option-content table,
        .explanation-content table {
            max-width: 100%;
        }

        .question-content iframe,
        .option-content iframe {
            max-width: 100%;
        }

        .option-item {
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
        }

        .option-item.option-correct {
            border-color: rgba(40, 199, 111, 0.6);
            background-color: rgba(40, 199, 111, 0.08);
        }

        .option-item.option-wrong {
            border-color: rgba(234, 84, 85, 0.6);
            background-color: rgba(234, 84, 85, 0.08);
        }

        .result-sticky {
            position: sticky;
            top: 90px;
        }

        .result-question-content {
            overflow-wrap: anywhere;
        }

        @media (max-width: 991.98px) {
            .result-sticky {
                position: static;
            }
        }

        @media (max-width: 575.98px) {
            .result-score {
                font-size: 2.25rem;
            }

            .result-stat {
                padding: 0.75rem;
            }

            .result-stat-icon {
                width: 34px;
                height: 34px;
                font-size: 1.1rem;
            }

            .result-question-card-body {
                padding: 1rem;
            }
        }
    </style>

    @php
        $exam = $attempt->exam;

        $questions = $exam
            ? $exam->questions()
                ->with('options')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $answers = $attempt->answers->keyBy('exam_question_id');

        $duration = max(0, (int) $attempt->duration_seconds);
        $hours = intdiv($duration, 3600);
        $minutes = intdiv($duration % 3600, 60);
        $seconds = $duration % 60;

        $showCorrectAnswers = (bool) ($exam?->show_correct_answers ?? false);

        $isStudent = auth()->user()->student !== null;
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>
                    <div>{{ session('error') }}</div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        @endif

        <div class="card mb-4">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>
                        <h4 class="card-title mb-1">
                            Hasil Ujian
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            {{ $exam?->title ?? 'Ujian tidak ditemukan' }}
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">

                        @if ($isStudent)
                            <a
                                href="{{ route('results.pdf', $attempt) }}"
                                class="btn btn-danger"
                                target="_blank">
                                <i class="bx bxs-file-pdf me-1"></i>
                                Download PDF
                            </a>

                            <a
                                href="{{ route('results.my') }}"
                                class="btn btn-outline-secondary">
                                <i class="bx bx-list-ul me-1"></i>
                                Riwayat Hasil
                            </a>

                            <a
                                href="{{ route('exams.available') }}"
                                class="btn btn-primary">
                                <i class="bx bx-book-open me-1"></i>
                                Ujian Tersedia
                            </a>
                        @else
                            <a
                                href="{{ route('results.index') }}"
                                class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>
                                Daftar Hasil
                            </a>
                        @endif

                    </div>

                </div>
            </div>

            <div class="card-body py-4">

                <div class="row g-4 align-items-center">

                    <div class="col-12 col-lg-7">

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

                            <span class="badge bg-label-primary">
                                {{ $exam?->code ?? '-' }}
                            </span>

                            @if ($attempt->status === 'submitted')
                                <span class="badge bg-label-success">
                                    <i class="bx bx-check-circle me-1"></i>
                                    Selesai
                                </span>
                            @elseif ($attempt->status === 'expired')
                                <span class="badge bg-label-warning">
                                    <i class="bx bx-time-five me-1"></i>
                                    Waktu Habis
                                </span>
                            @else
                                <span class="badge bg-label-secondary">
                                    {{ ucfirst($attempt->status ?? 'Belum diketahui') }}
                                </span>
                            @endif

                            @if ($attempt->passed === true)
                                <span class="badge bg-success">
                                    LULUS
                                </span>
                            @elseif ($attempt->passed === false)
                                <span class="badge bg-danger">
                                    TIDAK LULUS
                                </span>
                            @endif

                        </div>

                        <h5 class="mb-2">
                            {{ $exam?->title ?? '-' }}
                        </h5>

                        @if ($exam?->description)
                            <p class="text-muted mb-3">
                                {{ $exam->description }}
                            </p>
                        @endif

                        <div class="d-flex flex-wrap gap-3 text-muted small">

                            <span>
                                <i class="bx bx-calendar me-1"></i>
                                {{ $attempt->started_at?->format('d M Y') ?? '-' }}
                            </span>

                            <span>
                                <i class="bx bx-time-five me-1"></i>
                                {{ $exam?->duration_minutes ?? 0 }} menit
                            </span>

                            <span>
                                <i class="bx bx-list-ol me-1"></i>
                                {{ $attempt->total_questions }} soal
                            </span>

                        </div>

                    </div>

                    <div class="col-12 col-lg-5">

                        <div class="bg-label-primary rounded p-4 text-center">

                            <div class="small text-muted mb-2">
                                Nilai Akhir
                            </div>

                            <div class="result-score text-primary">
                                {{ number_format((float) $attempt->score, 2, ',', '.') }}
                            </div>

                            <div class="text-muted small mt-2">
                                Passing Grade:
                                {{ number_format((float) ($exam?->passing_score ?? 0), 2, ',', '.') }}
                            </div>

                            @if ($attempt->passed === true)
                                <div class="mt-3">
                                    <span class="badge bg-success">
                                        <i class="bx bx-check-circle me-1"></i>
                                        Selamat, Anda Lulus
                                    </span>
                                </div>
                            @elseif ($attempt->passed === false)
                                <div class="mt-3">
                                    <span class="badge bg-danger">
                                        <i class="bx bx-x-circle me-1"></i>
                                        Belum Mencapai Passing Grade
                                    </span>
                                </div>
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="row g-4">

            <div class="col-12 col-lg-8">

                <div class="card mb-4">

                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-1">
                            Statistik Hasil
                        </h5>

                        <p class="card-subtitle text-muted mb-0">
                            Ringkasan jawaban dan perolehan skor Anda.
                        </p>
                    </div>

                    <div class="card-body py-4">

                        <div class="row g-3">

                            <div class="col-6 col-md-3">
                                <div class="result-stat bg-label-success">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="result-stat-icon bg-success text-white">
                                            <i class="bx bx-check"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="small text-muted">Benar</div>
                                            <div class="fw-bold fs-5">
                                                {{ $attempt->correct_answers }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="result-stat bg-label-danger">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="result-stat-icon bg-danger text-white">
                                            <i class="bx bx-x"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="small text-muted">Salah</div>
                                            <div class="fw-bold fs-5">
                                                {{ $attempt->wrong_answers }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="result-stat bg-label-secondary">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="result-stat-icon bg-secondary text-white">
                                            <i class="bx bx-minus"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="small text-muted">Kosong</div>
                                            <div class="fw-bold fs-5">
                                                {{ $attempt->unanswered }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="result-stat bg-label-primary">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="result-stat-icon bg-primary text-white">
                                            <i class="bx bx-star"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="small text-muted">Total Skor</div>
                                            <div class="fw-bold fs-5">
                                                {{ number_format((float) $attempt->total_score, 2, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="card mb-4">

                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-1">
                            Ringkasan Pengerjaan
                        </h5>

                        <p class="card-subtitle text-muted mb-0">
                            Informasi waktu dan riwayat pengerjaan ujian.
                        </p>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-12 col-md-6">

                                <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-3">
                                    <span class="text-muted">Total Soal</span>
                                    <strong>{{ $attempt->total_questions }}</strong>
                                </div>

                                <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-3">
                                    <span class="text-muted">Percobaan</span>
                                    <strong>Ke-{{ $attempt->attempt_number }}</strong>
                                </div>

                                <div class="d-flex justify-content-between align-items-center py-3 gap-3">
                                    <span class="text-muted">Durasi</span>

                                    <strong class="text-end">
                                        @if ($hours > 0)
                                            {{ $hours }} jam
                                        @endif

                                        {{ $minutes }} menit
                                        {{ $seconds }} detik
                                    </strong>
                                </div>

                            </div>

                            <div class="col-12 col-md-6">

                                <div class="d-flex justify-content-between align-items-start py-3 border-bottom gap-3">
                                    <span class="text-muted">Mulai</span>

                                    <strong class="text-end">
                                        {{ $attempt->started_at?->format('d M Y H:i:s') ?? '-' }}
                                    </strong>
                                </div>

                                <div class="d-flex justify-content-between align-items-start py-3 gap-3">
                                    <span class="text-muted">Dikumpulkan</span>

                                    <strong class="text-end">
                                        {{ $attempt->submitted_at?->format('d M Y H:i:s') ?? '-' }}
                                    </strong>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="card">

                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-1">
                            Detail Jawaban
                        </h5>

                        <p class="card-subtitle text-muted mb-0">
                            Periksa jawaban Anda dan pelajari pembahasan soal.
                        </p>
                    </div>

                    <div class="card-body py-4">

                        @forelse ($questions as $index => $question)

                            @php
                                $answer = $answers->get($question->id);

                                $selectedOptionId = $answer?->selected_option_id;
                                $selectedOptionIds = $answer?->selected_option_ids ?? [];
                                if (!is_array($selectedOptionIds)) $selectedOptionIds = [];
                                if (empty($selectedOptionIds) && $selectedOptionId !== null) $selectedOptionIds = [(int) $selectedOptionId];

                                $selectedOption = $answer?->selectedOption;

                                $correctOption = $question->options
                                    ->firstWhere('is_correct', true);

                                $isAnswered = !empty($selectedOptionIds) || $selectedOptionId !== null;

                                $isCorrect = $answer?->is_correct === true;
                            @endphp

                            <div
                                id="question-{{ $question->id }}"
                                class="question-card border rounded-3 mb-4 overflow-hidden">

                                <div class="d-flex align-items-start gap-3 p-3 border-bottom bg-light">

                                    <div class="question-number bg-primary text-white">
                                        {{ $index + 1 }}
                                    </div>

                                    <div class="flex-grow-1">

                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">

                                            @if (!$isAnswered)
                                                <span class="badge bg-label-secondary">
                                                    <i class="bx bx-minus me-1"></i>
                                                    Tidak Dijawab
                                                </span>
                                            @elseif ($isCorrect)
                                                <span class="badge bg-label-success">
                                                    <i class="bx bx-check me-1"></i>
                                                    Benar
                                                </span>
                                            @else
                                                <span class="badge bg-label-danger">
                                                    <i class="bx bx-x me-1"></i>
                                                    Salah
                                                </span>
                                            @endif

                                            <span class="badge bg-label-primary">
                                                {{ number_format((float) $question->score, 2, ',', '.') }}
                                                poin
                                            </span>

                                        </div>

                                        <div class="small text-muted">
                                            Soal {{ $index + 1 }} dari {{ $questions->count() }}
                                        </div>

                                    </div>

                                </div>

                                <div class="result-question-content result-question-card-body p-4">

                                    <div class="question-content mb-4">
                                        {!! \App\Support\QuestionHtml::sanitize($question->question) !!}
                                    </div>

                                    @if ($question->type === 'statement_true_false')
                                        @php
                                            $statementGroups = $question->options->groupBy(fn ($option) => preg_replace('/[TF]$/', '', (string) $option->option_key));
                                        @endphp
                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($statementGroups as $groupKey => $groupOptions)
                                                @php
                                                    $trueOption = $groupOptions->firstWhere('option_key', $groupKey . 'T');
                                                    $falseOption = $groupOptions->firstWhere('option_key', $groupKey . 'F');
                                                    $selectedStatementOption = $groupOptions->first(fn ($option) => in_array((int) $option->id, array_map('intval', $selectedOptionIds), true));
                                                    $correctStatementOption = $groupOptions->firstWhere('is_correct', true);
                                                @endphp
                                                <div class="option-item {{ $selectedStatementOption && $selectedStatementOption->is_correct ? 'option-correct' : ($selectedStatementOption ? 'option-wrong' : '') }}">
                                                    <div class="fw-semibold mb-2">Pernyataan {{ preg_replace('/^S/', '', $groupKey) }}</div>
                                                    <div class="option-content mb-3">
                                                        {!! \App\Support\QuestionHtml::sanitize($trueOption?->option_text ?? '') !!}
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2 small">
                                                        <span class="badge {{ $selectedStatementOption && str_ends_with((string) $selectedStatementOption->option_key, 'T') ? 'bg-primary' : 'bg-label-secondary' }}">
                                                            Jawaban Anda: {{ $selectedStatementOption ? (str_ends_with((string) $selectedStatementOption->option_key, 'T') ? 'Benar' : 'Salah') : 'Belum dijawab' }}
                                                        </span>
                                                        @if ($showCorrectAnswers)
                                                            <span class="badge bg-success">
                                                                Kunci: {{ $correctStatementOption && str_ends_with((string) $correctStatementOption->option_key, 'T') ? 'Benar' : 'Salah' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-2">
                                            @foreach ($question->options as $option)
                                                @php
                                                    $isSelected = in_array((int) $option->id, array_map('intval', $selectedOptionIds), true);
                                                    $isCorrectOption = (bool) $option->is_correct;
                                                    $showCorrectOption = $showCorrectAnswers && $isCorrectOption;
                                                @endphp
                                                <div class="option-item {{ $showCorrectOption ? 'option-correct' : '' }} {{ $isSelected && !$isCorrectOption ? 'option-wrong' : '' }}">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="fw-bold flex-shrink-0">{{ $option->option_key }}.</div>
                                                        <div class="flex-grow-1 option-content">
                                                            {!! \App\Support\QuestionHtml::sanitize($option->option_text) !!}
                                                        </div>
                                                        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                                            @if ($isSelected)
                                                                <span class="badge bg-primary">Jawaban Anda</span>
                                                            @endif
                                                            @if ($showCorrectOption)
                                                                <span class="badge bg-success">Jawaban Benar</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (!$isAnswered)

                                        <div class="alert alert-secondary mt-4 mb-0">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bx bx-info-circle fs-5"></i>

                                                <div>
                                                    <div class="fw-semibold">
                                                        Tidak Dijawab
                                                    </div>

                                                    <div class="small">
                                                        Anda tidak memilih jawaban untuk soal ini.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    @elseif ($isCorrect)

                                        <div class="alert alert-success mt-4 mb-0">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bx bx-check-circle fs-5"></i>

                                                <div>
                                                    <div class="fw-semibold">
                                                        Jawaban Benar
                                                    </div>

                                                    <div class="small">
                                                        Jawaban Anda mendapatkan
                                                        {{ number_format((float) ($answer?->score ?? $question->score), 2, ',', '.') }}
                                                        poin.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    @else

                                        <div class="alert alert-danger mt-4 mb-0">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bx bx-x-circle fs-5"></i>

                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $question->type === 'statement_true_false' ? 'Jawaban Belum Sepenuhnya Benar' : 'Jawaban Salah' }}
                                                    </div>
                                                    @if ($question->type === 'statement_true_false')
                                                        <div class="small">
                                                            Skor: {{ number_format((float) ($answer?->score ?? 0), 2, ',', '.') }} dari {{ number_format((float) $question->score, 2, ',', '.') }} poin.
                                                        </div>
                                                    @endif

                                                    @if ($showCorrectAnswers && $correctOption)
                                                        <div class="small">
                                                            Jawaban yang benar adalah
                                                            <strong>{{ $correctOption->option_key }}</strong>.
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    @endif

                                    @if (
                                        $question->explanation
                                        && ($showCorrectAnswers || ($isAnswered && !$isCorrect))
                                    )

                                        <div class="alert alert-info mt-4 mb-0">

                                            <div class="d-flex align-items-start gap-2">

                                                <i class="bx bx-bulb fs-4"></i>

                                                <div class="flex-grow-1">

                                                    <div class="fw-semibold mb-2">
                                                        Pembahasan
                                                    </div>

                                                    <div class="explanation-content">
                                                        {!! \App\Support\QuestionHtml::sanitize($question->explanation) !!}
                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-5">

                                <i
                                    class="bx bx-file-blank text-muted"
                                    style="font-size: 4rem;">
                                </i>

                                <h5 class="mt-3">
                                    Soal Tidak Ditemukan
                                </h5>

                                <p class="text-muted mb-0">
                                    Detail soal untuk ujian ini tidak tersedia.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

            <div class="col-12 col-lg-4">

                <div class="result-sticky">

                    <div class="card mb-4">

                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-1">
                                Status Hasil
                            </h5>

                            <p class="card-subtitle text-muted mb-0">
                                Ringkasan pencapaian ujian.
                            </p>
                        </div>

                        <div class="card-body">

                            <div class="text-center py-3 mb-3">

                                @if ($attempt->passed === true)

                                    <i
                                        class="bx bx-check-circle text-success"
                                        style="font-size: 4rem;">
                                    </i>

                                    <h5 class="text-success mt-2 mb-1">
                                        Selamat!
                                    </h5>

                                    <p class="text-muted small mb-0">
                                        Anda dinyatakan lulus ujian.
                                    </p>

                                @elseif ($attempt->passed === false)

                                    <i
                                        class="bx bx-x-circle text-danger"
                                        style="font-size: 4rem;">
                                    </i>

                                    <h5 class="text-danger mt-2 mb-1">
                                        Belum Lulus
                                    </h5>

                                    <p class="text-muted small mb-0">
                                        Nilai belum mencapai passing grade.
                                    </p>

                                @else

                                    <i
                                        class="bx bx-info-circle text-info"
                                        style="font-size: 4rem;">
                                    </i>

                                    <h5 class="text-info mt-2 mb-1">
                                        Hasil Tersedia
                                    </h5>

                                @endif

                            </div>

                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-3">
                                <span class="text-muted">Nilai</span>

                                <strong class="text-primary">
                                    {{ number_format((float) $attempt->score, 2, ',', '.') }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-3">
                                <span class="text-muted">Passing Grade</span>

                                <strong>
                                    {{ number_format((float) ($exam?->passing_score ?? 0), 2, ',', '.') }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-3">
                                <span class="text-muted">Benar</span>

                                <strong class="text-success">
                                    {{ $attempt->correct_answers }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-3">
                                <span class="text-muted">Salah</span>

                                <strong class="text-danger">
                                    {{ $attempt->wrong_answers }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-3 gap-3">
                                <span class="text-muted">Tidak Dijawab</span>

                                <strong>
                                    {{ $attempt->unanswered }}
                                </strong>
                            </div>

                        </div>

                    </div>

                    <div class="card mb-4">

                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-1">
                                Navigasi Soal
                            </h5>

                            <p class="card-subtitle text-muted mb-0">
                                Pilih nomor untuk melihat jawaban.
                            </p>
                        </div>

                        <div class="card-body">

                            <div class="d-flex flex-wrap gap-2">

                                @foreach ($questions as $index => $question)

                                    @php
                                        $answer = $answers->get($question->id);

                                        $answered = !empty($answer?->selected_option_ids) || $answer?->selected_option_id !== null;

                                        $correct = $answer?->is_correct === true;
                                    @endphp

                                    <a
                                        href="#question-{{ $question->id }}"
                                        class="question-number text-decoration-none
                                            {{ $correct
                                                ? 'bg-success text-white'
                                                : ($answered
                                                    ? 'bg-danger text-white'
                                                    : 'bg-label-secondary text-dark') }}"
                                        aria-label="Lihat soal {{ $index + 1 }}"
                                        title="Soal {{ $index + 1 }}">
                                        {{ $index + 1 }}
                                    </a>

                                @endforeach

                            </div>

                            <hr>

                            <div class="d-flex flex-column gap-2 small">

                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="rounded-circle bg-success flex-shrink-0"
                                        style="width: 14px; height: 14px;">
                                    </span>

                                    <span>Jawaban benar</span>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="rounded-circle bg-danger flex-shrink-0"
                                        style="width: 14px; height: 14px;">
                                    </span>

                                    <span>Jawaban salah</span>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="rounded-circle bg-label-secondary border flex-shrink-0"
                                        style="width: 14px; height: 14px;">
                                    </span>

                                    <span>Tidak dijawab</span>
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="card">

                        <div class="card-body">

                            <div class="d-flex align-items-start gap-3">

                                <i class="bx bx-info-circle fs-3 text-primary"></i>

                                <div>
                                    <h6 class="mb-2">
                                        Informasi
                                    </h6>

                                    <p class="small text-muted mb-0">
                                        Hasil ujian tersimpan sebagai riwayat pengerjaan. Gunakan detail jawaban untuk
                                        mengevaluasi hasil belajar Anda.
                                    </p>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('a[href^="#question-"]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    const targetId = this.getAttribute('href');
                    const target = document.querySelector(targetId);

                    if (!target) {
                        return;
                    }

                    event.preventDefault();

                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });
        });
    </script>
@endpush
