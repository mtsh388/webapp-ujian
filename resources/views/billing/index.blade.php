@extends('template.app')

@section('title', 'Tagihan dan Pemasukan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex align-items-center"><i class="bx bx-check-circle me-2"></i><div>{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex align-items-center"><i class="bx bx-error-circle me-2"></i><div>{{ session('error') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!$canManage && $hasOutstanding)
        <div class="alert alert-warning alert-dismissible" role="alert">
            <div class="d-flex align-items-center"><i class="bx bx-info-circle me-2"></i><div><strong>Pengingat pembayaran:</strong> masih ada tagihan yang belum lunas. Anda tetap dapat mengikuti kelas dan ujian; pesan ini hanya pengingat.</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card px-3">
        <div class="card-header border-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h4 class="card-title mb-1">Tagihan dan Pemasukan</h4>
                    <p class="card-subtitle text-muted mb-0">Tagihan semesteran/tahunan, pembayaran cicilan, dan status verifikasi.</p>
                </div>
                @if($canManage)
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        <a href="{{ route('billing.master.index') }}" class="btn btn-outline-secondary"><i class="bx bx-data me-1"></i>Master Data</a>
                        <a href="{{ route('billing.schedules.index') }}" class="btn btn-outline-secondary"><i class="bx bx-calendar-check me-1"></i>Jadwal Otomatis</a>
                        <a href="{{ route('billing.report') }}" class="btn btn-outline-primary"><i class="bx bx-bar-chart-alt-2 me-1"></i>Laporan Pemasukan</a>
                        <a href="{{ route('billing.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Buat Tagihan</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-datatable table-responsive">
            <table id="billingTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tagihan</th>
                        @if($canManage)<th>Siswa</th>@endif
                        <th>Periode</th>
                        <th>Jatuh Tempo</th>
                        <th>Nominal</th>
                        <th>Terbayar</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('#billingTable');
    if (!table || !window.DataTable) return;

    const canManage = @json($canManage);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    const money = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits: 0});
    const statusBadge = row => {
        if (row.payment_status === 'paid') return '<span class="badge bg-label-success">Lunas</span>';
        if (row.is_overdue) return '<span class="badge bg-label-danger">Jatuh tempo</span>';
        if (row.payment_status === 'partial') return '<span class="badge bg-label-info">Cicilan</span>';
        return '<span class="badge bg-label-warning">Belum dibayar</span>';
    };

    const columns = [
        {data:'title', name:'title', render:(value,type,row)=>type!=='display'?value:'<span class="fw-semibold">'+esc(value)+'</span><small class="d-block text-muted">'+esc(row.student_number)+'</small>'}
    ];
    if (canManage) columns.push({data:'student_name', name:'student_name', orderable:false, render:(value,type,row)=>type!=='display'?value:esc(value)+'<small class="d-block text-muted">'+esc(row.student_number)+'</small>'});
    columns.push(
        {data:'period_label', name:'period_label', render:(value,type,row)=>type!=='display'?value:esc(row.period_type==='semester'?'Semester':'Tahunan')+'<small class="d-block text-muted">'+esc(value)+'</small>'},
        {data:'due_date', name:'due_date'},
        {data:'amount', name:'amount', render:(value,type)=>type==='display'?money(value):value},
        {data:'verified_amount', name:'verified_amount', orderable:false, searchable:false, render:(value,type)=>type==='display'?money(value):value},
        {data:'outstanding_amount', name:'outstanding_amount', orderable:false, searchable:false, render:(value,type)=>type==='display'?money(value):value},
        {data:'payment_status', name:'payment_status', render:(value,type,row)=>type!=='display'?value:statusBadge(row)+(row.has_pending_payment?'<small class="d-block text-muted">Menunggu verifikasi</small>':'')},
        {data:'id', name:'id', orderable:false, searchable:false, render:(id,type)=>type==='display'?'<a href="/tagihan/'+id+'" class="btn btn-sm btn-outline-primary"><i class="bx bx-show-alt me-1"></i>Detail</a>':id}
    );

    new DataTable(table, {
        processing:true,
        serverSide:true,
        ajax:{url:@json(route('billing.data')),type:'GET'},
        pageLength:10,
        lengthMenu:[[10,25,50,75,100],[10,25,50,75,100]],
        order:[[canManage ? 3 : 2,'desc']],
        responsive:false,
        scrollX:true,
        dom:'<"row mx-2"<"col-sm-12 col-md-6 d-flex align-items-center"l><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>t<"row mx-2 mt-3 mb-4"<"col-sm-12 col-md-6 d-flex align-items-center"i><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"p>>',
        columns:columns,
        language:{
            processing:'Memuat...',
            search:'',
            searchPlaceholder:'Cari tagihan atau siswa...',
            lengthMenu:'_MENU_',
            info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty:'Menampilkan 0 hasil',
            zeroRecords:'Data tidak ditemukan',
            emptyTable:'Belum ada tagihan.',
            paginate:{first:'Pertama',last:'Terakhir',next:'Selanjutnya',previous:'Sebelumnya'}
        },
        initComplete:function(){
            const wrapper=this.api().table().container();
            wrapper.querySelectorAll('.dataTables_filter input').forEach(el=>el.classList.add('form-control'));
            wrapper.querySelectorAll('.dataTables_length select').forEach(el=>el.classList.add('form-select'));
        }
    });
});
</script>
@endsection
