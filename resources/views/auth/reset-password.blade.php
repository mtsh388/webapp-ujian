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

    <title>Reset Password | Bimbel</title>

    <meta
        name="description"
        content="Reset password aplikasi Bimbel">

    <link
        rel="icon"
        type="image/x-icon"
        href="{{ asset('assets/sneat/img/favicon/favicon.ico') }}">

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
                                <span class="text-primary">
                                    <svg
                                        width="25"
                                        viewBox="0 0 25 42"
                                        version="1.1"
                                        xmlns="http://www.w3.org/2000/svg">

                                        <path
                                            d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960553 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338571,24.4997859 18.6973423,22.4544883 18.4080071,20.2388261 C17.9637532,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                                            fill="currentColor">
                                        </path>
                                    </svg>
                                </span>
                            </span>

                            <span class="app-brand-text demo text-heading fw-bold">
                                Bimbel
                            </span>
                        </a>
                    </div>

                    <h4 class="mb-1">Reset Password</h4>

                    <p class="mb-6">
                        Buat password baru untuk akun Anda.
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

                    <form method="POST" action="{{ route('password.store') }}">
                        @csrf

                        <input
                            type="hidden"
                            name="token"
                            value="{{ $token }}">

                        <div class="mb-6">
                            <label
                                for="email"
                                class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email', $email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                autocomplete="email"
                                required>

                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="mb-6 form-password-toggle">
                            <label
                                for="password"
                                class="form-label">
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

                        <div class="mb-6 form-password-toggle">
                            <label
                                for="password_confirmation"
                                class="form-label">
                                Konfirmasi Password
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

                        <button
                            type="submit"
                            class="btn btn-primary d-grid w-100">
                            Reset Password
                        </button>
                    </form>

                    <div class="text-center mt-6">
                        <a
                            href="{{ route('login') }}"
                            class="text-decoration-none">
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

</body>

</html>
