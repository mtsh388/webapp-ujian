@extends('template.app')

@section('title', 'Ranking Ujian')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Ranking Ujian</h4>
                <p class="text-muted mb-0">{{ $exam->title }} · {{ $exam->code }}</p>
            </div>
            <a href="{{ route('results.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>
                Kembali ke Hasil Ujian
            </a>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Jumlah Peserta dengan Hasil</div>
                        <div class="fs-4 fw-bold">{{ $attempts->count() }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Urutan Ranking</div>
                        <div class="fw-semibold">Nilai tertinggi, lalu waktu tercepat</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Keterangan</div>
                        <div class="fw-semibold">Nilai dan waktu sama mendapat peringkat sama</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Peringkat Peserta</h5>
                <span class="text-muted small">{{ $attempts->count() }} hasil</span>
            </div>
            <div class="card-body">
                @if ($attempts->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center">Peringkat</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th class="text-end">Nilai</th>
                                    <th class="text-end">Waktu Pengerjaan</th>
                                    <th>Status</th>
                                    <th>Dikumpulkan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attempts as $attempt)
                                    <tr>
                                        <td class="text-center">
                                            @if ($attempt->rank === 1)
                                                <span class="badge bg-label-warning">🥇 1</span>
                                            @else
                                                <span class="fw-semibold">{{ $attempt->rank }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->student?->full_name ?? '-' }}</div>
                                            <div class="small text-muted">{{ $attempt->student?->student_number ?? '-' }}</div>
                                        </td>
                                        <td>{{ $attempt->student?->classRoom?->name ?? '-' }}</td>
                                        <td class="text-end fw-bold">{{ number_format((float) $attempt->score, 2, ',', '.') }}</td>
                                        <td class="text-end">
                                            @if ($attempt->duration_seconds !== null)
                                                {{ gmdate('H:i:s', max(0, (int) $attempt->duration_seconds)) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attempt->status === 'submitted')
                                                @if ($attempt->passed === true)
                                                    <span class="badge bg-label-success">Lulus</span>
                                                @else
                                                    <span class="badge bg-label-danger">Tidak Lulus</span>
                                                @endif
                                            @else
                                                <span class="badge bg-label-warning">Waktu Habis</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $attempt->submitted_at?->format('d M Y') ?? '-' }}</div>
                                            <div class="small text-muted">{{ $attempt->submitted_at?->format('H:i:s') ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <a href="{{ route('results.show', $attempt->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat hasil">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-trophy text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Belum Ada Ranking</h5>
                        <p class="text-muted mb-0">Belum ada peserta yang mengumpulkan ujian ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
