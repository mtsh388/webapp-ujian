@extends('template.app')

@section('title', 'Master Data Keuangan')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div><h4 class="fw-bold mb-1">Master Data Keuangan</h4><p class="text-muted mb-0">Atur jenis biaya, tahun ajaran, tarif, dan rekening pembayaran.</p></div>
            <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">Kembali ke Tagihan</a>
        </div>
        @if(session('success'))<div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if(session('error'))<div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#fee-types" type="button">Jenis Biaya</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#academic-years" type="button">Tahun Ajaran</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fee-rates" type="button">Tarif Biaya</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bank-accounts" type="button">Rekening Pembayaran</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="fee-types">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Jenis Biaya</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.fee-types.store') }}" id="fee-type-form">@csrf<input type="hidden" name="id" id="fee-type-id">
                        <div class="row g-3"><div class="col-md-3"><label class="form-label">Kode</label><input class="form-control" name="code" id="fee-type-code" maxlength="40" required placeholder="SPP"></div><div class="col-md-4"><label class="form-label">Nama Biaya</label><input class="form-control" name="name" id="fee-type-name" required maxlength="120" placeholder="SPP Bulanan"></div><div class="col-md-5"><label class="form-label">Keterangan</label><input class="form-control" name="description" id="fee-type-description" maxlength="3000"></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="fee-type-is_active" checked><label class="form-check-label" for="fee-type-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Jenis Biaya</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('fee-type-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Kode</th><th>Jenis Biaya</th><th>Tarif</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @forelse($feeTypes as $item)<tr><td>{{ $item->code }}</td><td>{{ $item->name }}<div class="text-muted small">{{ $item->description }}</div></td><td>{{ $item->rates_count }}</td><td><span class="badge {{ $item->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-nowrap"><button class="btn btn-sm btn-outline-primary" type="button" onclick='editRecord("fee-type", {{ Illuminate\Support\Js::from($item->only(["id", "code", "name", "description", "is_active"])) }})'>Edit</button><form class="d-inline" method="POST" action="{{ route('billing.master.fee-types.destroy', $item) }}" onsubmit="return confirm('Hapus jenis biaya ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>@empty<tr><td colspan="5" class="text-center text-muted p-4">Belum ada jenis biaya.</td></tr>@endforelse
                </tbody></table></div></div>
            </div>

            <div class="tab-pane fade" id="academic-years">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Tahun Ajaran</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.academic-years.store') }}" id="academic-year-form">@csrf<input type="hidden" name="id" id="academic-year-id">
                        <div class="row g-3"><div class="col-md-4"><label class="form-label">Nama Tahun Ajaran</label><input class="form-control" name="name" id="academic-year-name" required maxlength="40" placeholder="2026/2027"></div><div class="col-md-3"><label class="form-label">Tanggal Mulai</label><input class="form-control" type="date" name="starts_on" id="academic-year-starts_on" required></div><div class="col-md-3"><label class="form-label">Tanggal Selesai</label><input class="form-control" type="date" name="ends_on" id="academic-year-ends_on" required></div><div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="academic-year-is_active" checked><label class="form-check-label" for="academic-year-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Tahun Ajaran</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('academic-year-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Tahun Ajaran</th><th>Mulai</th><th>Selesai</th><th>Tarif</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @forelse($academicYears as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->starts_on->format('d/m/Y') }}</td><td>{{ $item->ends_on->format('d/m/Y') }}</td><td>{{ $item->fee_rates_count }}</td><td><span class="badge {{ $item->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-nowrap"><button class="btn btn-sm btn-outline-primary" type="button" onclick='editRecord("academic-year", {{ Illuminate\Support\Js::from(["id" => $item->id, "name" => $item->name, "starts_on" => $item->starts_on->format("Y-m-d"), "ends_on" => $item->ends_on->format("Y-m-d"), "is_active" => $item->is_active]) }})'>Edit</button><form class="d-inline" method="POST" action="{{ route('billing.master.academic-years.destroy', $item) }}" onsubmit="return confirm('Hapus tahun ajaran ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-muted p-4">Belum ada tahun ajaran.</td></tr>@endforelse
                </tbody></table></div></div>
            </div>

            <div class="tab-pane fade" id="fee-rates">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Tarif Biaya</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.fee-rates.store') }}" id="fee-rate-form">@csrf<input type="hidden" name="id" id="fee-rate-id">
                        <div class="row g-3"><div class="col-md-4"><label class="form-label">Jenis Biaya</label><select class="form-select" name="fee_type_id" id="fee-rate-fee_type_id" required><option value="">Pilih jenis biaya</option>@foreach($feeTypes as $item)<option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Tahun Ajaran</label><select class="form-select" name="academic_year_id" id="fee-rate-academic_year_id" required><option value="">Pilih tahun ajaran</option>@foreach($academicYears as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Kelas (opsional)</label><select class="form-select" name="class_id" id="fee-rate-class_id"><option value="">Semua kelas</option>@foreach($classes as $item)<option value="{{ $item->id }}">{{ $item->name }} ({{ $item->education_level }} {{ $item->grade_level }})</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Jenis Periode</label><select class="form-select" name="period_type" id="fee-rate-period_type" required><option value="semester">Semesteran</option><option value="year">Tahunan</option></select></div><div class="col-md-3"><label class="form-label">Label Periode</label><input class="form-control" name="period_label" id="fee-rate-period_label" maxlength="80" required placeholder="Semester Ganjil"></div><div class="col-md-3"><label class="form-label">Nominal (Rp)</label><input class="form-control" type="number" min="1" step="0.01" name="amount" id="fee-rate-amount" required></div><div class="col-md-3"><label class="form-label">Tanggal jatuh tempo (hari)</label><input class="form-control" type="number" min="1" max="31" name="due_day" id="fee-rate-due_day" placeholder="Opsional"></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="fee-rate-is_active" checked><label class="form-check-label" for="fee-rate-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Tarif</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('fee-rate-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Jenis Biaya</th><th>Tahun Ajaran</th><th>Kelas</th><th>Periode</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @forelse($feeRates as $item)<tr><td>{{ $item->feeType->name }}</td><td>{{ $item->academicYear->name }}</td><td>{{ $item->classRoom?->name ?? 'Semua kelas' }}</td><td>{{ $item->period_label }} ({{ $item->period_type === 'year' ? 'Tahunan' : 'Semesteran' }})</td><td>Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</td><td><span class="badge {{ $item->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-nowrap"><button class="btn btn-sm btn-outline-primary" type="button" onclick='editRecord("fee-rate", {{ Illuminate\Support\Js::from($item->only(["id", "fee_type_id", "academic_year_id", "class_id", "period_type", "period_label", "amount", "due_day", "is_active"])) }})'>Edit</button><form class="d-inline" method="POST" action="{{ route('billing.master.fee-rates.destroy', $item) }}" onsubmit="return confirm('Hapus tarif ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>@empty<tr><td colspan="7" class="text-center text-muted p-4">Belum ada tarif biaya.</td></tr>@endforelse
                </tbody></table></div></div>
            </div>

            <div class="tab-pane fade" id="bank-accounts">
                <div class="card mb-4"><div class="card-header fw-semibold">Form Rekening Pembayaran</div><div class="card-body">
                    <form method="POST" action="{{ route('billing.master.bank-accounts.store') }}" id="bank-account-form">@csrf<input type="hidden" name="id" id="bank-account-id">
                        <div class="row g-3"><div class="col-md-3"><label class="form-label">Nama Bank</label><input class="form-control" name="bank_name" id="bank-account-bank_name" required maxlength="100" placeholder="Bank BSI"></div><div class="col-md-3"><label class="form-label">Nama Pemilik Rekening</label><input class="form-control" name="account_name" id="bank-account-account_name" required maxlength="120"></div><div class="col-md-3"><label class="form-label">Nomor Rekening</label><input class="form-control" name="account_number" id="bank-account-account_number" required maxlength="80"></div><div class="col-md-3"><label class="form-label">Cabang (opsional)</label><input class="form-control" name="branch" id="bank-account-branch" maxlength="100"></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="bank-account-is_active" checked><label class="form-check-label" for="bank-account-is_active">Aktif</label></div></div></div>
                        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan Rekening</button><button class="btn btn-outline-secondary" type="button" onclick="resetForm('bank-account-form')">Batal edit</button></div>
                    </form>
                </div></div>
                <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Bank</th><th>Pemilik</th><th>Nomor Rekening</th><th>Cabang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @forelse($bankAccounts as $item)<tr><td>{{ $item->bank_name }}</td><td>{{ $item->account_name }}</td><td>{{ $item->account_number }}</td><td>{{ $item->branch ?: '-' }}</td><td><span class="badge {{ $item->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-nowrap"><button class="btn btn-sm btn-outline-primary" type="button" onclick='editRecord("bank-account", {{ Illuminate\Support\Js::from($item->only(["id", "bank_name", "account_name", "account_number", "branch", "is_active"])) }})'>Edit</button><form class="d-inline" method="POST" action="{{ route('billing.master.bank-accounts.destroy', $item) }}" onsubmit="return confirm('Hapus rekening ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-muted p-4">Belum ada rekening pembayaran.</td></tr>@endforelse
                </tbody></table></div></div>
            </div>
        </div>
    </div>
</div>
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
function resetForm(formId) {
    const form = document.getElementById(formId);
    form.reset();
    const hidden = form.querySelector('input[name="id"]');
    if (hidden) hidden.value = '';
}
</script>
@endsection
