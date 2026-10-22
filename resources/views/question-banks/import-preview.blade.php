@extends('template.app')

@section('title', 'Preview Import Bank Soal')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="mb-1">Preview Import Bank Soal</h4>
                <p class="text-muted mb-0">Periksa hasil pembacaan dokumen sebelum soal disimpan ke bank soal.</p>
            </div>
            <a href="{{ route('questions.import') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>
                Kembali ke Upload
            </a>
        </div>

        <div class="alert alert-info">
            <div class="fw-semibold mb-1">Belum ada soal yang disimpan.</div>
            Periksa pertanyaan, pilihan jawaban, kunci jawaban, dan pembahasan di bawah ini. Data baru dibuat setelah Anda menekan tombol Konfirmasi Import.
        </div>

        @php
            $unreadableAnswerKeys = collect($preview['questions'])
                ->filter(fn ($item) => empty($item['answer_keys']))
                ->values();
        @endphp

        @if ($unreadableAnswerKeys->isNotEmpty())
            <div class="alert alert-danger border-danger mb-4" role="alert">
                <div class="d-flex align-items-start gap-3">
                    <i class="bx bx-error-circle fs-2"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold fs-5 mb-1">Kunci tidak terbaca ({{ $unreadableAnswerKeys->count() }} soal)</div>
                        <p class="mb-2">Kunci jawaban untuk soal berikut belum berhasil dikenali dari dokumen Word. Periksa kunci pada file sumber dan jangan mengonfirmasi impor sebelum memastikan jawaban yang benar.</p>
                        <ul class="mb-0 ps-3">
                            @foreach ($unreadableAnswerKeys as $unreadableItem)
                                <li class="mb-1">
                                    <strong>Soal {{ $unreadableItem['number'] }}:</strong>
                                    {{ \Illuminate\Support\Str::limit(trim(strip_tags($unreadableItem['question'])), 180) ?: 'Teks soal tidak tersedia' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (($preview['format'] ?? 'template') === 'flexiquiz')
            <div class="alert alert-warning">
                <div class="fw-semibold mb-2">Format TKA/FlexiQuiz terdeteksi</div>
                <p class="mb-2">Dokumen ini menggunakan kunci jawaban berupa teks, jawaban jamak, dan/atau pernyataan Benar/Salah. Agar kompatibel dengan format penilaian ujian yang saat ini tersedia, beberapa jenis soal dikonversi menjadi soal Benar/Salah per pilihan atau per pernyataan.</p>
                <ul class="mb-0 ps-3">
                    @foreach (($preview['conversion_notes'] ?? []) as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
                <div class="mt-2 fw-semibold">Pastikan seluruh teks bacaan, soal, dan kunci jawaban di bawah ini sesuai sebelum mengonfirmasi impor.</div>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Mata Pelajaran</div>
                    <div class="fw-semibold">{{ $subject?->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $subject?->education_level ?? '-' }} · Kelas {{ $subject?->grade_level ?? '-' }} · Topik {{ $subject?->topic ?? '-' }}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Total Soal</div>
                    <div class="fs-3 fw-semibold">{{ $preview['total'] }}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Pilihan Ganda</div>
                    <div class="fs-3 fw-semibold">{{ $preview['multiple_choice'] }}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Pilih Semua yang Benar</div>
                    <div class="fs-3 fw-semibold">{{ $preview['multiple_select'] ?? 0 }}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Benar / Salah</div>
                    <div class="fs-3 fw-semibold">{{ $preview['true_false'] }}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Benar / Salah per Pernyataan</div>
                    <div class="fs-3 fw-semibold">{{ $preview['statement_true_false'] ?? 0 }}</div>
                </div></div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <span class="badge bg-label-secondary">Kesulitan: {{ ['easy' => 'Mudah', 'medium' => 'Sedang', 'hard' => 'Sulit'][$pending['difficulty']] ?? $pending['difficulty'] }}</span>
            <span class="badge bg-label-secondary">Nilai default: {{ number_format((float) $pending['default_score'], 2, ',', '.') }}</span>
        </div>

        <div class="import-preview-scroll-controls">
            <button type="button" class="btn btn-primary rounded-circle shadow import-preview-scroll-button" id="scroll-to-import-top" aria-label="Ke bagian paling atas" title="Ke bagian paling atas">
                <i class="bx bx-chevrons-up"></i>
            </button>
            <button type="button" class="btn btn-primary rounded-circle shadow import-preview-scroll-button" id="scroll-to-import-bottom" aria-label="Ke bagian paling bawah" title="Ke bagian paling bawah">
                <i class="bx bx-chevrons-down"></i>
            </button>
        </div>

        <div class="d-flex flex-column gap-3 mb-4">
            @foreach ($preview['questions'] as $item)
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="fw-semibold">Soal {{ $item['number'] }}</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-primary">{{ $item['type'] === 'statement_true_false' ? 'Benar / Salah per Pernyataan' : ($item['type'] === 'multiple_select' ? 'Pilih Semua yang Benar' : ($item['type'] === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar / Salah')) }}</span>
                            @if (empty($item['answer_keys']))
                                <span class="badge bg-warning text-dark">Kunci tidak terbaca — periksa sebelum dipakai</span>
                            @endif
                            @if ($item['image_count'] > 0)
                                <span class="badge bg-label-info">{{ $item['image_count'] }} gambar pada soal</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="question-preview mb-3">{!! $item['question'] !!}</div>

                        @if (($item['type'] ?? null) === 'statement_true_false')
                            @php
                                $statementGroups = collect($item['options'] ?? [])->groupBy(fn ($option) => preg_replace('/[TF]$/', '', (string) ($option['key'] ?? '')));
                            @endphp
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered align-middle statement-question-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;">No.</th>
                                            <th>Pernyataan</th>
                                            <th class="text-center" style="width: 110px;">Benar</th>
                                            <th class="text-center" style="width: 110px;">Salah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($statementGroups as $groupKey => $groupOptions)
                                            @php
                                                $trueOption = $groupOptions->firstWhere('key', $groupKey . 'T');
                                                $falseOption = $groupOptions->firstWhere('key', $groupKey . 'F');
                                            @endphp
                                            <tr>
                                                <th scope="row" class="text-center">{{ preg_replace('/^S/', '', $groupKey) }}</th>
                                                <td>{!! \App\Support\QuestionHtml::sanitize($trueOption['html'] ?? $falseOption['html'] ?? $trueOption['text'] ?? $falseOption['text'] ?? '') !!}</td>
                                                <td class="text-center">
                                                    @if (!empty($trueOption['is_correct']))
                                                        <span class="badge bg-success">Kunci</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                    @if (($trueOption['image_count'] ?? 0) > 0)
                                                        <span class="badge bg-label-info">{{ $trueOption['image_count'] }} gambar</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (!empty($falseOption['is_correct']))
                                                        <span class="badge bg-success">Kunci</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif (!empty($item['options']))
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach ($item['options'] as $option)
                                    <div class="border rounded p-3 {{ $option['is_correct'] ? 'border-success bg-label-success' : '' }}">
                                        <div class="d-flex gap-2 align-items-start">
                                            <strong>{{ $option['key'] }}.</strong>
                                            <div class="flex-grow-1">{!! $option['html'] !!}</div>
                                            @if ($option['is_correct'])
                                                <span class="badge bg-success">Kunci jawaban</span>
                                            @endif
                                            @if ($option['image_count'] > 0)
                                                <span class="badge bg-label-info">{{ $option['image_count'] }} gambar</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small mb-3">Pilihan: A = Benar, B = Salah</div>
                            <div class="fw-semibold mb-3">Kunci jawaban: {{ $item['answer'] === 'A' ? 'Benar' : ($item['answer'] === 'B' ? 'Salah' : 'Belum teridentifikasi') }}</div>
                        @endif

                        @if (empty($item['options']) && empty($item['answer_keys']))
                            <div class="alert alert-warning py-2">Kunci jawaban tidak teridentifikasi. Periksa manual sebelum menambahkan soal ini ke ujian.</div>
                        @endif

                        @if (!empty($item['explanation']))
                            <div class="border-top pt-3">
                                <div class="fw-semibold mb-1">Pembahasan</div>
                                <div>{!! $item['explanation'] !!}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div id="import-preview-bottom" class="card">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="fw-semibold">Apakah hasil preview sudah benar?</div>
                    <div class="text-muted small">Kode soal akan dibuat otomatis berdasarkan mata pelajaran, jenjang, dan topik.</div>
                </div>
                <form action="{{ route('questions.import.confirm') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $pending['token'] }}">
                    <button type="submit" class="btn btn-primary" {{ $unreadableAnswerKeys->isNotEmpty() ? 'disabled' : '' }} title="{{ $unreadableAnswerKeys->isNotEmpty() ? 'Perbaiki kunci jawaban yang tidak terbaca sebelum mengimpor.' : '' }}">
                        <i class="bx bx-check-circle me-1"></i>
                        Konfirmasi Import {{ $preview['total'] }} Soal
                    </button>
                    @if ($unreadableAnswerKeys->isNotEmpty())
                        <div class="small text-danger mt-2">Konfirmasi dinonaktifkan karena ada {{ $unreadableAnswerKeys->count() }} soal dengan kunci jawaban tidak terbaca.</div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <script>
        const scrollToImportTopButton = document.getElementById('scroll-to-import-top');
        const scrollToImportBottomButton = document.getElementById('scroll-to-import-bottom');
        const importPreviewBottom = document.getElementById('import-preview-bottom');

        scrollToImportTopButton?.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        scrollToImportBottomButton?.addEventListener('click', function () {
            importPreviewBottom?.scrollIntoView({
                behavior: 'smooth',
                block: 'end'
            });
        });

        if ('IntersectionObserver' in window) {
            if (scrollToImportTopButton) {
                const importPreviewTopObserver = new IntersectionObserver(([entry]) => {
                    scrollToImportTopButton.classList.toggle('d-none', entry.isIntersecting);
                }, { threshold: 0.1 });

                importPreviewTopObserver.observe(document.querySelector('.container-fluid'));
            }

            if (scrollToImportBottomButton && importPreviewBottom) {
                const importPreviewBottomObserver = new IntersectionObserver(([entry]) => {
                    scrollToImportBottomButton.classList.toggle('d-none', entry.isIntersecting);
                }, { threshold: 0.15 });

                importPreviewBottomObserver.observe(importPreviewBottom);
            }
        }
    </script>

    <style>
        .import-preview-scroll-controls {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .import-preview-scroll-button {
            width: 3.25rem;
            height: 3.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .import-preview-scroll-button.d-none {
            display: none !important;
        }

        @media (max-width: 576px) {
            .import-preview-scroll-controls {
                right: 1rem;
                bottom: 1rem;
            }
        }

        .question-preview img,
        .question-preview table,
        .card-body img {
            max-width: 100%;
        }

        .question-preview table {
            border-collapse: collapse;
            margin: 1rem 0;
            width: 100%;
        }

        .question-preview td,
        .question-preview th {
            border: 1px solid #dee2e6;
            padding: .5rem;
        }

        .question-preview sup,
        .question-preview sub {
            line-height: 0;
        }
    </style>
@endsection
