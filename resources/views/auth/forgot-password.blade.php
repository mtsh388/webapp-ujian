<!doctype html>

<html
    lang="id"
    class="layout-wide customizer-hide"
    data-assets-path="{{ asset('assets/sneat') }}/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

    <title>Lupa Password | QSEE</title>

    <meta
        name="description"
        content="Pemulihan password akun QSEE">

    <link
        rel="icon"
        type="image/x-icon"
        href="{{ asset('assets/sneat/img/Logo.jpeg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/fonts/iconify-icons.css') }}">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/css/core.css') }}">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/css/demo.css') }}">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/css/pages/page-auth.css') }}">

    <script src="{{ asset('assets/sneat/vendor/js/helpers.js') }}"></script>

    <script src="{{ asset('assets/sneat/js/config.js') }}"></script>
</head>

<body>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{ route('login') }}" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo"><img src="{{ asset('assets/sneat/img/Logo.jpeg') }}" alt="QSEE" style="width: 48px; height: 48px; object-fit: contain;"></span>
                            <span class="app-brand-text demo text-heading fw-bold">QSEE</span>
                        </a>
                    </div>
                    <div class="mb-4">
                        <h4 class="fw-bold mb-2">Lupa Password?</h4>
                        <p class="text-muted mb-0">
                            Masukkan email akun Anda untuk menerima link reset password.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                required
                                autofocus
                            >

                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Kirim Link Reset Password
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" class="text-decoration-none">
                            Kembali ke Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('assets/sneat/vendor/libs/jquery/jquery.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/libs/popper/popper.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/js/bootstrap.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/js/menu.js') }}"></script>

<script src="{{ asset('assets/sneat/js/main.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('.form-password-toggle .input-group-text');
        const password = document.getElementById('password');
        const icon = toggle?.querySelector('i');

        if (!toggle || !password || !icon) {
            return;
        }

        toggle.addEventListener('click', function () {
            const isPassword = password.type === 'password';

            password.type = isPassword ? 'text' : 'password';

            icon.classList.toggle('bx-hide', !isPassword);
            icon.classList.toggle('bx-show', isPassword);
        });
    });
</script>
</body>

</html>

