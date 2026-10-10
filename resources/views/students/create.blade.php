@extends('template.app')

@section('title', 'Tambah Siswa')

@section('content')
    <div class="container-fluid py-3">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                    <div>
                        <h4 class="mb-1 fw-semibold">
                            <i class="bi bi-person-plus me-2"></i>
                            Tambah Siswa
                        </h4>

                        <p class="text-muted mb-0">
                            Tambahkan data siswa baru.
                        </p>
                    </div>

                    <a
                        href="{{ route('students.index') }}"
                        class="btn btn-secondary border"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali
                    </a>

                </div>
            </div>

            <div class="card-body p-4">

                <form
                    action="{{ route('students.store') }}"
                    method="POST"
                >
                    @csrf

                    <div class="row g-4">

                        <div class="col-md-4">
                            <label
                                for="student_number"
                                class="form-label fw-semibold"
                            >
                                Nomor Siswa
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="student_number"
                                id="student_number"
                                class="form-control @error('student_number') is-invalid @enderror"
                                value="{{ old('student_number') }}"
                                maxlength="50"
                                placeholder="Contoh: S001"
                                required
                            >

                            @error('student_number')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-8">
                            <label
                                for="full_name"
                                class="form-label fw-semibold"
                            >
                                Nama Lengkap
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                id="full_name"
                                class="form-control @error('full_name') is-invalid @enderror"
                                value="{{ old('full_name') }}"
                                maxlength="255"
                                placeholder="Masukkan nama lengkap siswa"
                                required
                            >

                            @error('full_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label
                                for="class_id"
                                class="form-label fw-semibold"
                            >
                                Kelas
                            </label>

                            <select
                                name="class_id"
                                id="class_id"
                                class="form-select @error('class_id') is-invalid @enderror"
                            >
                                <option value="">Pilih Kelas</option>

                                @foreach ($classes as $class)
                                    <option
                                        value="{{ $class->id }}"
                                        {{ old('class_id') == $class->id ? 'selected' : '' }}
                                    >
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('class_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label
                                for="gender"
                                class="form-label fw-semibold"
                            >
                                Jenis Kelamin
                            </label>

                            <select
                                name="gender"
                                id="gender"
                                class="form-select @error('gender') is-invalid @enderror"
                            >
                                <option value="">Pilih Jenis Kelamin</option>
                                <option
                                    value="Laki-laki"
                                    {{ old('gender') === 'Laki-laki' ? 'selected' : '' }}
                                >
                                    Laki-laki
                                </option>
                                <option
                                    value="Perempuan"
                                    {{ old('gender') === 'Perempuan' ? 'selected' : '' }}
                                >
                                    Perempuan
                                </option>
                            </select>

                            @error('gender')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label
                                for="birth_date"
                                class="form-label fw-semibold"
                            >
                                Tanggal Lahir
                            </label>

                            <input
                                type="date"
                                name="birth_date"
                                id="birth_date"
                                class="form-control @error('birth_date') is-invalid @enderror"
                                value="{{ old('birth_date') }}"
                            >

                            @error('birth_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label
                                for="phone"
                                class="form-label fw-semibold"
                            >
                                Nomor Telepon
                            </label>

                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}"
                                maxlength="30"
                                placeholder="Contoh: 081234567890"
                            >

                            @error('phone')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label
                                for="is_active"
                                class="form-label fw-semibold"
                            >
                                Status
                            </label>

                            <select
                                name="is_active"
                                id="is_active"
                                class="form-select @error('is_active') is-invalid @enderror"
                            >
                                <option
                                    value="1"
                                    {{ old('is_active', '1') === '1' ? 'selected' : '' }}
                                >
                                    Aktif
                                </option>

                                <option
                                    value="0"
                                    {{ old('is_active') === '0' ? 'selected' : '' }}
                                >
                                    Tidak Aktif
                                </option>
                            </select>

                            @error('is_active')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label
                                for="address"
                                class="form-label fw-semibold"
                            >
                                Alamat
                            </label>

                            <textarea
                                name="address"
                                id="address"
                                rows="4"
                                class="form-control @error('address') is-invalid @enderror"
                                maxlength="255"
                                placeholder="Masukkan alamat siswa"
                            >{{ old('address') }}</textarea>

                            @error('address')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                    </div>

                    <div class="border-top mt-4 pt-4">
                        <div class="d-flex justify-content-end gap-2">

                            <a
                                href="{{ route('students.index') }}"
                                class="btn btn-secondary border"
                            >
                                Batal
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-save me-1"></i>
                                Simpan
                            </button>

                        </div>
                    </div>

                </form>

            </div>
        </div>

    </div>
@endsection
