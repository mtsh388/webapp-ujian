@extends('template.app')

@section('title', 'Master Data Biaya Pendaftaran')

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
                <h4 class="card-title mb-1">Master Data Biaya Pendaftaran</h4>
                <p class="text-muted mb-0">Tarif di sini digunakan sebagai sumber biaya pada registrasi Berbayar.</p>
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
                    <th>Jenjang</th>
                    <th>Kelas</th>
                    <th>Biaya Pendaftaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($fees as $fee)
                    <tr>
                        <td>{{ $fee->education_level }}</td>
                        <td>Kelas {{ $fee->grade_level }}</td>
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
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data biaya pendaftaran.</td>
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
                title: 'Hapus biaya pendaftaran?',
                text: 'Data tarif ini akan dihapus dari master.',
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
