@extends('template.app')

@section('title', 'Dashboard Siswa')

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl container-p-y">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">
                                Selamat Datang, {{ $student?->full_name ?? auth()->user()->name }}
                            </h4>

                            <p class="text-muted mb-0">
                                Silakan pilih ujian yang tersedia dan lihat hasil ujian Anda.
                            </p>
                        </div>

                        <span class="badge bg-label-primary px-3 py-2">
                        <i class="icon-base bx bx-user me-1"></i>
                        Siswa
                    </span>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium d-block mb-1">Ujian Tersedia</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($availableExams, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base bx bx-file fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">
                                Ujian yang sedang tersedia
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium d-block mb-1">Total Percobaan</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($totalAttempts, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-history fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">
                                Seluruh percobaan ujian
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium d-block mb-1">Ujian Selesai</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($completedAttempts, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base bx bx-check-circle fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">
                                Ujian yang telah dikumpulkan
                            </small>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Riwayat Ujian Terbaru</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Ujian</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Tanggal</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse($recentAttempts as $attempt)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">
                                                {{ $attempt->exam?->title ?? '-' }}
                                            </span>
                                        </td>

                                        <td>
                                            @if($attempt->status === 'submitted')
                                                <span class="badge bg-label-success">Selesai</span>
                                            @elseif($attempt->status === 'in_progress')
                                                <span class="badge bg-label-warning">Sedang Dikerjakan</span>
                                            @elseif($attempt->status === 'expired')
                                                <span class="badge bg-label-danger">Expired</span>
                                            @else
                                                <span class="badge bg-label-secondary">
                                                    {{ ucfirst(str_replace('_', ' ', $attempt->status)) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($attempt->status === 'submitted')
                                                <span class="fw-semibold">
                                                    {{ number_format((float) $attempt->score, 2, ',', '.') }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <td>
                                            {{ $attempt->submitted_at?->format('d M Y H:i') ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            Belum ada riwayat ujian.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Informasi Siswa</h5>
                        </div>

                        <div class="card-body">

                            <div class="mb-4">
                                <small class="text-muted d-block mb-1">
                                    Nama
                                </small>

                                <span class="fw-semibold">
                                {{ $student?->full_name ?? auth()->user()->name }}
                            </span>
                            </div>

                            <div class="mb-4">
                                <small class="text-muted d-block mb-1">
                                    Nomor Siswa
                                </small>

                                <span class="fw-semibold">
                                {{ $student?->student_number ?? '-' }}
                            </span>
                            </div>

                            <div class="mb-4">
                                <small class="text-muted d-block mb-1">
                                    Kelas
                                </small>

                                <span class="fw-semibold">
                                {{ $student?->classRoom?->name ?? '-' }}
                            </span>
                            </div>

                            <div>
                                <small class="text-muted d-block mb-1">
                                    Status
                                </small>

                                @if($student?->is_active)
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak Aktif</span>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
