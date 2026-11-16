<x-app-layout>
    <x-slot name="header">Notifications</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">In-app notifications</h1>
            <p class="text-secondary mb-0">Assignment alerts and CRM notices.</p>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">Mark all read</button>
        </form>
    </div>

    <div class="pc-panel p-0">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item {{ $notification->read_at ? '' : 'list-group-item-warning' }}">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div>{{ $notification->data['message'] ?? 'Notification' }}</div>
                            <div class="small text-secondary">{{ $notification->created_at?->format('d M Y H:i') }}</div>
                        </div>
                        @unless($notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-pc-primary">Open</button>
                            </form>
                        @else
                            @if(!empty($notification->data['url']))
                                <a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @endif
                        @endunless
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-secondary">No notifications yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
</x-app-layout>
