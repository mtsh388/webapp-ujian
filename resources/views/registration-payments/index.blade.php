@extends('template.app')

@section('title', 'Verifikasi Pembayaran Registrasi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-1">Verifikasi Pembayaran Registrasi</h4>
                <p class="text-muted mb-0">Periksa data siswa, orang tua, nominal tagihan, dan bukti pembayaran sebelum menyetujui pendaftaran.</p>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Siswa</th><th>Orang Tua</th><th>Nominal</th><th>Rencana Bimbel</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $payment->student?->full_name ?? $payment->user?->name ?? '-' }}</div>
                                    <div class="small text-muted">{{ $payment->user?->email ?? '-' }}</div>
                                    <div class="small text-muted">{{ $payment->student?->classRoom?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    @forelse ($payment->student?->parents ?? collect() as $parent)
                                        <div>{{ $parent->full_name }}</div>
                                        <div class="small text-muted">{{ $parent->user?->email ?? '-' }}</div>
                                    @empty
                                        -
                                    @endforelse
                                </td>
                                <td class="text-nowrap">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                                <td><div>{{ $payment->duration_years ?? 1 }} tahun</div><div class="small text-muted">{{ $payment->installment_count ?? 1 }} kali pembayaran</div><div class="small text-muted">Promo: {{ $payment->tuition_promo_code ?: '-' }}</div></td>
                                <td>
                                    <span class="badge {{ $payment->status === 'verified' ? 'bg-label-success' : ($payment->status === 'pending_verification' ? 'bg-label-warning' : ($payment->status === 'rejected' ? 'bg-label-danger' : 'bg-label-secondary')) }}">
                                        {{ str_replace('_', ' ', ucfirst($payment->status)) }}
                                    </span>
                                    @if ($payment->rejection_reason)
                                        <div class="small text-danger mt-1">{{ $payment->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->proof_path)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('registration-payments.proof', $payment) }}">Lihat Bukti</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->status === 'pending_verification')
                                        <div class="d-flex flex-column gap-2">
                                            <form method="POST" action="{{ route('registration-payments.approve', $payment) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('registration-payments.reject', $payment) }}">
                                                @csrf
                                                <input type="text" name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan" required maxlength="1000">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted small">{{ $payment->verifier?->name ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pembayaran registrasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $payments->links() }}</div>
        </div>
    </div>
@endsection
