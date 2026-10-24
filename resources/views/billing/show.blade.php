@extends('template.app')

@section('title', 'Detail Tagihan')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-1">{{ $invoice->title }}</h4><p class="text-muted mb-0">Tagihan untuk {{ $invoice->student->full_name }} — {{ $invoice->period_label }}</p></div><a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">Kembali</a></div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if($invoice->payment_status !== 'paid')
            <div class="alert alert-warning"><strong>Pengingat:</strong> sisa tagihan Rp {{ number_format((float)$invoice->outstanding_amount, 0, ',', '.') }}. Tagihan ini tidak membatasi akses kelas atau ujian.</div>
        @endif
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">Total Tagihan</small><h4 class="mt-2 mb-0">Rp {{ number_format((float)$invoice->amount, 0, ',', '.') }}</h4></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">Pembayaran Terverifikasi</small><h4 class="mt-2 mb-0">Rp {{ number_format((float)$invoice->verified_amount, 0, ',', '.') }}</h4></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">Sisa Tagihan</small><h4 class="mt-2 mb-0">Rp {{ number_format((float)$invoice->outstanding_amount, 0, ',', '.') }}</h4></div></div></div>
        </div>
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Informasi Tagihan</h5></div><div class="card-body">
            <dl class="row mb-0"><dt class="col-sm-3">Periode</dt><dd class="col-sm-9">{{ $invoice->period_type === 'semester' ? 'Semesteran' : 'Tahunan' }} — {{ $invoice->period_label }}</dd><dt class="col-sm-3">Jatuh tempo</dt><dd class="col-sm-9">{{ $invoice->due_date->format('d/m/Y') }}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ['unpaid' => 'Belum dibayar', 'partial' => 'Cicilan', 'paid' => 'Lunas'][$invoice->payment_status] }} @if($invoice->is_overdue)<span class="badge bg-danger">Jatuh tempo</span>@endif</dd><dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">{{ $invoice->notes ?: '-' }}</dd></dl>
        </div></div>

        @if($canSubmitPayment && $invoice->payment_status !== 'paid')
            <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Catat Pembayaran / Cicilan</h5></div><div class="card-body">
                <p class="text-muted">Pilih transfer atau tunai. Transaksi berstatus menunggu verifikasi dan belum masuk laporan pemasukan sampai disetujui admin.</p>
                <form method="POST" action="{{ route('billing.payments.store', $invoice) }}">@csrf
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label" for="amount">Nominal (Rp)</label><input class="form-control" type="number" name="amount" id="amount" min="1" step="0.01" max="{{ $invoice->outstanding_amount }}" required></div>
                        <div class="col-md-3"><label class="form-label" for="method">Metode</label><select class="form-select" name="method" id="method" required><option value="transfer">Transfer</option><option value="cash">Tunai</option></select></div>
                        <div class="col-md-3"><label class="form-label" for="paid_at">Tanggal bayar</label><input class="form-control" type="date" name="paid_at" id="paid_at" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-3"><label class="form-label" for="reference">Referensi transaksi</label><input class="form-control" name="reference" id="reference" maxlength="120" placeholder="No. bukti / catatan"></div>
                        <div class="col-12"><label class="form-label" for="notes">Catatan</label><textarea class="form-control" name="notes" id="notes" rows="2"></textarea></div>
                    </div><button class="btn btn-primary mt-3" type="submit">Simpan Pembayaran</button>
                </form>
            </div></div>
        @endif

        <div class="card"><div class="card-header"><h5 class="mb-0">Riwayat Transaksi</h5></div><div class="table-responsive">
            <table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Metode</th><th>Nominal</th><th>Referensi / Catatan</th><th>Status</th><th>Dicatat oleh</th>@if($canManage)<th>Aksi</th>@endif</tr></thead><tbody>
            @forelse($invoice->payments as $payment)
                <tr><td>{{ $payment->paid_at->format('d/m/Y') }}</td><td>{{ $payment->method === 'transfer' ? 'Transfer' : 'Tunai' }}</td><td>Rp {{ number_format((float)$payment->amount, 0, ',', '.') }}</td><td>{{ $payment->reference ?: '-' }}<small class="d-block text-muted">{{ $payment->notes }}</small></td><td>@if($payment->status === 'verified')<span class="badge bg-success">Terverifikasi</span>@elseif($payment->status === 'rejected')<span class="badge bg-danger">Ditolak</span>@else<span class="badge bg-warning text-dark">Menunggu verifikasi</span>@endif @if($payment->verifiedBy)<small class="d-block text-muted">Admin: {{ $payment->verifiedBy->name }}</small>@endif</td><td>{{ $payment->recordedBy?->name ?? '-' }}</td>
                @if($canManage)<td>@if($payment->status === 'pending')<div class="d-flex gap-1"><form method="POST" action="{{ route('billing.payments.verify', $payment) }}">@csrf<input type="hidden" name="status" value="verified"><button class="btn btn-sm btn-success" type="submit">Verifikasi</button></form><form method="POST" action="{{ route('billing.payments.verify', $payment) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-outline-danger" type="submit">Tolak</button></form></div>@else-@endif</td>@endif
                </tr>
            @empty<tr><td colspan="{{ $canManage ? 7 : 6 }}" class="text-center py-4 text-muted">Belum ada transaksi.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
</div>
@endsection
