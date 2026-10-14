@extends('template.app')

@section('title', 'Ujian Saya')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>
                    <div>{{ session('error') }}</div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        @endif

        <div class="card">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>
                        <h4 class="card-title mb-1">
                            Ujian Saya
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            Daftar ujian yang ditugaskan kepada Anda. Perhatikan jadwal dan batas waktu pengerjaan.
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                        <a
                            href="{{ route('results.my') }}"
                            class="btn btn-outline-primary">
                            <i class="bx bx-file me-1"></i>
                            Hasil Ujian Saya
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
                                    <i class="bx bx-book-open"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">
                                    Ujian Tersedia
                                </small>

                                <span class="fw-semibold">
                                    {{ $exams->total() }} Ujian
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8">
                        <div class="alert alert-label-primary mb-0 py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle me-2"></i>

                                <small>
                                    Pastikan Anda mengerjakan ujian dalam rentang jadwal yang telah ditentukan.
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">

                    @forelse ($exams as $exam)
                        @php
                            $now = now();
                            $start = $exam->start_at
                                ? \Carbon\Carbon::parse($exam->start_at)
                                : null;
                            $end = $exam->end_at
                                ? \Carbon\Carbon::parse($exam->end_at)
                                : null;
                            $activeAttempt = $activeAttempts->get($exam->id);
                        @endphp

                        <div class="col-12 col-md-6 col-xl-4">

                            <div class="card border h-100 exam-card">

                                <div class="card-header border-bottom">
                                    <div class="d-flex justify-content-between align-items-start gap-2">

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="bx bx-book-open"></i>
                                                </span>
                                            </div>

                                            <div>
                                                <h5 class="card-title mb-1">
                                                    {{ $exam->title }}
                                                </h5>

                                                <small class="text-muted">
                                                    {{ $exam->subject->name ?? 'Umum' }}
                                                </small>
                                            </div>
                                        </div>

                                        <span class="badge bg-label-primary flex-shrink-0">
                                            {{ $exam->code }}
                                        </span>

                                    </div>
                                </div>

                                <div class="card-body d-flex flex-column">

                                    <p class="text-muted mb-4">
                                        {{ \Illuminate\Support\Str::limit($exam->description ?? 'Tidak ada deskripsi ujian.', 120) }}
                                    </p>

                                    <div class="d-flex flex-column gap-3 mb-4">

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-sm flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="bx bx-time-five"></i>
                                                </span>
                                            </div>

                                            <div>
                                                <small class="text-muted d-block">
                                                    Durasi
                                                </small>

                                                <span class="fw-semibold">
                                                    {{ $exam->duration_minutes }} Menit
                                                </span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-sm flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class="bx bx-check-shield"></i>
                                                </span>
                                            </div>

                                            <div>
                                                <small class="text-muted d-block">
                                                    Passing Grade
                                                </small>

                                                <span class="fw-semibold">
                                                    {{ number_format((float) $exam->passing_score, 2, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-sm flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-warning">
                                                    <i class="bx bx-calendar"></i>
                                                </span>
                                            </div>

                                            <div>
                                                <small class="text-muted d-block">
                                                    Mulai
                                                </small>

                                                <span class="fw-semibold">
                                                    {{ $start?->format('d M Y, H:i') ?? '-' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-sm flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-danger">
                                                    <i class="bx bx-calendar-x"></i>
                                                </span>
                                            </div>

                                            <div>
                                                <small class="text-muted d-block">
                                                    Berakhir
                                                </small>

                                                <span class="fw-semibold">
                                                    {{ $end?->format('d M Y, H:i') ?? '-' }}
                                                </span>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="mt-auto">
                                        @if (!$start || !$end)
                                            <button
                                                type="button"
                                                class="btn btn-secondary w-100"
                                                disabled>
                                                <i class="bx bx-info-circle me-1"></i>
                                                Jadwal Belum Tersedia
                                            </button>
                                        @elseif ($now->lt($start))
                                            <button
                                                type="button"
                                                class="btn btn-secondary w-100"
                                                disabled>
                                                <i class="bx bx-lock-alt me-1"></i>
                                                Belum Dimulai
                                            </button>
                                        @elseif ($now->gt($end))
                                            <button
                                                type="button"
                                                class="btn btn-danger w-100"
                                                disabled>
                                                <i class="bx bx-x-circle me-1"></i>
                                                Waktu Habis
                                            </button>
                                        @else
                                            <a
                                                href="{{ route('exams.start', ['exam' => $exam->id]) }}"
                                                class="btn btn-primary w-100">
                                                <i class="bx {{ $activeAttempt ? 'bx-refresh' : 'bx-play-circle' }} me-1"></i>
                                                {{ $activeAttempt ? 'Lanjutkan Ujian' : 'Kerjakan Ujian' }}
                                            </a>
                                        @endif
                                    </div>

                                </div>

                            </div>

                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-5">

                                <div class="mb-3">
                                    <i
                                        class="bx bx-book-open text-muted"
                                        style="font-size: 4rem;">
                                    </i>
                                </div>

                                <h5 class="mb-2">
                                    Belum Ada Ujian Tersedia
                                </h5>

                                <p class="text-muted mb-0">
                                    Belum ada jadwal ujian yang ditugaskan kepada Anda saat ini.
                                </p>

                            </div>
                        </div>
                    @endforelse

                </div>

                @if ($exams->hasPages())
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top">

                        <small class="text-muted">
                            Menampilkan {{ $exams->firstItem() }} sampai {{ $exams->lastItem() }}
                            dari {{ $exams->total() }} ujian
                        </small>

                        <div>
                            {{ $exams->links() }}
                        </div>

                    </div>
                @endif
            </div>

        </div>

    </div>

    <style>
        .exam-card {
            border-radius: 0.75rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .exam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(67, 89, 113, 0.12);
        }

        .exam-card .card-title {
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .exam-card .card-header {
            min-height: 100px;
        }

        .exam-card .card-body {
            padding: 1.25rem;
        }

        @media (max-width: 575.98px) {
            .exam-card .card-header {
                min-height: auto;
            }
        }
    </style>

@endsection
