@extends('template.app')

@section('title', 'Tambah Bank Soal')

@section('content')
    <div class="container-fluid py-3">

        <form
            action="{{ route('questions.store') }}"
            method="POST"
            enctype="multipart/form-data"
            id="question-form">

            @csrf
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bx bx-error-circle fs-4"></i>

                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-2">
                                Terdapat kesalahan pada data yang dikirim.
                            </div>

                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white p-4">
                            <h5 class="mb-0">Informasi Soal</h5>
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
                                                @selected(old('subject_id') == $subject->id)>
                                                {{ $subject->name }}
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
                                    <div class="form-control bg-lighter text-muted">Dibuat otomatis berdasarkan mata pelajaran, jenjang, dan topik.</div>
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

                                        <option value="multiple_choice">
                                            Pilihan Ganda
                                        </option>

                                        <option value="true_false">
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

                                        <option value="easy">
                                            Mudah
                                        </option>

                                        <option value="medium" selected>
                                            Sedang
                                        </option>

                                        <option value="hard">
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
                                        value="{{ old('default_score', 1) }}"
                                        min="0"
                                        step="0.01"
                                        required>
                                </div>

                                <div class="col-12">

                                    <label class="form-label">
                                        Pertanyaan
                                    </label>

                                    <textarea
                                        name="question"
                                        class="form-control"
                                        rows="7"
                                        required>{{ old('question') }}</textarea>

                                </div>

                                <div class="col-12">

                                    <label class="form-label">
                                        Pembahasan
                                    </label>

                                    <textarea
                                        name="explanation"
                                        class="form-control"
                                        rows="5">{{ old('explanation') }}</textarea>

                                </div>

                                <div class="col-12">

                                    <div class="form-check form-switch">

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            class="form-check-input"
                                            @checked(old('is_active', true))>

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
                                    Setiap pilihan dapat menggunakan teks dan gambar.
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

                                @foreach (['A', 'B', 'C', 'D'] as $index => $key)

                                    <div
                                        class="option-item border rounded-3 p-3 mb-3"
                                        data-option-index="{{ $index }}">

                                        <div class="d-flex justify-content-between align-items-center mb-3">

                                            <div class="d-flex align-items-center gap-2">

                                                <span class="badge bg-primary option-number">
                                                    {{ $key }}
                                                </span>

                                                <span class="fw-semibold">
                                                    Pilihan {{ $key }}
                                                </span>

                                            </div>

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="form-check mb-0">

                                                    <input
                                                        type="checkbox"
                                                        name="options[{{ $index }}][is_correct]"
                                                        value="1"
                                                        class="form-check-input correct-option"
                                                        id="correct-{{ $index }}">

                                                    <label
                                                        class="form-check-label"
                                                        for="correct-{{ $index }}">
                                                        Jawaban benar
                                                    </label>

                                                </div>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger remove-option">

                                                    <i class="bx bx-trash"></i>

                                                </button>

                                            </div>

                                        </div>

                                        <input
                                            type="hidden"
                                            name="options[{{ $index }}][option_key]"
                                            value="{{ $key }}">

                                        <div class="mb-3">

                                            <label class="form-label">
                                                Teks Jawaban
                                            </label>

                                            <textarea
                                                name="options[{{ $index }}][option_text]"
                                                class="form-control"
                                                rows="2"
                                                placeholder="Isi pilihan jawaban">{{ old("options.{$index}.option_text") }}</textarea>

                                        </div>

                                        <div>

                                            <label class="form-label">
                                                Gambar Jawaban
                                            </label>

                                            <input
                                                type="hidden"
                                                name="options[{{ $index }}][media][0][type]"
                                                value="image">

                                            <input
                                                type="hidden"
                                                name="options[{{ $index }}][media][0][source]"
                                                value="upload">

                                            <input
                                                type="file"
                                                name="options[{{ $index }}][media][0][file]"
                                                class="form-control option-image-input"
                                                accept="image/jpeg,image/png,image/webp">

                                            <div class="form-text">
                                                JPG, JPEG, PNG, atau WebP. Maksimal 5 MB.
                                            </div>

                                            <div
                                                class="option-image-preview d-none mt-3">

                                                <div class="position-relative d-inline-block">

                                                    <img
                                                        src=""
                                                        alt="Preview gambar"
                                                        class="img-fluid rounded-3 border option-preview-image"
                                                        style="max-height: 180px; max-width: 100%;">

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 remove-option-image">

                                                        <i class="bx bx-x"></i>

                                                    </button>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                            <div
                                id="option-empty-state"
                                class="text-center text-muted py-4 d-none">

                                <i class="bx bx-list-ul fs-1 d-block mb-2"></i>

                                <div>
                                    Belum ada pilihan jawaban.
                                </div>

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
                                Gambar, video, audio atau dokumen.
                            </small>

                        </div>

                        <div class="card-body p-4">

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

                                    Simpan
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
            let optionIndex = 4;
            let mediaIndex = 0;

            const optionsContainer = document.getElementById('options-container');
            const mediaContainer = document.getElementById('media-container');
            const addOptionButton = document.getElementById('add-option');
            const addMediaButton = document.getElementById('add-media');
            const optionEmptyState = document.getElementById('option-empty-state');

            function updateOptionState() {
                const options = optionsContainer.querySelectorAll('.option-item');

                optionEmptyState.classList.toggle(
                    'd-none',
                    options.length > 0
                );

                options.forEach(function (option, index) {
                    const number = option.querySelector('.option-number');
                    const keyInput = option.querySelector(
                        'input[name*="[option_key]"]'
                    );

                    const key = String.fromCharCode(65 + index);

                    if (number) {
                        number.textContent = key;
                    }

                    if (keyInput) {
                        keyInput.value = key;
                    }
                });
            }

            function createOption(index, key) {
                const wrapper = document.createElement('div');

                wrapper.className = 'option-item border rounded-3 p-3 mb-3';

                wrapper.dataset.optionIndex = index;

                wrapper.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <div class="d-flex align-items-center gap-2">

                            <span class="badge bg-primary option-number">
                                ${key}
                            </span>

                            <span class="fw-semibold">
                                Pilihan ${key}
                            </span>

                        </div>

                        <div class="d-flex align-items-center gap-3">

                            <div class="form-check mb-0">

                                <input
                                    type="checkbox"
                                    name="options[${index}][is_correct]"
                                    value="1"
                                    class="form-check-input correct-option"
                                    id="correct-${index}">

                                <label
                                    class="form-check-label"
                                    for="correct-${index}">
                                    Jawaban benar
                                </label>

                            </div>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger remove-option">

                                <i class="bx bx-trash"></i>

                            </button>

                        </div>

                    </div>

                    <input
                        type="hidden"
                        name="options[${index}][option_key]"
                        value="${key}">

                    <div class="mb-3">

                        <label class="form-label">
                            Teks Jawaban
                        </label>

                        <textarea
                            name="options[${index}][option_text]"
                            class="form-control"
                            rows="2"
                            placeholder="Isi pilihan jawaban"></textarea>

                    </div>

                    <div>

                        <label class="form-label">
                            Gambar Jawaban
                        </label>

                        <input
                            type="hidden"
                            name="options[${index}][media][0][type]"
                            value="image">

                        <input
                            type="hidden"
                            name="options[${index}][media][0][source]"
                            value="upload">

                        <input
                            type="file"
                            name="options[${index}][media][0][file]"
                            class="form-control option-image-input"
                            accept="image/jpeg,image/png,image/webp">

                        <div class="form-text">
                            JPG, JPEG, PNG, atau WebP. Maksimal 5 MB.
                        </div>

                        <div class="option-image-preview d-none mt-3">

                            <div class="position-relative d-inline-block">

                                <img
                                    src=""
                                    alt="Preview gambar"
                                    class="img-fluid rounded-3 border option-preview-image"
                                    style="max-height: 180px; max-width: 100%;">

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 remove-option-image">

                                    <i class="bx bx-x"></i>

                                </button>

                            </div>

                        </div>

                    </div>
                `;

                return wrapper;
            }

            addOptionButton.addEventListener('click', function () {
                const key = String.fromCharCode(65 + optionIndex);

                const option = createOption(optionIndex, key);

                optionsContainer.appendChild(option);

                optionIndex++;

                updateOptionState();
            });

            optionsContainer.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-option');

                if (!removeButton) {
                    return;
                }

                const options = optionsContainer.querySelectorAll('.option-item');

                if (options.length <= 2) {
                    return;
                }

                const option = removeButton.closest('.option-item');

                if (option) {
                    option.remove();
                }

                updateOptionState();
            });

            optionsContainer.addEventListener('change', function (event) {
                if (event.target.classList.contains('correct-option')) {
                    optionsContainer
                        .querySelectorAll('.correct-option')
                        .forEach(function (checkbox) {
                            if (checkbox !== event.target) {
                                checkbox.checked = false;
                            }
                        });
                }

                if (event.target.classList.contains('option-image-input')) {
                    const input = event.target;
                    const wrapper = input.closest('.option-item');
                    const preview = wrapper.querySelector(
                        '.option-image-preview'
                    );
                    const image = wrapper.querySelector(
                        '.option-preview-image'
                    );

                    if (!input.files || !input.files[0]) {
                        preview.classList.add('d-none');
                        image.removeAttribute('src');

                        return;
                    }

                    const file = input.files[0];

                    if (!file.type.startsWith('image/')) {
                        input.value = '';
                        preview.classList.add('d-none');
                        image.removeAttribute('src');

                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        input.value = '';
                        preview.classList.add('d-none');
                        image.removeAttribute('src');

                        alert('Ukuran gambar maksimal 5 MB.');

                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function (readerEvent) {
                        image.src = readerEvent.target.result;
                        preview.classList.remove('d-none');
                    };

                    reader.readAsDataURL(file);
                }
            });

            optionsContainer.addEventListener('click', function (event) {
                const removeImageButton = event.target.closest(
                    '.remove-option-image'
                );

                if (!removeImageButton) {
                    return;
                }

                const wrapper = removeImageButton.closest('.option-item');

                const input = wrapper.querySelector(
                    '.option-image-input'
                );

                const preview = wrapper.querySelector(
                    '.option-image-preview'
                );

                const image = wrapper.querySelector(
                    '.option-preview-image'
                );

                input.value = '';

                image.removeAttribute('src');

                preview.classList.add('d-none');
            });

            addMediaButton.addEventListener('click', function () {
                const index = mediaIndex++;

                const wrapper = document.createElement('div');

                wrapper.className =
                    'media-item border rounded-3 p-3 mb-3';

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
                            class="form-control">

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
            });

            mediaContainer.addEventListener('change', function (event) {
                if (!event.target.classList.contains('media-source')) {
                    return;
                }

                const wrapper = event.target.closest('.media-item');

                const upload = wrapper.querySelector('.media-upload');
                const url = wrapper.querySelector('.media-url');

                if (event.target.value === 'upload') {
                    upload.classList.remove('d-none');
                    url.classList.add('d-none');
                } else {
                    upload.classList.add('d-none');
                    url.classList.remove('d-none');
                }
            });

            mediaContainer.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-media');

                if (!button) {
                    return;
                }

                const wrapper = button.closest('.media-item');

                if (wrapper) {
                    wrapper.remove();
                }
            });

            updateOptionState();
        });
    </script>
@endsection
