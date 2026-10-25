@extends('template.app')

@section('title', 'Master Data Biaya')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="card-title mb-1">Master Data Biaya</h4>
                <p class="text-muted mb-0">Kelola tarif, diskon/promo, dan cicilan. Biaya Pendaftaran selalu dibayar penuh tanpa promo.</p>
            </div>
            @can('registration-fee.create')
                <a href="{{ route('registration-fees.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Tambah Biaya
                </a>
            @endcan
        </div>
        <div class="table-responsive">
            <table id="feesTable" class="table table-hover w-100">
                <thead>
                <tr>
                    <th>Nama Biaya</th>
                    <th>Jenjang</th>
                    <th>Kelas</th>
                    <th>Periode</th>
                    <th>Nominal</th>
                    <th>Diskon / Promo</th>
                    <th>Maks. Cicilan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody></tbody>           </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);

    const money = value => 'Rp' + Number(value || 0).toLocaleString('id-ID');
    const canEdit = @json(auth()->user()?->can('registration-fee.update') ?? false);
    const canDelete = @json(auth()->user()?->can('registration-fee.delete') ?? false);
    const baseUrl = @json(url('master-data/biaya'));
    const csrf = @json(csrf_token());

    new DataTable('#feesTable', {
        processing: true,
        serverSide: true,
        ajax: @json(route('registration-fees.data')),
        pageLength: 10,
        order: [[0, 'asc']],
        columns: [
            { data: 'fee_type_label', render: (value, type, row) => '<div class="fw-semibold">' + escapeHtml(value || row.fee_type || 'Biaya') + '</div>' + (row.description ? '<small class="text-muted">' + escapeHtml(row.description) + '</small>' : '') },
            { data: 'education_level', render: value => escapeHtml(value || 'Semua') },
            { data: 'grade_level', render: value => value ? 'Kelas ' + escapeHtml(value) : 'Semua' },
            { data: 'billing_period', render: value => ({ once: 'Sekali', monthly: 'Bulanan', semester: 'Per Semester', yearly: 'Tahunan' })[value] || '—' },
            { data: 'amount', render: value => escapeHtml(money(value)) },
            { data: 'promo_code', orderable: false, render: (value, type, row) => {
                if (row.fee_type === 'registration') return '<span class="badge bg-label-secondary">Tidak berlaku</span>';
                if (!row.discount_enabled) return '<span class="badge bg-label-secondary">Tidak</span>';
                const discount = row.discount_type === 'percentage' ? escapeHtml(row.discount_value) + '%' : escapeHtml(money(row.discount_value));
                return '<div class="fw-semibold">' + escapeHtml(value || '-') + '</div><small class="text-muted">' + discount + '<br>' + escapeHtml(row.promo_starts_at || '-') + ' – ' + escapeHtml(row.promo_ends_at || '-') + '</small>';
            }},
            { data: 'max_installments', render: (value, type, row) => row.fee_type === 'registration' ? 'Tidak bisa dicicil' : escapeHtml(value || 1) + ' kali' },
            { data: 'is_active', render: value => value ? '<span class="badge bg-label-success">Aktif</span>' : '<span class="badge bg-label-secondary">Tidak Aktif</span>' },
            { data: 'id', orderable: false, searchable: false, render: id => {
                let html = '<div class="d-flex gap-2">';
                if (canEdit) html += '<a href="' + baseUrl + '/' + Number(id) + '/edit" class="btn btn-sm btn-label-primary">Edit</a>';
                if (canDelete) html += '<form method="POST" action="' + baseUrl + '/' + Number(id) + '" class="delete-fee-form"><input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="btn btn-sm btn-label-danger">Hapus</button></form>';
                return html + '</div>';
            }}
        ],
        language: {
            processing: 'Memuat data...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            zeroRecords: 'Data tidak ditemukan',
            emptyTable: 'Belum ada data biaya.',
            paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('.delete-fee-form');
        if (!form) return;
        event.preventDefault();
        Swal.fire({
            title: 'Hapus data biaya?',
            text: 'Data biaya ini akan dihapus dari master.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endsection
