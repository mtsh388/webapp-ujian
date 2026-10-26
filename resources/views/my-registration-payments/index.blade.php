@extends('template.app')

@section('title', 'Pembayaran Registrasi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-1">Pembayaran Registrasi</h4>
                <p class="text-muted mb-0">
                    @if ($isParent)
                        Pantau status pembayaran registrasi anak yang terhubung dengan akun Anda.
                    @else
                        Pantau status pembayaran registrasi dan hasil verifikasi dari Keuangan.
                    @endif
                </p>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($payments->count())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    @if ($isParent)
                                        <th>Siswa</th>
                                    @endif
                                    <th>Nominal</th>
                                    <th>Kode Promo</th>
                                    <th>Status Pembayaran</th>
                                    <th>Bukti Pembayaran</th>
                                    <th>Tanggal Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        @if ($isParent)
                                            <td>
                                                <div class="fw-semibold">{{ $payment->student?->full_name ?? $payment->user?->name ?? '-' }}</div>
                                                <div class="small text-muted">{{ $payment->student?->classRoom?->name ?? '-' }}</div>
                                            </td>
                                        @endif
                                        <td class="text-nowrap">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                                        <td>{{ $payment->promo_code ?: '-' }}</td>
                                        <td>
                                            @php
                                                $statusLabels = [
                                                    'awaiting_payment' => 'Menunggu Pembayaran',
                                                    'pending_verification' => 'Menunggu Verifikasi',
                                                    'verified' => 'Disetujui',
                                                    'rejected' => 'Ditolak',
                                                ];
                                                $statusClasses = [
                                                    'awaiting_payment' => 'bg-label-secondary',
                                                    'pending_verification' => 'bg-label-warning',
                                                    'verified' => 'bg-label-success',
                                                    'rejected' => 'bg-label-danger',
                                                ];
                                            @endphp
                                            <span class="badge {{ $statusClasses[$payment->status] ?? 'bg-label-secondary' }}">
                                                {{ $statusLabels[$payment->status] ?? ucfirst(str_replace('_', ' ', $payment->status)) }}
                                            </span>
                                            @if ($payment->rejection_reason)
                                                <div class="small text-danger mt-2">
                                                    <strong>Alasan penolakan:</strong> {{ $payment->rejection_reason }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($payment->proof_path)
                                                <span class="text-success"><i class="bx bx-check-circle me-1"></i>Bukti sudah dikirim</span>
                                            @else
                                                <span class="text-muted">Belum diunggah</span>
                                            @endif
                                            @if (in_array($payment->status, ['awaiting_payment', 'rejected'], true))
                                                <div class="mt-2">
                                                    <a href="{{ URL::temporarySignedRoute('register.payment.show', now()->addDays(30), ['registrationPayment' => $payment->id]) }}" class="btn btn-sm btn-outline-primary">
                                                        {{ $payment->status === 'rejected' ? 'Perbaiki Bukti' : 'Unggah Bukti' }}
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $payment->verified_at?->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($payments->hasPages())
                        <div class="mt-3">{{ $payments->links() }}</div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-wallet text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5>Belum Ada Data Pembayaran</h5>
                        <p class="text-muted mb-0">
                            @if ($isParent)
                                Belum ditemukan pembayaran registrasi untuk anak yang terhubung dengan akun Anda.
                            @else
                                Belum ditemukan data pembayaran registrasi untuk akun Anda.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
