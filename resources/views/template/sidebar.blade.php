<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link gap-2">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/sneat/img/Logo.jpeg') }}" alt="QSEE" style="width: 34px; height: 34px; object-fit: contain;">
            </span>
            <span class="app-brand-text demo menu-text fw-bold">QSEE</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base bx bx-chevron-left"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>

    <ul class="menu-inner py-1">

        @if(auth()->user()->hasAnyRole(['Siswa', 'Orang Tua']))
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Keuangan</span>
            </li>
            <li class="menu-item {{ request()->routeIs('my-registration-payments.*') ? 'active' : '' }}">
                <a href="{{ route('my-registration-payments.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i>
                    <div>Pembayaran Registrasi</div>
                </a>
            </li>
        @endif

        @if(auth()->user()->hasRole('Siswa'))
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Data Siswa</span>
            </li>
            <li class="menu-item {{ request()->routeIs('students.profile.*') ? 'active' : '' }}">
                <a href="{{ route('students.profile.edit') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div>Data Siswa</div>
                </a>
            </li>
        @endif

        @can('dashboard.view')
            <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home"></i>
                    <div>Dashboard</div>
                </a>
            </li>
        @endcan

        @if(
            auth()->user()->can('class.view') ||
            auth()->user()->can('registration-fee.view') ||
            auth()->user()->can('promo-code.view') ||
            auth()->user()->can('subject.view') ||
            auth()->user()->can('student.view') ||
            auth()->user()->can('parent.view')
        )
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Master Data</span>
            </li>

            <li class="menu-item
                {{
                    request()->routeIs('classes.*') ||
                    request()->routeIs('registration-fees.*') ||
                    request()->routeIs('promo-codes.*') ||
                    request()->routeIs('subjects.*') ||
                    request()->routeIs('students.*') ||
                    request()->routeIs('parents.*')
                        ? 'active open'
                        : ''
                }}">

                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-data"></i>
                    <div>Master Data</div>
                </a>

                <ul class="menu-sub">

                    @can('class.view')
                        <li class="menu-item {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                            <a href="{{ route('classes.index') }}" class="menu-link">
                                <div>Kelas</div>
                            </a>
                        </li>
                    @endcan

                    @can('registration-fee.view')
                        <li class="menu-item {{ request()->routeIs('registration-fees.*') ? 'active' : '' }}">
                            <a href="{{ route('registration-fees.index') }}" class="menu-link">
                                <div>Biaya</div>
                            </a>
                        </li>
                    @endcan

                    @can('promo-code.view')
                        <li class="menu-item {{ request()->routeIs('promo-codes.*') ? 'active' : '' }}">
                            <a href="{{ route('promo-codes.index') }}" class="menu-link">
                                <div>Kode Promo</div>
                            </a>
                        </li>
                    @endcan

                    @can('subject.view')
                        <li class="menu-item {{ request()->routeIs('subjects.*') ? 'active' : '' }}">
                            <a href="{{ route('subjects.index') }}" class="menu-link">
                                <div>Mata Pelajaran</div>
                            </a>
                        </li>
                    @endcan

                    @can('student.view')
                        <li class="menu-item {{ request()->routeIs('students.*') ? 'active' : '' }}">
                            <a href="{{ route('students.index') }}" class="menu-link">
                                <div>Siswa</div>
                            </a>
                        </li>
                    @endcan

                    @can('parent.view')
                        <li class="menu-item {{ request()->routeIs('parents.*') ? 'active' : '' }}">
                            <a href="{{ route('parents.index') }}" class="menu-link">
                                <div>Orang Tua</div>
                            </a>
                        </li>
                    @endcan

                </ul>
            </li>
        @endif

        @can('registration-payment.view')
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Keuangan</span>
            </li>
            <li class="menu-item {{ request()->routeIs('registration-payments.*') ? 'active' : '' }}">
                <a href="{{ route('registration-payments.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i>
                    <div>Verifikasi Pembayaran</div>
                </a>
            </li>
        @endcan

        @if(
            auth()->user()->can('question.view') ||
            auth()->user()->can('question.create') ||
            auth()->user()->can('question.update') ||
            auth()->user()->can('question.delete')
        )
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Bank Soal</span>
            </li>

            <li class="menu-item {{ request()->routeIs('questions.*') ? 'active' : '' }}">
                <a href="{{ route('questions.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-book-open"></i>
                    <div>Bank Soal</div>
                </a>
            </li>
        @endif

        @if(
            auth()->user()->can('exam.view') ||
            auth()->user()->can('exam.create') ||
            auth()->user()->can('exam.update') ||
            auth()->user()->can('exam.delete') ||
            auth()->user()->can('exam.take') ||
            auth()->user()->can('exam.attempt-approval.view')
        )
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Ujian</span>
            </li>

            <li class="menu-item {{ (request()->routeIs('exams.*') || request()->routeIs('exam-attempt-approvals.*')) ? 'active open' : '' }}">

                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-edit"></i>
                    <div>Ujian</div>
                </a>

                <ul class="menu-sub">

                    @can('exam.view')
                        @unless(auth()->user()->hasRole('Siswa'))
                            <li class="menu-item {{ request()->routeIs('exams.index') ? 'active' : '' }}">
                                <a href="{{ route('exams.index') }}" class="menu-link">
                                    <div>Daftar Ujian</div>
                                </a>
                            </li>
                        @endunless
                    @endcan

                    @can('exam.create')
                        <li class="menu-item {{ request()->routeIs('exams.create') ? 'active' : '' }}">
                            <a href="{{ route('exams.create') }}" class="menu-link">
                                <div>Buat Ujian</div>
                            </a>
                        </li>
                    @endcan

                    @can('exam.take')
                        <li class="menu-item {{ request()->routeIs('exams.available') ? 'active' : '' }}">
                            <a href="{{ route('exams.available') }}" class="menu-link">
                                <div>Ujian Saya</div>
                            </a>
                        </li>
                    @endcan

                    @can('exam.attempt-approval.view')
                        <li class="menu-item {{ request()->routeIs('exam-attempt-approvals.*') ? 'active' : '' }}">
                            <a href="{{ route('exam-attempt-approvals.index') }}" class="menu-link">
                                <div>Persetujuan Percobaan Kedua</div>
                            </a>
                        </li>
                    @endcan

                </ul>
            </li>
        @endif

        @if(
            auth()->user()->can('result.view') ||
            auth()->user()->can('result.view_own') ||
            auth()->user()->can('result.view_child')
        )
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Hasil Ujian</span>
            </li>

            <li class="menu-item {{ request()->routeIs('results.*') ? 'active' : '' }}">
                @can('result.view')
                    <a href="{{ route('results.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                        <div>Hasil Ujian</div>
                    </a>
                @elsecan('result.view_own')
                    <a href="{{ route('results.my') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                        <div>Hasil Saya</div>
                    </a>
                @elsecan('result.view_child')
                    <a href="{{ route('results.children') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                        <div>Hasil Anak</div>
                    </a>
                @endcan
            </li>
        @endif


        @can('exam.violation.view')
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Pengawasan</span>
            </li>
            <li class="menu-item {{ request()->routeIs('proctoring.*') ? 'active' : '' }}">
                <a href="{{ route('proctoring.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                    <div>Log Aktivitas Ujian</div>
                </a>
            </li>
        @endcan

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Akun</span>
        </li>

        <li class="menu-item {{ request()->routeIs('profile') ? 'active' : '' }}">
            <a href="{{ route('profile') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div>Profil Saya</div>
            </a>
        </li>
        <li class="menu-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                        class="menu-link border-0 bg-transparent w-100 text-start">
                    <i class="menu-icon tf-icons bx bx-log-out"></i>
                    <div>Logout</div>
                </button>
            </form>
        </li>

    </ul>
</aside>
