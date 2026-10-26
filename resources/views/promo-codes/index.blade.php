@extends('template.app')

@section('title', 'Kode Promo')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-1">{{ $promoCode->exists ? 'Edit Kode Promo' : 'Buat Kode Promo' }}</h4>
                        <p class="text-muted mb-0">Kode promo menggunakan teks bebas dan masa berlaku hingga tingkat tanggal serta waktu.</p>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <form method="POST" action="{{ $promoCode->exists ? route('promo-codes.update', $promoCode) : route('promo-codes.store') }}">
                            @csrf
                            @if ($promoCode->exists) @method('PUT') @endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="code" class="form-label">Kode Promo *</label>
                                    <input id="code" name="code" class="form-control" value="{{ old('code', $promoCode->code) }}" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nama/Keterangan</label>
                                    <input id="name" name="name" class="form-control" value="{{ old('name', $promoCode->name) }}" maxlength="255">
                                </div>
                                <div class="col-md-4">
                                    <label for="discount_type" class="form-label">Jenis Diskon *</label>
                                    <select id="discount_type" name="discount_type" class="form-select" required>
                                        <option value="percentage" @selected(old('discount_type', $promoCode->discount_type) === 'percentage')>Persentase (%)</option>
                                        <option value="fixed" @selected(old('discount_type', $promoCode->discount_type) === 'fixed')>Nominal (Rp)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="discount_value" class="form-label">Nilai Diskon *</label>
                                    <input id="discount_value" name="discount_value" type="number" min="1" max="100000000" class="form-control" value="{{ old('discount_value', $promoCode->discount_value) }}" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $promoCode->exists ? $promoCode->is_active : true))>
                                        <label class="form-check-label" for="is_active">Kode promo aktif</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="starts_at" class="form-label">Tanggal dan Waktu Mulai *</label>
                                    <input id="starts_at" name="starts_at" type="datetime-local" class="form-control" value="{{ old('starts_at', $promoCode->starts_at?->format('Y-m-d\TH:i')) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ends_at" class="form-label">Tanggal dan Waktu Berakhir *</label>
                                    <input id="ends_at" name="ends_at" type="datetime-local" class="form-control" value="{{ old('ends_at', $promoCode->ends_at?->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">{{ $promoCode->exists ? 'Simpan Perubahan' : 'Simpan Kode Promo' }}</button>
                                @if ($promoCode->exists)
                                    <a href="{{ route('promo-codes.index') }}" class="btn btn-outline-secondary">Batal</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Daftar Kode Promo</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Kode</th><th>Diskon</th><th>Mulai</th><th>Berakhir</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                            @forelse ($promoCodes as $item)
                                <tr>
                                    <td><div class="fw-semibold">{{ $item->code }}</div><small class="text-muted">{{ $item->name }}</small></td>
                                    <td>{{ $item->discount_type === 'percentage' ? $item->discount_value . '%' : 'Rp ' . number_format($item->discount_value, 0, ',', '.') }}</td>
                                    <td>{{ $item->starts_at->format('d-m-Y H:i') }}</td>
                                    <td>{{ $item->ends_at->format('d-m-Y H:i') }}</td>
                                    <td><span class="badge {{ $item->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('promo-codes.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form method="POST" action="{{ route('promo-codes.destroy', $item) }}" onsubmit="return confirm('Hapus kode promo ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kode promo.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body">{{ $promoCodes->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
