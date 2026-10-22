@extends('template.app')

@section('title', 'Detail Hasil Ujian Anak')

@section('content')
    @php
        $showCorrectAnswers = (bool) ($attempt->exam?->show_correct_answers ?? false);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <span class="text-muted fw-light">Hasil Ujian Anak /</span>
                    Detail Hasil
                </h4>
                <p class="text-muted mb-0">
                    Detail hasil ujian dan pembahasan jawaban anak.
                </p>
            </div>

            <a href="{{ route('results.children') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>
                Kembali
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Siswa</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Nama Siswa</small>
                            <span class="fw-semibold">
                                {{ $attempt->student->full_name ?? '-' }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Nomor Siswa</small>
                            <span>
                                {{ $attempt->student->student_number ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <small class="text-muted d-block">Kelas</small>
                            <span>
                                {{ $attempt->student->classRoom->name ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Ujian</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Nama Ujian</small>
                            <span class="fw-semibold">
                                {{ $attempt->exam->title ?? $attempt->exam->name ?? '-' }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Mulai Ujian</small>
                            <span>
                                {{ $attempt->started_at
                                    ? \Illuminate\Support\Carbon::parse($attempt->started_at)->format('d/m/Y H:i')
                                    : '-' }}
                            </span>
                        </div>

                        <div>
                            <small class="text-muted d-block">Selesai Ujian</small>
                            <span>
                                {{ $attempt->submitted_at
                                    ? \Illuminate\Support\Carbon::parse($attempt->submitted_at)->format('d/m/Y H:i')
                                    : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Nilai Akhir</small>
                        <h2 class="fw-bold text-primary mb-0">
                            {{ number_format((float) ($attempt->score ?? 0), 2, ',', '.') }}
                        </h2>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block">Status Ujian</small>
                        @if ($attempt->submitted_at)
                            <span class="badge bg-label-success">Selesai</span>
                        @else
                            <span class="badge bg-label-warning">Belum Selesai</span>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block">Jumlah Soal Dijawab</small>
                        <h5 class="mb-0 mt-1">
                            {{ $attempt->answers->count() }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Detail Jawaban dan Pembahasan</h5>
                <p class="text-muted mb-0">
                    Jawaban yang salah ditampilkan bersama pembahasannya.
                </p>
            </div>

            <div class="card-body">
                @forelse ($attempt->answers as $answer)
                    @php
                        $question = $answer->examQuestion;
                        $isCorrect = (bool) $answer->is_correct;
                        $selectedOptionIds = $answer->selected_option_ids ?? [];
                        if (!is_array($selectedOptionIds)) $selectedOptionIds = [];
                        if (empty($selectedOptionIds) && $answer->selected_option_id) $selectedOptionIds = [(int) $answer->selected_option_id];
                    @endphp

                    <div class="border rounded p-3 mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                            <h6 class="fw-bold mb-0">
                                Soal {{ $loop->iteration }}
                            </h6>

                            @if ($isCorrect)
                                <span class="badge bg-label-success">Jawaban Benar</span>
                            @else
                                <span class="badge bg-label-danger">Jawaban Salah</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            {!! \App\Support\QuestionHtml::sanitize($question->question ?? 'Soal tidak tersedia.') !!}
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Jawaban</small>

                            @if ($question?->type === 'statement_true_false')
                                @php
                                    $statementGroups = ($question->options ?? collect())->groupBy(fn ($option) => preg_replace('/[TF]$/', '', (string) $option->option_key));
                                    $selectedOptionIds = array_map('intval', $selectedOptionIds);
                                @endphp
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle statement-question-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 70px;">No.</th>
                                                <th>Pernyataan</th>
                                                <th>Jawaban Anak</th>
                                                @if ($showCorrectAnswers)
                                                    <th>Kunci Jawaban</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($statementGroups as $groupKey => $groupOptions)
                                                @php
                                                    $trueOption = $groupOptions->firstWhere('option_key', $groupKey . 'T');
                                                    $falseOption = $groupOptions->firstWhere('option_key', $groupKey . 'F');
                                                    $statementOption = $trueOption ?: $falseOption;
                                                    $selectedStatementOption = $groupOptions->first(fn ($option) => in_array((int) $option->id, $selectedOptionIds, true));
                                                    $correctStatementOption = $groupOptions->firstWhere('is_correct', true);
                                                @endphp
                                                <tr class="{{ $selectedStatementOption ? ($selectedStatementOption->is_correct ? 'table-success' : 'table-danger') : '' }}">
                                                    <th scope="row" class="text-center">{{ preg_replace('/^S/', '', $groupKey) }}</th>
                                                    <td>{!! \App\Support\QuestionHtml::sanitize($statementOption?->option_text ?? '') !!}</td>
                                                    <td>{{ $selectedStatementOption ? (str_ends_with((string) $selectedStatementOption->option_key, 'T') ? 'Benar' : 'Salah') : 'Belum dijawab' }}</td>
                                                    @if ($showCorrectAnswers)
                                                        <td>{{ $correctStatementOption && str_ends_with((string) $correctStatementOption->option_key, 'T') ? 'Benar' : 'Salah' }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                @foreach (($question->options ?? collect()) as $option)
                                    @php
                                        $isSelected = in_array((int) $option->id, array_map('intval', $selectedOptionIds), true);
                                        $isAnswerKey = (bool) ($option->is_correct ?? false);
                                    @endphp
                                    <div class="rounded border p-2 mb-2 {{ $showCorrectAnswers && $isSelected && $isAnswerKey ? 'border-success bg-label-success' : '' }} {{ $showCorrectAnswers && $isSelected && !$isAnswerKey ? 'border-danger bg-label-danger' : '' }}">
                                        <div class="d-flex align-items-start gap-2">
                                            <span>{{ $option->option_key ?? $option->option_label ?? $option->label ?? '' }}</span>
                                            <div class="flex-grow-1">{!! \App\Support\QuestionHtml::sanitize($option->option_text ?? $option->text ?? '') !!}</div>
                                            @if ($isSelected)
                                                <span class="badge bg-label-primary">Jawaban Anak</span>
                                            @endif
                                            @if ($showCorrectAnswers && $isAnswerKey)
                                                <span class="badge bg-label-success">Kunci Jawaban</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @if ($showCorrectAnswers && !$isCorrect && $question && $question->explanation)
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading">
                                    <i class="bx bx-bulb me-1"></i>
                                    Pembahasan
                                </h6>
                                <div>{!! \App\Support\QuestionHtml::sanitize($question->explanation) !!}</div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="bx bx-file fs-1 text-muted"></i>
                        <h6 class="mt-2">Belum Ada Detail Jawaban</h6>
                        <p class="text-muted mb-0">
                            Data jawaban untuk percobaan ujian ini belum tersedia.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
