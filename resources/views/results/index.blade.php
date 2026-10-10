@extends('template.app')

@section('title', 'Hasil Ujian')

@section('content')
    <div class="container-fluid py-3">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    Hasil Ujian
                </h4>

                <p class="text-muted mb-0">
                    Daftar hasil ujian siswa.
                </p>
            </div>

            <a
                href="{{ route('results.my') }}"
                class="btn btn-primary">
                <i class="bx bx-user me-1"></i>
                Hasil Saya
            </a>
        </div>

        <div class="card border-0 shadow-sm">

            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <h5 class="mb-0">
                        Riwayat Hasil Ujian
                    </h5>

                    <span class="text-muted small">
                        Total {{ $attempts->total() }} hasil
                    </span>
                </div>
            </div>

            <div class="card-body">

                @if ($attempts->count())

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th width="60">
                                    #
                                </th>

                                <th>
                                    Ujian
                                </th>

                                <th>
                                    Siswa
                                </th>

                                <th>
                                    Nilai
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Dikumpulkan
                                </th>

                                <th width="100">
                                    Aksi
                                </th>
                            </tr>
                            </thead>

                            <tbody>

                            @foreach ($attempts as $index => $attempt)

                                <tr>

                                    <td>
                                        {{ $attempts->firstItem() + $index }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ $attempt->exam?->title ?? '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $attempt->exam?->code ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        {{ $attempt->student?->full_name ?? '-' }}
                                    </td>

                                    <td>
                                            <span class="fw-bold">
                                                {{ number_format((float) $attempt->score, 2, ',', '.') }}
                                            </span>

                                        @if ($attempt->exam)
                                            <div class="small text-muted">
                                                Passing:
                                                {{ number_format((float) $attempt->exam->passing_score, 2, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($attempt->status === 'submitted')

                                            @if ($attempt->passed === true)
                                                <span class="badge bg-label-success">
                                                        Lulus
                                                    </span>
                                            @else
                                                <span class="badge bg-label-danger">
                                                        Tidak Lulus
                                                    </span>
                                            @endif

                                        @elseif ($attempt->status === 'expired')

                                            <span class="badge bg-label-warning">
                                                    Waktu Habis
                                                </span>

                                        @else

                                            <span class="badge bg-label-secondary">
                                                    {{ ucfirst($attempt->status) }}
                                                </span>

                                        @endif
                                    </td>

                                    <td>
                                        <div>
                                            {{ $attempt->submitted_at?->format('d M Y') ?? '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $attempt->submitted_at?->format('H:i:s') ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <a
                                            href="{{ route('results.show', $attempt->id) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Lihat hasil">
                                            <i class="bx bx-show"></i>
                                        </a>
                                    </td>

                                </tr>

                            @endforeach

                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $attempts->links() }}
                    </div>

                @else

                    <div class="text-center py-5">

                        <div class="mb-3">
                            <i
                                class="bx bx-file-blank text-muted"
                                style="font-size: 4rem;">
                            </i>
                        </div>

                        <h5>
                            Belum Ada Hasil Ujian
                        </h5>

                        <p class="text-muted mb-4">
                            Belum terdapat hasil ujian yang dapat ditampilkan.
                        </p>

                    </div>

                @endif

            </div>
        </div>

    </div>
@endsection
