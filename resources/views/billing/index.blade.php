@extends('template.app')

@section('title', 'Tagihan dan Pemasukan')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1">Tagihan dan Pembayaran</h4>
                <p class="text-muted mb-0">Tagihan semesteran/tahunan, pembayaran cicilan, dan status verifikasi.</p>
            </div>
            @if($canManage)
                <div class="d-flex gap-2">
                    <a href="{{ route('billing.report') }}" class="btn btn-outline-primary">Laporan Pemasukan</a>
                    <a href="{{ route('billing.create') }}" class="btn btn-primary">Buat Tagihan</a>
                </div>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(!$canManage && $invoices->getCollection()->contains(fn ($invoice) => $invoice->payment_status !== 'paid'))
            <div class="alert alert-warning" role="alert">
                <strong>Pengingat pembayaran:</strong> masih ada tagihan yang belum lunas. Anda tetap dapat mengikuti kelas dan ujian; pesan ini hanya pengingat.
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Tagihan</th>@if($canManage)<th>Siswa</th>@endif<th>Periode</th><th>Jatuh Tempo</th><th>Nominal</th><th>Terbayar</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><span class="fw-semibold">{{ $invoice->title }}</span><small class="d-block text-muted">{{ $invoice->student->student_number }}</small></td>
                            @if($canManage)<td>{{ $invoice->student->full_name }}</td>@endif
                            <td>{{ $invoice->period_type === 'semester' ? 'Semester' : 'Tahunan' }}<small class="d-block text-muted">{{ $invoice->period_label }}</small></td>
                            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td>Rp {{ number_format((float)$invoice->amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float)$invoice->verified_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($invoice->payment_status === 'paid')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($invoice->is_overdue)
                                    <span class="badge bg-danger">Jatuh tempo</span>
                                @elseif($invoice->payment_status === 'partial')
                                    <span class="badge bg-info">Cicilan</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum dibayar</span>
                                @endif
                                @if($invoice->payments->contains(fn ($payment) => $payment->status === 'pending'))
                                    <small class="d-block text-muted">Menunggu verifikasi</small>
                                @endif
                            </td>
                            <td><a href="{{ route('billing.show', $invoice) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 8 : 7 }}" class="text-center py-5 text-muted">Belum ada tagihan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $invoices->links() }}</div>
        </div>
    </div>
</div>
@endsection
