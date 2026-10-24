@extends('template.app')

@section('title', 'Master Data Keuangan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
        <div class="card px-3 mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div><h4 class="card-title mb-1">Master Data Keuangan</h4><p class="card-subtitle text-muted mb-0">Atur jenis biaya, tahun ajaran, tarif, dan rekening pembayaran.</p></div>
                    <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Kembali ke Tagihan</a>
                </div>
            </div>
            <div class="card-body pb-0"><ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#fee-types" type="button">Jenis Biaya</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#academic-years" type="button">Tahun Ajaran</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fee-rates" type="button">Tarif Biaya</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bank-accounts" type="button">Rekening Pembayaran</button></li>
            </ul></div>
        </div>
        @if(session('success'))<div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if(session('error'))<div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="tab-content">
            <div class="tab-pane fade show active" id="fee-types">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Jenis Biaya</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.fee-types.store') }}" id="fee-type-form">@csrf<input type="hidden" name="id" id="fee-type-id">
                        <div class="row g-3"><div class="col-md-3"><label class="form-label">Kode</label><input class="form-control" name="code" id="fee-type-code" maxlength="40" required placeholder="SPP"></div><div class="col-md-4"><label class="form-label">Nama Biaya</label><input class="form-control" name="name" id="fee-type-name" required maxlength="120" placeholder="SPP Bulanan"></div><div class="col-md-5"><label class="form-label">Keterangan</label><input class="form-control" name="description" id="fee-type-description" maxlength="3000"></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="fee-type-is_active" checked><label class="form-check-label" for="fee-type-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Jenis Biaya</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('fee-type-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card px-3"><div class="card-header border-bottom"><div><h5 class="card-title mb-1">Daftar Jenis Biaya</h5><p class="card-subtitle text-muted mb-0">Pencarian, pengurutan, dan pagination diproses di server.</p></div></div><div class="card-datatable table-responsive"><table id="feeTypesTable" class="table table-bordered"><thead><tr><th>Kode</th><th>Jenis Biaya</th><th>Tarif</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div>
            </div>

            <div class="tab-pane fade" id="academic-years">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Tahun Ajaran</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.academic-years.store') }}" id="academic-year-form">@csrf<input type="hidden" name="id" id="academic-year-id">
                        <div class="row g-3"><div class="col-md-4"><label class="form-label">Nama Tahun Ajaran</label><input class="form-control" name="name" id="academic-year-name" required maxlength="40" placeholder="2026/2027"></div><div class="col-md-3"><label class="form-label">Tanggal Mulai</label><input class="form-control" type="date" name="starts_on" id="academic-year-starts_on" required></div><div class="col-md-3"><label class="form-label">Tanggal Selesai</label><input class="form-control" type="date" name="ends_on" id="academic-year-ends_on" required></div><div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="academic-year-is_active" checked><label class="form-check-label" for="academic-year-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Tahun Ajaran</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('academic-year-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card px-3"><div class="card-header border-bottom"><div><h5 class="card-title mb-1">Daftar Tahun Ajaran</h5><p class="card-subtitle text-muted mb-0">Pencarian, pengurutan, dan pagination diproses di server.</p></div></div><div class="card-datatable table-responsive"><table id="academicYearsTable" class="table table-bordered"><thead><tr><th>Tahun Ajaran</th><th>Mulai</th><th>Selesai</th><th>Tarif</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div>
            </div>

            <div class="tab-pane fade" id="fee-rates">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Tarif Biaya</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.fee-rates.store') }}" id="fee-rate-form">@csrf<input type="hidden" name="id" id="fee-rate-id">
                        <div class="row g-3"><div class="col-md-4"><label class="form-label">Jenis Biaya</label><select class="form-select" name="fee_type_id" id="fee-rate-fee_type_id" required><option value="">Pilih jenis biaya</option>@foreach($feeTypes as $item)<option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Tahun Ajaran</label><select class="form-select" name="academic_year_id" id="fee-rate-academic_year_id" required><option value="">Pilih tahun ajaran</option>@foreach($academicYears as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Kelas (opsional)</label><select class="form-select" name="class_id" id="fee-rate-class_id"><option value="">Semua kelas</option>@foreach($classes as $item)<option value="{{ $item->id }}">{{ $item->name }} ({{ $item->education_level }} {{ $item->grade_level }})</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Jenis Periode</label><select class="form-select" name="period_type" id="fee-rate-period_type" required><option value="semester">Semesteran</option><option value="year">Tahunan</option></select></div><div class="col-md-3"><label class="form-label">Label Periode</label><input class="form-control" name="period_label" id="fee-rate-period_label" maxlength="80" required placeholder="Semester Ganjil"></div><div class="col-md-3"><label class="form-label">Nominal (Rp)</label><input class="form-control" type="number" min="1" step="0.01" name="amount" id="fee-rate-amount" required></div><div class="col-md-3"><label class="form-label">Tanggal jatuh tempo (hari)</label><input class="form-control" type="number" min="1" max="31" name="due_day" id="fee-rate-due_day" placeholder="Opsional"></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="fee-rate-is_active" checked><label class="form-check-label" for="fee-rate-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Tarif</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('fee-rate-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card px-3"><div class="card-header border-bottom"><div><h5 class="card-title mb-1">Daftar Tarif Biaya</h5><p class="card-subtitle text-muted mb-0">Pencarian, pengurutan, dan pagination diproses di server.</p></div></div><div class="card-datatable table-responsive"><table id="feeRatesTable" class="table table-bordered"><thead><tr><th>Jenis Biaya</th><th>Tahun Ajaran</th><th>Kelas</th><th>Periode</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div>
            </div>

            <div class="tab-pane fade" id="bank-accounts">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Rekening Pembayaran</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.bank-accounts.store') }}" id="bank-account-form">@csrf<input type="hidden" name="id" id="bank-account-id">
                        <div class="row g-3"><div class="col-md-3"><label class="form-label">Nama Bank</label><input class="form-control" name="bank_name" id="bank-account-bank_name" required maxlength="100" placeholder="Bank BSI"></div><div class="col-md-3"><label class="form-label">Nama Pemilik Rekening</label><input class="form-control" name="account_name" id="bank-account-account_name" required maxlength="120"></div><div class="col-md-3"><label class="form-label">Nomor Rekening</label><input class="form-control" name="account_number" id="bank-account-account_number" required maxlength="80"></div><div class="col-md-3"><label class="form-label">Cabang (opsional)</label><input class="form-control" name="branch" id="bank-account-branch" maxlength="100"></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="bank-account-is_active" checked><label class="form-check-label" for="bank-account-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Rekening</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('bank-account-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card px-3"><div class="card-header border-bottom"><div><h5 class="card-title mb-1">Daftar Rekening Pembayaran</h5><p class="card-subtitle text-muted mb-0">Pencarian, pengurutan, dan pagination diproses di server.</p></div></div><div class="card-datatable table-responsive"><table id="bankAccountsTable" class="table table-bordered"><thead><tr><th>Bank</th><th>Pemilik</th><th>Nomor Rekening</th><th>Cabang</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div>
            </div>
        </div>
    </div>

@section('scripts')
<script>
function editRecord(prefix, data) {
    const form = document.getElementById(prefix + '-form');
    Object.keys(data).forEach((key) => {
        const input = document.getElementById(prefix + '-' + key);
        if (!input) return;
        if (input.type === 'checkbox') input.checked = Boolean(data[key]);
        else input.value = data[key] ?? '';
    });
    const tab = document.querySelector('[data-bs-target="#' + ({
        'fee-type':'fee-types',
        'academic-year':'academic-years',
        'fee-rate':'fee-rates',
        'bank-account':'bank-accounts'
    }[prefix]) + '"]');
    if (tab && window.bootstrap) bootstrap.Tab.getOrCreateInstance(tab).show();
    form.scrollIntoView({behavior:'smooth', block:'center'});
}
function editRecordFromButton(button) {
    try { editRecord(button.dataset.editPrefix, JSON.parse(button.dataset.record || '{}')); }
    catch (error) { console.error('Data edit tidak valid', error); }
}
function resetForm(formId) {
    const form = document.getElementById(formId);
    form.reset();
    const hidden = form.querySelector('input[name="id"]');
    if (hidden) hidden.value = '';
}
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    const statusBadge = active => active ? '<span class="badge bg-label-success">Aktif</span>' : '<span class="badge bg-label-secondary">Nonaktif</span>';
    const actions = (prefix, row, deleteUrl, editData) => '<div class="d-flex gap-1"><button class="btn btn-sm btn-outline-primary" type="button" data-edit-prefix="' + prefix + '" data-record="' + esc(JSON.stringify(editData)) + '" onclick="editRecordFromButton(this)"><i class="bx bx-edit-alt"></i> Edit</button><form method="POST" action="' + deleteUrl + '" onsubmit="return confirm(\\'Hapus data ini?\\')"><input type="hidden" name="_token" value="' + esc(csrfToken) + '"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="bx bx-trash"></i> Hapus</button></form></div>';
    const initTable = (selector, type, columns, order) => {
        const table = document.querySelector(selector);
        if (!table || !window.DataTable) return;
        new DataTable(table, {
            processing: true, serverSide: true, pageLength: 10,
            lengthMenu: [[10,25,50,75,100],[10,25,50,75,100]],
            ajax: {url: @json(route('billing.master.data', ['type' => '__TYPE__'])).replace('__TYPE__', type), type:'GET'},
            columns, order: order || [[0,'asc']], responsive:false, scrollX:true,
            dom: '<"row mx-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>t<"row mx-2 mt-3 mb-4"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 d-flex justify-content-end"p>>',
            language: {processing:'Memuat...',search:'',searchPlaceholder:'Cari data...',lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 hasil',zeroRecords:'Data tidak ditemukan',emptyTable:'Belum ada data',paginate:{first:'Pertama',last:'Terakhir',next:'Selanjutnya',previous:'Sebelumnya'}},
            initComplete:function(){const wrapper=this.api().table().container();wrapper.querySelectorAll('.dataTables_filter input').forEach(el=>el.classList.add('form-control'));wrapper.querySelectorAll('.dataTables_length select').forEach(el=>el.classList.add('form-select'));}
        });
    };
    initTable('#feeTypesTable','fee-types',[
        {data:'code',render:(d,t,r)=>t==='display'?'<span class="fw-semibold">'+esc(d)+'</span>':d},
        {data:'name',render:(d,t,r)=>t==='display'?esc(d)+'<small class="d-block text-muted">'+esc(r.description || '')+'</small>':d},
        {data:'rates_count',orderable:false,searchable:false},
        {data:'is_active',orderable:false,searchable:false,render:(d,t)=>t==='display'?statusBadge(d):d},
        {data:'id',orderable:false,searchable:false,render:(id,t,r)=>t!=='display'?id:actions('fee-type',r,'/keuangan/master-data/jenis-biaya/'+id,{id,code:r.code,name:r.name,description:r.description,is_active:r.is_active})}
    ],[[1,'asc']]);
    initTable('#academicYearsTable','academic-years',[
        {data:'name'}, {data:'starts_on'}, {data:'ends_on'},
        {data:'fee_rates_count',orderable:false,searchable:false},
        {data:'is_active',orderable:false,searchable:false,render:(d,t)=>t==='display'?statusBadge(d):d},
        {data:'id',orderable:false,searchable:false,render:(id,t,r)=>t!=='display'?id:actions('academic-year',r,'/keuangan/master-data/tahun-ajaran/'+id,{id,name:r.name,starts_on:r.starts_on_value,ends_on:r.ends_on_value,is_active:r.is_active})}
    ],[[0,'desc']]);
    initTable('#feeRatesTable','fee-rates',[
        {data:'fee_type',orderable:false}, {data:'academic_year',orderable:false}, {data:'class_name',orderable:false},
        {data:'period_label',render:(d,t,r)=>t==='display'?esc(d)+' <small class="text-muted">('+ (r.period_type==='year'?'Tahunan':'Semesteran') +')</small>':d},
        {data:'amount',render:(d,t)=>t==='display'?'Rp '+Number(d).toLocaleString('id-ID'):d},
        {data:'is_active',orderable:false,searchable:false,render:(d,t)=>t==='display'?statusBadge(d):d},
        {data:'id',orderable:false,searchable:false,render:(id,t,r)=>t!=='display'?id:actions('fee-rate',r,'/keuangan/master-data/tarif/'+id,{id,fee_type_id:r.fee_type_id,academic_year_id:r.academic_year_id,class_id:r.class_id,period_type:r.period_type,period_label:r.period_label,amount:r.amount,due_day:r.due_day,is_active:r.is_active})}
    ],[[3,'asc']]);
    initTable('#bankAccountsTable','bank-accounts',[
        {data:'bank_name'}, {data:'account_name'}, {data:'account_number'}, {data:'branch',render:d=>esc(d || '-')},
        {data:'is_active',orderable:false,searchable:false,render:(d,t)=>t==='display'?statusBadge(d):d},
        {data:'id',orderable:false,searchable:false,render:(id,t,r)=>t!=='display'?id:actions('bank-account',r,'/keuangan/master-data/rekening/'+id,{id,bank_name:r.bank_name,account_name:r.account_name,account_number:r.account_number,branch:r.branch,is_active:r.is_active})}
    ]);
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab=>tab.addEventListener('shown.bs.tab',()=>window.dispatchEvent(new Event('resize'))));
});
</script>
@endsection
