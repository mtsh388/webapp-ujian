@extends('template.app')

@section('title', 'Verifikasi Pembayaran Bimbel')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">Verifikasi Pembayaran Bimbel</h4>
            <p class="text-muted mb-0">Verifikasi setiap cicilan yang dikirim siswa atau orang tua.</p>
        </div>
        <div class="card-body">
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Siswa</th><th>Paket / Periode</th><th>Total</th><th>Cicilan / Jatuh Tempo</th><th>Bukti</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($transactions as $transaction)
                    @foreach ($transaction->installments as $installment)
                        <tr>
                            <td><div class="fw-semibold">{{ $transaction->student?->full_name ?? '-' }}</div><div class="small text-muted">{{ $transaction->student?->classRoom?->name ?? '-' }}</div></td>
                            <td><div class="fw-semibold">{{ $transaction->package_type === 'semester' ? '1 Semester' : ($transaction->duration_months === 24 ? '2 Tahun' : '1 Tahun') }}</div><div class="small text-muted">{{ $transaction->period_starts_at->format('d M Y') }}–{{ $transaction->period_ends_at->format('d M Y') }}</div></td>
                            <td><div>Tagihan: Rp {{ number_format($transaction->amount, 0, ',', '.') }}</div><div class="small text-muted">Cicilan: Rp {{ number_format($installment->amount, 0, ',', '.') }}</div></td>
                            <td>
                                {{ $installment->installment_number }} / {{ $transaction->installment_count }}<div class="small text-muted">Jatuh tempo: {{ $installment->due_date?->format('d M Y') ?? '-' }}</div>
                                <div><span class="badge {{ $installment->status === 'verified' ? 'bg-label-success' : ($installment->status === 'pending_verification' ? 'bg-label-warning' : ($installment->status === 'rejected' ? 'bg-label-danger' : 'bg-label-secondary')) }}">{{ str_replace('_', ' ', ucfirst($installment->status)) }}</span></div>
                                @if ($installment->rejection_reason)<div class="small text-danger">{{ $installment->rejection_reason }}</div>@endif
                            </td>
                            <td>@if ($installment->proof_path)<a class="btn btn-sm btn-outline-primary" href="{{ route('student-fee-payments.proof', $installment) }}">Lihat Bukti</a>@else<span class="text-muted">Belum ada</span>@endif</td>
                            <td>
                                @if ($installment->status === 'pending_verification')
                                    <div class="d-flex flex-column gap-2">
                                        <form method="POST" action="{{ route('student-fee-payments.approve', $installment) }}">@csrf<button class="btn btn-sm btn-success" type="submit">Setujui</button></form>
                                        <form method="POST" action="{{ route('student-fee-payments.reject', $installment) }}">@csrf<input name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan" maxlength="1000" required><button class="btn btn-sm btn-outline-danger" type="submit">Tolak</button></form>
                                    </div>
                                @else
                                    <span class="text-muted small">{{ $installment->verifier?->name ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tagihan bimbel.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection