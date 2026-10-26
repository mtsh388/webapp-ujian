@extends('template.app')

@section('title', 'Edit Ujian')

@section('content')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector = document.getElementById('schedule_mode');
            const examTypeSelector = document.getElementById('exam_type');
            const passingScore = document.getElementById('passing_score');
            const passingScoreLabel = document.querySelector('label[for="passing_score"]');
            const fields = document.querySelectorAll('.duration-settings');
            const scheduleFields = document.querySelectorAll('.schedule-settings');

            function toggleDurationFields() {
                const durationEnabled = selector.value !== 'no_duration';
                const scheduleEnabled = selector.value === 'scheduled';

                fields.forEach(function (field) {
                    field.classList.toggle('d-none', !durationEnabled);
                    field.querySelectorAll('input').forEach(function (input) {
                        input.required = durationEnabled;
                        input.disabled = !durationEnabled;
                    });
                });

                scheduleFields.forEach(function (field) {
                    field.classList.toggle('d-none', !scheduleEnabled);
                    field.querySelectorAll('input').forEach(function (input) {
                        input.required = scheduleEnabled;
                        input.disabled = !scheduleEnabled;
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

            if (window.jQuery) {
                jQuery(selector).on('change.examSettings', toggleDurationFields);
                jQuery(examTypeSelector).on('change.examSettings', togglePassingScore);
            } else {
                selector.addEventListener('change', toggleDurationFields);
                examTypeSelector.addEventListener('change', togglePassingScore);
            }
            toggleDurationFields();
            togglePassingScore();
        });
    </script>
    <div class="container-xxl flex-grow-1 container-p-y">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>
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
                        <h6 class="alert-heading mb-1">Periksa kembali formulir Anda</h6>

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

        <form action="{{ route('exams.update', $exam) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">

                <div class="card-header border-bottom">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                        <div>
                            <h4 class="card-title mb-1">
                                Edit Ujian
                            </h4>

                            <p class="card-subtitle text-muted mb-0">
                                Perbarui informasi, jadwal, status, dan konfigurasi ujian.
                            </p>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">

                            <a href="{{ route('exams.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>
                                Kembali
                            </a>

                            <a href="{{ route('exams.questions', $exam) }}" class="btn btn-outline-primary">
                                <i class="bx bx-list-check me-1"></i>
                                Kelola Soal
                            </a>

                        </div>
                    </div>
                </div>

                <div class="card-body border-bottom py-3">
                    <div class="row g-3">

                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="bx bx-notepad"></i>
                                    </span>
                                </div>

                                <div>
                                    <small class="text-muted d-block">
                                        ID Ujian
                                    </small>

                                    <span class="fw-semibold">
                                        #{{ $exam->id }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-8">
                            <div class="alert alert-label-primary mb-0 py-2 px-3">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-info-circle me-2"></i>

                                    <small>
                                        Periksa kembali konfigurasi ujian sebelum menyimpan perubahan.
                                    </small>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-header border-bottom">
                    <h5 class="mb-1">Informasi Ujian</h5>

                    <p class="text-muted mb-0">
                        Atur mata pelajaran, judul, dan petunjuk pengerjaan ujian.
                    </p>
                </div>

                <div class="card-body pt-4">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label for="exam_type" class="form-label">Jenis Ujian <span class="text-danger">*</span></label>
                            <select class="form-select @error('exam_type') is-invalid @enderror" id="exam_type" name="exam_type" required>
                                <option value="trial" {{ old('exam_type', $exam->exam_type) === 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="ujian" {{ old('exam_type', $exam->exam_type) === 'ujian' ? 'selected' : '' }}>Ujian</option>
                            </select>
                            @error('exam_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="schedule_mode" class="form-label">Pengaturan Durasi dan Jadwal <span class="text-danger">*</span></label>
                            <select class="form-select @error('schedule_mode') is-invalid @enderror" id="schedule_mode" name="schedule_mode" required>
                                <option value="scheduled" {{ old('schedule_mode', $exam->schedule_mode ?? ($exam->has_duration ? 'scheduled' : 'no_duration')) === 'scheduled' ? 'selected' : '' }}>Menggunakan durasi dan jadwal</option>
                                <option value="duration_without_schedule" {{ old('schedule_mode', $exam->schedule_mode ?? '') === 'duration_without_schedule' ? 'selected' : '' }}>Menggunakan durasi tanpa jadwal</option>
                                <option value="no_duration" {{ old('schedule_mode', $exam->schedule_mode ?? ($exam->has_duration ? '' : 'no_duration')) === 'no_duration' ? 'selected' : '' }}>Tanpa durasi dan jadwal</option>
                            </select>
                            @error('schedule_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">
                                Mata Pelajaran <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select @error('subject_id') is-invalid @enderror"
                                id="subject_id"
                                name="subject_id"
                                required
                            >
                                <option value="">-- Pilih Mata Pelajaran --</option>

                                @foreach ($subjects as $subject)
                                    <option
                                        value="{{ $subject->id }}"
                                        {{ old('subject_id', $exam->subject_id) == $subject->id ? 'selected' : '' }}
                                    >
                                        {{ $subject->name }}@if ($subject->topic) ({{ $subject->topic }})@endif
                                    </option>
                                @endforeach
                            </select>

                            @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">
                                Judul Ujian <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control @error('title') is-invalid @enderror"
                                id="title"
                                name="title"
                                value="{{ old('title', $exam->title) }}"
                                placeholder="Contoh: Ujian Tengah Semester"
                                maxlength="255"
                                required
                            >

                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">
                                Deskripsi / Petunjuk
                            </label>

                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Masukkan petunjuk ujian..."
                            >{{ old('description', $exam->description) }}</textarea>

                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="card-header border-top border-bottom">
                    <h5 class="mb-1">Pengaturan Penilaian</h5>

                    <p class="text-muted mb-0">
                        Tentukan durasi, nilai kelulusan, dan batas percobaan peserta.
                    </p>
                </div>

                <div class="card-body pt-4">
                    <div class="row">

                        <div class="col-md-4 mb-3 duration-settings">
                            <label for="duration_minutes" class="form-label">
                                Durasi (Menit) <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                class="form-control @error('duration_minutes') is-invalid @enderror"
                                id="duration_minutes"
                                name="duration_minutes"
                                value="{{ old('duration_minutes', $exam->duration_minutes) }}"
                                min="1"
                                required
                            >

                            @error('duration_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="passing_score" class="form-label">
                                Nilai KKM / Lulus <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control @error('passing_score') is-invalid @enderror"
                                id="passing_score"
                                name="passing_score"
                                value="{{ old('passing_score', $exam->passing_score) }}"
                                min="0"
                                max="100"
                            >

                            @error('passing_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3 duration-settings">
                            <label for="max_attempts" class="form-label">
                                Batas Percobaan <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                class="form-control @error('max_attempts') is-invalid @enderror"
                                id="max_attempts"
                                name="max_attempts"
                                value="{{ old('max_attempts', $exam->max_attempts) }}"
                                min="1"
                                required
                            >

                            @error('max_attempts')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="card-header border-top border-bottom">
                    <h5 class="mb-1">Jadwal Pelaksanaan</h5>

                    <p class="text-muted mb-0">
                        Tentukan kapan ujian dimulai dan kapan ujian berakhir.
                    </p>
                </div>

                <div class="card-body pt-4">
                    <div class="row">

                        <div class="col-md-6 mb-3 schedule-settings">
                            <label for="start_at" class="form-label">
                                Waktu Mulai <span class="text-danger">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control @error('start_at') is-invalid @enderror"
                                id="start_at"
                                name="start_at"
                                value="{{ old('start_at', $exam->start_at ? $exam->start_at->format('Y-m-d\TH:i') : '') }}"
                                required
                            >

                            @error('start_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3 duration-settings">
                            <label for="end_at" class="form-label">
                                Waktu Berakhir <span class="text-danger">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control @error('end_at') is-invalid @enderror"
                                id="end_at"
                                name="end_at"
                                value="{{ old('end_at', $exam->end_at ? $exam->end_at->format('Y-m-d\TH:i') : '') }}"
                                required
                            >

                            @error('end_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="alert alert-label-info mb-0" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-info-circle me-2"></i>

                                    <small>
                                        Pastikan waktu berakhir lebih lambat daripada waktu mulai.
                                    </small>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-header border-top border-bottom">
                    <h5 class="mb-1">Status dan Opsi Ujian</h5>

                    <p class="text-muted mb-0">
                        Atur publikasi ujian, pengacakan soal, dan penampilan hasil.
                    </p>
                </div>

                <div class="card-body pt-4">

                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="auto_assign_new_students"
                                    name="auto_assign_new_students"
                                    value="1"
                                    {{ old('auto_assign_new_students', $exam->auto_assign_new_students) ? 'checked' : '' }}
                                >
                                <label class="form-check-label fw-semibold" for="auto_assign_new_students">
                                    Tambahkan Siswa Baru Otomatis
                                </label>
                            </div>
                            <div class="form-text">
                                Siswa aktif yang kelas dan jenjangnya sesuai dengan mata pelajaran akan otomatis menjadi peserta setelah ujian dipublikasikan. Saat opsi diaktifkan, siswa yang sudah ada dan cocok juga akan disinkronkan.
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="status" class="form-label">
                                Status Ujian
                            </label>

                            <select
                                class="form-select @error('status') is-invalid @enderror"
                                id="status"
                                name="status"
                            >
                                <option
                                    value="draft"
                                    {{ old('status', $exam->status) == 'draft' ? 'selected' : '' }}
                                >
                                    Draft
                                </option>

                                <option
                                    value="published"
                                    {{ old('status', $exam->status) == 'published' ? 'selected' : '' }}
                                >
                                    Published
                                </option>

                                <option
                                    value="closed"
                                    {{ old('status', $exam->status) == 'closed' ? 'selected' : '' }}
                                >
                                    Closed
                                </option>
                            </select>

                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                Pastikan kesiapan soal dan peserta sebelum menerbitkan ujian.
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="shuffle_questions"
                                        name="shuffle_questions"
                                        value="1"
                                        {{ old('shuffle_questions', $exam->shuffle_questions) ? 'checked' : '' }}
                                    >

                                    <label class="form-check-label fw-semibold" for="shuffle_questions">
                                        Acak Urutan Soal
                                    </label>
                                </div>

                                <p class="text-muted small mb-0 mt-2">
                                    Mengacak urutan soal yang diterima peserta.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="shuffle_options"
                                        name="shuffle_options"
                                        value="1"
                                        {{ old('shuffle_options', $exam->shuffle_options) ? 'checked' : '' }}
                                    >

                                    <label class="form-check-label fw-semibold" for="shuffle_options">
                                        Acak Pilihan Jawaban
                                    </label>
                                </div>

                                <p class="text-muted small mb-0 mt-2">
                                    Mengacak urutan pilihan jawaban pada soal.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="show_result_immediately"
                                        name="show_result_immediately"
                                        value="1"
                                        {{ old('show_result_immediately', $exam->show_result_immediately) ? 'checked' : '' }}
                                    >

                                    <label class="form-check-label fw-semibold" for="show_result_immediately">
                                        Tampilkan Nilai Langsung
                                    </label>
                                </div>

                                <p class="text-muted small mb-0 mt-2">
                                    Mengizinkan peserta melihat nilai setelah menyelesaikan ujian.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="show_correct_answers"
                                        name="show_correct_answers"
                                        value="1"
                                        {{ old('show_correct_answers', $exam->show_correct_answers) ? 'checked' : '' }}
                                    >

                                    <label class="form-check-label fw-semibold" for="show_correct_answers">
                                        Tampilkan Kunci Jawaban
                                    </label>
                                </div>

                                <p class="text-muted small mb-0 mt-2">
                                    Mengizinkan peserta melihat jawaban benar setelah ujian selesai.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer border-top">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">

                        <a href="{{ route('exams.index') }}" class="btn btn-outline-secondary mt-3">
                            <i class="bx bx-x me-1"></i>
                            Batal
                        </a>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="bx bx-save me-1"></i>
                            Update Ujian
                        </button>

                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection
