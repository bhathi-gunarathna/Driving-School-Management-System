<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Vehicle Expiry Notification History</h1><p class="mt-1 text-sm text-slate-500">Sent reminders and expiry alerts for insurance and revenue licenses.</p></div>
        <a href="{{ route('admin.vehicles.expiry') }}" class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Document Expiry</a>
    </div>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Notification</th><th class="px-4 py-3">Vehicle</th><th class="px-4 py-3">Document</th><th class="px-4 py-3">Alert type</th><th class="px-4 py-3">Sent</th><th class="px-4 py-3">Read status</th><th class="px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($notifications as $notification)
                        @php($data = $notification->data)
                        <tr class="{{ $notification->read_at ? '' : 'bg-blue-50/40' }}">
                            <td class="min-w-56 px-4 py-3"><p class="font-semibold text-slate-900">{{ $data['title'] ?? 'Vehicle document alert' }}</p><p class="mt-1 text-xs text-slate-500">{{ $data['message'] ?? '' }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">{{ $data['registration_number'] ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ ($data['document_type'] ?? '') === 'revenue_license' ? 'Revenue License' : (($data['document_type'] ?? '') === 'insurance' ? 'Insurance' : '—') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">@if(($data['alert_type'] ?? '') === 'expired')Expired alert @elseif(isset($data['alert_type'])){{ $data['alert_type'] }}-day reminder @else—@endif</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $notification->created_at?->format(config('school.date_format', 'd M Y').' H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">@if($notification->read_at)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Read</span>@else<span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Unread</span>@endif</td>
                            <td class="whitespace-nowrap px-4 py-3"><div class="flex items-center gap-3">@if(!empty($data['url']))<a href="{{ $data['url'] }}" class="font-semibold text-blue-700 hover:text-blue-800">Open</a>@endif @unless($notification->read_at)<button type="button" wire:click="markAsRead('{{ $notification->id }}')" wire:loading.attr="disabled" class="text-xs font-semibold text-slate-600 hover:text-slate-900">Mark read</button>@endunless</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No vehicle expiry notifications have been sent.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $notifications->links() }}</div>
    </section>
</div>
