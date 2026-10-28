@extends('template.app')

@section('title', 'Lengkapi Data Siswa')

@section('content')
    @php
        $profileComplete = $student
            && filled($student->full_name)
            && filled($student->school_name)
            && filled($student->phone)
            && filled($student->education_level)
            && filled($student->class_id);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-1">Lengkapi Data Siswa</h4>
                        <p class="text-muted mb-0">Isi data berikut sebelum mulai mengerjakan ujian.</p>
                    </div>

                    <div class="card-body">
                        @if (session('warning'))
                            <div class="alert alert-warning">{{ session('warning') }}</div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
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
                                <label for="email" class="form-label">Email</label>
                                <input id="email" type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                                <div class="form-text">Email akun tidak dapat diubah dari halaman ini.</div>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input id="full_name" name="full_name" type="text" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $student?->full_name) }}" maxlength="255" autocomplete="off" autocapitalize="words" required>
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="school_name" class="form-label">Nama Sekolah</label>
                                <input id="school_name" name="school_name" type="text" class="form-control @error('school_name') is-invalid @enderror" value="{{ old('school_name', $student?->school_name) }}" maxlength="255" required>
                                @error('school_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor HP Siswa</label>
                                <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $student?->phone) }}" maxlength="30" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="parent_phone" class="form-label">Nomor HP Orang Tua <span class="text-muted">(Opsional)</span></label>
                                <input id="parent_phone" name="parent_phone" type="tel" class="form-control @error('parent_phone') is-invalid @enderror" value="{{ old('parent_phone', $student?->parent_phone) }}" maxlength="30" autocomplete="tel">
                                <div class="form-text">Isi jika ingin mencantumkan nomor HP orang tua atau wali.</div>
                                @error('parent_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="education_level" class="form-label">Jenjang Pendidikan</label>
                                <select id="education_level" name="education_level" class="form-select @error('education_level') is-invalid @enderror" required>
                                    <option value="">Pilih jenjang</option>
                                    @foreach (['SD' => 'SD', 'SMP' => 'SMP', 'SMA' => 'SMA'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('education_level', $student?->education_level) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('education_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="class_id" class="form-label">Kelas</label>
                                <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                                    <option value="">Pilih kelas</option>
                                    @foreach ($classes as $classRoom)
                                        <option value="{{ $classRoom->id }}" data-level="{{ $classRoom->education_level }}" data-registration-type="{{ $classRoom->registration_type }}" @selected((string) old('class_id', $student?->class_id) === (string) $classRoom->id)>
                                            {{ $classRoom->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilihan kelas mengikuti jenjang pendidikan yang dipilih.</div>
                                @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>
                                    Simpan Data
                                </button>

                                @if ($profileComplete)
                                    <a href="{{ route('exams.available') }}" class="btn btn-success">
                                        <i class="bx bx-play-circle me-1"></i>
                                        Mulai Ujian
                                    </a>
                                @else
                                    <button type="button" class="btn btn-success" disabled title="Simpan data siswa terlebih dahulu">
                                        <i class="bx bx-play-circle me-1"></i>
                                        Mulai Ujian
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @php
        $classFilterOptions = $classes->map(function ($classRoom) {
            return [
                'value' => (string) $classRoom->id,
                'text' => $classRoom->name,
                'level' => strtoupper(trim((string) $classRoom->education_level)),
            ];
        })->values();
    @endphp
    <script>
        (function () {
            const classOptions = @js($classFilterOptions);

            function initializeClassFilter() {
                const levelSelect = document.getElementById('education_level');
                const classSelect = document.getElementById('class_id');

                if (!levelSelect || !classSelect || classSelect.dataset.filterInitialized === 'true') {
                    return;
                }

                classSelect.dataset.filterInitialized = 'true';

                function filterClasses() {
                    const previousClass = classSelect.value;
                    const selectedLevel = String(levelSelect.value || '').trim().toUpperCase();
                    const availableOptions = classOptions.filter(function (option) {
                        return option.level === selectedLevel;
                    });
                    const select2Active = window.jQuery
                        && jQuery.fn.select2
                        && jQuery(classSelect).hasClass('select2-hidden-accessible');

                    if (select2Active) {
                        jQuery(classSelect).select2('destroy');
                    }

                    classSelect.replaceChildren();

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Pilih kelas';
                    classSelect.add(placeholder);

                    availableOptions.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.text;
                        classSelect.add(option);
                    });

                    classSelect.value = availableOptions.some(function (item) {
                        return item.value === previousClass;
                    }) ? previousClass : '';

                    if (select2Active && typeof window.qseeInitSelect2 === 'function') {
                        window.qseeInitSelect2(classSelect);
                    }

                    if (window.jQuery) {
                        jQuery(classSelect).trigger('change');
                    }
                }

                levelSelect.addEventListener('change', filterClasses);
                filterClasses();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeClassFilter, { once: true });
            } else {
                initializeClassFilter();
            }
        })();
    </script>
@endsection
