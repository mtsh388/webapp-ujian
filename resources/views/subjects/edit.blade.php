@extends('template.app')

@section('title', 'Edit Mata Pelajaran')

@section('content')
    <div class="container-fluid py-3">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="mb-1 fw-semibold">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Mata Pelajaran
                        </h4>
                        <p class="text-muted mb-0">
                            Perbarui informasi mata pelajaran.
                        </p>
                    </div>

                    <a href="{{ route('subjects.index') }}" class="btn btn-secondary border">
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('subjects.update', $subject) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">

                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">
                                Nama Mata Pelajaran
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $subject->name) }}"
                                placeholder="Contoh: Matematika"
                                maxlength="255"
                                required
                            >

                            @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="code" class="form-label fw-semibold">
                                Kode Mata Pelajaran
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="code"
                                id="code"
                                class="form-control text-uppercase @error('code') is-invalid @enderror"
                                value="{{ old('code', $subject->code) }}"
                                placeholder="Contoh: MTK"
                                maxlength="50"
                                required
                            >

                            @error('code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror

                            <div class="form-text">
                                Gunakan huruf, angka, tanda hubung, atau underscore.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">
                                Deskripsi
                            </label>

                            <textarea
                                name="description"
                                id="description"
                                rows="5"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Masukkan deskripsi mata pelajaran jika diperlukan..."
                            >{{ old('description', $subject->description) }}</textarea>

                            @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="is_active" class="form-label fw-semibold">
                                Status
                            </label>

                            <select
                                name="is_active"
                                id="is_active"
                                class="form-select @error('is_active') is-invalid @enderror"
                            >
                                <option
                                    value="1" {{ old('is_active', $subject->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option
                                    value="0" {{ old('is_active', $subject->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>
                                    Tidak Aktif
                                </option>
                            </select>

                            @error('is_active')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                    </div>

                    <div class="border-top mt-4 pt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a
                                href="{{ route('subjects.show', $subject) }}"
                                class="btn btn-light border"
                            >
                                <i class="bi bi-eye me-1"></i>
                                Lihat Detail
                            </a>

                            <div class="d-flex gap-2">
                                <a
                                    href="{{ route('subjects.index') }}"
                                    class="btn btn-secondary border"
                                >
                                    Batal
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <script>
        document.getElementById('code')?.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    </script>
@endsection
