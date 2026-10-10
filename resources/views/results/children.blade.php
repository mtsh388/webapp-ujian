@extends('template.app')

@section('title', 'Hasil Ujian Anak')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <span class="text-muted fw-light">Hasil Ujian /</span>
                    Hasil Ujian Anak
                </h4>
                <p class="text-muted mb-0">
                    Pantau hasil dan perkembangan ujian anak Anda.
                </p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted">Jumlah Anak</span>
                                <h3 class="mb-0 mt-2">
                                    {{ $attempts->pluck('student.id')->unique()->count() }}
                                </h3>
                            </div>
                            <span class="avatar-initial rounded bg-label-primary p-3">
                                <i class="bx bx-group fs-3"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted">Total Percobaan Ujian</span>
                                <h3 class="mb-0 mt-2">{{ $attempts->count() }}</h3>
                            </div>
                            <span class="avatar-initial rounded bg-label-info p-3">
                                <i class="bx bx-book-open fs-3"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted">Ujian Selesai</span>
                                <h3 class="mb-0 mt-2">
                                    {{ $attempts->whereNotNull('submitted_at')->count() }}
                                </h3>
                            </div>
                            <span class="avatar-initial rounded bg-label-success p-3">
                                <i class="bx bx-check-circle fs-3"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="mb-1">Daftar Hasil Ujian Anak</h5>
                    <p class="text-muted mb-0">
                        Informasi ujian seluruh anak yang terhubung dengan akun Anda.
                    </p>
                </div>

                <div class="input-group input-group-merge" style="max-width: 300px;">
                    <span class="input-group-text">
                        <i class="bx bx-search"></i>
                    </span>
                    <input
                        type="text"
                        id="searchResults"
                        class="form-control"
                        placeholder="Cari nama anak atau ujian..."
                    >
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover" id="childrenResultsTable">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Anak</th>
                        <th>Nama Ujian</th>
                        <th>Mulai Ujian</th>
                        <th>Selesai Ujian</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($attempts as $attempt)
                        @php
                            $status = $attempt->status ?? null;
                            $isFinished = !is_null($attempt->submitted_at);

                            $statusClass = $isFinished
                                ? 'bg-label-success'
                                : 'bg-label-warning';

                            $statusLabel = $isFinished
                                ? 'Selesai'
                                : 'Belum Selesai';
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                        <span class="fw-semibold">
                                            {{ $attempt->student->full_name }}
                                        </span>
                                    <small class="text-muted">
                                        {{ $attempt->student->student_number ?? '-' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                {{ $attempt->exam->title ?? $attempt->exam->name ?? '-' }}
                            </td>
                            <td>
                                {{ $attempt->started_at
                                    ? \Illuminate\Support\Carbon::parse($attempt->started_at)->format('d/m/Y H:i')
                                    : '-' }}
                            </td>
                            <td>
                                {{ $attempt->submitted_at
                                    ? \Illuminate\Support\Carbon::parse($attempt->submitted_at)->format('d/m/Y H:i')
                                    : '-' }}
                            </td>
                            <td>
                                @if ($isFinished)
                                    <span class="fw-semibold">
                                            {{ number_format((float) ($attempt->score ?? 0), 2, ',', '.') }}
                                        </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                    <span class="badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                            </td>
                            <td class="text-center">
                                @if ($isFinished)
                                    <a
                                        href="{{ route('results.children.show', $attempt->id) }}"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="bx bx-show-alt me-1"></i>
                                        Detail
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        disabled
                                    >
                                        <i class="bx bx-time me-1"></i>
                                        Menunggu
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bx bx-folder-open fs-1 text-muted"></i>
                                <h6 class="mt-2 mb-1">Belum Ada Hasil Ujian</h6>
                                <p class="text-muted mb-0">
                                    Hasil ujian anak akan muncul setelah anak mulai mengikuti ujian.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($attempts->isNotEmpty())
                <div class="card-footer text-muted">
                    Menampilkan {{ $attempts->count() }} percobaan ujian.
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchResults');
            const tableRows = document.querySelectorAll('#childrenResultsTable tbody tr');

            searchInput.addEventListener('input', function () {
                const keyword = this.value.toLowerCase().trim();

                tableRows.forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().includes(keyword)
                        ? ''
                        : 'none';
                });
            });
        });
    </script>
@endsection
