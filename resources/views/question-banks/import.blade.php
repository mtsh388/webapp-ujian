@extends('template.app')

@section('title', 'Import Bank Soal')

@section('content')
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

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">

                <div class="fw-semibold mb-2">
                    Terdapat kesalahan:
                </div>

                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white border-0 p-4">

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>
                        <h4 class="mb-1">
                            Import Bank Soal
                        </h4>

                        <p class="text-muted mb-0">
                            Import soal pilihan ganda dan benar/salah dari Microsoft Word.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                    <a
                        href="{{ route('questions.import.template') }}"
                        class="btn btn-outline-primary">
                        <i class="bx bx-download me-1"></i>
                        Download Template DOCX
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

                        <div class="card border-0 bg-light">

                            <div class="card-header bg-transparent border-0 p-4">

                                <h5 class="mb-1">
                                    Import File Word
                                </h5>

                                <p class="text-muted mb-0">
                                    Upload file DOCX untuk membuat bank soal secara otomatis.
                                </p>

                            </div>

                            <div class="card-body p-4">

                                <form
                                    action="{{ route('questions.import.preview') }}"
                                    method="POST"
                                    enctype="multipart/form-data">

                                    @csrf

                                    <div class="mb-4">

                                        <label
                                            for="subject_id"
                                            class="form-label">

                                            Mata Pelajaran

                                        </label>

                                        <select
                                            name="subject_id"
                                            id="subject_id"
                                            class="form-select @error('subject_id') is-invalid @enderror"
                                            required>

                                            <option value="">
                                                Pilih Mata Pelajaran
                                            </option>

                                            @foreach ($subjects as $subject)

                                                <option
                                                    value="{{ $subject->id }}"
                                                    @selected(old('subject_id') == $subject->id)>

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

                                    <div class="row g-3">

                                        <div class="col-md-6">

                                            <div class="mb-4">

                                                <label
                                                    for="difficulty"
                                                    class="form-label">

                                                    Tingkat Kesulitan

                                                </label>

                                                <select
                                                    name="difficulty"
                                                    id="difficulty"
                                                    class="form-select @error('difficulty') is-invalid @enderror"
                                                    required>

                                                    <option value="">
                                                        Pilih Tingkat Kesulitan
                                                    </option>

                                                    <option
                                                        value="easy"
                                                        @selected(old('difficulty') === 'easy')>
                                                        Mudah
                                                    </option>

                                                    <option
                                                        value="medium"
                                                        @selected(old('difficulty', 'medium') === 'medium')>
                                                        Sedang
                                                    </option>

                                                    <option
                                                        value="hard"
                                                        @selected(old('difficulty') === 'hard')>
                                                        Sulit
                                                    </option>

                                                </select>

                                                @error('difficulty')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                                @enderror

                                            </div>

                                        </div>

                                        <div class="col-md-6">

                                            <div class="mb-4">

                                                <label
                                                    for="default_score"
                                                    class="form-label">

                                                    Nilai Default

                                                </label>

                                                <input
                                                    type="number"
                                                    name="default_score"
                                                    id="default_score"
                                                    class="form-control @error('default_score') is-invalid @enderror"
                                                    value="{{ old('default_score', 1) }}"
                                                    min="0"
                                                    max="999999.99"
                                                    step="0.01"
                                                    required>

                                                @error('default_score')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                                @enderror

                                            </div>

                                        </div>

                                    </div>

                                    <div class="mb-4">

                                        <label
                                            for="file"
                                            class="form-label">

                                            File Word

                                        </label>

                                        <input
                                            type="file"
                                            name="file"
                                            id="file"
                                            class="form-control @error('file') is-invalid @enderror"
                                            accept=".docx"
                                            required>

                                        <div class="form-text">
                                            Format yang diperbolehkan: DOCX.
                                            Maksimal 10 MB.
                                        </div>

                                        @error('file')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                    <div class="border-top pt-4">

                                        <div class="d-flex justify-content-end gap-2">

                                            <a
                                                href="{{ route('questions.index') }}"
                                                class="btn btn-outline-secondary">

                                                Batal

                                            </a>

                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                id="btn-import">

                                                <i class="bx bx-upload me-1"></i>
                                                Import Bank Soal

                                            </button>

                                        </div>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card border-0 bg-light mb-4">

                            <div class="card-header bg-transparent border-0 p-4">

                                <h5 class="mb-1">
                                    Format DOCX
                                </h5>

                                <p class="text-muted mb-0">
                                    Gunakan struktur berikut pada file Word.
                                </p>

                            </div>

                            <div class="card-body p-4">

                                <div class="alert alert-primary mb-4">

                                    <div class="fw-semibold mb-2">
                                        Dokumen Word asli dapat langsung diunggah.
                                    </div>

                                    <div>
                                        Importer mencoba mengenali format soal TKA tanpa meminta Anda mengubah dokumen. Format template SOAL/JAWABAN lama juga tetap didukung. Selalu periksa preview sebelum konfirmasi.
                                    </div>

                                </div>

                                <div class="mb-4">

                                    <div class="fw-semibold mb-2">
                                        Pilihan Ganda
                                    </div>

                                    <pre class="bg-white rounded p-3 mb-0 border"><code>SOAL 1 [PILIHAN_GANDA]

Pertanyaan...

A. Pilihan A
B. Pilihan B
C. Pilihan C
D. Pilihan D

JAWABAN: B

PEMBAHASAN:
Pembahasan...</code></pre>

                                </div>

                                <div class="mb-4">

                                    <div class="fw-semibold mb-2">
                                        Benar / Salah
                                    </div>

                                    <pre class="bg-white rounded p-3 mb-0 border"><code>SOAL 2 [BENAR_SALAH]

Indonesia berada di Asia Tenggara.

JAWABAN: A

PEMBAHASAN:
Pernyataan benar.</code></pre>

                                </div>

                                <div>

                                    <div class="fw-semibold mb-2">
                                        Mixed Type
                                    </div>

                                    <pre class="bg-white rounded p-3 mb-0 border"><code>SOAL 1 [PILIHAN_GANDA]
...

SOAL 2 [BENAR_SALAH]
...

SOAL 3 [PILIHAN_GANDA]
...</code></pre>

                                </div>

                            </div>

                        </div>

                        <div class="card border-0 bg-light">

                            <div class="card-header bg-transparent border-0 p-4">

                                <h5 class="mb-0">
                                    Aturan Import
                                </h5>

                            </div>

                            <div class="card-body p-4">

                                <ul class="mb-0 ps-3">

                                    <li class="mb-2">
                                        Setiap soal wajib memiliki header.
                                    </li>

                                    <li class="mb-2">
                                        Gunakan
                                        <code>[PILIHAN_GANDA]</code>
                                        atau
                                        <code>[BENAR_SALAH]</code>.
                                    </li>

                                    <li class="mb-2">
                                        Pilihan ganda wajib memiliki A-D.
                                    </li>

                                    <li class="mb-2">
                                        Benar/Salah menggunakan A = Benar dan B = Salah.
                                    </li>

                                    <li class="mb-2">
                                        Gambar dapat dimasukkan langsung ke Word.
                                    </li>

                                    <li>
                                        Pembahasan bersifat opsional.
                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector(
                'form[action="{{ route('questions.import.preview') }}"]'
            );

            const button = document.getElementById('btn-import');

            if (!form || !button) {
                return;
            }

            form.addEventListener('submit', function () {
                button.disabled = true;

                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>' +
                    'Memproses...';
            });
        });
    </script>
@endsection
