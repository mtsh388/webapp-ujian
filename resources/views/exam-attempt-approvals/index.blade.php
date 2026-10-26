@extends('template.app')

@section('title', 'Persetujuan Percobaan Kedua')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-1">Persetujuan Percobaan Kedua</h4>
                <p class="text-muted mb-0">Permintaan ini hanya berlaku untuk ujian berdurasi tanpa jadwal setelah siswa menyelesaikan percobaan pertama.</p>
            </div>
            <div class="card-body">
                @foreach (['success', 'error'] as $key)
                    @if (session($key)) <div class="alert alert-{{ $key === 'success' ? 'success' : 'danger' }}">{{ session($key) }}</div> @endif
                @endforeach
                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Siswa</th><th>Ujian</th><th>Diminta</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($approvals as $approval)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $approval->student?->full_name ?? '-' }}</div>
                                    <div class="small text-muted">{{ $approval->student?->student_number ?? '-' }} · {{ $approval->student?->classRoom?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $approval->exam?->title ?? '-' }}</div>
                                    <div class="small text-muted">{{ $approval->exam?->subject?->name ?? '-' }}</div>
                                </td>
                                <td>{{ $approval->requested_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td><span class="badge {{ $approval->status === 'approved' ? 'bg-label-success' : ($approval->status === 'pending' ? 'bg-label-warning' : 'bg-label-danger') }}">{{ ucfirst($approval->status) }}</span></td>
                                <td>{{ $approval->review_note ?: '-' }}</td>
                                <td>
                                    @if ($approval->status === 'pending')
                                        <div class="d-flex flex-column gap-2">
                                            <form method="POST" action="{{ route('exam-attempt-approvals.approve', $approval) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('exam-attempt-approvals.reject', $approval) }}">
                                                @csrf
                                                <input type="text" name="review_note" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan" required maxlength="1000">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="small text-muted">{{ $approval->reviewer?->name ?? '-' }}<br>{{ $approval->reviewed_at?->format('d-m-Y H:i') ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada permintaan persetujuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $approvals->links() }}</div>
        </div>
    </div>
@endsection
