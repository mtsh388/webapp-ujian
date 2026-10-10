@extends('template.app')

@section('title', 'Detail Orang Tua')

@section('content')
    <div class="container-fluid py-3">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <h4 class="mb-1 fw-semibold">
                            <i class="bx bx-user me-2"></i>
                            Detail Orang Tua
                        </h4>

                        <p class="text-muted mb-0">
                            Informasi orang tua dan siswa yang terhubung.
                        </p>
                    </div>

                    <div class="d-flex gap-2">

                        @can('parent.update')
                            <a
                                href="{{ route('parents.edit', $parent) }}"
                                class="btn btn-primary"
                            >
                                <i class="bx bx-edit me-1"></i>
                                Edit
                            </a>
                        @endcan

                        <a
                            href="{{ route('parents.index') }}"
                            class="btn btn-light border"
                        >
                            Kembali
                        </a>

                    </div>

                </div>
            </div>

            <div class="card-body p-4">

                <div class="row g-4">

                    <div class="col-md-6">

                        <div class="card border shadow-none h-100">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0 fw-semibold">
                                    Informasi Orang Tua
                                </h6>
                            </div>

                            <div class="card-body">

                                <div class="mb-3">
                                    <div class="text-muted small">
                                        Nama Lengkap
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $parent->full_name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small">
                                        No. Telepon
                                    </div>

                                    <div>
                                        {{ $parent->phone ?: '-' }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small">
                                        Alamat
                                    </div>

                                    <div>
                                        {{ $parent->address ?: '-' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted small mb-1">
                                        Status
                                    </div>

                                    @if($parent->is_active)
                                        <span class="badge bg-label-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="card border shadow-none h-100">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0 fw-semibold">
                                    Siswa
                                </h6>
                            </div>

                            <div class="card-body">

                                @forelse($parent->students as $student)
                                    <div class="d-flex align-items-center mb-3">

                                        <div class="avatar me-3">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="bx bx-user"></i>
                                            </span>
                                        </div>

                                        <div>
                                            <div class="fw-semibold">
                                                {{ $student->full_name }}
                                            </div>

                                            <div class="text-muted small">
                                                {{ $student->student_number }}
                                                @if($student->classRoom)
                                                    · {{ $student->classRoom->name }}
                                                @endif
                                            </div>

                                            @if($student->pivot->relationship)
                                                <div class="text-muted small">
                                                    {{ $student->pivot->relationship }}

                                                    @if($student->pivot->is_primary)
                                                        · Utama
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                @empty
                                    <div class="text-muted">
                                        Belum ada siswa yang terhubung.
                                    </div>
                                @endforelse

                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>

    </div>
@endsection
