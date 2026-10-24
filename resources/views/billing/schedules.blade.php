@extends('template.app')

@section('title', 'Jadwal Tagihan Otomatis')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div><h4 class="fw-bold mb-1">Jadwal Tagihan Otomatis</h4><p class="text-muted mb-0">Atur kapan sistem membuat tagihan untuk siswa aktif berdasarkan tarif dan kelas.</p></div>
            <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">Kembali ke Tagihan</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="alert alert-info">
            <strong>Catatan aktivasi:</strong> jadwal diproses otomatis oleh Laravel Scheduler. Pada komputer lokal, jalankan <code>php artisan schedule:work</code>. Pada server, tambahkan cron Laravel Scheduler sesuai petunjuk setelah pull.
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Tambah Jadwal</div>
            <div class="card-body">
                <form method="POST" action="{{ route('billing.schedules.store') }}">@csrf
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Tarif</label><select class="form-select" name="fee_rate_id" id="fee_rate_id" required><option value="">Pilih tarif biaya</option>@foreach($feeRates as $rate)<option value="{{ $rate->id }}" data-period="{{ $rate->period_label }}" data-year="{{ $rate->academicYear->name }}">{{ $rate->feeType->name }} — {{ $rate->period_label }} ({{ $rate->academicYear->name }}){{ $rate->classRoom ? ' — '.$rate->classRoom->name : ' — Semua kelas' }} — Rp {{ number_format((float)$rate->amount,0,',','.') }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Label Periode Tagihan</label><input class="form-control" name="period_label" id="period_label" required maxlength="80" placeholder="Contoh: Semester Ganjil 2026/2027"><div class="form-text">Label unik periode yang akan muncul pada tagihan. Gunakan label berbeda untuk periode berbeda.</div></div>
                        <div class="col-md-6"><label class="form-label">Waktu Generate</label><input class="form-control" type="datetime-local" name="scheduled_at" required min="{{ now()->format('Y-m-d\TH:i') }}"></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Jatuh Tempo</label><input class="form-control" type="date" name="due_date" required min="{{ now()->toDateString() }}"></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked><label class="form-check-label" for="is_active">Aktifkan jadwal</label></div></div>
                    </div>
                    <button class="btn btn-primary mt-3">Simpan Jadwal</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Tarif / Periode</th><th>Waktu Generate</th><th>Jatuh Tempo</th><th>Hasil</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($schedules as $schedule)
                    <tr>
                        <td><strong>{{ $schedule->feeRate->feeType->name }}</strong><small class="d-block text-muted">{{ $schedule->period_label }} · {{ $schedule->feeRate->academicYear->name }}</small><small class="d-block text-muted">{{ $schedule->feeRate->classRoom?->name ?? 'Semua kelas' }}</small></td>
                        <td>{{ $schedule->scheduled_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $schedule->due_date->format('d/m/Y') }}</td>
                        <td>{{ $schedule->last_run_at ? $schedule->generated_count.' tagihan dibuat' : 'Menunggu jadwal' }}@if($schedule->last_result)<small class="d-block text-muted">{{ $schedule->last_result }}</small>@endif</td>
                        <td>@if($schedule->last_run_at)<span class="badge bg-secondary">Selesai</span>@elseif($schedule->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-warning text-dark">Nonaktif</span>@endif</td>
                        <td>@if(!$schedule->last_run_at)<form method="POST" action="{{ route('billing.schedules.toggle',$schedule) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">{{ $schedule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form><form method="POST" action="{{ route('billing.schedules.destroy',$schedule) }}" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>@else<span class="text-muted small">Riwayat tersimpan</span>@endif</td>
                    </tr>
                @empty<tr><td colspan="6" class="text-center text-muted p-4">Belum ada jadwal generate tagihan.</td></tr>@endforelse
                </tbody>
            </table></div>
            <div class="card-footer">{{ $schedules->links() }}</div>
        </div>
    </div>
</div>
<script>
document.getElementById('fee_rate_id')?.addEventListener('change', function () {
    const option = this.options[this.selectedIndex];
    const label = document.getElementById('period_label');
    if (option?.dataset.period && !label.value) label.value = option.dataset.period + ' ' + option.dataset.year;
});
</script>
@endsection
