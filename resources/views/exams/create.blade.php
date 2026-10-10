@extends('template.app')

@section('title', 'Buat Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Breadcrumb / Header Title opsional jika ingin tetap dipertahankan,
             namun karena card-header sudah lengkap, ini bisa dihilangkan agar persis index -->

        <div class="card">
            <!-- Card Header ala Index -->
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">
                            Buat Ujian Baru
                        </h4>
                        <p class="card-subtitle text-muted mb-0">
                            Silakan isi formulir di bawah ini untuk menambahkan jadwal dan konfigurasi ujian.
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                        <a href="{{ route('exams.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>
                            Kembali
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Info / Alert ala Index -->
            <div class="card-body border-bottom py-3 bg-lighter">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-edit"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">
                                    Formulir
                                </small>
                                <span class="fw-semibold">
                                    Data Ujian
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8">
                        <div class="alert alert-label-primary mb-0 py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle me-2"></i>
                                <small>
                                    Tanda <span class="text-danger fw-bold">*</span> wajib diisi. Pastikan Anda mengatur
                                    waktu ujian dan KKM dengan benar.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <form action="{{ route('exams.store') }}" method="POST">
                @csrf
                <div class="card-body pt-4">
                    <div class="row">
                        <!-- Informasi Dasar -->
                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select class="form-select @error('subject_id') is-invalid @enderror"
                                    id="subject_id" name="subject_id" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($subjects as $subject)
                                    <option
                                        value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }} ({{ $subject->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Judul Ujian <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}"
                                   placeholder="Contoh: Ujian Tengah Semester" required>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Deskripsi / Petunjuk</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="Masukkan petunjuk ujian...">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pengaturan Waktu & Nilai -->
                        <div class="col-md-4 mb-3">
                            <label for="duration_minutes" class="form-label">Durasi (Menit) <span
                                    class="text-danger">*</span></label>
                            <input type="number"
                                   class="form-control @error('duration_minutes') is-invalid @enderror"
                                   id="duration_minutes" name="duration_minutes"
                                   value="{{ old('duration_minutes', 60) }}" min="1" required>
                            @error('duration_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="passing_score" class="form-label">Nilai KKM / Lulus <span
                                    class="text-danger">*</span></label>
                            <input type="number" step="0.01"
                                   class="form-control @error('passing_score') is-invalid @enderror"
                                   id="passing_score" name="passing_score"
                                   value="{{ old('passing_score', 75.00) }}" required>
                            @error('passing_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="max_attempts" class="form-label">Batas Percobaan <span
                                    class="text-danger">*</span></label>
                            <input type="number"
                                   class="form-control @error('max_attempts') is-invalid @enderror"
                                   id="max_attempts" name="max_attempts" value="{{ old('max_attempts', 1) }}"
                                   min="1" required>
                            @error('max_attempts')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="start_at" class="form-label">Waktu Mulai <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local"
                                   class="form-control @error('start_at') is-invalid @enderror" id="start_at"
                                   name="start_at" value="{{ old('start_at') }}" required>
                            @error('start_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_at" class="form-label">Waktu Berakhir <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local"
                                   class="form-control @error('end_at') is-invalid @enderror" id="end_at"
                                   name="end_at" value="{{ old('end_at') }}" required>
                            @error('end_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status & Checkbox Pengaturan -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status Ujian</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status"
                                    name="status">
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft
                                </option>
                                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>
                                    Published
                                </option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3 mt-2">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="shuffle_questions"
                                       name="shuffle_questions"
                                       value="1" {{ old('shuffle_questions', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="shuffle_questions">Acak Urutan Soal</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="shuffle_options"
                                       name="shuffle_options"
                                       value="1" {{ old('shuffle_options', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="shuffle_options">Acak Pilihan Ganda
                                    (Opsi)</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="show_result_immediately"
                                       name="show_result_immediately"
                                       value="1" {{ old('show_result_immediately', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_result_immediately">Tampilkan Nilai
                                    Langsung Setelah Selesai</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="show_correct_answers"
                                       name="show_correct_answers"
                                       value="1" {{ old('show_correct_answers', false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_correct_answers">Tampilkan Kunci
                                    Jawaban Setelah Selesai</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end border-top py-3">
                    <a href="{{ route('exams.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan Ujian
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection
