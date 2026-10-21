@extends('template.app')

@section('title', 'Buat Ujian')

@section('content')
    <style>
        .subject-picker-card {
            border: 1px solid #e7e7ef;
            border-radius: .75rem;
            padding: 1.1rem;
            background: linear-gradient(145deg, #fff 0%, #fafbff 100%);
        }
        .subject-picker-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
            border-radius: .7rem;
            color: #696cff;
            background: rgba(105, 108, 255, .12);
            font-size: 1.5rem;
        }
        .subject-selected-preview {
            padding: .75rem .85rem;
            border: 1px solid rgba(113, 221, 55, .3);
            border-radius: .55rem;
            background: rgba(113, 221, 55, .08);
        }
        @media (max-width: 575.98px) {
            .subject-picker-card { padding: .85rem; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector = document.getElementById('has_duration');
            const examTypeSelector = document.getElementById('exam_type');
            const passingScore = document.getElementById('passing_score');
            const passingScoreLabel = document.querySelector('label[for="passing_score"]');
            const fields = document.querySelectorAll('.duration-settings');

            function toggleDurationFields() {
                const enabled = selector.value === '1';

                fields.forEach(function (field) {
                    field.classList.toggle('d-none', !enabled);
                    field.querySelectorAll('input').forEach(function (input) {
                        input.required = enabled;
                        input.disabled = !enabled;
                    });
                });
            }

            function togglePassingScore() {
                const required = examTypeSelector.value === 'ujian';
                passingScore.required = required;
                passingScoreLabel.innerHTML = required
                    ? 'Nilai KKM / Lulus <span class="text-danger">*</span>'
                    : 'Nilai KKM / Lulus <span class="text-muted">(Opsional untuk Trial)</span>';
            }

            selector.addEventListener('change', toggleDurationFields);
            examTypeSelector.addEventListener('change', togglePassingScore);
            toggleDurationFields();
            togglePassingScore();
        });
    </script>
    <div class="container-xxl flex-grow-1 container-p-y">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bx bx-error-circle me-2 fs-4"></i>
                    <div>{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bx bx-error-circle me-2 fs-4"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Ujian gagal disimpan. Periksa kembali data berikut:</h6>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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
                            <div class="subject-picker-card h-100">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="subject-picker-icon">
                                        <i class="bx bx-book-open"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <label for="subject_id" class="form-label fw-semibold mb-1">
                                            Mata Pelajaran <span class="text-danger">*</span>
                                        </label>
                                        <div class="small text-muted">Pilih mata pelajaran untuk ujian ini.</div>
                                    </div>
                                    <span class="badge bg-label-primary">{{ $subjects->count() }} tersedia</span>
                                </div>

                                <select class="form-select @error('subject_id') is-invalid @enderror"
                                        id="subject_id" name="subject_id" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($subjects as $subject)
                                        <option
                                            value="{{ $subject->id }}"
                                            {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }} - Kelas {{ $subject->grade_level ?? '-' }}@if ($subject->topic) - {{ $subject->topic }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div id="subject-selected-preview" class="subject-selected-preview mt-3 d-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bx bx-check-circle text-success fs-4"></i>
                                        <div class="min-w-0">
                                            <div class="small text-muted">Mata pelajaran terpilih</div>
                                            <div class="fw-semibold text-break" id="subject-selected-name"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

                        <div class="col-md-6 mb-3">
                            <label for="exam_type" class="form-label">Jenis Ujian <span class="text-danger">*</span></label>
                            <select class="form-select @error('exam_type') is-invalid @enderror" id="exam_type" name="exam_type" required>
                                <option value="trial" {{ old('exam_type', 'trial') === 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="ujian" {{ old('exam_type') === 'ujian' ? 'selected' : '' }}>Ujian</option>
                            </select>
                            @error('exam_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="has_duration" class="form-label">Pengaturan Durasi <span class="text-danger">*</span></label>
                            <select class="form-select @error('has_duration') is-invalid @enderror" id="has_duration" name="has_duration" required>
                                <option value="1" {{ old('has_duration', '1') === '1' ? 'selected' : '' }}>Menggunakan durasi dan jadwal</option>
                                <option value="0" {{ old('has_duration') === '0' ? 'selected' : '' }}>Tanpa durasi dan jadwal</option>
                            </select>
                            @error('has_duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pengaturan Waktu & Nilai -->
                        <div class="col-md-4 mb-3 duration-settings">
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
                                   value="{{ old('passing_score') }}" min="0" max="100">
                            @error('passing_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3 duration-settings">
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

                        <div class="col-md-6 mb-3 duration-settings">
                            <label for="start_at" class="form-label">Waktu Mulai <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local"
                                   class="form-control @error('start_at') is-invalid @enderror" id="start_at"
                                   name="start_at" value="{{ old('start_at') }}" required>
                            @error('start_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3 duration-settings">
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
