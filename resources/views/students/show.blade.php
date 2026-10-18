@extends('template.app')

@section('title', 'Detail Siswa')

@section('content')
    <div class="container-fluid py-3">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">

                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                Data Siswa
                            </span>

                            @if ($student->is_active)
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Aktif
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Tidak Aktif
                                </span>
                            @endif

                        </div>

                        <h4 class="mb-1 fw-semibold">
                            {{ $student->full_name }}
                        </h4>

                        <p class="text-muted mb-0">
                            {{ $student->student_number }}
                        </p>
                    </div>

                    <div class="d-flex gap-2">

                        <a
                            href="{{ route('students.index') }}"
                            class="btn btn-light border"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali
                        </a>

                        @can('student.update')
                            <a
                                href="{{ route('students.edit', $student) }}"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-pencil-square me-1"></i>
                                Edit
                            </a>
                        @endcan

                    </div>
                </div>
            </div>

            <div class="card-body p-4">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="card border rounded-4 h-100">
                            <div class="card-body p-4">

                                <h6 class="fw-semibold mb-4">
                                    <i class="bi bi-person-vcard me-2"></i>
                                    Informasi Siswa
                                </h6>

                                <div class="row g-4">

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Nomor Siswa
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->student_number }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Nama Lengkap
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->full_name }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Kelas
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->classRoom?->name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Nama Sekolah
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->school_name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Jenjang Pendidikan
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->education_level ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Jenis Kelamin
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->gender ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Tanggal Lahir
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->birth_date?->translatedFormat('d F Y') ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Nomor Telepon
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->phone ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-muted small mb-1">
                                            Alamat
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $student->address ?? '-' }}
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card border rounded-4 h-100">
                            <div class="card-body p-4">

                                <h6 class="fw-semibold mb-4">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Informasi Sistem
                                </h6>

                                <div class="mb-4">
                                    <div class="text-muted small mb-1">
                                        ID
                                    </div>

                                    <div class="fw-semibold">
                                        #{{ $student->id }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="text-muted small mb-1">
                                        Status
                                    </div>

                                    @if ($student->is_active)
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <div class="text-muted small mb-1">
                                        Akun Pengguna
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $student->user?->name ?? 'Belum terhubung' }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="text-muted small mb-1">
                                        Dibuat
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $student->created_at?->format('d M Y H:i') ?? '-' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted small mb-1">
                                        Terakhir Diperbarui
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $student->updated_at?->format('d M Y H:i') ?? '-' }}
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <div class="card-footer bg-white border-0 p-4">
                <div class="d-flex justify-content-between">

                    <a
                        href="{{ route('students.index') }}"
                        class="btn btn-light border"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali ke Daftar
                    </a>

                    @can('student.update')
                        <a
                            href="{{ route('students.edit', $student) }}"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-pencil-square me-1"></i>
                            Edit Siswa
                        </a>
                    @endcan

                </div>
            </div>

        </div>

    </div>
@endsection
