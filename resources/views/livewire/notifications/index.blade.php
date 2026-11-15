<div class="mx-auto max-w-4xl space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><h1 class="text-2xl font-bold">Notifications</h1><p class="mt-1 text-sm text-slate-500">Updates relevant to your account and work.</p></div><button wire:click="markAllAsRead" wire:loading.attr="disabled" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold disabled:opacity-50">Mark all as read</button></div>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm divide-y divide-slate-100">
        @forelse($notifications as $notification)
            @php($data = $notification->data)
            <article class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between {{ $notification->read_at ? '' : 'bg-blue-50/60' }}">
                <a href="{{ $data['url'] ?? route('notifications.index') }}" wire:click="markAsRead('{{ $notification->id }}')" class="min-w-0 flex-1"><div class="flex items-center gap-2"><h2 class="font-semibold text-slate-900">{{ $data['title'] ?? 'Notification' }}</h2>@unless($notification->read_at)<span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase text-blue-700">New</span>@endunless</div><p class="mt-1 text-sm text-slate-600">{{ $data['message'] ?? '' }}</p><p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->format('d M Y H:i') }}</p></a>
                @unless($notification->read_at)<button wire:click="markAsRead('{{ $notification->id }}')" class="self-start text-xs font-semibold text-blue-700">Mark read</button>@endunless
            </article>
        @empty
            <p class="px-5 py-14 text-center text-sm text-slate-500">You have no notifications.</p>
        @endforelse
        <div class="p-5">{{ $notifications->links() }}</div>
    </section>
</div>
