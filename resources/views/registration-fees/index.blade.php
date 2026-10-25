@extends('template.app')

@section('title', 'Master Data Biaya')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="card-title mb-1">Master Data Biaya</h4>
                <p class="text-muted mb-0">Kelola tarif pendaftaran dan biaya lain. Tarif pendaftaran digunakan pada formulir registrasi Berbayar.</p>
            </div>
            @can('registration-fee.create')
                <a href="{{ route('registration-fees.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Tambah Biaya
                </a>
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Jenis Biaya</th>
                    <th>Nama Biaya</th>
                    <th>Jenjang</th>
                    <th>Kelas</th>
                    <th>Periode</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($fees as $fee)
                    <tr>
                        <td>{{ $fee->fee_type_label ?: ($typeLabels[$fee->fee_type] ?? \Illuminate\Support\Str::headline($fee->fee_type)) }}</td>
                        <td>
                            <div class="fw-semibold">{{ $fee->name ?: 'Biaya Pendaftaran' }}</div>
                            @if($fee->description)
                                <small class="text-muted">{{ $fee->description }}</small>
                            @endif
                        </td>
                        <td>{{ $fee->education_level ?: 'Semua' }}</td>
                        <td>{{ $fee->grade_level ? 'Kelas '.$fee->grade_level : 'Semua' }}</td>
                        <td>{{ ['once' => 'Sekali', 'monthly' => 'Bulanan', 'semester' => 'Per Semester', 'yearly' => 'Tahunan'][$fee->billing_period] ?? '—' }}</td>
                        <td>Rp{{ number_format($fee->amount, 0, ',', '.') }}</td>
                        <td>
                            @if($fee->is_active)
                                <span class="badge bg-label-success">Aktif</span>
                            @else
                                <span class="badge bg-label-secondary">Tidak Aktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                @can('registration-fee.update')
                                    <a href="{{ route('registration-fees.edit', $fee) }}" class="btn btn-sm btn-label-primary">Edit</a>
                                @endcan
                                @can('registration-fee.delete')
                                    <form method="POST" action="{{ route('registration-fees.destroy', $fee) }}" class="delete-fee-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-label-danger">Hapus</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada data biaya.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-fee-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({
                title: 'Hapus data biaya?',
                text: 'Data biaya ini akan dihapus dari master.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endsection
