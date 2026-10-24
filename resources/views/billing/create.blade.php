@extends('template.app')

@section('title', 'Buat Tagihan')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-4"><h4 class="fw-bold mb-1">Buat Tagihan Siswa</h4><p class="text-muted mb-0">Buat tagihan individual dengan periode semesteran atau tahunan.</p></div>
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('billing.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="student_id">Siswa</label><select class="form-select" id="student_id" name="student_id" required><option value="">Pilih siswa</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->full_name }} ({{ $student->student_number }})</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label" for="title">Nama tagihan</label><input class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="180" required placeholder="Contoh: Biaya Bimbingan Belajar"></div>
                    <div class="col-md-4"><label class="form-label" for="period_type">Periode</label><select class="form-select" id="period_type" name="period_type" required><option value="semester" @selected(old('period_type') === 'semester')>Semesteran</option><option value="year" @selected(old('period_type') === 'year')>Tahunan</option></select></div>
                    <div class="col-md-4"><label class="form-label" for="period_label">Label periode</label><input class="form-control" id="period_label" name="period_label" value="{{ old('period_label') }}" required maxlength="80" placeholder="Contoh: Semester Ganjil 2026/2027"></div>
                    <div class="col-md-4"><label class="form-label" for="amount">Nominal (Rp)</label><input class="form-control" id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount') }}" required></div>
                    <div class="col-md-4"><label class="form-label" for="due_date">Jatuh tempo</label><input class="form-control" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}" required></div>
                    <div class="col-12"><label class="form-label" for="notes">Catatan (opsional)</label><textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
                </div>
                <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Simpan Tagihan</button><a class="btn btn-outline-secondary" href="{{ route('billing.index') }}">Batal</a></div>
            </form>
        </div></div>
    </div>
</div>
@endsection
