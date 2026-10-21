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

    <title>Login | QSEE</title>

    <meta
        name="description"
        content="Login aplikasi QSEE">

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

<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">

            <div class="card px-sm-6 px-0">
                <div class="card-body">

                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{ url('/') }}" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo">
                                <img src="{{ asset('assets/sneat/img/Logo.jpeg') }}" alt="QSEE" style="width: 48px; height: 48px; object-fit: contain;">
                            </span>
                            <span class="app-brand-text demo text-heading fw-bold">QSEE</span>
                        </a>
                    </div>

                    <h4 class="mb-1">Selamat Datang 👋</h4>

                    <p class="mb-6">
                        Silakan masuk untuk melanjutkan ke aplikasi QSEE.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger mb-6">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success mb-6">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form
                        id="formAuthentication"
                        method="POST"
                        action="{{ route('login') }}">

                        @csrf

                        <div class="mb-6">
                            <label
                                for="email"
                                class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Masukkan email"
                                autocomplete="email"
                                autofocus
                                required>

                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="mb-6 form-password-toggle">
                            <label
                                class="form-label"
                                for="password">
                                Password
                            </label>

                            <div class="input-group input-group-merge">

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="••••••••"
                                    autocomplete="current-password"
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

                        <div class="mb-8">
                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="remember"
                                    name="remember"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="remember">
                                    Ingat saya
                                </label>

                            </div>
                            <div class="text-end mt-2">
                                <a href="{{ route('password.request') }}" class="text-decoration-none">
                                    Lupa password?
                                </a>
                            </div>
                        </div>

                        <div class="mb-6">
                            <button
                                class="btn btn-primary d-grid w-100"
                                type="submit">
                                Login
                            </button>
                        </div>

                    </form>

                    <p class="text-center mb-0">
                        Belum punya akun siswa? <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Registrasi</a>
                    </p>

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
