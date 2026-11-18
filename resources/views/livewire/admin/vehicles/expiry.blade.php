<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Vehicle Document Expiry</h1><p class="mt-1 text-sm text-slate-500">Track insurance and revenue license expiry dates across the fleet.</p></div>
        <a href="{{ route('admin.vehicles.notifications') }}" class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Notification History</a>
    </div>

    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Document expiry status">
        @foreach(['all' => 'All Documents', 'expiring' => 'Expiring Soon', 'expired' => 'Expired', 'valid' => 'Valid Documents'] as $filter => $label)
            <button type="button" wire:click="$set('expiryFilter', '{{ $filter }}')" role="tab" aria-selected="{{ $expiryFilter === $filter ? 'true' : 'false' }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold {{ $expiryFilter === $filter ? 'bg-blue-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $label }}</button>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-medium text-slate-700">Vehicle registration
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search registration number" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label class="text-sm font-medium text-slate-700">Document type
                <select wire:model.live="documentType" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="all">All document types</option><option value="insurance">Insurance</option><option value="revenue_license">Revenue license</option></select>
            </label>
            <label class="text-sm font-medium text-slate-700">Expiry date from
                <input type="date" wire:model.live="dateFrom" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                @error('dateFrom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <label class="text-sm font-medium text-slate-700">Expiry date to
                <input type="date" wire:model.live="dateTo" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                @error('dateTo')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
        </div>
        <div wire:loading class="mt-3 text-xs font-medium text-blue-700">Updating vehicle documents…</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Vehicle</th><th class="px-4 py-3">Document</th><th class="px-4 py-3">Expiry date</th><th class="px-4 py-3">Remaining / overdue</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $document)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $document->registration_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $document->document_type === 'revenue_license' ? 'Revenue License' : 'Insurance' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($document->expiry_date)->format(config('school.date_format', 'd M Y')) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">@if($document->days_remaining < 0){{ abs($document->days_remaining) }} {{ abs($document->days_remaining) === 1 ? 'day' : 'days' }} overdue @elseif($document->days_remaining === 0)Due today @else{{ $document->days_remaining }} {{ $document->days_remaining === 1 ? 'day' : 'days' }} remaining @endif</td>
                            <td class="whitespace-nowrap px-4 py-3">@if($document->days_remaining < 0)<span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Expired</span>@elseif($document->days_remaining <= 30)<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Expiring soon</span>@else<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Valid</span>@endif</td>
                            <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('admin.vehicles.view', $document->vehicle_id) }}" class="font-semibold text-blue-700 hover:text-blue-800">View vehicle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No vehicle documents match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $documents->links() }}</div>
    </section>
</div>
