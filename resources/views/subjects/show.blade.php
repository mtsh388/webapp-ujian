@extends('template.app')

@section('title', 'Detail Mata Pelajaran')

@section('content')
    <div class="container-fluid py-3">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                Mata Pelajaran
                            </span>

                            @if ($subject->is_active)
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
                            {{ $subject->name }}
                        </h4>

                        <p class="text-muted mb-0">
                            Detail informasi mata pelajaran
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <a
                            href="{{ route('subjects.index') }}"
                            class="btn btn-secondary border"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali
                        </a>

                        @can('subject.update')
                            <a
                                href="{{ route('subjects.edit', $subject) }}"
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
                                    <i class="bi bi-info-circle me-2"></i>
                                    Informasi Mata Pelajaran
                                </h6>

                                <div class="row g-4">

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Nama Mata Pelajaran
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $subject->name }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">
                                            Kode Mata Pelajaran
                                        </div>

                                        <div>
                                            <span class="badge bg-light text-dark border px-3 py-2">
                                                {{ $subject->code }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-muted small mb-1">
                                            Deskripsi
                                        </div>

                                        @if ($subject->description)
                                            <div class="text-body">
                                                {!! nl2br(e($subject->description)) !!}
                                            </div>
                                        @else
                                            <div class="text-muted fst-italic">
                                                Tidak ada deskripsi.
                                            </div>
                                        @endif
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border rounded-4 h-100">
                            <div class="card-body p-4">

                                <h6 class="fw-semibold mb-4">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Informasi Sistem
                                </h6>

                                <div class="mb-4">
                                    <div class="text-muted small mb-1">
                                        ID
                                    </div>

                                    <div class="fw-semibold">
                                        #{{ $subject->id }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="text-muted small mb-1">
                                        Status
                                    </div>

                                    @if ($subject->is_active)
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
                                        Dibuat
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $subject->created_at?->format('d F Y H:i') ?? '-' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted small mb-1">
                                        Terakhir Diperbarui
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $subject->updated_at?->format('d F Y H:i') ?? '-' }}
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer bg-white border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">

                    <a
                        href="{{ route('subjects.index') }}"
                        class="btn btn-secondary border"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali ke Daftar
                    </a>

                    @can('subject.update')
                        <a
                            href="{{ route('subjects.edit', $subject) }}"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-pencil-square me-1"></i>
                            Edit Mata Pelajaran
                        </a>
                    @endcan

                </div>
            </div>

        </div>

    </div>
@endsection
