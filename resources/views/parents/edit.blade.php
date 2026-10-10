@extends('template.app')

@section('title', 'Edit Orang Tua')

@section('content')
    <div class="container-fluid py-3">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                    <div>
                        <h4 class="mb-1 fw-semibold">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Orang Tua
                        </h4>

                        <p class="text-muted mb-0">
                            Perbarui informasi data orang tua.
                        </p>
                    </div>

                    <a
                        href="{{ route('parents.index') }}"
                        class="btn btn-secondary border"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali
                    </a>

                </div>
            </div>

            <form
                action="{{ route('parents.update', $parent) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="card-body p-4">

                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label">
                                Nama Lengkap
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                value="{{ old('full_name', $parent->full_name) }}"
                                class="form-control @error('full_name') is-invalid @enderror"
                                maxlength="255"
                                required
                            >

                            @error('full_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                No. Telepon
                            </label>

                            <input
                                type="text"
                                name="phone"
                                value="{{ old('phone', $parent->phone) }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                maxlength="30"
                            >

                            @error('phone')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Alamat
                            </label>

                            <textarea
                                name="address"
                                rows="3"
                                class="form-control @error('address') is-invalid @enderror"
                            >{{ old('address', $parent->address) }}</textarea>

                            @error('address')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Siswa
                            </label>

                            @php
                                $selectedStudents = old(
                                    'students',
                                    $parent->students->pluck('id')->toArray()
                                );
                            @endphp

                            <div class="border rounded-3 p-3">

                                @forelse($students as $student)
                                    <div class="form-check mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="students[]"
                                            value="{{ $student->id }}"
                                            id="student{{ $student->id }}"
                                            @checked(in_array(
                                                $student->id,
                                                $selectedStudents
                                            ))
                                        >

                                        <label
                                            class="form-check-label"
                                            for="student{{ $student->id }}"
                                        >
                                            {{ $student->full_name }}

                                            <span class="text-muted">
                                                ({{ $student->student_number }})
                                            </span>
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted">
                                        Belum ada siswa aktif.
                                    </div>
                                @endforelse

                            </div>

                            @error('students')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Status
                            </label>

                            <div class="form-check form-switch">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                >

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    id="is_active"
                                    @checked(old('is_active', $parent->is_active))
                                >

                                <label
                                    class="form-check-label"
                                    for="is_active"
                                >
                                    Aktif
                                </label>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="card-footer bg-white border-0 p-4">
                    <div class="d-flex justify-content-end gap-2">

                        <a
                            href="{{ route('parents.index') }}"
                            class="btn btn-secondary border"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bx bx-save me-1"></i>
                            Simpan Perubahan
                        </button>

                    </div>
                </div>

            </form>

        </div>

    </div>
@endsection
