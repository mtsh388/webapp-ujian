@extends('template.app')

@section('title', 'Laporan Pemasukan')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-1">Laporan Pemasukan</h4><p class="text-muted mb-0">Hanya pembayaran yang sudah diverifikasi admin dihitung sebagai pemasukan.</p></div><a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">Kembali</a></div>
        <div class="card mb-4"><div class="card-body"><form method="GET" action="{{ route('billing.report') }}" class="row g-3 align-items-end"><div class="col-md-4"><label class="form-label" for="from">Dari tanggal</label><input class="form-control" type="date" id="from" name="from" value="{{ $from }}"></div><div class="col-md-4"><label class="form-label" for="to">Sampai tanggal</label><input class="form-control" type="date" id="to" name="to" value="{{ $to }}"></div><div class="col-md-4"><button class="btn btn-primary" type="submit">Terapkan Filter</button> <a class="btn btn-outline-secondary" href="{{ route('billing.report') }}">Reset</a></div></form></div></div>
        <div class="row g-4 mb-4"><div class="col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Total Pemasukan Terverifikasi</small><h3 class="mb-0 mt-2">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3></div></div></div><div class="col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Jumlah Transaksi</small><h3 class="mb-0 mt-2">{{ number_format($transactionCount, 0, ',', '.') }}</h3></div></div></div></div>
        <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal bayar</th><th>Siswa</th><th>Tagihan</th><th>Metode</th><th>Nominal</th><th>Diverifikasi oleh</th></tr></thead><tbody>
            @forelse($payments as $payment)<tr><td>{{ $payment->paid_at->format('d/m/Y') }}</td><td>{{ $payment->invoice->student->full_name }}</td><td>{{ $payment->invoice->title }}<small class="d-block text-muted">{{ $payment->invoice->period_label }}</small></td><td>{{ $payment->method === 'transfer' ? 'Transfer' : 'Tunai' }}</td><td>Rp {{ number_format((float)$payment->amount, 0, ',', '.') }}</td><td>{{ $payment->verifiedBy?->name ?? '-' }}</td></tr>
            @empty<tr><td colspan="6" class="text-center py-5 text-muted">Belum ada pemasukan terverifikasi untuk periode ini.</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $payments->links() }}</div></div>
    </div>
</div>
@endsection
