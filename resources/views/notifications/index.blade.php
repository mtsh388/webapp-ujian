@extends('template.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Notifikasi</h4>
                <p class="text-muted mb-0">Informasi terbaru terkait aktivitas akun dan kegiatan QSEE.</p>
            </div>
            @if ($notifications->contains(fn ($notification) => is_null($notification->read_at)))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="icon-base bx bx-check-double me-1"></i>
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Semua notifikasi</h5>
                <span class="badge bg-label-primary">{{ $notifications->total() }} notifikasi</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($notifications as $notification)
                    <div class="list-group-item px-4 py-3 {{ $notification->read_at ? '' : 'bg-label-primary' }}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-{{ $notification->read_at ? 'secondary' : 'primary' }}">
                                    <i class="icon-base bx {{ $notification->read_at ? 'bx-bell' : 'bx-bell-ring' }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <h6 class="mb-1">{{ data_get($notification->data, 'title', 'Notifikasi QSEE') }}</h6>
                                    <small class="text-body-secondary">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <p class="mb-2 text-body">{{ data_get($notification->data, 'message', '') }}</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    @if ($notification->read_at)
                                        <span class="badge bg-label-secondary">Sudah dibaca</span>
                                    @else
                                        <span class="badge bg-label-primary">Belum dibaca</span>
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-text-primary">
                                                Tandai dibaca
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="icon-base bx bx-bell-off icon-xl text-body-secondary mb-2"></i>
                        <h6 class="mb-1">Belum ada notifikasi</h6>
                        <p class="text-body-secondary mb-0">Notifikasi aktivitas akun akan tampil di sini.</p>
                    </div>
                @endforelse
            </div>
            @if ($notifications->hasPages())
                <div class="card-footer">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
