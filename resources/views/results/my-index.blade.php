@extends('template.app')

@section('title', 'Hasil Ujian Saya')

@section('content')
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

        <div class="card">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>
                        <h4 class="card-title mb-1">
                            Hasil Ujian Saya
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            Riwayat nilai, status kelulusan, dan detail ujian yang telah Anda kerjakan.
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                        <a href="{{ route('exams.available') }}" class="btn btn-primary">
                            <i class="bx bx-play me-1"></i>
                            Ujian Tersedia
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
                                    <i class="bx bx-file"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">
                                    Total Riwayat Ujian
                                </small>

                                <span class="fw-semibold">
                                    {{ $attempts->total() }} Ujian
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8">
                        <div class="alert alert-label-primary mb-0 py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle me-2"></i>
                                <small>
                                    Pilih <strong>Lihat Detail Hasil</strong> untuk memeriksa nilai dan pembahasan
                                    jawaban.
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body">
                @if ($attempts->count())

                    <div class="row g-4">

                        @foreach ($attempts as $attempt)
                            <div class="col-12 col-md-6 col-xl-4">

                                <div class="card border h-100 result-card">

                                    <div class="card-header border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-3">

                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar flex-shrink-0">
                                                    <span class="avatar-initial rounded bg-label-primary">
                                                        <i class="bx bx-book-open"></i>
                                                    </span>
                                                </div>

                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        {{ $attempt->exam?->title ?? '-' }}
                                                    </h5>

                                                    <small class="text-muted">
                                                        {{ $attempt->exam?->code ?? '-' }}
                                                    </small>
                                                </div>
                                            </div>

                                            @if ($attempt->status === 'expired')
                                                <span class="badge bg-label-warning flex-shrink-0">
                                                    Expired
                                                </span>
                                            @else
                                                <span class="badge bg-label-success flex-shrink-0">
                                                    Selesai
                                                </span>
                                            @endif

                                        </div>
                                    </div>

                                    <div class="card-body d-flex flex-column">

                                        <div class="text-center mb-4">
                                            <div class="small text-muted mb-1">
                                                Nilai Akhir
                                            </div>

                                            <div class="result-score text-primary">
                                                {{ number_format((float) $attempt->score, 2, ',', '.') }}
                                            </div>

                                            @if ($attempt->exam)
                                                <div class="small text-muted mt-1">
                                                    Passing Grade:
                                                    {{ number_format((float) $attempt->exam->passing_score, 2, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="row g-2 mb-4">

                                            <div class="col-4">
                                                <div class="text-center p-2 rounded bg-label-success">
                                                    <div class="fw-bold text-success">
                                                        {{ $attempt->correct_answers ?? 0 }}
                                                    </div>

                                                    <div class="small text-muted">
                                                        Benar
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="text-center p-2 rounded bg-label-danger">
                                                    <div class="fw-bold text-danger">
                                                        {{ $attempt->wrong_answers ?? 0 }}
                                                    </div>

                                                    <div class="small text-muted">
                                                        Salah
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="text-center p-2 rounded bg-label-secondary">
                                                    <div class="fw-bold text-secondary">
                                                        {{ $attempt->unanswered ?? 0 }}
                                                    </div>

                                                    <div class="small text-muted">
                                                        Kosong
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                            <span class="text-muted">
                                                Kelulusan
                                            </span>

                                            @if ($attempt->passed === true)
                                                <span class="badge bg-success">
                                                    Lulus
                                                </span>
                                            @elseif ($attempt->passed === false)
                                                <span class="badge bg-danger">
                                                    Tidak Lulus
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    -
                                                </span>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-4">
                                            <span class="text-muted">
                                                Dikumpulkan
                                            </span>

                                            <span class="fw-semibold text-end">
                                                {{ $attempt->submitted_at?->format('d M Y H:i') ?? '-' }}
                                            </span>
                                        </div>

                                        <div class="mt-auto">
                                            <a
                                                href="{{ route('results.my.show', $attempt->id) }}"
                                                class="btn btn-primary w-100 mb-2">
                                                <i class="bx bx-show me-1"></i>
                                                Lihat Detail Hasil
                                            </a>
                                            <a
                                                href="{{ route('results.ranking', $attempt->exam_id) }}"
                                                class="btn btn-outline-primary w-100">
                                                <i class="bx bx-trophy me-1"></i>
                                                Lihat Ranking Ujian
                                            </a>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        @endforeach

                    </div>

                    @if ($attempts->hasPages())
                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top">

                            <small class="text-muted">
                                Menampilkan {{ $attempts->firstItem() }} sampai {{ $attempts->lastItem() }}
                                dari {{ $attempts->total() }} hasil ujian
                            </small>

                            <div>
                                {{ $attempts->links() }}
                            </div>

                        </div>
                    @endif

                @else
                    <div class="text-center py-5">

                        <div class="mb-3">
                            <i class="bx bx-file-blank text-muted" style="font-size: 4rem;"></i>
                        </div>

                        <h5 class="mb-2">
                            Belum Ada Hasil Ujian
                        </h5>

                        <p class="text-muted mb-4">
                            Anda belum memiliki riwayat hasil ujian.
                        </p>

                        <a href="{{ route('exams.available') }}" class="btn btn-primary">
                            <i class="bx bx-play me-1"></i>
                            Lihat Ujian Tersedia
                        </a>

                    </div>
                @endif
            </div>

        </div>
    </div>

    <style>
        .result-card {
            border-radius: 0.75rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(67, 89, 113, 0.12);
        }

        .result-card .card-header {
            min-height: 100px;
        }

        .result-card .card-title {
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .result-score {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.3;
        }

        @media (max-width: 575.98px) {
            .result-card .card-header {
                min-height: auto;
            }
        }
    </style>
@endsection
