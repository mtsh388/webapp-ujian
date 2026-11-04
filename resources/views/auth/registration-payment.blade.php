<!doctype html>
<html lang="id" class="layout-wide customizer-hide" data-assets-path="{{ asset('assets/sneat') }}/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Registrasi | QSEE</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/sneat/img/Logo.jpeg') }}">
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/pages/page-auth.css') }}">
</head>
<body>
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner" style="max-width: 760px;">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-5">
                        <img src="{{ asset('assets/sneat/img/Logo.jpeg') }}" alt="QSEE" style="width:48px;height:48px;object-fit:contain">
                        <h3 class="mb-0">QSEE</h3>
                    </div>
                    <h4 class="mb-1">Pembayaran Registrasi</h4>
                    <p class="text-muted mb-4">Data siswa dan orang tua sudah disimpan. Unggah bukti pembayaran untuk mengirim pendaftaran ke Keuangan.</p>
                    @if ($payment->status === 'rejected')
                        <div class="alert alert-danger">
                            <h6 class="alert-heading mb-1">Pembayaran ditolak</h6>
                            <p class="mb-0">{{ $payment->rejection_reason ?: 'Bukti pembayaran perlu diperbaiki.' }} Silakan unggah ulang bukti pembayaran yang benar.</p>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="border rounded p-4 mb-4">
                        <div class="d-flex justify-content-between gap-3 mb-2"><span class="text-muted">Nama Siswa</span><strong>{{ $payment->student?->full_name ?? $payment->user?->name }}</strong></div>
                        <div class="d-flex justify-content-between gap-3 mb-2"><span class="text-muted">Kelas</span><strong>{{ $payment->class_name_snapshot ?: 'Kelas belum tercatat' }}</strong></div>
                        <div class="d-flex justify-content-between gap-3 mb-2"><span class="text-muted">Kode Promo Pendaftaran</span><strong>Tidak digunakan</strong></div>
                        <hr>
                        <div class="d-flex justify-content-between gap-3"><span class="fw-semibold">Total Biaya Pendaftaran</span><strong class="fs-4 text-primary">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</strong></div>
                    </div>
                    <form method="POST" action="{{ request()->fullUrl() }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="payment_proof" class="form-label">Bukti Pembayaran <span class="text-danger">*</span></label>
                            <input id="payment_proof" name="payment_proof" type="file" class="form-control @error('payment_proof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                            <div class="form-text">Format JPG, PNG, atau PDF. Maksimal 5 MB.</div>
                            @error('payment_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Kirim Bukti Pembayaran</button>
                    </form>
                    <p class="text-muted small mt-4 mb-0">Setelah bukti dikirim, Keuangan akan memverifikasi pembayaran. Akun Orang Tua dapat digunakan setelah pembayaran pendaftaran disetujui. Biaya bimbel dipilih dan dibayar secara terpisah.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
