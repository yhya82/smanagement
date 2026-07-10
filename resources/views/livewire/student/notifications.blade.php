<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Notifications</h1>

    <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
        @forelse ($notifications as $notification)
            <div wire:key="notification-{{ $notification->id }}" @class(['px-4 py-3 flex items-start justify-between gap-4', 'bg-indigo-50/50' => ! $notification->is_read])>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                    <p class="text-sm text-gray-500">{{ $notification->message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @unless ($notification->is_read)
                    <button type="button" wire:click="markRead({{ $notification->id }})" class="text-xs text-indigo-600 hover:text-indigo-500 whitespace-nowrap">
                        Mark read
                    </button>
                @endunless
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-gray-500">No notifications yet.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
