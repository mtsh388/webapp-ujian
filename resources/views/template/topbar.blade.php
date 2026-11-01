<nav
    class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base bx bx-menu icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center me-auto">
            <div class="nav-item d-flex align-items-center">

            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
            <!-- Place this tag where you want the button to render. -->
            <li class="nav-item me-3">

                <span class="fw-semibold">
                    QSEE
                </span>

            </li>
            {{--            <li class="nav-item dropdown me-2 me-xl-0">--}}
            {{--                <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Toggle theme (light)" aria-expanded="false">--}}
            {{--                    <i class="bx-sun icon-base bx icon-md theme-icon-active"></i>--}}
            {{--                    <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>--}}
            {{--                </a>--}}
            {{--                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">--}}
            {{--                    <li>--}}
            {{--                        <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="true">--}}
            {{--                            <span><i class="icon-base bx bx-sun icon-md me-3" data-icon="sun"></i>Light</span>--}}
            {{--                        </button>--}}
            {{--                    </li>--}}
            {{--                    <li>--}}
            {{--                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="false">--}}
            {{--                            <span><i class="icon-base bx bx-moon icon-md me-3" data-icon="moon"></i>Dark</span>--}}
            {{--                        </button>--}}
            {{--                    </li>--}}
            {{--                    <li>--}}
            {{--                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system" aria-pressed="false">--}}
            {{--                            <span><i class="icon-base bx bx-desktop icon-md me-3" data-icon="desktop"></i>System</span>--}}
            {{--                        </button>--}}
            {{--                    </li>--}}
            {{--                </ul>--}}
            {{--            </li>--}}
            @php
                $unreadNotifications = auth()->user()->unreadNotifications()->latest()->take(5)->get();
                $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
            @endphp
            <li class="nav-item dropdown me-2">
                <a class="nav-link dropdown-toggle hide-arrow position-relative" href="{{ route('notifications.index') }}" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                    <i class="icon-base bx bx-bell icon-md"></i>
                    @if ($unreadNotificationCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-0" style="width: min(22rem, calc(100vw - 2rem));">
                    <li class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                        <span class="fw-semibold">Notifikasi</span>
                        <a href="{{ route('notifications.index') }}" class="small">Lihat semua</a>
                    </li>
                    @forelse ($unreadNotifications as $notification)
                        <li>
                            <a class="dropdown-item py-3 text-wrap border-bottom" href="{{ route('notifications.index') }}">
                                <span class="d-block fw-semibold">{{ data_get($notification->data, 'title', 'Notifikasi QSEE') }}</span>
                                <span class="d-block small text-body-secondary mt-1">{{ \Illuminate\Support\Str::limit(data_get($notification->data, 'message', ''), 90) }}</span>
                                <span class="d-block small text-body-tertiary mt-1">{{ $notification->created_at->diffForHumans() }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-3 py-4 text-center small text-body-secondary">Tidak ada notifikasi baru.</li>
                    @endforelse
                    <li class="p-2 text-center border-top">
                        <a href="{{ route('notifications.index') }}" class="small">Buka pusat notifikasi</a>
                    </li>
                </ul>
            </li>
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a
                    class="nav-link dropdown-toggle hide-arrow p-0"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img
                            src="{{asset('assets/sneat/img/avatars/1.png')}}"
                            class="w-px-40 h-auto rounded-circle"
                        />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img
                                            src="{{asset('assets/sneat/img/avatars/1.png')}}"
                                            class="w-px-40 h-auto rounded-circle"
                                        />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        {{ auth()->user()->name ?? 'Administrator' }}
                                    </h6>

                                    <small class="text-body-secondary">
                                        Admin
                                    </small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile') }}">
                            <i class="icon-base bx bx-user icon-md me-3"></i><span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('password.change') }}">
                            <i class="icon-base bx bx-cog icon-md me-3"></i><span>Ubah Password</span>
                        </a>
                    </li>
                    
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); this.closest('form').submit();">
                                <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
                            </a>
                        </form>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
