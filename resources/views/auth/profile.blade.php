@extends('template.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Profil Saya</h4>
                        <p class="card-subtitle text-muted mb-0">
                            Kelola dan lihat informasi akun Anda.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('password.change') }}" class="btn btn-primary">
                            <i class="bx bx-lock-alt me-1"></i>
                            Ubah Password
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-lg">
                                <span class="avatar-initial rounded-circle bg-label-primary fs-3">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            </div>

                            <div>
                                <h5 class="mb-1">{{ $user->name }}</h5>
                                <p class="text-muted mb-1">{{ $user->email }}</p>

                                @if ($user->must_change_password)
                                    <span class="badge bg-label-warning">
                                        <i class="bx bx-error-circle me-1"></i>
                                        Password Perlu Diubah
                                    </span>
                                @else
                                    <span class="badge bg-label-success">
                                        <i class="bx bx-check-circle me-1"></i>
                                        Akun Aktif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex flex-wrap justify-content-md-end gap-3">
                            <div class="text-md-end">
                                <small class="text-muted d-block">Bergabung Sejak</small>
                                <span class="fw-semibold">
                                    {{ $user->created_at->translatedFormat('d F Y') }}
                                </span>
                            </div>

                            <div class="text-md-end">
                                <small class="text-muted d-block">Password Terakhir Diubah</small>
                                <span class="fw-semibold">
                                    {{ $user->password_changed_at
                                        ? $user->password_changed_at->translatedFormat('d F Y H:i')
                                        : 'Belum tersedia' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <h5 class="mb-1">Informasi Akun</h5>
                <p class="text-muted mb-4">
                    Informasi dasar akun yang sedang digunakan.
                </p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Nama</label>
                        <div class="form-control bg-light">
                            {{ $user->name }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Email</label>
                        <div class="form-control bg-light">
                            {{ $user->email }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Password Terakhir Diubah</label>
                        <div class="form-control bg-light">
                            {{ $user->password_changed_at
                                ? $user->password_changed_at->translatedFormat('d F Y H:i')
                                : 'Belum tersedia' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Bergabung Sejak</label>
                        <div class="form-control bg-light">
                            {{ $user->created_at->translatedFormat('d F Y') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
