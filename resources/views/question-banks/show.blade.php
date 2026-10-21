@extends('template.app')

@section('title', 'Detail Bank Soal')

@section('content')
    <style>
        .question-content,
        .option-content,
        .explanation-content {
            color: #566a7f;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .question-content p,
        .option-content p,
        .explanation-content p {
            margin-bottom: 0.75rem;
        }

        .question-content p:last-child,
        .option-content p:last-child,
        .explanation-content p:last-child {
            margin-bottom: 0;
        }

        .question-content sup,
        .option-content sup,
        .explanation-content sup,
        .question-word-table sup {
            font-size: 0.75em;
            line-height: 0;
            vertical-align: super;
        }

        .question-content sub,
        .option-content sub,
        .explanation-content sub,
        .question-word-table sub {
            font-size: 0.75em;
            line-height: 0;
            vertical-align: sub;
        }

        .question-content img,
        .option-content img,
        .explanation-content img,
        .question-word-image {
            max-width: 100%;
            height: auto;
        }

        .question-content table,
        .option-content table,
        .explanation-content table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .question-content td,
        .question-content th,
        .option-content td,
        .option-content th,
        .explanation-content td,
        .explanation-content th {
            border: 1px solid #dee2e6;
            padding: 0.75rem;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .question-content table img,
        .option-content table img,
        .explanation-content table img {
            max-width: 100%;
            height: auto;
        }

        .media-preview {
            max-height: 420px;
            object-fit: contain;
            background: #f8f9fa;
        }

        .detail-label {
            font-size: 0.8125rem;
            color: #697a8d;
            margin-bottom: 0.25rem;
        }

        .question-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .option-item {
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .option-item.correct {
            border-color: #71dd37 !important;
            background-color: rgba(113, 221, 55, 0.08);
        }

        .option-key {
            min-width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <div class="container-fluid py-3">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-1"></i>
                {{ session('success') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-1"></i>
                {{ session('error') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white border-0 p-4">

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                        <span class="badge bg-label-primary">
                            {{ $questionBank->code }}
                        </span>

                            <span class="badge bg-label-{{ $questionBank->is_active ? 'success' : 'secondary' }}">
                            {{ $questionBank->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>

                            <span class="badge bg-label-info">
                            {{ $questionBank->type === 'statement_true_false' ? 'Benar / Salah per Pernyataan' : ($questionBank->type === 'multiple_select' ? 'Pilih Semua yang Benar' : ($questionBank->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar / Salah')) }}
                        </span>

                            <span class="badge
                            @if ($questionBank->difficulty === 'easy')
                                bg-label-success
                            @elseif ($questionBank->difficulty === 'medium')
                                bg-label-warning
                            @else
                                bg-label-danger
                            @endif">
                            {{ ucfirst($questionBank->difficulty) }}
                        </span>

                        </div>

                        <h4 class="mb-1">
                            Detail Bank Soal
                        </h4>

                        <p class="text-muted mb-0">
                            {{ $questionBank->subject->name ?? '-' }}
                        </p>

                    </div>

                    <div class="d-flex gap-2">

                        <a
                            href="{{ route('questions.edit', $questionBank) }}"
                            class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>
                            Edit
                        </a>

                        <a
                            href="{{ route('questions.index') }}"
                            class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>
                            Kembali
                        </a>

                    </div>

                </div>

            </div>

            <div class="card-body p-4">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="mb-4">

                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

                                <div class="d-flex align-items-center gap-2">

                                <span class="badge bg-primary">
                                    <i class="bx bx-help-circle"></i>
                                </span>

                                    <h5 class="mb-0">
                                        Pertanyaan
                                    </h5>

                                </div>

                                <span class="text-muted small">
                                Nilai:
                                <strong>
                                    {{ number_format((float) $questionBank->default_score, 2, ',', '.') }}
                                </strong>
                            </span>

                            </div>

                            <div class="border rounded-3 p-4 bg-light">

                                <div class="question-content">
                                    {!! \App\Support\QuestionHtml::sanitize($questionBank->question) !!}
                                </div>

                            </div>

                        </div>

                        @if ($questionBank->media->count())
                            <div class="mb-4">

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-info">
                                        <i class="bx bx-paperclip"></i>
                                    </span>

                                    <h5 class="mb-0">
                                        Media Soal
                                    </h5>
                                </div>

                                <div class="row g-3">

                                    @foreach ($questionBank->media as $media)

                                        <div class="col-md-6">

                                            <div class="border rounded-3 p-3 h-100">

                                                @if ($media->type === 'image' && $media->path)

                                                    <img
                                                        src="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                        class="img-fluid rounded-3 border media-preview w-100"
                                                        alt="{{ $media->original_name ?? 'Media soal' }}">

                                                @elseif ($media->type === 'video' && $media->path)

                                                    <video
                                                        controls
                                                        preload="metadata"
                                                        class="w-100 rounded-3 border media-preview">
                                                        <source
                                                            src="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                            type="{{ $media->mime_type }}">
                                                        Browser Anda tidak mendukung video.
                                                    </video>

                                                @elseif ($media->type === 'audio' && $media->path)

                                                    <div class="p-3 bg-light rounded-3">
                                                        <div class="d-flex align-items-center gap-3 mb-3">
                                                            <div class="avatar">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="bx bx-music fs-4"></i>
                                        </span>
                                                            </div>

                                                            <div class="flex-grow-1 overflow-hidden">
                                                                <div class="fw-semibold text-truncate">
                                                                    {{ $media->original_name ?? 'Audio soal' }}
                                                                </div>

                                                                <small class="text-muted">
                                                                    {{ $media->mime_type ?? 'Audio' }}
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <audio controls class="w-100">
                                                            <source
                                                                src="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                                type="{{ $media->mime_type }}">
                                                            Browser Anda tidak mendukung audio.
                                                        </audio>
                                                    </div>

                                                @elseif ($media->type === 'document' && $media->path)

                                                    <div class="d-flex align-items-center gap-3">

                                                        <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class="bx bx-file fs-4"></i>
                                    </span>
                                                        </div>

                                                        <div class="flex-grow-1 overflow-hidden">

                                                            <div class="fw-semibold text-truncate">
                                                                {{ $media->original_name ?? 'Dokumen' }}
                                                            </div>

                                                            <small class="text-muted d-block text-truncate">
                                                                {{ $media->mime_type ?? 'Dokumen' }}
                                                            </small>

                                                        </div>

                                                        <a
                                                            href="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-link-external"></i>
                                                        </a>

                                                    </div>

                                                @elseif ($media->source === 'youtube' && $media->external_url)

                                                    @php
                                                        $youtubeId = null;

                                                        if (preg_match(
                                                            '/(?:youtube\.com\/watch\?v=|youtube\.com\/shorts\/|youtu\.be\/|youtube\.com\/embed\/)([^&?\/]+)/',
                                                            $media->external_url,
                                                            $matches
                                                        )) {
                                                            $youtubeId = $matches[1];
                                                        }
                                                    @endphp

                                                    @if ($youtubeId)

                                                        <div class="ratio ratio-16x9">
                                                            <iframe
                                                                src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                                                title="Video YouTube"
                                                                loading="lazy"
                                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                                allowfullscreen>
                                                            </iframe>
                                                        </div>

                                                    @else

                                                        <div class="alert alert-warning mb-0">
                                                            <i class="bx bx-error-circle me-1"></i>
                                                            URL YouTube tidak valid.
                                                        </div>

                                                    @endif

                                                @elseif ($media->source === 'vimeo' && $media->external_url)

                                                    @php
                                                        $vimeoId = null;

                                                        if (preg_match(
                                                            '/vimeo\.com\/(?:video\/)?([0-9]+)/',
                                                            $media->external_url,
                                                            $matches
                                                        )) {
                                                            $vimeoId = $matches[1];
                                                        }
                                                    @endphp

                                                    @if ($vimeoId)

                                                        <div class="ratio ratio-16x9">
                                                            <iframe
                                                                src="https://player.vimeo.com/video/{{ $vimeoId }}"
                                                                title="Video Vimeo"
                                                                loading="lazy"
                                                                allow="autoplay; fullscreen; picture-in-picture"
                                                                allowfullscreen>
                                                            </iframe>
                                                        </div>

                                                    @else

                                                        <div class="d-flex align-items-center gap-3">

                                                            <div class="avatar">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="bx bx-video fs-4"></i>
                                        </span>
                                                            </div>

                                                            <div class="flex-grow-1 overflow-hidden">
                                                                <div class="fw-semibold">
                                                                    Video Vimeo
                                                                </div>

                                                                <small class="text-muted text-truncate d-block">
                                                                    {{ $media->external_url }}
                                                                </small>
                                                            </div>

                                                            <a
                                                                href="{{ $media->external_url }}"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="btn btn-sm btn-outline-primary">
                                                                <i class="bx bx-link-external"></i>
                                                            </a>

                                                        </div>

                                                    @endif

                                                @elseif ($media->source === 'external' && $media->external_url)

                                                    <div class="d-flex align-items-center gap-3">

                                                        <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="bx bx-link-external fs-4"></i>
                                    </span>
                                                        </div>

                                                        <div class="flex-grow-1 overflow-hidden">

                                                            <div class="fw-semibold">
                                                                Media Eksternal
                                                            </div>

                                                            <small class="text-muted text-truncate d-block">
                                                                {{ $media->external_url }}
                                                            </small>

                                                        </div>

                                                        <a
                                                            href="{{ $media->external_url }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-link-external"></i>
                                                        </a>

                                                    </div>

                                                @else

                                                    <div class="text-center text-muted py-4">
                                                        <i class="bx bx-file-blank fs-1 d-block mb-2"></i>
                                                        <div>Media tidak dapat ditampilkan.</div>
                                                    </div>

                                                @endif

                                                <div
                                                    class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">

                                                    <small class="text-muted">
                                                        {{ ucfirst($media->type) }}
                                                    </small>

                                                    <small class="text-muted">
                                                        {{ ucfirst($media->source) }}
                                                    </small>

                                                </div>

                                            </div>

                                        </div>

                                    @endforeach

                                </div>

                            </div>
                        @endif

                        <div class="mb-4">

                            <div class="d-flex align-items-center gap-2 mb-3">

                            <span class="badge bg-success">
                                <i class="bx bx-list-check"></i>
                            </span>

                                <h5 class="mb-0">
                                    Pilihan Jawaban
                                </h5>

                            </div>

                            <div class="d-flex flex-column gap-3">

                                @forelse ($questionBank->options as $option)

                                    <div
                                        class="option-item border rounded-3 p-3 {{ $option->is_correct ? 'correct' : '' }}">

                                        <div class="d-flex align-items-start gap-3">

                                        <span
                                            class="option-key badge rounded-pill {{ $option->is_correct ? 'bg-success' : 'bg-secondary' }} flex-shrink-0">
                                            {{ $option->option_key }}
                                        </span>

                                            <div class="flex-grow-1 min-width-0">

                                                @if (filled($option->option_text))
                                                    <div class="option-content">
                                                        {!! \App\Support\QuestionHtml::sanitize($option->option_text) !!}
                                                    </div>
                                                @endif

                                                @if ($option->media->count())

                                                    <div class="row g-3 mt-1">

                                                        @foreach ($option->media as $media)

                                                            <div class="col-md-6">

                                                                @if ($media->type === 'image' && $media->path)

                                                                    <img
                                                                        src="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                                        class="img-fluid rounded-3 border media-preview"
                                                                        alt="{{ $media->original_name ?? 'Gambar pilihan jawaban' }}">

                                                                @elseif ($media->type === 'video' && $media->path)

                                                                    <video
                                                                        controls
                                                                        preload="metadata"
                                                                        class="w-100 rounded-3 border media-preview">

                                                                        <source
                                                                            src="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                                            type="{{ $media->mime_type }}">

                                                                    </video>

                                                                @elseif ($media->type === 'audio' && $media->path)

                                                                    <audio controls class="w-100">
                                                                        <source
                                                                            src="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                                            type="{{ $media->mime_type }}">
                                                                    </audio>

                                                                @elseif ($media->type === 'document' && $media->path)

                                                                    <div class="d-flex align-items-center gap-3">

                                                                        <div class="avatar">
                                                                        <span
                                                                            class="avatar-initial rounded bg-label-danger">
                                                                            <i class="bx bx-file fs-4"></i>
                                                                        </span>
                                                                        </div>

                                                                        <div class="flex-grow-1 overflow-hidden">

                                                                            <div class="fw-semibold text-truncate">
                                                                                {{ $media->original_name ?? 'Dokumen' }}
                                                                            </div>

                                                                            <small class="text-muted">
                                                                                {{ $media->mime_type ?? 'Dokumen' }}
                                                                            </small>

                                                                        </div>

                                                                        <a
                                                                            href="{{ Storage::disk($media->disk ?: 'public')->url($media->path) }}"
                                                                            target="_blank"
                                                                            rel="noopener"
                                                                            class="btn btn-sm btn-outline-primary">
                                                                            <i class="bx bx-download"></i>
                                                                        </a>

                                                                    </div>

                                                                @elseif ($media->external_url)

                                                                    <a
                                                                        href="{{ $media->external_url }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="btn btn-sm btn-outline-primary">
                                                                        <i class="bx bx-link-external me-1"></i>
                                                                        Buka Media
                                                                    </a>

                                                                @endif

                                                            </div>

                                                        @endforeach

                                                    </div>

                                                @endif

                                                @if ($option->is_correct)
                                                    <div class="mt-3">
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-check me-1"></i>
                                                        Jawaban benar
                                                    </span>
                                                    </div>
                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                @empty

                                    <div class="border rounded-3 p-4 text-center text-muted">
                                        Belum ada pilihan jawaban.
                                    </div>

                                @endforelse

                            </div>

                        </div>

                        @if (filled($questionBank->explanation))

                            <div>

                                <div class="d-flex align-items-center gap-2 mb-3">

                                <span class="badge bg-warning">
                                    <i class="bx bx-bulb"></i>
                                </span>

                                    <h5 class="mb-0">
                                        Pembahasan
                                    </h5>

                                </div>

                                <div class="border rounded-3 p-4 bg-light">

                                    <div class="explanation-content">
                                        {!! \App\Support\QuestionHtml::sanitize($questionBank->explanation) !!}
                                    </div>

                                </div>

                            </div>

                        @endif

                    </div>

                    <div class="col-lg-4">

                        <div class="card border-0 bg-light mb-4">

                            <div class="card-header bg-transparent border-0 p-4">

                                <h5 class="mb-0">
                                    Informasi Soal
                                </h5>

                            </div>

                            <div class="card-body p-4">

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Kode Soal
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $questionBank->code }}
                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Mata Pelajaran
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $questionBank->subject->name ?? '-' }}
                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Tipe Soal
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $questionBank->type === 'multiple_select' ? 'Pilih Semua yang Benar' : ($questionBank->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar / Salah') }}
                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Tingkat Kesulitan
                                    </div>

                                    <div>

                                    <span class="badge
                                        @if ($questionBank->difficulty === 'easy')
                                            bg-label-success
                                        @elseif ($questionBank->difficulty === 'medium')
                                            bg-label-warning
                                        @else
                                            bg-label-danger
                                        @endif">
                                        {{ ucfirst($questionBank->difficulty) }}
                                    </span>

                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Nilai Soal
                                    </div>

                                    <div class="fw-semibold">
                                        {{ number_format((float) $questionBank->default_score, 2, ',', '.') }}
                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Jumlah Pilihan
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $questionBank->options->count() }} Pilihan
                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="detail-label">
                                        Media Soal
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $questionBank->media->count() }} Media
                                    </div>

                                </div>

                                <div>

                                    <div class="detail-label">
                                        Dibuat
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $questionBank->created_at?->format('d M Y H:i') }}
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="card border-0 bg-light">

                            <div class="card-header bg-transparent border-0 p-4">

                                <h5 class="mb-0">
                                    Status
                                </h5>

                            </div>

                            <div class="card-body p-4">

                                <div class="d-flex align-items-center gap-3">

                                    <div class="avatar">

                                    <span
                                        class="avatar-initial rounded {{ $questionBank->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">

                                        <i class="bx {{ $questionBank->is_active ? 'bx-check-circle' : 'bx-x-circle' }} fs-4"></i>

                                    </span>

                                    </div>

                                    <div>

                                        <div class="fw-semibold">
                                            {{ $questionBank->is_active ? 'Soal Aktif' : 'Soal Nonaktif' }}
                                        </div>

                                        <small class="text-muted">
                                            {{ $questionBank->is_active
                                                ? 'Soal dapat digunakan dalam ujian.'
                                                : 'Soal tidak dapat digunakan dalam ujian.' }}
                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
