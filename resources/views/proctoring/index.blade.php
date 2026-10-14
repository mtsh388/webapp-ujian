@extends('template.app')

@section('title', 'Log Aktivitas Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Aktivitas hari ini</span>
                            <span class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="bx bx-error-circle"></i>
                                </span>
                            </span>
                        </div>
                        <h3 class="mb-0">{{ number_format($totalToday, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Sesi dengan aktivitas hari ini</span>
                            <span class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-user-check"></i>
                                </span>
                            </span>
                        </div>
                        <h3 class="mb-0">{{ number_format($attemptsFlagged, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Sesi aktif perlu ditinjau</span>
                            <span class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-shield-quarter"></i>
                                </span>
                            </span>
                        </div>
                        <h3 class="mb-0">{{ number_format($activeAttemptsFlagged, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card px-3">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">
                            Log Aktivitas Ujian
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            Pantau aktivitas ujian yang perlu ditinjau. Waktu dicatat oleh server; tinjau konteks sebelum mengambil tindakan.
                        </p>
                    </div>

                    <span class="badge bg-label-secondary">
                        Total {{ number_format($violations->total(), 0, ',', '.') }} aktivitas
                    </span>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table class="table table-bordered table-hover align-middle w-100">
                    <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Peserta</th>
                        <th>Ujian</th>
                        <th>Aktivitas</th>
                        <th>Total Kejadian Sesi</th>
                        <th>Status Sesi</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse ($violations as $violation)
                        <tr>
                            <td class="text-nowrap">
                                <div class="fw-medium">
                                    {{ $violation->created_at?->format('d-m-Y') ?? '-' }}
                                </div>
                                <small class="text-muted">
                                    {{ $violation->created_at?->format('H:i:s') ?? '-' }}
                                </small>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $violation->attempt?->student?->full_name ?? 'Peserta tidak tersedia' }}
                                </div>
                                <small class="text-muted">
                                    Sesi #{{ $violation->exam_attempt_id }}
                                </small>
                            </td>

                            <td>
                                {{ $violation->attempt?->exam?->title ?? 'Ujian tidak tersedia' }}
                            </td>

                            <td>
                                <span class="badge bg-label-warning">
                                    {{ $violation->event_label }}
                                </span>

                                @if (!empty($violation->metadata['visibility_state']))
                                    <small class="d-block text-muted mt-1">
                                        {{ $violation->metadata['visibility_state'] }}
                                    </small>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $violation->attempt?->violations_count ?? $violation->attempt?->violations()->count() ?? 0 }}
                            </td>

                            <td>
                                @if ($violation->attempt?->status === 'in_progress')
                                    <span class="badge bg-label-primary">Sedang Ujian</span>
                                @else
                                    <span class="badge bg-label-secondary">
                                        {{ $violation->attempt?->status ? ucfirst(str_replace('_', ' ', $violation->attempt->status)) : 'Tidak tersedia' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="mb-2">
                                    <i class="bx bx-shield-quarter text-muted" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="mb-1">Belum Ada Aktivitas Ujian</h6>
                                <p class="text-muted mb-0">
                                    Aktivitas ujian yang tercatat akan ditampilkan di sini.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($violations->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $violations->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection
