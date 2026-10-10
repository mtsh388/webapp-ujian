@extends('template.app')

@section('title', 'Dashboard Tentor')

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl container-p-y">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">Selamat Datang, {{ auth()->user()->name }}</h4>
                            <p class="text-muted mb-0">
                                Kelola bank soal dan ujian siswa melalui dashboard Tentor.
                            </p>
                        </div>

                        <div>
                        <span class="badge bg-label-primary px-3 py-2">
                            <i class="icon-base bx bx-user me-1"></i>
                            Tentor
                        </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-medium d-block mb-1">Bank Soal</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($totalQuestions, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base bx bx-book-open fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">Total soal tersedia</small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-medium d-block mb-1">Ujian</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($totalExams, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base bx bx-file fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">Total ujian</small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-medium d-block mb-1">Siswa</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($totalStudents, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-group fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">Total siswa</small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-medium d-block mb-1">Ujian Terbaru</span>
                                    <h3 class="card-title mb-0">
                                        {{ $recentExams->count() }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="icon-base bx bx-calendar-event fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">Data ujian terbaru</small>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Ujian Terbaru</h5>
                            <span class="badge bg-label-primary">
                            {{ $recentExams->count() }} Data
                        </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Ujian</th>
                                    <th>Status</th>
                                    <th>Mulai</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse($recentExams as $exam)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">{{ $exam->code }}</span>
                                        </td>

                                        <td>
                                            {{ $exam->title }}
                                        </td>

                                        <td>
                                            @if($exam->status === 'published')
                                                <span class="badge bg-label-success">Published</span>
                                            @elseif($exam->status === 'closed')
                                                <span class="badge bg-label-danger">Closed</span>
                                            @else
                                                <span class="badge bg-label-secondary">Draft</span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $exam->start_at ? $exam->start_at->format('d M Y H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            Belum ada data ujian.
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
                            <h5 class="mb-0">Modul Utama</h5>
                        </div>

                        <div class="card-body">

                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base bx bx-book-open"></i>
                                </span>
                                </div>

                                <div>
                                    <h6 class="mb-1">Bank Soal</h6>
                                    <small class="text-muted">
                                        Kelola dan susun soal ujian.
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base bx bx-file"></i>
                                </span>
                                </div>

                                <div>
                                    <h6 class="mb-1">Ujian</h6>
                                    <small class="text-muted">
                                        Buat dan kelola ujian siswa.
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-bar-chart-alt-2"></i>
                                </span>
                                </div>

                                <div>
                                    <h6 class="mb-1">Hasil Ujian</h6>
                                    <small class="text-muted">
                                        Pantau hasil ujian siswa.
                                    </small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
