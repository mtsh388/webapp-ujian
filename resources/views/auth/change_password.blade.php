@extends('template.app')

@section('title', 'Ubah Password')

@section('content')
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="mb-4">
                            <h4 class="fw-bold mb-2">Ubah Password</h4>
                            <p class="text-muted mb-0">
                                Silakan buat password baru untuk melanjutkan.
                            </p>
                        </div>

                        @if (session('warning'))
                            <div class="alert alert-warning">
                                {{ session('warning') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3 form-password-toggle">
                                <label for="current_password" class="form-label">
                                    Password Saat Ini
                                </label>

                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        name="current_password"
                                        id="current_password"
                                        class="form-control @error('current_password') is-invalid @enderror"
                                        autocomplete="current-password"
                                        required
                                        autofocus>

                                    <span class="input-group-text cursor-pointer">
                                    <i class="icon-base bx bx-hide"></i>
                                </span>
                                </div>

                                @error('current_password')
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="mb-3 form-password-toggle">
                                <label for="password" class="form-label">
                                    Password Baru
                                </label>

                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        autocomplete="new-password"
                                        required>

                                    <span class="input-group-text cursor-pointer">
                                    <i class="icon-base bx bx-hide"></i>
                                </span>
                                </div>

                                @error('password')
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="mb-4 form-password-toggle">
                                <label for="password_confirmation" class="form-label">
                                    Konfirmasi Password Baru
                                </label>

                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        class="form-control"
                                        autocomplete="new-password"
                                        required>

                                    <span class="input-group-text cursor-pointer">
                                    <i class="icon-base bx bx-hide"></i>
                                </span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Simpan Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.form-password-toggle').forEach(function (wrapper) {
                    const input = wrapper.querySelector('input[type="password"], input[type="text"]');
                    const toggle = wrapper.querySelector('.input-group-text');
                    const icon = toggle?.querySelector('i');

                    if (!input || !toggle || !icon) {
                        return;
                    }

                    toggle.addEventListener('click', function () {
                        const isPassword = input.type === 'password';

                        input.type = isPassword ? 'text' : 'password';

                        icon.classList.toggle('bx-hide', !isPassword);
                        icon.classList.toggle('bx-show', isPassword);
                    });
                });
            });
        </script>
    @endpush
@endsection
