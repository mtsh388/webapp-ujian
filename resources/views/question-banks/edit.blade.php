@extends('template.app')

@section('title', 'Edit Bank Soal')

@section('content')
    <style>
        .question-rich-editor { min-height: 220px; overflow-y: auto; white-space: normal; }
        .question-rich-editor:empty::before { content: attr(data-placeholder); color: #8993a4; pointer-events: none; }
        .question-rich-editor table { width: 100%; border-collapse: collapse; margin: .75rem 0; }
        .question-rich-editor th, .question-rich-editor td { border: 1px solid #adb5bd; padding: .5rem; vertical-align: top; min-width: 70px; }
        .question-rich-editor th { background: #f1f3f5; }
    </style>
    <div class="container-fluid py-3">

        <form
            action="{{ route('questions.update', ['question' => $questionBank->id]) }}"
            method="POST"
            enctype="multipart/form-data"
            id="question-form">

            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <div class="fw-semibold mb-2">
                        Terdapat kesalahan:
                    </div>

                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white p-4">
                            <h5 class="mb-0">
                                Informasi Soal
                            </h5>
                        </div>

                        <div class="card-body p-4">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Mata Pelajaran
                                    </label>

                                    <select
                                        name="subject_id"
                                        class="form-select @error('subject_id') is-invalid @enderror"
                                        required>

                                        <option value="">
                                            Pilih Mata Pelajaran
                                        </option>

                                        @foreach ($subjects as $subject)

                                            <option
                                                value="{{ $subject->id }}"
                                                @selected(old('subject_id', $questionBank->subject_id) == $subject->id)>

                                                {{ $subject->name }} — {{ $subject->education_level ?? '-' }} — Kelas {{ $subject->grade_level ?? '-' }} — {{ $subject->topic ?? '-' }}

                                            </option>

                                        @endforeach

                                    </select>

                                    @error('subject_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Kode Soal</label>
                                    <input type="text" class="form-control" value="{{ $questionBank->code }}" readonly>
                                    <div class="form-text">Kode dibuat otomatis. Jika mata pelajaran diubah, kode akan diperbarui.</div>
                                </div>

                                <div class="col-md-4">

                                    <label class="form-label">
                                        Tipe Soal
                                    </label>

                                    <select
                                        name="type"
                                        id="question-type"
                                        class="form-select"
                                        required>

                                        <option
                                            value="multiple_choice"
                                            @selected(old('type', $questionBank->type) === 'multiple_choice')>
                                            Pilihan Ganda
                                        </option>

                                        <option
                                            value="multiple_select"
                                            @selected(old('type', $questionBank->type) === 'multiple_select')>Pilih Semua yang Benar</option>
                                        <option
                                            value="statement_true_false"
                                            @selected(old('type', $questionBank->type) === 'statement_true_false')>
                                            Benar / Salah per Pernyataan
                                        </option>

                                        <option
                                            value="true_false"
                                            @selected(old('type', $questionBank->type) === 'true_false')>
                                            Benar / Salah
                                        </option>

                                    </select>

                                </div>

                                <div class="col-md-4">

                                    <label class="form-label">
                                        Kesulitan
                                    </label>

                                    <select
                                        name="difficulty"
                                        class="form-select"
                                        required>

                                        <option
                                            value="easy"
                                            @selected(old('difficulty', $questionBank->difficulty) === 'easy')>
                                            Mudah
                                        </option>

                                        <option
                                            value="medium"
                                            @selected(old('difficulty', $questionBank->difficulty) === 'medium')>
                                            Sedang
                                        </option>

                                        <option
                                            value="hard"
                                            @selected(old('difficulty', $questionBank->difficulty) === 'hard')>
                                            Sulit
                                        </option>

                                    </select>

                                </div>

                                <div class="col-md-4">

                                    <label class="form-label">
                                        Nilai
                                    </label>

                                    <input
                                        type="number"
                                        name="default_score"
                                        class="form-control"
                                        value="{{ old('default_score', $questionBank->default_score) }}"
                                        min="0"
                                        step="0.01"
                                        required>

                                </div>

                                <div class="col-12">

                                    <label class="form-label">
                                        Pertanyaan
                                    </label>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="form-text">
                                            Bisa berisi teks, tabel pernyataan, rumus sederhana, atau kombinasi.
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary insert-statement-table">
                                            <i class="bx bx-table me-1"></i> Sisipkan Tabel Pernyataan
                                        </button>
                                    </div>
                                    <div
                                        id="question-editor"
                                        class="form-control question-rich-editor"
                                        contenteditable="true"
                                        role="textbox"
                                        aria-multiline="true"
                                        data-placeholder="Tuliskan pertanyaan di sini...">{!! \App\Support\QuestionHtml::sanitize(old('question', $questionBank->question)) !!}</div>
                                    <textarea name="question" id="question-content" class="d-none">{{ old('question', $questionBank->question) }}</textarea>
                                    <div class="form-text mt-2">
                                        Untuk soal kombinasi Benar/Salah, pilih tipe <strong>Pilihan Ganda</strong>, isi empat opsi kombinasi, lalu tandai satu opsi yang benar.
                                    </div>

                                </div>

                                <div class="col-12">

                                    <label class="form-label">
                                        Pembahasan
                                    </label>

                                    <textarea
                                        name="explanation"
                                        class="form-control"
                                        rows="5">{{ old('explanation', $questionBank->explanation) }}</textarea>

                                </div>

                                <div class="col-12">

                                    <div class="form-check form-switch">

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            class="form-check-input"
                                            @checked(old('is_active', $questionBank->is_active))>

                                        <label class="form-check-label">
                                            Soal aktif
                                        </label>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">

                            <div>

                                <h5 class="mb-1">
                                    Pilihan Jawaban
                                </h5>

                                <small class="text-muted">
                                    Pilihan dapat berupa teks, gambar, atau kombinasi teks dan gambar.
                                </small>

                            </div>

                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                id="add-option">

                                <i class="bx bx-plus me-1"></i>
                                Tambah

                            </button>

                        </div>

                        <div class="card-body p-4">

                            <div id="options-container">

                                @foreach ($questionBank->options as $index => $option)

                                    <div
                                        class="option-item border rounded-3 p-3 mb-3"
                                        data-option-id="{{ $option->id }}">

                                        <div class="row g-3">

                                            <div class="col-12">

                                                <div class="d-flex align-items-center justify-content-between gap-2">

                                                    <div class="d-flex align-items-center gap-2">

                                                        <span class="badge bg-primary option-number">
                                                            {{ $option->option_key }}
                                                        </span>

                                                        <span class="fw-semibold">
                                                            Pilihan {{ $option->option_key }}
                                                        </span>

                                                    </div>

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger remove-option">

                                                        <i class="bx bx-trash"></i>

                                                    </button>

                                                </div>

                                                <input
                                                    type="hidden"
                                                    name="options[{{ $index }}][id]"
                                                    value="{{ $option->id }}"
                                                    class="option-id">

                                                <input
                                                    type="hidden"
                                                    name="options[{{ $index }}][option_key]"
                                                    value="{{ $option->option_key }}"
                                                    class="option-key">

                                            </div>

                                            <div class="col-12">

                                                <label class="form-label">
                                                    Teks Pilihan
                                                </label>

                                                <textarea
                                                    name="options[{{ $index }}][option_text]"
                                                    class="form-control"
                                                    rows="2"
                                                    placeholder="Kosongkan jika pilihan hanya berupa gambar">{{ old("options.{$index}.option_text", $option->option_text) }}</textarea>

                                            </div>

                                            <div class="col-12">

                                                @if ($option->media->count())

                                                    <div class="mb-3">

                                                        <label class="form-label">
                                                            Media Saat Ini
                                                        </label>

                                                        <div class="row g-3">

                                                            @foreach ($option->media as $media)

                                                                <div
                                                                    class="col-md-6 existing-option-media"
                                                                    data-media-id="{{ $media->id }}">

                                                                    <div class="border rounded-3 p-3 h-100">

                                                                        @if ($media->type === 'image' && $media->path)

                                                                            <img
                                                                                src="{{ asset('storage/' . ltrim($media->path, '/')) }}"
                                                                                class="img-fluid rounded-3 border mb-3"
                                                                                alt="{{ $media->original_name ?? 'Media pilihan' }}">

                                                                        @elseif ($media->type === 'video' && $media->path)

                                                                            <video
                                                                                controls
                                                                                preload="metadata"
                                                                                class="w-100 rounded-3 border mb-3">

                                                                                <source
                                                                                    src="{{ asset('storage/' . ltrim($media->path, '/')) }}"
                                                                                    type="{{ $media->mime_type }}">

                                                                            </video>

                                                                        @elseif ($media->type === 'audio' && $media->path)

                                                                            <audio
                                                                                controls
                                                                                class="w-100 mb-3">

                                                                                <source
                                                                                    src="{{ asset('storage/' . ltrim($media->path, '/')) }}"
                                                                                    type="{{ $media->mime_type }}">

                                                                            </audio>

                                                                        @elseif ($media->type === 'document' && $media->path)

                                                                            <div
                                                                                class="d-flex align-items-center gap-2 mb-3">

                                                                                <i class="bx bx-file fs-3 text-danger"></i>

                                                                                <span class="text-truncate">
                                                                                    {{ $media->original_name ?? 'Dokumen' }}
                                                                                </span>

                                                                            </div>

                                                                        @elseif ($media->source === 'youtube' && $media->external_url)

                                                                            @php
                                                                                $youtubeId = null;

                                                                                if (preg_match(
                                                                                    '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?\/]+)/',
                                                                                    $media->external_url,
                                                                                    $matches
                                                                                )) {
                                                                                    $youtubeId = $matches[1];
                                                                                }
                                                                            @endphp

                                                                            @if ($youtubeId)

                                                                                <div class="ratio ratio-16x9 mb-3">

                                                                                    <iframe
                                                                                        src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                                                                        title="Video YouTube"
                                                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                                                        allowfullscreen>
                                                                                    </iframe>

                                                                                </div>

                                                                            @endif

                                                                        @elseif ($media->source === 'vimeo' && $media->external_url)

                                                                            <div class="ratio ratio-16x9 mb-3">

                                                                                <iframe
                                                                                    src="{{ $media->external_url }}"
                                                                                    title="Video Vimeo"
                                                                                    allow="autoplay; fullscreen; picture-in-picture"
                                                                                    allowfullscreen>
                                                                                </iframe>

                                                                            </div>

                                                                        @endif

                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center gap-2">

                                                                            <small class="text-muted text-truncate">
                                                                                {{ ucfirst($media->type) }}
                                                                            </small>

                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-sm btn-outline-danger delete-option-media"
                                                                                data-media-id="{{ $media->id }}"
                                                                                data-url="{{ route('questions.option-media.destroy', ['media' => $media->id]) }}">

                                                                                <i class="bx bx-trash"></i>

                                                                            </button>

                                                                        </div>

                                                                    </div>

                                                                </div>

                                                            @endforeach

                                                        </div>

                                                    </div>

                                                @endif

                                                <div class="new-option-media-container">

                                                    <div class="option-media-items"></div>

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary add-option-media">

                                                        <i class="bx bx-image-add me-1"></i>
                                                        Tambah Media

                                                    </button>

                                                </div>

                                            </div>

                                            <div class="col-12">

                                                <div class="form-check">

                                                    <input
                                                        type="checkbox"
                                                        name="options[{{ $index }}][is_correct]"
                                                        value="1"
                                                        class="form-check-input correct-option"
                                                        @checked(old("options.{$index}.is_correct", $option->is_correct))>

                                                    <label class="form-check-label">
                                                        Jawaban benar
                                                    </label>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white p-4">

                            <h5 class="mb-1">
                                Media Soal
                            </h5>

                            <small class="text-muted">
                                Gambar, video, audio, dokumen, YouTube, Vimeo atau URL eksternal.
                            </small>

                        </div>

                        <div class="card-body p-4">

                            <div id="existing-media-container">

                                @foreach ($questionBank->media as $media)

                                    <div
                                        class="existing-media-item border rounded-3 p-3 mb-3"
                                        data-media-id="{{ $media->id }}">

                                        <div class="mb-3">

                                            @if ($media->type === 'image' && $media->path)

                                                <img
                                                    src="{{ asset('storage/' . ltrim($media->path, '/')) }}"
                                                    class="img-fluid rounded-3"
                                                    alt="{{ $media->original_name ?? 'Media soal' }}">

                                            @elseif ($media->type === 'video' && $media->path)

                                                <video
                                                    controls
                                                    preload="metadata"
                                                    class="w-100 rounded-3">

                                                    <source
                                                        src="{{ asset('storage/' . ltrim($media->path, '/')) }}"
                                                        type="{{ $media->mime_type }}">

                                                </video>

                                            @elseif ($media->type === 'audio' && $media->path)

                                                <audio
                                                    controls
                                                    class="w-100">

                                                    <source
                                                        src="{{ asset('storage/' . ltrim($media->path, '/')) }}"
                                                        type="{{ $media->mime_type }}">

                                                </audio>

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

                                                        <small class="text-muted">
                                                            {{ $media->mime_type ?? 'Dokumen' }}
                                                        </small>

                                                    </div>

                                                </div>

                                            @elseif ($media->source === 'youtube' && $media->external_url)

                                                @php
                                                    $youtubeId = null;

                                                    if (preg_match(
                                                        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?\/]+)/',
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
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                            allowfullscreen>
                                                        </iframe>

                                                    </div>

                                                @endif

                                            @elseif ($media->source === 'vimeo' && $media->external_url)

                                                <div class="ratio ratio-16x9">

                                                    <iframe
                                                        src="{{ $media->external_url }}"
                                                        title="Video Vimeo"
                                                        allow="autoplay; fullscreen; picture-in-picture"
                                                        allowfullscreen>
                                                    </iframe>

                                                </div>

                                            @elseif ($media->external_url)

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

                                                </div>

                                            @endif

                                        </div>

                                        <div class="d-flex justify-content-between align-items-center gap-2">

                                            <div>

                                                <div class="fw-semibold">
                                                    {{ ucfirst($media->type) }}
                                                </div>

                                                <small class="text-muted">
                                                    {{ ucfirst($media->source) }}
                                                </small>

                                            </div>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger delete-question-media"
                                                data-media-id="{{ $media->id }}"
                                                data-url="{{ route('questions.media.destroy', ['media' => $media->id]) }}">

                                                <i class="bx bx-trash"></i>

                                            </button>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                            <div id="media-container"></div>

                            <button
                                type="button"
                                class="btn btn-outline-primary w-100"
                                id="add-media">

                                <i class="bx bx-plus me-1"></i>
                                Tambah Media

                            </button>

                        </div>

                    </div>

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <div class="d-flex gap-2">

                                <a
                                    href="{{ route('questions.index') }}"
                                    class="btn btn-outline-secondary flex-fill">

                                    Batal

                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-primary flex-fill">

                                    <i class="bx bx-save me-1"></i>

                                    Simpan Perubahan

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let optionIndex = {{ $questionBank->options->count() }};
            let mediaIndex = 0;
            let optionMediaIndex = 0;

            const questionForm = document.getElementById('question-form');
            const optionsContainer = document.getElementById('options-container');
            const mediaContainer = document.getElementById('media-container');
            const existingMediaContainer = document.getElementById('existing-media-container');

            function getMediaAccept(type) {
                const accept = {
                    image: 'image/jpeg,image/png,image/webp',
                    video: 'video/mp4,video/webm,video/quicktime',
                    audio: 'audio/mpeg,audio/wav,audio/ogg',
                    document: 'application/pdf'
                };

                return accept[type] || '';
            }

            function updateMediaFileAccept(wrapper) {
                const type = wrapper.querySelector('.media-type');
                const file = wrapper.querySelector('.media-file');

                if (!type || !file) {
                    return;
                }

                file.accept = getMediaAccept(type.value);
            }

            function updateOptionNumbers() {
                if (document.getElementById('question-type')?.value === 'statement_true_false') {
                    return;
                }

                const items = optionsContainer.querySelectorAll('.option-item');

                items.forEach(function (item, index) {
                    const key = String.fromCharCode(65 + index);

                    const number = item.querySelector('.option-number');
                    const keyInput = item.querySelector('.option-key');

                    if (number) {
                        number.textContent = key;
                    }

                    if (keyInput) {
                        keyInput.value = key;
                    }
                });
            }

            function enforceCorrectOption(target) {
                if (!target.classList.contains('correct-option')) {
                    return;
                }

                if (['multiple_select', 'statement_true_false'].includes(document.getElementById('question-type')?.value)) {
                    return;
                }

                if (!target.checked) {
                    return;
                }

                optionsContainer
                    .querySelectorAll('.correct-option')
                    .forEach(function (checkbox) {
                        if (checkbox !== target) {
                            checkbox.checked = false;
                        }
                    });
            }

            function createOptionMedia(index) {
                const wrapper = document.createElement('div');

                wrapper.className = 'option-media-item border rounded-3 p-3 mb-3';

                wrapper.innerHTML = `
                    <div class="mb-3">

                        <label class="form-label">
                            Tipe Media
                        </label>

                        <select
                            name="options[${index}][media][${optionMediaIndex}][type]"
                            class="form-select option-media-type">

                            <option value="image">
                                Gambar
                            </option>

                            <option value="video">
                                Video
                            </option>

                            <option value="audio">
                                Audio
                            </option>

                            <option value="document">
                                Dokumen
                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Sumber
                        </label>

                        <select
                            name="options[${index}][media][${optionMediaIndex}][source]"
                            class="form-select option-media-source">

                            <option value="upload">
                                Upload
                            </option>

                            <option value="youtube">
                                YouTube
                            </option>

                            <option value="vimeo">
                                Vimeo
                            </option>

                            <option value="external">
                                External URL
                            </option>

                        </select>

                    </div>

                    <div class="option-media-upload mb-3">

                        <label class="form-label">
                            File
                        </label>

                        <input
                            type="file"
                            name="options[${index}][media][${optionMediaIndex}][file]"
                            class="form-control option-media-file">

                    </div>

                    <div class="option-media-url mb-3 d-none">

                        <label class="form-label">
                            URL
                        </label>

                        <input
                            type="url"
                            name="options[${index}][media][${optionMediaIndex}][external_url]"
                            class="form-control"
                            placeholder="https://">

                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger remove-option-media">

                        <i class="bx bx-trash me-1"></i>
                        Hapus Media

                    </button>
                `;

                optionMediaIndex++;

                return wrapper;
            }

            document.getElementById('add-option').addEventListener('click', function () {
                const index = optionIndex++;

                const wrapper = document.createElement('div');

                wrapper.className = 'option-item border rounded-3 p-3 mb-3';

                wrapper.innerHTML = `
                    <div class="row g-3">

                        <div class="col-12">

                            <div class="d-flex align-items-center justify-content-between gap-2">

                                <div class="d-flex align-items-center gap-2">

                                    <span class="badge bg-primary option-number">
                                        A
                                    </span>

                                    <span class="fw-semibold">
                                        Pilihan
                                    </span>

                                </div>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger remove-option">

                                    <i class="bx bx-trash"></i>

                                </button>

                            </div>

                            <input
                                type="hidden"
                                name="options[${index}][option_key]"
                                value="A"
                                class="option-key">

                        </div>

                        <div class="col-12">

                            <label class="form-label">
                                Teks Pilihan
                            </label>

                            <textarea
                                name="options[${index}][option_text]"
                                class="form-control"
                                rows="2"
                                placeholder="Kosongkan jika pilihan hanya berupa gambar"></textarea>

                        </div>

                        <div class="col-12">

                            <div class="new-option-media-container">

                                <div class="option-media-items"></div>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary add-option-media">

                                    <i class="bx bx-image-add me-1"></i>
                                    Tambah Media

                                </button>

                            </div>

                        </div>

                        <div class="col-12">

                            <div class="form-check">

                                <input
                                    type="checkbox"
                                    name="options[${index}][is_correct]"
                                    value="1"
                                    class="form-check-input correct-option">

                                <label class="form-check-label">
                                    Jawaban benar
                                </label>

                            </div>

                        </div>

                    </div>
                `;

                optionsContainer.appendChild(wrapper);

                updateOptionNumbers();
            });

            optionsContainer.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-option');

                if (removeButton) {
                    const items = optionsContainer.querySelectorAll('.option-item');

                    if (items.length <= 2) {
                        return;
                    }

                    removeButton.closest('.option-item').remove();

                    updateOptionNumbers();

                    return;
                }

                const addMediaButton = event.target.closest('.add-option-media');

                if (addMediaButton) {
                    const optionItem = addMediaButton.closest('.option-item');
                    const optionKeyInput = optionItem.querySelector('.option-key');
                    const optionIdInput = optionItem.querySelector('.option-id');
                    const mediaItems = optionItem.querySelector('.option-media-items');

                    const optionIndexValue = optionIdInput
                        ? optionIdInput.name.match(/options\[(\d+)\]/)?.[1]
                        : optionKeyInput.name.match(/options\[(\d+)\]/)?.[1];

                    if (optionIndexValue === undefined) {
                        return;
                    }

                    const media = createOptionMedia(optionIndexValue);

                    mediaItems.appendChild(media);

                    const file = media.querySelector('.option-media-file');

                    if (file) {
                        file.accept = getMediaAccept(
                            media.querySelector('.option-media-type').value
                        );
                    }

                    return;
                }

                const removeOptionMediaButton = event.target.closest('.remove-option-media');

                if (removeOptionMediaButton) {
                    removeOptionMediaButton
                        .closest('.option-media-item')
                        .remove();
                }
            });

            optionsContainer.addEventListener('change', function (event) {
                if (event.target.classList.contains('correct-option')) {
                    enforceCorrectOption(event.target);
                }

                if (event.target.classList.contains('option-media-type')) {
                    const wrapper = event.target.closest('.option-media-item');
                    const file = wrapper.querySelector('.option-media-file');

                    if (file) {
                        file.accept = getMediaAccept(event.target.value);
                    }
                }

                if (event.target.classList.contains('option-media-source')) {
                    const wrapper = event.target.closest('.option-media-item');

                    const upload = wrapper.querySelector('.option-media-upload');
                    const url = wrapper.querySelector('.option-media-url');

                    if (event.target.value === 'upload') {
                        upload.classList.remove('d-none');
                        url.classList.add('d-none');
                    } else {
                        upload.classList.add('d-none');
                        url.classList.remove('d-none');
                    }
                }

                if (event.target.classList.contains('option-media-file')) {
                    const input = event.target;

                    if (!input.files || !input.files[0]) {
                        return;
                    }

                    const file = input.files[0];

                    if (file.size > 50 * 1024 * 1024) {
                        input.value = '';
                        alert('Ukuran file maksimal 50 MB.');
                    }
                }
            });

            document.getElementById('add-media').addEventListener('click', function () {
                const index = mediaIndex++;

                const wrapper = document.createElement('div');

                wrapper.className = 'media-item border rounded-3 p-3 mb-3';

                wrapper.innerHTML = `
                    <div class="mb-3">

                        <label class="form-label">
                            Tipe Media
                        </label>

                        <select
                            name="question_media[${index}][type]"
                            class="form-select media-type">

                            <option value="image">
                                Gambar
                            </option>

                            <option value="video">
                                Video
                            </option>

                            <option value="audio">
                                Audio
                            </option>

                            <option value="document">
                                Dokumen
                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Sumber
                        </label>

                        <select
                            name="question_media[${index}][source]"
                            class="form-select media-source">

                            <option value="upload">
                                Upload
                            </option>

                            <option value="youtube">
                                YouTube
                            </option>

                            <option value="vimeo">
                                Vimeo
                            </option>

                            <option value="external">
                                External URL
                            </option>

                        </select>

                    </div>

                    <div class="media-upload mb-3">

                        <label class="form-label">
                            File
                        </label>

                        <input
                            type="file"
                            name="question_media[${index}][file]"
                            class="form-control media-file">

                    </div>

                    <div class="media-url mb-3 d-none">

                        <label class="form-label">
                            URL
                        </label>

                        <input
                            type="url"
                            name="question_media[${index}][external_url]"
                            class="form-control"
                            placeholder="https://">

                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger remove-media">

                        <i class="bx bx-trash me-1"></i>
                        Hapus Media

                    </button>
                `;

                mediaContainer.appendChild(wrapper);

                updateMediaFileAccept(wrapper);
            });

            mediaContainer.addEventListener('change', function (event) {
                const wrapper = event.target.closest('.media-item');

                if (!wrapper) {
                    return;
                }

                if (event.target.classList.contains('media-type')) {
                    updateMediaFileAccept(wrapper);
                }

                if (event.target.classList.contains('media-source')) {
                    const upload = wrapper.querySelector('.media-upload');
                    const url = wrapper.querySelector('.media-url');

                    if (event.target.value === 'upload') {
                        upload.classList.remove('d-none');
                        url.classList.add('d-none');
                    } else {
                        upload.classList.add('d-none');
                        url.classList.remove('d-none');
                    }
                }

                if (event.target.classList.contains('media-file')) {
                    const input = event.target;

                    if (!input.files || !input.files[0]) {
                        return;
                    }

                    const file = input.files[0];

                    if (file.size > 50 * 1024 * 1024) {
                        input.value = '';
                        alert('Ukuran file maksimal 50 MB.');
                    }
                }
            });

            mediaContainer.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-media');

                if (!button) {
                    return;
                }

                button.closest('.media-item').remove();
            });

            async function deleteMedia(button) {
                const url = button.dataset.url;
                const mediaItem = button.closest(
                    '.existing-media-item, .existing-option-media'
                );

                if (!url || !mediaItem) {
                    return;
                }

                const confirmed = confirm('Hapus media ini?');

                if (!confirmed) {
                    return;
                }

                button.disabled = true;

                try {
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Gagal menghapus media.');
                    }

                    mediaItem.remove();

                } catch (error) {
                    button.disabled = false;
                    alert(error.message);
                }
            }

            existingMediaContainer.addEventListener('click', function (event) {
                const button = event.target.closest('.delete-question-media');

                if (!button) {
                    return;
                }

                deleteMedia(button);
            });

            optionsContainer.addEventListener('click', function (event) {
                const button = event.target.closest('.delete-option-media');

                if (!button) {
                    return;
                }

                deleteMedia(button);
            });

            const questionEditor = document.getElementById('question-editor');
            const questionContent = document.getElementById('question-content');

            function syncQuestionContent() {
                questionContent.value = questionEditor.innerHTML.trim();
            }

            questionEditor.addEventListener('input', syncQuestionContent);

            document.querySelectorAll('.insert-statement-table').forEach(function (button) {
                button.addEventListener('click', function () {
                    questionEditor.focus();
                    const table = `<table><thead><tr><th>PERNYATAAN</th><th>BENAR</th><th>SALAH</th></tr></thead><tbody><tr><td>Pernyataan 1</td><td></td><td></td></tr><tr><td>Pernyataan 2</td><td></td><td></td></tr><tr><td>Pernyataan 3</td><td></td><td></td></tr></tbody></table><p></p>`;
                    document.execCommand('insertHTML', false, table);
                    syncQuestionContent();
                });
            });

            questionForm.addEventListener('submit', function (event) {
                syncQuestionContent();
                if (!questionEditor.textContent.trim() && !questionEditor.querySelector('img, table')) {
                    event.preventDefault();
                    questionEditor.classList.add('is-invalid');
                    questionEditor.focus();
                    alert('Pertanyaan wajib diisi.');
                    return;
                }
                const submitButton = questionForm.querySelector(
                    'button[type="submit"]'
                );

                if (!submitButton) {
                    return;
                }

                submitButton.disabled = true;

                submitButton.innerHTML = `
                    <span
                        class="spinner-border spinner-border-sm me-1"
                        role="status"
                        aria-hidden="true">
                    </span>
                    Menyimpan...
                `;
            });
        });
    </script>
@endsection
