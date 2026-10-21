<!doctype html>
<html
    lang="id"
    class="layout-navbar-fixed layout-menu-fixed layout-compact"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="{{ asset('assets') }}/"
    data-template="vertical-menu-template">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

    <title>@yield('title', config('app.name', 'QSEE'))</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="QSEE - Sistem Ujian">

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
        href="{{ asset('assets/sneat/vendor/libs/apex-charts/apex-charts.css') }}">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link
        rel="stylesheet"
        href="{{asset('assets/sneat/vendor/libs/datatables-bs5/fixedColumns.dataTables.min.css')}}"
    >
    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">

    <link
        rel="stylesheet"
        href="{{ asset('assets/sneat/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(2.25rem + 2px);
        }

        .select2-container--bootstrap-5 .select2-selection.is-invalid {
            border-color: var(--bs-form-invalid-border-color, #dc3545);
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-top: .25rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%;
            transform: translateY(-50%);
        }
    </style>

    @yield('styles')

    <script src="{{ asset('assets/sneat/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/sneat/js/config.js') }}"></script>
</head>

<body>

<style>
    .app-brand {
        position: relative;
        overflow: visible !important;
    }

    .layout-menu-collapsed .app-brand {
        width: 5.25rem;
        padding-inline: 0;
        justify-content: center;
        position: relative;
        overflow: visible !important;
    }

    .layout-menu-collapsed .app-brand-link {
        display: none !important;
    }

    .layout-menu-collapsed .app-brand .layout-menu-toggle {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: absolute !important;
        inset-inline-start: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 2.5rem !important;
        height: 2.5rem !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 9999 !important;
        pointer-events: auto !important;
    }

    .layout-menu-collapsed .app-brand .layout-menu-toggle i {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: rotate(180deg);
        font-size: 1.25rem;
    }

    .layout-menu-collapsed .app-brand .layout-menu-toggle::before,
    .layout-menu-collapsed .app-brand .layout-menu-toggle::after {
        display: none !important;
    }

    @media (max-width: 1199.98px) {
        .app-brand {
            overflow: visible !important;
        }

        .layout-menu-expanded .app-brand .layout-menu-toggle,
        .layout-menu-collapsed .app-brand .layout-menu-toggle {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    }
</style>

<div class="layout-wrapper layout-content-navbar">

    <div class="layout-container">

        @include('template.sidebar')

        <div class="layout-page">

            @include('template.topbar')

            @yield('content')

        </div>

    </div>

    <div class="layout-overlay layout-menu-toggle"></div>

</div>

<script src="{{ asset('assets/sneat/vendor/libs/jquery/jquery.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/sneat/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/js/bootstrap.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/libs/pickr/pickr.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/i18n/i18n.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/js/menu.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

<script src="{{asset('assets/sneat/vendor/libs/datatables-bs5/dataTables.fixedColumns.min.js')}}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/flatpickr/flatpickr.js') }}"></script>

<script src="{{ asset('assets/sneat/vendor/libs/@form-validation/popular.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/@form-validation/auto-focus.js') }}"></script>

<script src="{{ asset('assets/sneat/js/main.js') }}"></script>
<script src="{{ asset('assets/sneat/js/tables-datatables-basic.js') }}"></script>

<script>
    (function () {
        function initializeSelect2(context) {
            if (!window.jQuery || !jQuery.fn.select2) {
                return;
            }

            const $context = jQuery(context || document);
            const $selects = $context.find('select').addBack('select');

            $selects.each(function () {
                const $select = jQuery(this);

                if (
                    $select.hasClass('select2-hidden-accessible') ||
                    $select.is('[data-no-select2]') ||
                    $select.closest('.dataTables_wrapper').length
                ) {
                    return;
                }

                const firstOption = $select.find('option').first();
                const options = {
                    theme: 'bootstrap-5',
                    width: '100%',
                    language: {
                        noResults: function () {
                            return 'Tidak ada hasil yang cocok';
                        },
                        searching: function () {
                            return 'Mencari...';
                        }
                    }
                };

                if (firstOption.length && firstOption.val() === '') {
                    options.placeholder = firstOption.text().trim();
                    options.allowClear = !$select.prop('required');
                }

                const $modal = $select.closest('.modal');

                if ($modal.length) {
                    options.dropdownParent = $modal;
                }

                $select.select2(options);

                if ($select.hasClass('is-invalid')) {
                    $select.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                }

                $select.on('change.qseeSelect2', function () {
                    if (this.value !== '') {
                        $select.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                    }
                });
            });
        }

        window.qseeInitSelect2 = initializeSelect2;

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.alert = function (message) {
                window.Swal.fire({
                    icon: 'info',
                    title: 'Informasi',
                    text: String(message ?? ''),
                    confirmButtonText: 'OK'
                });
            };
        }

        jQuery(function () {
            initializeSelect2(document);
        });

        jQuery(document).on('shown.bs.modal', function (event) {
            initializeSelect2(event.target);
        });
    })();
</script>

@yield('scripts')

</body>
</html>
