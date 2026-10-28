@extends('template.app')

@section('title', 'Tambah Kelas')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">

                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>

                    <div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>
        @endif


        <form method="POST" action="{{ route('classes.store') }}">

            @csrf

            <div class="card px-3">
                <div class="card-header border-bottom">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                        <div>
                            <h4 class="card-title mb-1">
                                Tambah Kelas
                            </h4>

                            <p class="card-subtitle text-muted mb-0">
                                Tambahkan data kelas baru pada sistem bimbingan belajar.
                            </p>
                        </div>


                        <div class="d-flex justify-content-end">

                            <a href="{{ route('classes.index') }}"
                               class="btn btn-secondary">

                                <i class="bx bx-arrow-back me-1"></i>

                                Kembali

                            </a>

                        </div>

                    </div>

                </div>

                <div class="card-body">


                    <div class="row g-4">


                        <div class="col-md-6">

                            <label for="name" class="form-label">
                                Nama Kelas
                            </label>


                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Contoh: Kelas 10 IPA 1"
                                required>


                            @error('name')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                            @enderror


                        </div>



                                                <div class="col-md-6">
                            <label for="education_level" class="form-label">Jenjang Pendidikan</label>
                            <select id="education_level" name="education_level" class="form-select @error('education_level') is-invalid @enderror" required>
                                <option value="">Pilih Jenjang</option>
                                <option value="SD" {{ old('education_level', $classRoom->education_level ?? '') === 'SD' ? 'selected' : '' }}>SD</option>
                                <option value="SMP" {{ old('education_level', $classRoom->education_level ?? '') === 'SMP' ? 'selected' : '' }}>SMP</option>
                                <option value="SMA" {{ old('education_level', $classRoom->education_level ?? '') === 'SMA' ? 'selected' : '' }}>SMA</option>
                            </select>
                            @error('education_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="grade_level" class="form-label">Tingkat Kelas</label>
                            <select id="grade_level" name="grade_level" class="form-select @error('grade_level') is-invalid @enderror" required>
                                <option value="">Pilih Kelas</option>
                                @foreach([4, 5, 6, 7, 8, 9, 10, 11, 12] as $grade)
                                    <option value="{{ $grade }}" {{ (string) old('grade_level', $classRoom->grade_level ?? '') === (string) $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                @endforeach
                            </select>
                            @error('grade_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="registration_type" class="form-label">Jenis Kelas</label>
                            <select id="registration_type" name="registration_type" class="form-select @error('registration_type') is-invalid @enderror" required>
                                <option value="">Pilih Jenis Kelas</option>
                                <option value="trial" {{ old('registration_type', $classRoom->registration_type ?? '') === 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="paid" {{ old('registration_type', $classRoom->registration_type ?? '') === 'paid' ? 'selected' : '' }}>Berbayar</option>
                            </select>
                            @error('registration_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">


                            <label for="description" class="form-label">
                                Deskripsi
                            </label>


                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Deskripsi kelas">{{ old('description') }}</textarea>


                            @error('description')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                            @enderror


                        </div>




                        <div class="col-md-6">


                            <label for="is_active" class="form-label">
                                Status
                            </label>


                            <select
                                id="is_active"
                                name="is_active"
                                class="form-select @error('is_active') is-invalid @enderror">


                                <option value="1"
                                    {{ old('is_active', '1') == '1' ? 'selected' : '' }}>
                                    Aktif
                                </option>


                                <option value="0"
                                    {{ old('is_active') === '0' ? 'selected' : '' }}>
                                    Tidak Aktif
                                </option>


                            </select>


                            @error('is_active')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                            @enderror


                        </div>


                    </div>


                </div>



                <div class="card-footer border-top d-flex justify-content-end gap-2">


                    <a
                        href="{{ route('classes.index') }}"
                        class="btn btn-label-secondary mt-3">

                        <i class="bx bx-x me-1"></i>

                        Batal

                    </a>



                    <button
                        type="submit"
                        class="btn btn-primary mt-3">


                        <i class="bx bx-save me-1"></i>

                        Simpan


                    </button>


                </div>


            </div>


        </form>


    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const levelSelect = document.getElementById('education_level');
            const gradeSelect = document.getElementById('grade_level');
            const gradesByLevel = {
                SD: [4, 5, 6],
                SMP: [7, 8, 9],
                SMA: [10, 11, 12]
            };

            if (!levelSelect || !gradeSelect) {
                return;
            }

            function filterGrades() {
                const allowedGrades = gradesByLevel[levelSelect.value] || [];

                Array.from(gradeSelect.options).forEach(function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = !allowedGrades.includes(Number(option.value));
                });

                if (gradeSelect.value && !allowedGrades.includes(Number(gradeSelect.value))) {
                    gradeSelect.value = '';
                }
            }

            levelSelect.addEventListener('change', filterGrades);
            filterGrades();
        });
    </script>

@endsection
