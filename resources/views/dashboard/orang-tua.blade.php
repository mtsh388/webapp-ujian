@extends('template.app')

@section('title', 'Dashboard Orang Tua')

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl container-p-y">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">
                                Selamat Datang, {{ $parent?->full_name ?? auth()->user()->name }}
                            </h4>

                            <p class="text-muted mb-0">
                                Pantau aktivitas dan hasil ujian anak Anda.
                            </p>
                        </div>

                        <span class="badge bg-label-primary px-3 py-2">
                        <i class="icon-base bx bx-home-heart me-1"></i>
                        Orang Tua
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
                                    <span class="fw-medium d-block mb-1">Jumlah Anak</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($children->count(), 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base bx bx-group fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">
                                Anak yang terhubung
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium d-block mb-1">Total Ujian</span>
                                    <h3 class="card-title mb-0">
                                        {{ number_format($totalAttempts, 0, ',', '.') }}
                                    </h3>
                                </div>

                                <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-file fs-4"></i>
                                </span>
                                </div>
                            </div>

                            <small class="text-muted">
                                Percobaan ujian seluruh anak
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
                                Ujian yang telah diselesaikan
                            </small>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-4">

                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Data Anak</h5>
                        </div>

                        <div class="card-body">

                            @forelse($children as $child)
                                <div class="d-flex align-items-center {{ !$loop->last ? 'mb-4' : '' }}">
                                    <div class="avatar me-3">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        {{ strtoupper(substr($child->full_name, 0, 1)) }}
                                    </span>
                                    </div>

                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            {{ $child->full_name }}
                                        </h6>

                                        <small class="text-muted d-block">
                                            {{ $child->student_number }}
                                        </small>

                                        <small class="text-muted">
                                            Kelas: {{ $child->classRoom?->name ?? '-' }}
                                        </small>
                                    </div>

                                    @if($child->pivot?->is_primary)
                                        <span class="badge bg-label-success">
                                        Utama
                                    </span>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="icon-base bx bx-user-x fs-1 text-muted"></i>
                                    </div>

                                    <p class="text-muted mb-0">
                                        Belum ada anak yang terhubung dengan akun ini.
                                    </p>
                                </div>
                            @endforelse

                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Hasil Ujian Terbaru</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Anak</th>
                                    <th>Ujian</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse($recentAttempts as $attempt)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">
                                                {{ $attempt->student?->full_name ?? '-' }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $attempt->exam?->title ?? '-' }}
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
                                            @if($attempt->status === 'submitted')
                                                @if($attempt->passed === true)
                                                    <span class="badge bg-label-success">
                                                        Lulus
                                                    </span>
                                                @elseif($attempt->passed === false)
                                                    <span class="badge bg-label-danger">
                                                        Belum Lulus
                                                    </span>
                                                @else
                                                    <span class="badge bg-label-info">
                                                        Selesai
                                                    </span>
                                                @endif
                                            @elseif($attempt->status === 'in_progress')
                                                <span class="badge bg-label-warning">
                                                    Berlangsung
                                                </span>
                                            @else
                                                <span class="badge bg-label-secondary">
                                                    {{ ucfirst(str_replace('_', ' ', $attempt->status)) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            Belum ada hasil ujian anak.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
