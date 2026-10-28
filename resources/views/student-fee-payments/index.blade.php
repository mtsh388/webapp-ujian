@extends('template.app')

@section('title', 'Pembayaran Bimbel')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">Pembayaran Bimbel</h4>
            <p class="text-muted mb-0">{{ $isParent ? 'Pantau tagihan bimbel anak dan status cicilannya.' : 'Lihat tagihan bimbel, periode belajar, dan status cicilan Anda.' }}</p>
        </div>
        <div class="card-body">
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @forelse ($transactions as $transaction)
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">{{ $transaction->student?->full_name ?? 'Siswa' }} — {{ $transaction->duration_years }} tahun</h5>
                            <div class="text-muted small">{{ $transaction->student?->classRoom?->name ?? '-' }} · Periode {{ $transaction->period_starts_at->format('d M Y') }}–{{ $transaction->period_ends_at->format('d M Y') }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="text-muted small">Total tagihan</div>
                            <div class="fs-5 fw-semibold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</div>
                            <div class="small text-muted">Dibayar Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Rincian Kelas</th><th class="text-end">Nominal</th></tr></thead>
                            <tbody>
                                @foreach ($transaction->details ?? [] as $detail)
                                    <tr><td>{{ $detail['name'] ?? 'Belajar' }} — {{ $detail['education_level'] ?? '' }} Kelas {{ $detail['grade_level'] ?? '' }}
                                        @if ((int) ($detail['discount_amount'] ?? 0) > 0)<div class="small text-success">Nominal Rp {{ number_format((int) $detail['amount'], 0, ',', '.') }} · Diskon Rp {{ number_format((int) $detail['discount_amount'], 0, ',', '.') }}</div>@endif
                                    </td><td class="text-end">Rp {{ number_format((int) ($detail['amount_due'] ?? 0), 0, ',', '.') }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead><tr><th>Cicilan</th><th>Nominal</th><th>Status</th><th>Bukti / Aksi</th></tr></thead>
                            <tbody>
                                @foreach ($transaction->installments as $installment)
                                    <tr>
                                        <td>{{ $installment->installment_number }} dari {{ $transaction->installment_count }}</td>
                                        <td class="text-nowrap">Rp {{ number_format($installment->amount, 0, ',', '.') }}</td>
                                        <td>
                                            @php($labels = ['awaiting_payment' => 'Menunggu Pembayaran', 'pending_verification' => 'Menunggu Verifikasi', 'verified' => 'Disetujui', 'rejected' => 'Ditolak'])
                                            <span class="badge {{ $installment->status === 'verified' ? 'bg-label-success' : ($installment->status === 'pending_verification' ? 'bg-label-warning' : ($installment->status === 'rejected' ? 'bg-label-danger' : 'bg-label-secondary')) }}">{{ $labels[$installment->status] ?? $installment->status }}</span>
                                            @if ($installment->rejection_reason)<div class="small text-danger mt-1">{{ $installment->rejection_reason }}</div>@endif
                                        </td>
                                        <td>
                                            @if (in_array($installment->status, ['awaiting_payment', 'rejected'], true))
                                                <form method="POST" action="{{ route('student-fee-payments.proof.store', $installment) }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                                    @csrf
                                                    <input type="file" name="payment_proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
                                                    <button type="submit" class="btn btn-sm btn-primary">Kirim Bukti</button>
                                                </form>
                                            @elseif ($installment->status === 'pending_verification')
                                                <span class="text-muted small">Bukti sudah dikirim</span>
                                            @else
                                                <span class="text-success small">Pembayaran terverifikasi</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <h5>Belum Ada Tagihan Bimbel</h5>
                    <p class="text-muted mb-0">Tagihan bimbel akan muncul setelah pembayaran registrasi disetujui.</p>
                </div>
            @endforelse
            @if ($transactions->hasPages())<div class="mt-3">{{ $transactions->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection