<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifikasi</h2>
            @if ($notifications->count())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg divide-y">
                @forelse ($notifications as $notification)
                    <div class="p-5 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ data_get($notification->data, 'title', 'Notifikasi QSEE') }}</h3>
                                <p class="mt-1 text-sm text-gray-700">{{ data_get($notification->data, 'message', '') }}</p>
                                <p class="mt-2 text-xs text-gray-500">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if (! $notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button class="text-xs text-indigo-600 hover:text-indigo-800" type="submit">Tandai dibaca</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">Dibaca</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">Belum ada notifikasi.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>
