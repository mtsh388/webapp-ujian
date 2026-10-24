@extends('template.app')

@section('title', 'Jadwal Tagihan Otomatis')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center"><i class="bx bx-check-circle me-2"></i><div>{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center"><i class="bx bx-error-circle me-2"></i><div>{{ session('error') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h4 class="card-title mb-1">Jadwal Tagihan Otomatis</h4>
                    <p class="card-subtitle text-muted mb-0">Atur waktu pembuatan tagihan semesteran atau tahunan untuk siswa aktif.</p>
                </div>
                <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Kembali ke Tagihan
                </a>
            </div>
        </div>
        <div class="card-body border-bottom py-3">
            <div class="alert alert-label-primary mb-0 py-2 px-3">
                <div class="d-flex align-items-center">
                    <i class="bx bx-info-circle me-2"></i>
                    <small>Jadwal diproses oleh Laravel Scheduler. Lokal: jalankan <code>php artisan schedule:work</code>. Server: aktifkan cron <code>php artisan schedule:run</code> setiap menit.</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <h5 class="mb-3"><i class="bx bx-calendar-plus me-1"></i> Tambah Jadwal</h5>
            <form method="POST" action="{{ route('billing.schedules.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label" for="fee_rate_id">Tarif Biaya</label>
                        <select class="form-select" name="fee_rate_id" id="fee_rate_id" required>
                            <option value="">Pilih tarif biaya</option>
                            @foreach($feeRates as $rate)
                                <option value="{{ $rate->id }}" data-period="{{ $rate->period_label }}" data-year="{{ $rate->academicYear->name }}" @selected(old('fee_rate_id') == $rate->id)>
                                    {{ $rate->feeType->name }} — {{ $rate->period_label }} ({{ $rate->academicYear->name }}){{ $rate->classRoom ? ' — '.$rate->classRoom->name : ' — Semua kelas' }} — Rp {{ number_format((float)$rate->amount, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label" for="period_label">Label Periode Tagihan</label>
                        <input class="form-control" name="period_label" id="period_label" required maxlength="80" value="{{ old('period_label') }}" placeholder="Contoh: Semester Ganjil 2026/2027">
                        <div class="form-text">Gunakan label unik untuk setiap periode tagihan.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="scheduled_at">Waktu Generate</label>
                        <input class="form-control" type="datetime-local" name="scheduled_at" id="scheduled_at" required min="{{ now()->format('Y-m-d\\TH:i') }}" value="{{ old('scheduled_at') }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="due_date">Tanggal Jatuh Tempo</label>
                        <input class="form-control" type="date" name="due_date" id="due_date" required min="{{ now()->toDateString() }}" value="{{ old('due_date') }}">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', '1') === '1')>
                            <label class="form-check-label" for="is_active">Aktifkan jadwal</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h5 class="card-title mb-1">Daftar Jadwal</h5>
                    <p class="card-subtitle text-muted mb-0">Data dimuat menggunakan DataTables server-side.</p>
                </div>
                <div id="billingSchedulesTableTools"></div>
            </div>
        </div>
        <div class="card-body border-bottom py-3">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-calendar-check"></i></span></div>
                        <div><small class="text-muted d-block">Penjadwalan</small><span class="fw-semibold">Generate Tagihan</span></div>
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="alert alert-label-info mb-0 py-2 px-3">
                        <div class="d-flex align-items-center"><i class="bx bx-shield-quarter me-2"></i><small>Tagihan yang sudah dibuat tidak digandakan. Riwayat jadwal yang telah dijalankan tidak dapat dihapus atau diaktifkan ulang.</small></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table id="billingSchedulesTable" class="table table-hover border-top align-middle">
                <thead>
                    <tr>
                        <th>Tarif / Periode</th>
                        <th>Waktu Generate</th>
                        <th>Jatuh Tempo</th>
                        <th>Hasil</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rateSelect = document.getElementById('fee_rate_id');
    const periodLabel = document.getElementById('period_label');
    rateSelect?.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        if (option?.dataset.period && !periodLabel.value.trim()) {
            periodLabel.value = option.dataset.period + ' ' + (option.dataset.year || '');
        }
    });

    if (!window.jQuery || !jQuery.fn.DataTable) {
        console.error('DataTables belum dimuat pada template.');
        return;
    }

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));

    jQuery('#billingSchedulesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[1, 'desc']],
        ajax: {
            url: @json(route('billing.schedules.data')),
            type: 'GET'
        },
        columns: [
            {
                data: 'fee_type',
                name: 'fee_type',
                render: function (value, type, row) {
                    if (type !== 'display') return value;
                    return '<span class="fw-semibold">' + escapeHtml(value) + '</span>' +
                        '<small class="d-block text-muted">' + escapeHtml(row.period_label) + ' · ' + escapeHtml(row.academic_year) + '</small>' +
                        '<small class="d-block text-muted">' + escapeHtml(row.class_name) + '</small>';
                }
            },
            { data: 'scheduled_at', name: 'scheduled_at' },
            { data: 'due_date', name: 'due_date' },
            {
                data: 'generated_count',
                name: 'generated_count',
                render: function (value, type, row) {
                    if (type !== 'display') return value;
                    let html = row.last_run_at
                        ? '<span class="fw-semibold">' + escapeHtml(value) + ' tagihan dibuat</span><small class="d-block text-muted">' + escapeHtml(row.last_run_at) + '</small>'
                        : '<span class="text-muted">Menunggu jadwal</span>';
                    if (row.last_result) html += '<small class="d-block text-muted">' + escapeHtml(row.last_result) + '</small>';
                    return html;
                }
            },
            {
                data: 'is_active',
                name: 'is_active',
                render: function (value, type, row) {
                    if (type !== 'display') return value ? 1 : 0;
                    if (row.last_run_at) return '<span class="badge bg-label-secondary">Selesai</span>';
                    return value
                        ? '<span class="badge bg-label-success">Aktif</span>'
                        : '<span class="badge bg-label-warning">Nonaktif</span>';
                }
            },
            {
                data: 'id',
                name: 'id',
                orderable: false,
                searchable: false,
                render: function (id, type, row) {
                    if (type !== 'display') return id;
                    if (row.last_run_at) return '<span class="text-muted small">Riwayat tersimpan</span>';
                    const toggleLabel = row.is_active ? 'Nonaktifkan' : 'Aktifkan';
                    const toggleIcon = row.is_active ? 'bx-pause-circle' : 'bx-play-circle';
                    const toggleClass = row.is_active ? 'text-warning' : 'text-success';
                    return '<div class="dropdown">' +
                        '<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-label="Aksi jadwal"><i class="bx bx-dots-vertical-rounded"></i></button>' +
                        '<div class="dropdown-menu dropdown-menu-end">' +
                        '<form method="POST" action="/keuangan/jadwal-tagihan/' + id + '/status">@csrf<form>' +
                        '<form method="POST" action="/keuangan/jadwal-tagihan/' + id + '/status">' +
                        '<input type="hidden" name="_token" value="' + escapeHtml(document.querySelector('meta[name="csrf-token"]')?.content || '') + '">' +
                        '<input type="hidden" name="_method" value="PATCH">' +
                        '<button type="submit" class="dropdown-item ' + toggleClass + '"><i class="bx ' + toggleIcon + ' me-1"></i>' + toggleLabel + '</button></form>' +
                        '<form method="POST" action="/keuangan/jadwal-tagihan/' + id + '" onsubmit="return confirm(\'Hapus jadwal ini?\')">' +
                        '<input type="hidden" name="_token" value="' + escapeHtml(document.querySelector('meta[name="csrf-token"]')?.content || '') + '">' +
                        '<input type="hidden" name="_method" value="DELETE">' +
                        '<button type="submit" class="dropdown-item text-danger"><i class="bx bx-trash me-1"></i> Hapus</button></form>' +
                        '</div></div>';
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: '',
            searchPlaceholder: 'Cari tarif atau periode...',
            lengthMenu: '_MENU_',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 hasil',
            zeroRecords: 'Data tidak ditemukan',
            emptyTable: 'Belum ada jadwal generate tagihan',
            paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
        },
        initComplete: function () {
            jQuery('#billingSchedulesTable_wrapper .dataTables_filter input').addClass('form-control');
            jQuery('#billingSchedulesTable_wrapper .dataTables_length select').addClass('form-select');
        }
    });
});
</script>
@endsection
