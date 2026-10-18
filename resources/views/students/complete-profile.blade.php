@extends('template.app')

@section('title', 'Lengkapi Profil Siswa')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-1">Lengkapi Profil Siswa</h4>
                        <p class="text-muted mb-0">Lengkapi data berikut sebelum mulai mengerjakan ujian.</p>
                    </div>
                    <div class="card-body">
                        @if (session('warning'))
                            <div class="alert alert-warning">{{ session('warning') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('students.profile.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input id="full_name" name="full_name" type="text" class="form-control" value="{{ old('full_name', $student?->full_name ?? auth()->user()->name) }}" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label for="school_name" class="form-label">Nama Sekolah</label>
                                <input id="school_name" name="school_name" type="text" class="form-control" value="{{ old('school_name', $student?->school_name) }}" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor HP</label>
                                <input id="phone" name="phone" type="tel" class="form-control" value="{{ old('phone', $student?->phone) }}" maxlength="30" required>
                            </div>
                            <div class="mb-3">
                                <label for="education_level" class="form-label">Jenjang Pendidikan</label>
                                <select id="education_level" name="education_level" class="form-select" required>
                                    <option value="">Pilih jenjang</option>
                                    @foreach (['SD' => 'SD', 'SMP' => 'SMP', 'SMA' => 'SMA'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('education_level', $student?->education_level) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="class_id" class="form-label">Kelas</label>
                                <select id="class_id" name="class_id" class="form-select" required>
                                    <option value="">Pilih kelas</option>
                                    @foreach ($classes as $classRoom)
                                        <option value="{{ $classRoom->id }}" data-level="{{ $classRoom->education_level }}" @selected((string) old('class_id', $student?->class_id) === (string) $classRoom->id)>
                                            {{ $classRoom->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilihan kelas mengikuti jenjang pendidikan yang dipilih.</div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>
                                    Simpan Profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const level = document.getElementById('education_level');
    const classSelect = document.getElementById('class_id');

    function filterClasses() {
        const selectedLevel = level.value;
        Array.from(classSelect.options).forEach(function (option) {
            if (!option.value) return;
            option.hidden = option.dataset.level !== selectedLevel;
            option.disabled = option.hidden;
            if (option.hidden && option.selected) classSelect.value = '';
        });
    }

    level.addEventListener('change', filterClasses);
    filterClasses();
});
</script>
@endsection