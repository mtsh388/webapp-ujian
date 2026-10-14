@extends('template.app')

@section('title', 'Dashboard Pengawas')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Dashboard Pengawas</h4>
                <p class="text-muted mb-0">Pantau aktivitas ujian yang perlu ditinjau. Catatan ini bukan bukti otomatis kecurangan.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Aktivitas hari ini</div>
                    <h3 class="mb-0">{{ number_format($totalToday, 0, ',', '.') }}</h3>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Sesi dengan aktivitas hari ini</div>
                    <h3 class="mb-0">{{ number_format($attemptsFlagged, 0, ',', '.') }}</h3>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted mb-1">Sesi aktif perlu ditinjau</div>
                    <h3 class="mb-0">{{ number_format($activeAttemptsFlagged, 0, ',', '.') }}</h3>
                </div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1">Log Aktivitas Ujian</h5>
                <p class="card-subtitle mb-0">Waktu dicatat oleh server. Tinjau konteks sebelum mengambil tindakan.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr>
                        <th>Waktu</th>
                        <th>Peserta</th>
                        <th>Ujian</th>
                        <th>Aktivitas</th>
                        <th>Total kejadian sesi</th>
                        <th>Status sesi</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($violations as $violation)
                        <tr>
                            <td class="text-nowrap">
                                {{ $violation->created_at?->format('d-m-Y H:i:s') }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $violation->attempt?->student?->full_name ?? 'Peserta tidak tersedia' }}</div>
                                <small class="text-muted">Sesi #{{ $violation->exam_attempt_id }}</small>
                            </td>
                            <td>{{ $violation->attempt?->exam?->title ?? 'Ujian tidak tersedia' }}</td>
                            <td>
                                <span class="badge bg-label-warning">{{ $violation->event_label }}</span>
                                @if (!empty($violation->metadata['visibility_state']))
                                    <small class="d-block text-muted">{{ $violation->metadata['visibility_state'] }}</small>
                                @endif
                            </td>
                            <td>{{ $violation->attempt?->violations_count ?? $violation->attempt?->violations()->count() }}</td>
                            <td>
                                @if ($violation->attempt?->status === 'in_progress')
                                    <span class="badge bg-label-primary">Sedang ujian</span>
                                @else
                                    <span class="badge bg-label-secondary">{{ $violation->attempt?->status ?? 'Tidak tersedia' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada aktivitas yang tercatat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($violations->hasPages())
                <div class="card-footer">{{ $violations->links() }}</div>
            @endif
        </div>
    </div>
@endsection
